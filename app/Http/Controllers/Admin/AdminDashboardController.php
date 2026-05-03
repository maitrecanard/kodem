<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\ContactMessage;
use App\Models\PageVisit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function index(): Response
    {
        $since7 = Carbon::now()->subDays(7);
        $since30 = Carbon::now()->subDays(30);

        $visitsByDay = PageVisit::query()
            ->where('created_at', '>=', $since30)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $topPages = PageVisit::query()
            ->where('created_at', '>=', $since30)
            ->selectRaw('url, COUNT(*) as total')
            ->groupBy('url')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'visits_7d' => PageVisit::where('created_at', '>=', $since7)->count(),
                'visits_30d' => PageVisit::where('created_at', '>=', $since30)->count(),
                'unique_visitors_30d' => PageVisit::where('created_at', '>=', $since30)
                    ->distinct('ip_hash')->count('ip_hash'),
                'audits_total' => Audit::count(),
                'audits_7d' => Audit::where('created_at', '>=', $since7)->count(),
                'messages_total' => ContactMessage::count(),
                'messages_new' => ContactMessage::where('status', 'new')->count(),
            ],
            'visitsByDay' => $visitsByDay,
            'topPages' => $topPages,
            'recentAudits' => Audit::latest()->limit(5)->get(['uuid', 'url', 'score_total', 'status', 'created_at']),
            'recentMessages' => ContactMessage::latest()->limit(5)->get(['id', 'name', 'email', 'subject', 'status', 'created_at']),
        ]);
    }
}
