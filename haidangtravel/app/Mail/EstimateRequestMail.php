<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Src\Domains\Cms\Models\EstimateRequest;

class EstimateRequestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public EstimateRequest $estimateRequest,
        public string $companyName,
    ) {}

    public function content(): Content
    {
        return new Content(
            view: 'emails.estimate-request',
            with: [
                'companyName' => $this->companyName,
                'estimateRequest' => $this->estimateRequest,
                'submittedAt' => now(),
            ],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yêu cầu dự toán mới: '.$this->estimateRequest->tier_name,
        );
    }
}
