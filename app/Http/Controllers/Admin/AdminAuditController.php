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
        $audit->load(['followups' => fn ($q) => $q->orderByDesc('sent_at')]);

        return Inertia::render('Admin/Audits/Show', [
            'audit' => $audit,
            'followups' => $audit->followups->map(fn ($f) => [
                'id' => $f->id,
                'reason' => $f->reason,
                'status' => $f->status,
                'email' => $f->email,
                'subject' => $f->subject,
                'score_at_send' => $f->score_at_send,
                'sent_at' => $f->sent_at?->toIso8601String(),
                'error' => $f->error,
            ])->values(),
        ]);
    }
}
