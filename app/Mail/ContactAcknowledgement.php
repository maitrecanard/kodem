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
 * Accusé de réception envoyé au visiteur ayant rempli /contact.
 *
 * Reply-To pointe vers l'adresse de contact interne : si le visiteur répond
 * à cet accusé, sa réponse arrive dans la bonne boîte et non dans un no-reply.
 */
class ContactAcknowledgement extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        $replyTo = config('contact.notify_email');

        return new Envelope(
            subject: 'Votre message a bien été reçu — KODEM',
            replyTo: $replyTo ? [new Address($replyTo)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact_acknowledgement',
            with: ['contactMessage' => $this->contactMessage],
        );
    }
}
