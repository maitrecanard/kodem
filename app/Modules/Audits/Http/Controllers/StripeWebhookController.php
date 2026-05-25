<?php

declare(strict_types=1);

namespace App\Modules\Audits\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Audits\Models\AuditRequest;
use App\Modules\Audits\Notifications\NewPremiumOrderForAdmin;
use App\Modules\Audits\Mail\PremiumOrderConfirmation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        // 1. Vérification de signature — sécurité critique
        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException $e) {
            Log::warning('Stripe webhook: payload invalide', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook: signature invalide', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        try {
            match ($event->type) {
                'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
                'checkout.session.expired'   => $this->handleCheckoutExpired($event->data->object),
                'charge.refunded'            => $this->handleChargeRefunded($event->data->object),
                default                      => Log::info("Stripe webhook ignoré: {$event->type}"),
            };
        } catch (\Throwable $e) {
            report($e);
            Log::error('Stripe webhook: échec de traitement', [
                'event' => $event->type,
                'error' => $e->getMessage(),
            ]);
            // 500 → Stripe retry (backoff exponentiel jusqu'à 3 jours)
            return response('Webhook processing failed', 500);
        }

        return response('OK', 200);
    }

    private function handleCheckoutCompleted(\Stripe\Checkout\Session $session): void
    {
        $auditRequestId = $session->metadata->audit_request_id ?? null;

        // ⚠️ Pas de metadata audit_request_id = paiement pour AUTRE CHOSE.
        // On l'ignore proprement, c'est pas notre business.
        if (!$auditRequestId) {
            Log::info('Stripe webhook: session sans audit_request_id, ignorée', [
                'session_id' => $session->id,
            ]);
            return;
        }

        DB::transaction(function () use ($session, $auditRequestId) {
            /** @var AuditRequest|null $audit */
            $audit = AuditRequest::lockForUpdate()->find($auditRequestId);

            if (!$audit) {
                Log::warning('Stripe webhook: AuditRequest introuvable', [
                    'audit_request_id_from_metadata' => $auditRequestId,
                ]);
                return;
            }

            // Idempotence : si déjà traité, on sort proprement
            if ($audit->status !== 'pending_payment') {
                Log::info('Stripe webhook: déjà traité, idempotent', [
                    'audit_uuid' => $audit->uuid,
                    'status'    => $audit->status,
                ]);
                return;
            }

            $audit->update([
                'status'                => 'queued',
                'stripe_payment_intent' => $session->payment_intent,
                'amount_cents'          => $session->amount_total,
                'currency'              => $session->currency,
                'paid_at'               => now(),
            ]);

            Mail::to($audit->email)->queue(new PremiumOrderConfirmation($audit));
            Notification::route('mail', config('audits.admin_email'))
                ->notify(new NewPremiumOrderForAdmin($audit));
        });
    }

    private function handleCheckoutExpired(\Stripe\Checkout\Session $session): void
    {
        $auditRequestId = $session->metadata->audit_request_id ?? null;
        if (!$auditRequestId) {
            return;
        }

        DB::transaction(function () use ($auditRequestId): void {
            /** @var AuditRequest|null $audit */
            $audit = AuditRequest::lockForUpdate()->find($auditRequestId);

            if (!$audit || $audit->status !== 'pending_payment') {
                return;
            }

            $audit->update(['status' => 'failed']);
        });
    }

    private function handleChargeRefunded(\Stripe\Charge $charge): void
    {
        DB::transaction(function () use ($charge): void {
            /** @var AuditRequest|null $audit */
            $audit = AuditRequest::where('stripe_payment_intent', $charge->payment_intent)
                ->lockForUpdate()
                ->first();

            if (!$audit) {
                return;
            }

            if ($audit->status === 'refunded') {
                Log::info('Stripe webhook: déjà remboursé, idempotent', [
                    'audit_uuid' => $audit->uuid,
                ]);

                return;
            }

            $audit->update(['status' => 'refunded']);
        });
    }
}
