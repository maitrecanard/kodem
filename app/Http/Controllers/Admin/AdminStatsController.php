<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\PageVisit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminStatsController extends Controller
{
    /**
     * Statistiques de fréquentation (visites par jour / mois / année,
     * avec provenance) et de clics (boutons, pages, articles, prestations).
     */
    public function index(Request $request): Response
    {
        $period = in_array($request->query('period'), ['day', 'month', 'year'], true)
            ? $request->query('period')
            : 'day';

        $since = match ($period) {
            'year' => Carbon::now()->subYears(5)->startOfYear(),
            'month' => Carbon::now()->subMonths(11)->startOfMonth(),
            default => Carbon::now()->subDays(29)->startOfDay(),
        };

        $bucket = $this->bucketExpression($period);

        $visitsByBucket = PageVisit::query()
            ->where('created_at', '>=', $since)
            ->selectRaw("$bucket as bucket, COUNT(*) as total, COUNT(DISTINCT ip_hash) as uniques")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return Inertia::render('Admin/Stats', [
            'period' => $period,
            'since' => $since->toIso8601String(),
            'stats' => [
                'visits' => (int) PageVisit::where('created_at', '>=', $since)->count(),
                'uniques' => (int) PageVisit::where('created_at', '>=', $since)
                    ->distinct('ip_hash')->count('ip_hash'),
                'clicks' => (int) Event::where('type', 'button_click')
                    ->where('created_at', '>=', $since)->count(),
            ],
            'visitsByBucket' => $visitsByBucket,
            'provenance' => $this->provenance($since),
            'topReferers' => $this->topReferers($since),
            'topClicks' => $this->topClicks($since),
            'clicksByPage' => $this->clicksByPage($since),
        ]);
    }

    /**
     * Expression SQL de regroupement temporel, compatible SQLite (tests) et
     * MySQL/MariaDB (production).
     */
    protected function bucketExpression(string $period): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return match ($period) {
                'year' => "strftime('%Y', created_at)",
                'month' => "strftime('%Y-%m', created_at)",
                default => "strftime('%Y-%m-%d', created_at)",
            };
        }

        return match ($period) {
            'year' => "DATE_FORMAT(created_at, '%Y')",
            'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
        };
    }

    /**
     * Provenance agrégée par catégorie (Direct, Moteurs, Réseaux, Référents).
     *
     * @return array<int, array{category:string, total:int}>
     */
    protected function provenance(Carbon $since): array
    {
        $rows = PageVisit::query()
            ->where('created_at', '>=', $since)
            ->selectRaw('referer, COUNT(*) as total')
            ->groupBy('referer')
            ->get();

        $categories = [];
        foreach ($rows as $row) {
            $category = $this->classifyReferer($row->referer);
            $categories[$category] = ($categories[$category] ?? 0) + (int) $row->total;
        }

        arsort($categories);

        return array_map(
            fn ($category, $total) => ['category' => $category, 'total' => $total],
            array_keys($categories),
            array_values($categories),
        );
    }

    /**
     * Top des sites référents externes (hors trafic direct et interne).
     *
     * @return array<int, array{host:string, total:int}>
     */
    protected function topReferers(Carbon $since): array
    {
        $rows = PageVisit::query()
            ->where('created_at', '>=', $since)
            ->whereNotNull('referer')
            ->selectRaw('referer, COUNT(*) as total')
            ->groupBy('referer')
            ->get();

        $selfHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));

        $hosts = [];
        foreach ($rows as $row) {
            $host = $this->refererHost($row->referer);
            if ($host === null || $host === $selfHost) {
                continue;
            }
            $hosts[$host] = ($hosts[$host] ?? 0) + (int) $row->total;
        }

        arsort($hosts);
        $hosts = array_slice($hosts, 0, 15, true);

        return array_map(
            fn ($host, $total) => ['host' => $host, 'total' => $total],
            array_keys($hosts),
            array_values($hosts),
        );
    }

    /**
     * Top des clics par cible (name = bouton / CTA / lien tracké).
     *
     * @return Collection<int, object>
     */
    protected function topClicks(Carbon $since)
    {
        return Event::query()
            ->where('type', 'button_click')
            ->where('created_at', '>=', $since)
            ->selectRaw('name, COUNT(*) as total')
            ->groupBy('name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();
    }

    /**
     * Clics regroupés par page d'origine.
     *
     * @return Collection<int, object>
     */
    protected function clicksByPage(Carbon $since)
    {
        return Event::query()
            ->where('type', 'button_click')
            ->where('created_at', '>=', $since)
            ->whereNotNull('url')
            ->selectRaw('url, COUNT(*) as total')
            ->groupBy('url')
            ->orderByDesc('total')
            ->limit(20)
            ->get();
    }

    protected function classifyReferer(?string $referer): string
    {
        if ($referer === null || trim($referer) === '') {
            return 'Direct';
        }

        $host = $this->refererHost($referer);
        if ($host === null) {
            return 'Direct';
        }

        $selfHost = strtolower((string) parse_url(config('app.url'), PHP_URL_HOST));
        if ($host === $selfHost) {
            return 'Navigation interne';
        }

        $engines = ['google.', 'bing.', 'duckduckgo.', 'qwant.', 'ecosia.', 'yahoo.', 'yandex.', 'baidu.'];
        foreach ($engines as $needle) {
            if (str_contains($host, $needle)) {
                return 'Moteurs de recherche';
            }
        }

        $social = ['facebook.', 'instagram.', 'linkedin.', 'twitter.', 'x.com', 't.co', 'youtube.', 'tiktok.', 'pinterest.', 'reddit.'];
        foreach ($social as $needle) {
            if (str_contains($host, $needle)) {
                return 'Réseaux sociaux';
            }
        }

        return 'Sites référents';
    }

    protected function refererHost(?string $referer): ?string
    {
        if ($referer === null || trim($referer) === '') {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            return null;
        }

        return strtolower(preg_replace('/^www\./', '', $host));
    }
}
