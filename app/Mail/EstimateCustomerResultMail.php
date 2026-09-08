<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Src\Domains\Cms\Models\EstimateRequest;
use Src\Domains\Cms\Models\SiteSetting;

class EstimateCustomerResultMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public EstimateRequest $estimateRequest,
        public string $companyName,
        public SiteSetting $siteSettings,
    ) {}

    public function content(): Content
    {
        return new Content(
            view: 'emails.estimate-customer-result',
            with: [
                'companyName' => $this->companyName,
                'estimateRequest' => $this->estimateRequest,
                'siteSettings' => $this->siteSettings,
                'submittedAt' => now(),
            ],
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Phiếu tiếp nhận dự toán: '.$this->estimateRequest->tier_name,
        );
    }
}
