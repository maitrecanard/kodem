<?php

declare(strict_types=1);

namespace App\Modules\Audits\Notifications;

use App\Modules\Audits\Models\AuditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewPremiumOrderForAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AuditRequest $auditRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $audit = $this->auditRequest;
        $amount = number_format(($audit->amount_cents ?? 0) / 100, 2, ',', ' ');
        $currency = strtoupper((string) $audit->currency);
        $options = collect($audit->options ?? [])
            ->filter()
            ->keys()
            ->implode(', ') ?: 'aucune';

        return (new MailMessage())
            ->subject("Nouvel audit premium payé — {$audit->domain}")
            ->greeting('Nouvelle commande premium payée')
            ->line("Domaine : {$audit->domain}")
            ->line("Client : {$audit->name} <{$audit->email}>")
            ->line("Options : {$options}")
            ->line("Montant : {$amount} {$currency}")
            ->line("Référence interne (UUID) : {$audit->uuid}");
    }
}
