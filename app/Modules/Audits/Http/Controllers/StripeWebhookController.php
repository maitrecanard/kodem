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

            $this->notifyPaidOrder($audit);
        });
    }

    /**
     * Confirmation au client + notification interne, après encaissement.
     *
     * ENVOI SYNCHRONE VOLONTAIRE. Ces messages passaient auparavant par la file
     * (`->queue()` / `ShouldQueue`) : sans worker `queue:work` en fonctionnement,
     * le job restait indéfiniment dans la table `jobs`, sans erreur ni alerte —
     * le client payait 150 € sans rien recevoir, et personne n'était prévenu
     * puisque la notification interne dormait dans la même file. Deux envois
     * ajoutent moins d'une seconde au webhook et suppriment ce mode de panne.
     *
     * La commande est DÉJÀ enregistrée et payée à ce stade : un incident SMTP ne
     * fait rien perdre. Les deux envois sont isolés pour qu'un échec côté client
     * (adresse invalide) n'empêche pas la notification interne, et inversement.
     */
    private function notifyPaidOrder(AuditRequest $audit): void
    {
        try {
            Mail::to($audit->email)->send(new PremiumOrderConfirmation($audit));
        } catch (\Throwable $e) {
            Log::error('Audits premium : échec de la confirmation au client.', [
                'audit_uuid' => $audit->uuid,
                'exception' => $e->getMessage(),
            ]);
        }

        $admin = config('audits.admin_email');

        if (blank($admin)) {
            Log::warning('Audits premium : aucune adresse admin configurée, commande non notifiée.', [
                'audit_uuid' => $audit->uuid,
                'config' => 'audits.admin_email (AUDITS_ADMIN_EMAIL)',
            ]);

            return;
        }

        try {
            Notification::route('mail', $admin)
                ->notifyNow(new NewPremiumOrderForAdmin($audit));
        } catch (\Throwable $e) {
            Log::error('Audits premium : échec de la notification interne.', [
                'audit_uuid' => $audit->uuid,
                'destinataire' => $admin,
                'exception' => $e->getMessage(),
            ]);
        }
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
