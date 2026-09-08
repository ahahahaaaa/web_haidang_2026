<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Src\Domains\Cms\Models\Service;

class ServiceConsultationRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Service $service,
        public array $payload,
        public string $companyName,
    ) {}

    public function content(): Content
    {
        return new Content(
            view: 'emails.service-consultation-request',
            with: [
                'companyName' => $this->companyName,
                'payload' => $this->payload,
                'service' => $this->service,
                'submittedAt' => now(),
            ],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yêu cầu tư vấn dịch vụ: '.$this->service->title,
        );
    }
}
