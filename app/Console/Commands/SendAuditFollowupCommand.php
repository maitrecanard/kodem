<?php

namespace App\Console\Commands;

use App\Mail\AuditFollowupMail;
use App\Models\Audit;
use App\Models\AuditFollowup;
use App\Services\DiscordNotifier;
use App\Services\TrackingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendAuditFollowupCommand extends Command
{
    protected $signature = 'audits:send-followup
        {--threshold=75 : Score total en-dessous duquel relancer}
        {--delay-days=7 : Délai minimum depuis l\'audit}
        {--max-age-days=30 : Au-delà de cet âge, on ne relance plus}
        {--audit= : UUID d\'un audit précis (force la relance)}
        {--dry-run : Simule sans envoyer ni écrire en base}';

    protected $description = 'Envoie un mail de relance commerciale pour les audits dont le score est inférieur au seuil';

    public function handle(TrackingService $tracking, DiscordNotifier $discord): int
    {
        $threshold = (int) $this->option('threshold');
        $delayDays = (int) $this->option('delay-days');
        $maxAgeDays = (int) $this->option('max-age-days');
        $forceUuid = $this->option('audit');
        $dryRun = (bool) $this->option('dry-run');

        $query = Audit::query()
            ->where('status', 'completed')
            ->whereNotNull('email')
            ->whereNotNull('score_total')
            ->whereNull('followup_unsubscribed_at')
            ->whereDoesntHave('followups', function ($q) {
                $q->where('reason', AuditFollowup::REASON_LOW_SCORE)
                  ->where('status', AuditFollowup::STATUS_SENT);
            });

        if ($forceUuid) {
            $query->where('uuid', $forceUuid);
        } else {
            $query->where('score_total', '<', $threshold)
                  ->where('created_at', '<=', now()->subDays($delayDays))
                  ->where('created_at', '>=', now()->subDays($maxAgeDays));
        }

        $count = $query->count();
        $this->info("Audits à relancer : {$count}".($dryRun ? ' (dry-run)' : ''));

        $sent = 0;
        $failed = 0;

        $query->chunkById(50, function ($audits) use (&$sent, &$failed, $tracking, $discord, $dryRun) {
            foreach ($audits as $audit) {
                $this->line(" → {$audit->url} ({$audit->email}) score={$audit->score_total}");

                if ($dryRun) {
                    continue;
                }

                $subject = 'Votre audit Kodem : passez au-dessus de 75 % pour '.parse_url($audit->url, PHP_URL_HOST);
                $recos = $this->topRecommendations($audit);

                $followup = $audit->followups()->create([
                    'email' => $audit->email,
                    'reason' => AuditFollowup::REASON_LOW_SCORE,
                    'score_at_send' => $audit->score_total,
                    'subject' => mb_substr($subject, 0, 200),
                    'status' => AuditFollowup::STATUS_SENT,
                    'metadata' => [
                        'recos' => $recos,
                        'score_seo' => $audit->score_seo,
                        'score_security' => $audit->score_security,
                    ],
                    'sent_at' => now(),
                ]);

                $unsubscribeUrl = URL::route('audit.followup.unsubscribe', [
                    'audit' => $audit->uuid,
                    'token' => $audit->followupUnsubscribeToken(),
                ]);

                try {
                    Mail::to($audit->email)->send(new AuditFollowupMail($audit, $followup, $unsubscribeUrl));
                    $sent++;

                    $tracking->record('audit.followup.sent', 'audit_'.substr($audit->uuid, 0, 8), [
                        'audit_uuid' => $audit->uuid,
                        'followup_id' => $followup->id,
                        'score_total' => $audit->score_total,
                    ]);

                    $discord->notifyAuditEvent(DiscordNotifier::EVENT_FOLLOWUP_SENT, $audit, [
                        'Raison' => $followup->reason,
                        'Followup #' => (string) $followup->id,
                    ]);
                } catch (\Throwable $e) {
                    $followup->update([
                        'status' => AuditFollowup::STATUS_FAILED,
                        'error' => mb_substr($e->getMessage(), 0, 65535),
                    ]);
                    $failed++;
                    $this->error('   échec : '.$e->getMessage());
                    report($e);
                }
            }
        });

        $this->info("Envoyés : {$sent} — échecs : {$failed}");

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function topRecommendations(Audit $audit, int $limit = 3): array
    {
        $results = $audit->results ?? [];
        $checks = [];

        foreach (['seo', 'security'] as $section) {
            foreach ($results[$section] ?? [] as $key => $check) {
                $status = is_array($check) ? ($check['status'] ?? null) : $check;
                if (in_array($status, ['fail', 'warn'], true)) {
                    $reco = \App\Services\AuditRecommendations::for($key);
                    if ($reco !== null) {
                        $checks[] = $reco['fix'];
                    }
                }
            }
        }

        return array_slice(array_values(array_unique($checks)), 0, $limit);
    }
}
