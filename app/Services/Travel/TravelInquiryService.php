<?php

namespace App\Services\Travel;

use App\Mail\TravelInquiryMail;
use App\Services\Cms\SiteSettingsManager;
use App\Services\Frontsite\FrontsiteCacheInvalidator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Src\Domains\Cms\Models\TourFlashSaleItem;
use Src\Domains\Cms\Models\TravelInquiry;

class TravelInquiryService
{
    public function __construct(
        protected SiteSettingsManager $site,
        protected TourBookingQuoteService $tourBookingQuotes,
        protected FrontsiteCacheInvalidator $frontsiteCacheInvalidator,
    ) {}

    public function submit(array $validated): TravelInquiry
    {
        $adultGuestCount = array_key_exists('adult_guest_count', $validated) && filled($validated['adult_guest_count'])
            ? (int) $validated['adult_guest_count']
            : null;

        $inquiry = DB::transaction(function () use ($validated, $adultGuestCount): TravelInquiry {
            $bookingQuote = $this->tourBookingQuotes->resolveAndReserve($validated);
            $meta = array_filter([
                'address' => $validated['address'] ?? null,
                'adult_guest_count' => $adultGuestCount,
                'booking_quote' => $bookingQuote,
                'company_name' => $validated['company_name'] ?? null,
                'expected_destination' => $validated['expected_destination'] ?? null,
                'expected_time' => $validated['expected_time'] ?? null,
                'inquiry_type' => $validated['inquiry_type'] ?? null,
                'subject' => $validated['subject'] ?? null,
                'voucher_campaign_slug' => $validated['voucher_campaign_slug'] ?? null,
                'voucher_variant' => $validated['voucher_variant'] ?? $validated['ab_variant'] ?? null,
            ], fn ($value) => filled($value));

            return TravelInquiry::query()->create([
                ...Arr::except($validated, ['address', 'adult_guest_count', 'company_name', 'expected_destination', 'expected_time', 'flash_sale_slug', 'inquiry_type', 'subject', 'submission_mode', 'tour_departure_id', 'voucher_campaign_slug', 'voucher_detail_required', 'voucher_variant', 'ab_variant']),
                'tour_departure_id' => data_get($bookingQuote, 'tour_departure_id'),
                'tour_flash_sale_item_id' => data_get($bookingQuote, 'tour_flash_sale_item_id'),
                'travel_date' => data_get($bookingQuote, 'departure_date') ?? ($validated['travel_date'] ?? null),
                'quoted_unit_price' => data_get($bookingQuote, 'quoted_unit_price'),
                'regular_unit_price' => data_get($bookingQuote, 'regular_unit_price'),
                'price_type' => data_get($bookingQuote, 'price_type'),
                'ticket_count' => data_get($bookingQuote, 'ticket_count'),
                'quoted_at' => data_get($bookingQuote, 'quoted_at'),
                'context_title' => $validated['context_title'] ?? $validated['subject'] ?? null,
                'meta' => $meta !== [] ? $meta : null,
                'status' => 'new',
                'mail_status' => 'pending',
            ]);
        }, 3);

        if ($inquiry->price_type === 'flash_sale' && $inquiry->tour_flash_sale_item_id) {
            rescue(function () use ($inquiry): void {
                $item = TourFlashSaleItem::query()->find($inquiry->tour_flash_sale_item_id);

                if ($item) {
                    $this->frontsiteCacheInvalidator->modelChanged($item);
                }
            }, report: true);
        }

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

    public function confirmationMessage(TravelInquiry $inquiry): string
    {
        return $this->tourBookingQuotes->confirmationMessage(data_get($inquiry->meta, 'booking_quote'));
    }

    protected function resolveRecipient(): ?string
    {
        $settings = $this->site->current();

        return $settings->mail_contact_recipient
            ?: $settings->primary_email
            ?: $settings->mail_from_address;
    }
}
