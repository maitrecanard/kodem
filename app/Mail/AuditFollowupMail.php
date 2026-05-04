<?php

namespace App\Mail;

use App\Models\Audit;
use App\Models\AuditFollowup;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuditFollowupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Audit $audit,
        public AuditFollowup $followup,
        public string $unsubscribeUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->followup->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.audit_followup',
            with: [
                'audit' => $this->audit,
                'followup' => $this->followup,
                'unsubscribeUrl' => $this->unsubscribeUrl,
                'recos' => $this->followup->metadata['recos'] ?? [],
            ],
        );
    }
}
