<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Services\DiscordNotifier;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditFollowupController extends Controller
{
    public function unsubscribe(Request $request, Audit $audit, TrackingService $tracking, DiscordNotifier $discord): Response
    {
        $token = (string) $request->query('token', '');
        $expected = $audit->followupUnsubscribeToken();

        if (! hash_equals($expected, $token)) {
            abort(403, 'Lien de désinscription invalide.');
        }

        $alreadyUnsubscribed = $audit->isFollowupUnsubscribed();

        if (! $alreadyUnsubscribed) {
            $audit->update(['followup_unsubscribed_at' => now()]);

            $tracking->record('audit.followup.unsubscribed', 'audit_'.substr($audit->uuid, 0, 8), [
                'audit_uuid' => $audit->uuid,
            ], $request);

            $discord->notifyAuditEvent(DiscordNotifier::EVENT_FOLLOWUP_UNSUBSCRIBED, $audit->fresh());
        }

        return Inertia::render('Public/AuditFollowupUnsubscribed', [
            'meta' => [
                'title' => 'Désinscription confirmée — Kodem',
                'description' => 'Vous ne recevrez plus de relance pour cet audit.',
                'keywords' => 'désinscription audit',
            ],
            'audit' => [
                'uuid' => $audit->uuid,
                'url' => $audit->url,
            ],
            'already' => $alreadyUnsubscribed,
        ]);
    }
}
