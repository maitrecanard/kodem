<?php

namespace App\Http\Controllers;

use App\Mail\ContactAcknowledgement;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Services\TrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function store(Request $request, TrackingService $tracking): RedirectResponse
    {
        // Honeypot — si rempli, on silent-discard.
        if (filled($request->input('website'))) {
            $tracking->record('contact.spam_blocked', 'honeypot', [], $request);
            return back()->with('success', 'Merci.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:180'],
            'company' => ['nullable', 'string', 'max:120'],
            'subject' => ['required', 'string', 'min:3', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $message = ContactMessage::create([
            ...$validated,
            'ip_hash' => hash('sha256', (string) $request->ip().config('app.key')),
            'status' => 'new',
        ]);

        $tracking->record('contact.submitted', 'msg_'.$message->id, [
            'message_id' => $message->id,
            'has_company' => ! empty($validated['company']),
        ], $request);

        $this->notify($message);

        return back()->with('success', 'Message envoyé.');
    }

    /**
     * Envoi SMTP synchrone : la notification interne, puis l'accusé au visiteur.
     *
     * Le message est DÉJÀ persisté à ce stade : un incident SMTP ne fait donc
     * jamais perdre une demande. On journalise l'erreur — jamais de `@` ni de
     * catch muet — et on laisse la confirmation s'afficher, l'échec d'envoi
     * n'étant pas imputable au visiteur ni corrigeable par lui.
     *
     * Les deux envois sont isolés : un accusé qui échoue (adresse jetable,
     * domaine inexistant) ne doit pas empêcher la notification interne.
     */
    private function notify(ContactMessage $message): void
    {
        $destinataire = config('contact.notify_email');

        if (blank($destinataire)) {
            Log::warning('Contact : aucune adresse de notification configurée, message non transmis par e-mail.', [
                'message_id' => $message->id,
                'config' => 'contact.notify_email (CONTACT_NOTIFY_EMAIL ou AUDITS_ADMIN_EMAIL)',
            ]);
        } else {
            try {
                $mailer = Mail::to($destinataire);

                if (filled($cc = config('contact.notify_cc'))) {
                    $mailer->cc($cc);
                }

                $mailer->send(new ContactMessageReceived($message));
            } catch (Throwable $e) {
                Log::error('Contact : échec de la notification interne.', [
                    'message_id' => $message->id,
                    'destinataire' => $destinataire,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        if (! config('contact.send_acknowledgement')) {
            return;
        }

        try {
            Mail::to($message->email)->send(new ContactAcknowledgement($message));
        } catch (Throwable $e) {
            Log::error("Contact : échec de l'accusé de réception au visiteur.", [
                'message_id' => $message->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
