<?php

namespace App\Mail;

use App\Models\Audit;
use App\Models\MonitoringSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonitoringReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonitoringSubscription $subscription,
        public Audit $audit,
        public ?int $previousScore,
        public bool $alert,
    ) {}

    public function envelope(): Envelope
    {
        $prefix = $this->alert ? '⚠️ [ALERTE] ' : '';

        return new Envelope(
            subject: $prefix.'Rapport monitoring Kodem — '.$this->subscription->url,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.monitoring_report',
            with: [
                'subscription' => $this->subscription,
                'audit' => $this->audit,
                'previous' => $this->previousScore,
                'alert' => $this->alert,
            ],
        );
    }
}
