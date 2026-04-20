<?php

namespace App\Services;

use App\Mail\MonitoringReportMail;
use App\Models\Audit;
use App\Models\MonitoringSubscription;
use Illuminate\Support\Facades\Mail;

class MonitoringRunner
{
    public function __construct(protected AuditRunner $auditRunner)
    {
    }

    /**
     * Exécute un audit pour l'abonnement et envoie un rapport par email.
     * Si le score a baissé au-delà du seuil configuré, l'email est marqué
     * comme alerte (sujet différent).
     *
     * @return array{audit:\App\Models\Audit, previous_score:int|null, new_score:int|null, alert:bool}
     */
    public function run(MonitoringSubscription $subscription): array
    {
        $outcome = $this->auditRunner->run($subscription->url);

        $audit = Audit::create([
            'url' => $subscription->url,
            'email' => $subscription->email,
            'type' => 'full',
            'status' => $outcome['status'],
            'score_seo' => $outcome['score_seo'],
            'score_security' => $outcome['score_security'],
            'score_total' => $outcome['score_total'],
            'results' => $outcome['results'],
            'error' => $outcome['error'],
            'price_cents' => (int) config('audit.price_cents', 2900),
            'paid_at' => now(), // monitoring inclut le rapport complet
            'payment_reference' => 'MONITORING-'.$subscription->token,
            'ip_hash' => hash('sha256', 'monitoring-'.$subscription->id.config('app.key')),
        ]);

        $previous = $subscription->last_score_total;
        $newScore = $audit->score_total;
        $threshold = (int) config('audit.monitoring_alert_threshold', 10);
        $alert = is_int($previous) && is_int($newScore) && ($previous - $newScore) >= $threshold;

        $subscription->update([
            'last_run_at' => now(),
            'last_score_total' => $newScore,
            'last_audit_uuid' => $audit->uuid,
        ]);

        if (config('mail.default') !== 'array') {
            Mail::to($subscription->email)
                ->send(new MonitoringReportMail($subscription, $audit, $previous, $alert));
        }

        return [
            'audit' => $audit,
            'previous_score' => $previous,
            'new_score' => $newScore,
            'alert' => $alert,
        ];
    }
}
