<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuditController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Audits/Index', [
            'audits' => Audit::latest()
                ->paginate(20)
                ->through(fn (Audit $a) => [
                    'uuid' => $a->uuid,
                    'url' => $a->url,
                    'status' => $a->status,
                    'score_total' => $a->score_total,
                    'score_seo' => $a->score_seo,
                    'score_security' => $a->score_security,
                    'created_at' => $a->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function show(Audit $audit): Response
    {
        return Inertia::render('Admin/Audits/Show', [
            'audit' => $audit,
        ]);
    }
}
