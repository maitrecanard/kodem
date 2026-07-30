<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notification interne : un message a été déposé depuis /contact.
 *
 * L'expéditeur (From) reste le domaine du site — mettre l'adresse du visiteur
 * en From ferait échouer SPF/DKIM et enverrait la notification en spam.
 * C'est le Reply-To qui porte l'adresse du visiteur, pour répondre en un clic.
 */
class ContactMessageReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        $sujet = $this->contactMessage->subject;

        return new Envelope(
            subject: "[kodem] Nouveau message — {$sujet}",
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact_message_received',
            with: ['contactMessage' => $this->contactMessage],
        );
    }
}
