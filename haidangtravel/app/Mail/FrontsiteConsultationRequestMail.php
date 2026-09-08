<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FrontsiteConsultationRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public array $payload,
        public string $companyName,
    ) {}

    public function content(): Content
    {
        return new Content(
            view: 'emails.frontsite-consultation-request',
            with: [
                'companyName' => $this->companyName,
                'payload' => $this->payload,
                'submittedAt' => now(),
            ],
        );
    }

    public function envelope(): Envelope
    {
        $context = trim((string) ($this->payload['context_label'] ?? ''));

        return new Envelope(
            subject: $context !== ''
                ? 'Yêu cầu tư vấn mới: '.$context
                : 'Yêu cầu tư vấn mới từ frontsite',
        );
    }
}
