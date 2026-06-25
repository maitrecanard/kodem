<?php

declare(strict_types=1);

namespace App\Modules\Audits\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audits\Models\AuditRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class AuditReportController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $audit = AuditRequest::byAccessToken($token)->first();

        if ($audit === null || ! $audit->isReportAccessible()) {
            abort(404);
        }

        // Capability URL : le token secret est dans l'URL. On empêche la mise
        // en cache partagée et l'indexation pour éviter toute fuite du lien.
        return Inertia::render('Audits/Report', [
            'domain'   => $audit->domain,
            'type'     => $audit->type,
            'options'  => $audit->options,
            'status'   => $audit->status,
            'paidAt'   => optional($audit->paid_at)->toIso8601String(),
            'amount'   => $audit->amount_cents,
            'currency' => $audit->currency,
        ])->toResponse($request)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, private',
            'X-Robots-Tag'  => 'noindex, nofollow',
        ]);
    }
}
