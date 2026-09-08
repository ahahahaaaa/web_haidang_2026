<?php

namespace App\Services\Travel;

use App\Mail\TravelInquiryMail;
use App\Services\Cms\SiteSettingsManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;
use Src\Domains\Cms\Models\TravelInquiry;

class TravelInquiryService
{
    public function __construct(protected SiteSettingsManager $site)
    {
    }

    public function submit(array $validated): TravelInquiry
    {
        $adultGuestCount = array_key_exists('adult_guest_count', $validated) && filled($validated['adult_guest_count'])
            ? (int) $validated['adult_guest_count']
            : null;

        $meta = array_filter([
            'address' => $validated['address'] ?? null,
            'adult_guest_count' => $adultGuestCount,
            'company_name' => $validated['company_name'] ?? null,
            'expected_destination' => $validated['expected_destination'] ?? null,
            'expected_time' => $validated['expected_time'] ?? null,
            'inquiry_type' => $validated['inquiry_type'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'voucher_campaign_slug' => $validated['voucher_campaign_slug'] ?? null,
            'voucher_variant' => $validated['voucher_variant'] ?? $validated['ab_variant'] ?? null,
        ], fn ($value) => filled($value));

        $inquiry = TravelInquiry::query()->create([
            ...Arr::except($validated, ['address', 'adult_guest_count', 'company_name', 'expected_destination', 'expected_time', 'inquiry_type', 'subject', 'submission_mode', 'voucher_campaign_slug', 'voucher_detail_required', 'voucher_variant', 'ab_variant']),
            'context_title' => $validated['context_title'] ?? $validated['subject'] ?? null,
            'meta' => $meta !== [] ? $meta : null,
            'status' => 'new',
            'mail_status' => 'pending',
        ]);

        $recipient = $this->resolveRecipient();

        if (! $recipient) {
            $inquiry->forceFill([
                'mail_status' => 'skipped',
                'meta' => [
                    ...($inquiry->meta ?? []),
                    'reason' => 'missing_contact_recipient',
                ],
            ])->save();

            return $inquiry;
        }

        try {
            Mail::to($recipient)->send(new TravelInquiryMail($inquiry));

            $inquiry->forceFill([
                'mail_status' => 'sent',
                'mailed_at' => now(),
                'mailed_to' => $recipient,
            ])->save();
        } catch (\Throwable $exception) {
            report($exception);

            $inquiry->forceFill([
                'mail_status' => 'failed',
                'mailed_to' => $recipient,
                'meta' => [
                    ...($inquiry->meta ?? []),
                    'mail_error' => $exception->getMessage(),
                ],
            ])->save();
        }

        return $inquiry;
    }

    protected function resolveRecipient(): ?string
    {
        $settings = $this->site->current();

        return $settings->mail_contact_recipient
            ?: $settings->primary_email
            ?: $settings->mail_from_address;
    }
}
