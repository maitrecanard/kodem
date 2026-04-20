<?php

namespace App\Console\Commands;

use App\Models\MonitoringSubscription;
use App\Services\MonitoringRunner;
use Illuminate\Console\Command;

class RunMonitoringCommand extends Command
{
    protected $signature = 'monitoring:run {--token= : Limiter à un abonnement précis (token)}';
    protected $description = 'Exécute les audits périodiques pour les abonnements de monitoring actifs';

    public function handle(MonitoringRunner $runner): int
    {
        $query = MonitoringSubscription::query()
            ->where('status', 'active')
            ->where('active_until', '>', now());

        if ($token = $this->option('token')) {
            $query->where('token', $token);
        }

        $subs = $query->get();

        $this->info("Abonnements à traiter : {$subs->count()}");

        foreach ($subs as $sub) {
            $this->line(" → {$sub->url} ({$sub->email})");
            try {
                $res = $runner->run($sub);
                $this->line("   score : {$res['new_score']}/100".($res['alert'] ? '  ⚠️ ALERTE' : ''));
            } catch (\Throwable $e) {
                $this->error("   échec : ".$e->getMessage());
            }
        }

        // Marque les expirés
        MonitoringSubscription::query()
            ->where('status', 'active')
            ->where('active_until', '<=', now())
            ->update(['status' => 'expired']);

        return self::SUCCESS;
    }
}
