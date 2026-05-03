<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Event;
use App\Models\PageVisit;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminEventController extends Controller
{
    public function index(): Response
    {
        $since7 = Carbon::now()->subDays(7);
        $since30 = Carbon::now()->subDays(30);

        $topEvents = Event::query()
            ->where('created_at', '>=', $since30)
            ->selectRaw('type, name, COUNT(*) as total')
            ->groupBy('type', 'name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $countsByType = Event::query()
            ->where('created_at', '>=', $since30)
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->get();

        $recent = Event::query()
            ->latest('created_at')
            ->limit(50)
            ->get([
                'id', 'type', 'name', 'url', 'user_id',
                'ip_hash', 'session_hash', 'metadata', 'created_at',
            ]);

        return Inertia::render('Admin/Events', [
            'range' => ['since7' => $since7->toIso8601String(), 'since30' => $since30->toIso8601String()],
            'funnel' => $this->funnel($since30),
            'stats' => [
                'events_7d' => Event::where('created_at', '>=', $since7)->count(),
                'events_30d' => Event::where('created_at', '>=', $since30)->count(),
                'visits_30d' => PageVisit::where('created_at', '>=', $since30)->count(),
                'unique_sessions_30d' => Event::where('created_at', '>=', $since30)->distinct('session_hash')->count('session_hash'),
            ],
            'topEvents' => $topEvents,
            'countsByType' => $countsByType,
            'recent' => $recent,
        ]);
    }

    /**
     * @return array<int, array{step:string, label:string, count:int, rate:float|null}>
     */
    protected function funnel(Carbon $since): array
    {
        $visits = PageVisit::where('created_at', '>=', $since)->count();

        $auditStarted = Event::where('type', 'audit.submitted')
            ->where('created_at', '>=', $since)->count();
        if ($auditStarted === 0) {
            // Fallback sur les audits créés (si l'event a été ajouté après coup).
            $auditStarted = Audit::where('created_at', '>=', $since)->count();
        }

        $auditPaid = Event::where('type', 'audit.paid')
            ->where('created_at', '>=', $since)->count();
        if ($auditPaid === 0) {
            $auditPaid = Audit::whereNotNull('paid_at')->where('paid_at', '>=', $since)->count();
        }

        $pdfPaid = Event::where('type', 'audit.pdf.paid')
            ->where('created_at', '>=', $since)->count();
        $cwvPaid = Event::where('type', 'audit.cwv.paid')
            ->where('created_at', '>=', $since)->count();
        $monitoringSubscribed = Event::where('type', 'monitoring.subscribed')
            ->where('created_at', '>=', $since)->count();

        $steps = [
            ['step' => 'visits', 'label' => 'Visites', 'count' => $visits],
            ['step' => 'audit_started', 'label' => 'Audits lancés', 'count' => $auditStarted],
            ['step' => 'audit_paid', 'label' => 'Rapports payés (29 €)', 'count' => $auditPaid],
            ['step' => 'pdf_paid', 'label' => 'Add-on PDF (+9 €)', 'count' => $pdfPaid],
            ['step' => 'cwv_paid', 'label' => 'Add-on CWV (+19 €)', 'count' => $cwvPaid],
            ['step' => 'monitoring', 'label' => 'Abonnements monitoring (49 €/mois)', 'count' => $monitoringSubscribed],
        ];

        $base = $steps[0]['count'] ?: 1;
        foreach ($steps as $i => $s) {
            $steps[$i]['rate'] = $s['count'] === 0 ? 0.0 : round(($s['count'] / $base) * 100, 2);
        }

        return $steps;
    }
}
