<?php

declare(strict_types=1);

namespace App\Modules\Audits\Mail;

use App\Modules\Audits\Models\AuditRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PremiumOrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AuditRequest $auditRequest)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de votre commande — Audit premium',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.premium_order_confirmation',
            with: [
                'auditRequest' => $this->auditRequest,
            ],
        );
    }
}
