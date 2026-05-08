<?php

namespace App\Services;

use App\Models\Audit;
use Illuminate\Support\Facades\Http;

class DiscordNotifier
{
    public const EVENT_SUBMITTED = 'audit.submitted';
    public const EVENT_COMPLETED = 'audit.completed';
    public const EVENT_FAILED = 'audit.failed';
    public const EVENT_PAID = 'audit.paid';
    public const EVENT_PDF_PAID = 'audit.pdf.paid';
    public const EVENT_CWV_PAID = 'audit.cwv.paid';
    public const EVENT_FOLLOWUP_SENT = 'audit.followup.sent';
    public const EVENT_FOLLOWUP_UNSUBSCRIBED = 'audit.followup.unsubscribed';

    /**
     * Notifie une étape du cycle de vie d'un audit.
     *
     * Silent-fail : une indisponibilité Discord ne doit jamais propager
     * vers la requête utilisateur.
     *
     * @param array<string,mixed> $extra
     */
    public function notifyAuditEvent(string $event, Audit $audit, array $extra = []): void
    {
        $url = (string) config('audit.discord_webhook_url', '');
        if ($url === '' || ! config('audit.discord_enabled', false)) {
            return;
        }

        try {
            $payload = $this->buildPayload($event, $audit, $extra);
            Http::timeout(3)->connectTimeout(3)->post($url, $payload);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    public function buildPayload(string $event, Audit $audit, array $extra = []): array
    {
        [$emoji, $title, $color] = $this->presentation($event, $audit);

        $fields = [
            ['name' => 'URL', 'value' => mb_substr($audit->url, 0, 512), 'inline' => false],
            ['name' => 'UUID', 'value' => substr($audit->uuid, 0, 8), 'inline' => true],
        ];

        if ($audit->email !== null) {
            $fields[] = ['name' => 'Email', 'value' => $audit->email, 'inline' => true];
        }

        if ($audit->score_total !== null && in_array($event, [
            self::EVENT_COMPLETED,
            self::EVENT_PAID,
            self::EVENT_PDF_PAID,
            self::EVENT_CWV_PAID,
            self::EVENT_FOLLOWUP_SENT,
        ], true)) {
            $fields[] = [
                'name' => 'Score',
                'value' => $audit->score_total.'/100 (SEO '.($audit->score_seo ?? '–').' / Sécu '.($audit->score_security ?? '–').')',
                'inline' => false,
            ];
        }

        foreach ($extra as $name => $value) {
            $fields[] = ['name' => (string) $name, 'value' => mb_substr((string) $value, 0, 1024), 'inline' => true];
        }

        return [
            'username' => 'Kodem audits',
            'embeds' => [[
                'title' => $emoji.' '.$title,
                'color' => $color,
                'fields' => $fields,
                'timestamp' => now()->toIso8601String(),
                'footer' => ['text' => 'kodem.fr · '.$event],
            ]],
        ];
    }

    /**
     * @return array{0:string, 1:string, 2:int}
     */
    protected function presentation(string $event, Audit $audit): array
    {
        return match ($event) {
            self::EVENT_SUBMITTED => ['📝', 'Nouvel audit demandé', 0x4F46E5],
            self::EVENT_COMPLETED => ['✅', 'Audit terminé', 0x16A34A],
            self::EVENT_FAILED => ['❌', 'Audit échoué', 0xBE123C],
            self::EVENT_PAID => ['💳', 'Audit payé', 0x059669],
            self::EVENT_PDF_PAID => ['📄', 'PDF débloqué', 0x0EA5E9],
            self::EVENT_CWV_PAID => ['⚡', 'Core Web Vitals débloqué', 0x9333EA],
            self::EVENT_FOLLOWUP_SENT => ['📬', 'Relance commerciale envoyée', 0xF59E0B],
            self::EVENT_FOLLOWUP_UNSUBSCRIBED => ['🚪', 'Désinscription des relances', 0x64748B],
            default => ['ℹ️', $event, 0x1E3A8A],
        };
    }
}
