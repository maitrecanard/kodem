<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Services\PdfReportGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuditPdfController extends Controller
{
    public function download(Request $request, Audit $audit, PdfReportGenerator $generator): SymfonyResponse
    {
        $this->requirePaidAudit($audit);

        $isAdmin = (bool) optional($request->user())->is_admin;
        if (! $audit->isPdfPaid() && ! $isAdmin) {
            return redirect()->route('audit.pdf.pay', $audit->uuid);
        }

        $pdf = $generator->generate($audit);
        $filename = 'kodem-audit-'.substr($audit->uuid, 0, 8).'.pdf';

        return $pdf->download($filename);
    }

    public function pay(Audit $audit): Response|RedirectResponse
    {
        $this->requirePaidAudit($audit);

        if ($audit->isPdfPaid()) {
            return redirect()->route('audit.pdf', $audit->uuid);
        }

        return Inertia::render('Public/AuditPdfCheckout', [
            'meta' => [
                'title' => 'Débloquer le PDF du rapport — Kodem',
                'description' => 'Téléchargez le rapport d\'audit au format PDF.',
                'keywords' => 'rapport PDF audit',
            ],
            'audit' => [
                'uuid' => $audit->uuid,
                'url' => $audit->url,
                'score_total' => $audit->score_total,
            ],
            'price' => [
                'cents' => $audit->pdf_price_cents,
                'label' => number_format($audit->pdf_price_cents / 100, 2, ',', ' ').' €',
            ],
            'driver' => config('audit.payment_driver', 'stub'),
        ]);
    }

    public function confirmPayment(Request $request, Audit $audit): RedirectResponse
    {
        $this->requirePaidAudit($audit);

        if ($audit->isPdfPaid()) {
            return redirect()->route('audit.pdf', $audit->uuid);
        }

        $driver = config('audit.payment_driver', 'stub');

        if ($driver !== 'stub') {
            abort(501, 'Driver de paiement non supporté : '.$driver);
        }

        $request->validate(['confirm' => ['required', 'accepted']]);

        $audit->update([
            'pdf_paid_at' => now(),
            'payment_reference' => trim(($audit->payment_reference ?? '').' PDF-'.strtoupper(Str::random(8))),
        ]);

        return redirect()
            ->route('audit.pdf', $audit->uuid)
            ->with('success', 'Add-on PDF débloqué — téléchargement lancé.');
    }

    protected function requirePaidAudit(Audit $audit): void
    {
        if (! $audit->isPaid()) {
            abort(redirect()->route('audit.pay', $audit->uuid)
                ->with('error', 'Payez d\'abord le rapport complet pour débloquer le PDF.'));
        }
    }
}
