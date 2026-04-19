<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class AuditRunner
{
    /**
     * Exécute un audit complet (SEO + sécurité) sur une URL.
     *
     * @return array{
     *     status: string,
     *     score_seo: int|null,
     *     score_security: int|null,
     *     score_total: int|null,
     *     results: array<string,mixed>,
     *     error: string|null
     * }
     */
    public function run(string $url): array
    {
        $normalized = $this->normalizeUrl($url);
        if ($normalized === null) {
            return $this->failure('URL invalide.');
        }

        try {
            $response = Http::timeout(15)
                ->connectTimeout(10)
                ->withUserAgent('KodemAuditBot/1.0 (+https://kodem.fr)')
                ->withHeaders(['Accept' => 'text/html,*/*'])
                ->withOptions(['allow_redirects' => ['max' => 5, 'track_redirects' => true]])
                ->get($normalized);
        } catch (\Throwable $e) {
            return $this->failure('Le site est injoignable : '.$e->getMessage());
        }

        $headers = $this->normalizeHeaders($response->headers());
        $body = (string) $response->body();

        $seo = $this->analyseSeo($normalized, $body, $headers, $response->status());
        $sec = $this->analyseSecurity($normalized, $headers, $response->status());

        $companions = $this->probeCompanions($normalized);

        $scoreSeo = $this->score($seo['checks']);
        $scoreSec = $this->score($sec['checks']);
        $scoreTotal = (int) round(($scoreSeo + $scoreSec) / 2);

        return [
            'status' => 'completed',
            'score_seo' => $scoreSeo,
            'score_security' => $scoreSec,
            'score_total' => $scoreTotal,
            'results' => [
                'url' => $normalized,
                'http_status' => $response->status(),
                'response_time_ms' => (int) round($response->handlerStats()['total_time'] ?? 0 * 1000),
                'seo' => $seo,
                'security' => $sec,
                'companions' => $companions,
                'audited_at' => now()->toIso8601String(),
            ],
            'error' => null,
        ];
    }

    /**
     * @param array<int, array<string,mixed>> $checks
     */
    protected function score(array $checks): int
    {
        if ($checks === []) {
            return 0;
        }
        $totalWeight = 0;
        $earned = 0;
        foreach ($checks as $c) {
            $weight = $c['weight'] ?? 1;
            $totalWeight += $weight;
            if (($c['status'] ?? 'fail') === 'pass') {
                $earned += $weight;
            } elseif (($c['status'] ?? '') === 'warn') {
                $earned += $weight * 0.5;
            }
        }
        if ($totalWeight === 0) {
            return 0;
        }
        return (int) max(0, min(100, round(($earned / $totalWeight) * 100)));
    }

    /**
     * @param array<string,string> $headers
     * @return array{checks: array<int, array<string,mixed>>}
     */
    protected function analyseSeo(string $url, string $body, array $headers, int $status): array
    {
        $title = $this->regexFirst('~<title[^>]*>(.*?)</title>~is', $body);
        $metaDesc = $this->regexFirst('~<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\'][^>]*>~i', $body);
        $h1 = $this->regexFirst('~<h1[^>]*>(.*?)</h1>~is', $body);
        $og = $this->regexAll('~<meta[^>]+property=["\']og:(\w+)["\'][^>]+content=["\']([^"\']*)["\'][^>]*>~i', $body);
        $twitter = $this->regexAll('~<meta[^>]+name=["\']twitter:(\w+)["\'][^>]+content=["\']([^"\']*)["\'][^>]*>~i', $body);
        $lang = $this->regexFirst('~<html[^>]*lang=["\']([^"\']+)["\']~i', $body);
        $canonical = $this->regexFirst('~<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\'][^>]*>~i', $body);
        $viewport = (bool) preg_match('~<meta[^>]+name=["\']viewport["\']~i', $body);

        $checks = [
            [
                'key' => 'http_200',
                'label' => 'Réponse HTTP 200 OK',
                'status' => $status >= 200 && $status < 300 ? 'pass' : 'fail',
                'weight' => 3,
                'detail' => "HTTP {$status}",
            ],
            [
                'key' => 'title',
                'label' => 'Balise <title> présente et de longueur raisonnable',
                'status' => $this->lenScore($title, 15, 65),
                'weight' => 3,
                'detail' => $title ? "Longueur : ".mb_strlen(trim($title))." car." : 'absente',
            ],
            [
                'key' => 'meta_description',
                'label' => 'Meta description présente et optimisée',
                'status' => $this->lenScore($metaDesc, 70, 170),
                'weight' => 3,
                'detail' => $metaDesc ? 'Longueur : '.mb_strlen($metaDesc).' car.' : 'absente',
            ],
            [
                'key' => 'h1',
                'label' => 'Balise <h1> présente',
                'status' => $h1 !== null ? 'pass' : 'fail',
                'weight' => 2,
                'detail' => $h1 !== null ? 'H1 détecté' : 'H1 manquant',
            ],
            [
                'key' => 'viewport',
                'label' => 'Meta viewport (mobile friendly)',
                'status' => $viewport ? 'pass' : 'fail',
                'weight' => 1,
                'detail' => $viewport ? 'présente' : 'absente',
            ],
            [
                'key' => 'html_lang',
                'label' => 'Attribut lang sur <html>',
                'status' => $lang ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $lang ?: 'non défini',
            ],
            [
                'key' => 'canonical',
                'label' => 'Balise canonical',
                'status' => $canonical ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $canonical ?: 'absente',
            ],
            [
                'key' => 'og',
                'label' => 'Open Graph (au moins og:title)',
                'status' => isset($og['title']) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => isset($og['title']) ? 'og:title présent' : 'aucune balise og:*',
            ],
            [
                'key' => 'twitter',
                'label' => 'Twitter Card',
                'status' => isset($twitter['card']) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => isset($twitter['card']) ? 'twitter:card présent' : 'absent',
            ],
            [
                'key' => 'compressed',
                'label' => 'Compression HTTP (gzip/br)',
                'status' => isset($headers['content-encoding']) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $headers['content-encoding'] ?? 'aucune',
            ],
        ];

        return [
            'summary' => [
                'title' => $title ? trim(strip_tags($title)) : null,
                'description' => $metaDesc,
                'h1' => $h1 ? trim(strip_tags($h1)) : null,
                'lang' => $lang,
                'canonical' => $canonical,
            ],
            'checks' => $checks,
        ];
    }

    /**
     * @param array<string,string> $headers
     * @return array{checks: array<int, array<string,mixed>>}
     */
    protected function analyseSecurity(string $url, array $headers, int $status): array
    {
        $https = str_starts_with($url, 'https://');

        $setCookie = $headers['set-cookie'] ?? '';
        $cookieStatus = 'warn';
        if ($setCookie === '') {
            $cookieStatus = 'pass';
            $cookieDetail = 'aucun cookie';
        } else {
            $cookieLc = strtolower($setCookie);
            if (str_contains($cookieLc, 'secure') && str_contains($cookieLc, 'httponly')) {
                $cookieStatus = 'pass';
                $cookieDetail = 'Secure + HttpOnly détectés';
            } else {
                $cookieDetail = 'flags Secure/HttpOnly manquants';
            }
        }

        $checks = [
            [
                'key' => 'https',
                'label' => 'Site servi en HTTPS',
                'status' => $https ? 'pass' : 'fail',
                'weight' => 4,
                'detail' => $https ? 'HTTPS' : 'HTTP seulement — critique',
            ],
            [
                'key' => 'hsts',
                'label' => 'Strict-Transport-Security (HSTS)',
                'status' => isset($headers['strict-transport-security']) ? 'pass' : 'fail',
                'weight' => 3,
                'detail' => $headers['strict-transport-security'] ?? 'absent',
            ],
            [
                'key' => 'csp',
                'label' => 'Content-Security-Policy',
                'status' => isset($headers['content-security-policy']) ? 'pass' : 'fail',
                'weight' => 3,
                'detail' => isset($headers['content-security-policy']) ? 'présent' : 'absent',
            ],
            [
                'key' => 'x_frame',
                'label' => 'X-Frame-Options (clickjacking)',
                'status' => isset($headers['x-frame-options']) ? 'pass' : 'fail',
                'weight' => 2,
                'detail' => $headers['x-frame-options'] ?? 'absent',
            ],
            [
                'key' => 'x_content_type',
                'label' => 'X-Content-Type-Options: nosniff',
                'status' => (($headers['x-content-type-options'] ?? '') === 'nosniff') ? 'pass' : 'fail',
                'weight' => 2,
                'detail' => $headers['x-content-type-options'] ?? 'absent',
            ],
            [
                'key' => 'referrer_policy',
                'label' => 'Referrer-Policy',
                'status' => isset($headers['referrer-policy']) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $headers['referrer-policy'] ?? 'absent',
            ],
            [
                'key' => 'permissions_policy',
                'label' => 'Permissions-Policy',
                'status' => isset($headers['permissions-policy']) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $headers['permissions-policy'] ?? 'absent',
            ],
            [
                'key' => 'server_header',
                'label' => 'En-tête Server minimaliste',
                'status' => (! isset($headers['server']) || ! preg_match('/\d/', $headers['server'])) ? 'pass' : 'warn',
                'weight' => 1,
                'detail' => $headers['server'] ?? 'masqué',
            ],
            [
                'key' => 'x_powered_by',
                'label' => 'X-Powered-By absent',
                'status' => isset($headers['x-powered-by']) ? 'warn' : 'pass',
                'weight' => 1,
                'detail' => $headers['x-powered-by'] ?? 'absent (bon)',
            ],
            [
                'key' => 'cookies_secure',
                'label' => 'Cookies Secure + HttpOnly',
                'status' => $cookieStatus,
                'weight' => 2,
                'detail' => $cookieDetail,
            ],
        ];

        return ['checks' => $checks];
    }

    /**
     * @return array<string, array<string,mixed>>
     */
    protected function probeCompanions(string $url): array
    {
        $base = preg_replace('~(https?://[^/]+).*~', '$1', $url);
        $result = [];
        foreach (['robots.txt', 'sitemap.xml'] as $file) {
            try {
                $res = Http::timeout(8)->withUserAgent('KodemAuditBot/1.0')->get($base.'/'.$file);
                $result[$file] = [
                    'found' => $res->successful(),
                    'status' => $res->status(),
                ];
            } catch (\Throwable $e) {
                $result[$file] = ['found' => false, 'status' => 0];
            }
        }
        return $result;
    }

    protected function normalizeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://'.$url;
        }
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        $parts = parse_url($url);
        if (! isset($parts['host'])) {
            return null;
        }
        $host = strtolower($parts['host']);
        if ($this->isBlockedHost($host)) {
            return null;
        }
        return $url;
    }

    protected function isBlockedHost(string $host): bool
    {
        $blocked = ['localhost', '127.0.0.1', '0.0.0.0', '::1'];
        if (in_array($host, $blocked, true)) {
            return true;
        }
        if (preg_match('~^(10|192\.168|172\.(1[6-9]|2\d|3[01]))\.~', $host)) {
            return true;
        }
        return false;
    }

    /**
     * @param array<string, array<int, string>|string> $raw
     * @return array<string,string>
     */
    protected function normalizeHeaders(array $raw): array
    {
        $out = [];
        foreach ($raw as $k => $v) {
            $out[strtolower($k)] = is_array($v) ? implode(', ', $v) : (string) $v;
        }
        return $out;
    }

    protected function regexFirst(string $re, string $h): ?string
    {
        if (! preg_match($re, $h, $m)) {
            return null;
        }
        return $m[1] ?? $m[0] ?? null;
    }

    /**
     * @return array<string,string>
     */
    protected function regexAll(string $re, string $h): array
    {
        preg_match_all($re, $h, $m, PREG_SET_ORDER);
        $out = [];
        foreach ($m as $row) {
            $out[strtolower($row[1])] = $row[2];
        }
        return $out;
    }

    protected function lenScore(?string $s, int $min, int $max): string
    {
        if ($s === null) {
            return 'fail';
        }
        $len = mb_strlen(trim(strip_tags($s)));
        if ($len >= $min && $len <= $max) {
            return 'pass';
        }
        return $len === 0 ? 'fail' : 'warn';
    }

    /**
     * @return array{status:string, score_seo:null, score_security:null, score_total:null, results:array<string,mixed>, error:string}
     */
    protected function failure(string $msg): array
    {
        return [
            'status' => 'failed',
            'score_seo' => null,
            'score_security' => null,
            'score_total' => null,
            'results' => [],
            'error' => $msg,
        ];
    }
}
