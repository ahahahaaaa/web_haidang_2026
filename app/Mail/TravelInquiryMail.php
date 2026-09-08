<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Src\Domains\Cms\Models\TravelInquiry;

class TravelInquiryMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public TravelInquiry $inquiry)
    {
    }

    public function envelope(): Envelope
    {
        $context = data_get($this->inquiry->meta, 'subject')
            ?: $this->inquiry->context_title
            ?: $this->inquiry->source->label();

        return new Envelope(
            subject: 'Travel inquiry: '.$context,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.travel-inquiry',
        );
    }
}
