<?php

namespace App\Services\Travel;

use App\Support\VietnamPhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\TravelInquiry;
use Src\Domains\Cms\Models\VoucherCampaign;
use Src\Domains\Cms\Models\VoucherCode;
use Symfony\Component\HttpFoundation\Cookie;

class VoucherCampaignService
{
    public function activeForLanding(LandingPage $landing): ?VoucherCampaign
    {
        return $this->latestRedeemableCampaign(
            VoucherCampaign::query()
                ->redeemable()
                ->where('landing_page_id', $landing->getKey())
        );
    }

    public function activeForTourVoucherCta(): ?VoucherCampaign
    {
        $tag = trim((string) config('frontsite_voucher.tour_cta_tag', 'voucher-du-lich'));

        if ($this->tourVoucherCtaTagDisabled($tag)) {
            return null;
        }

        $landing = LandingPage::query()
            ->whereNull('page_key')
            ->where('slug', 'voucher-du-lich')
            ->where('is_active', true)
            ->first();

        if (! $landing) {
            return null;
        }

        $query = VoucherCampaign::query()
            ->redeemable()
            ->with('landingPage')
            ->where('landing_page_id', $landing->getKey());

        if ($this->tourVoucherCtaTagRequestsLatest($tag)) {
            return $this->latestRedeemableCampaign($query);
        }

        return $this->latestRedeemableCampaign(
            (clone $query)->where('slug', $tag)
        );
    }

    public function remembered(string $campaignSlug, Request $request): array
    {
        $campaign = VoucherCampaign::query()->where('slug', $campaignSlug)->first();

        if (! $campaign) {
            return ['voucher' => null, 'cookie' => null, 'forget_cookie' => false];
        }

        $remembered = $this->rememberedCode($campaign, $request);

        if (! $remembered) {
            $hasCookie = filled($request->cookie($campaign->cookieName()));

            return [
                'voucher' => null,
                'cookie' => $hasCookie ? $this->forgetCookie($campaign) : null,
                'forget_cookie' => $hasCookie,
            ];
        }

        return [
            'voucher' => $this->payload($campaign, $remembered),
            'cookie' => null,
            'forget_cookie' => false,
        ];
    }

    public function redeem(string $campaignSlug, TravelInquiry $inquiry, Request $request): array
    {
        $campaign = VoucherCampaign::query()->redeemable()->where('slug', $campaignSlug)->first();

        if (! $campaign) {
            $this->appendInquiryVoucherMeta($inquiry, [
                'campaign_slug' => $campaignSlug,
                'status' => 'inactive',
            ]);

            return ['voucher' => null, 'cookie' => null, 'status' => 'inactive'];
        }

        if (($remembered = $this->rememberedCode($campaign, $request)) && $remembered->code_set_version === $campaign->code_set_version) {
            $this->appendInquiryVoucherMeta($inquiry, [
                'campaign_id' => $campaign->getKey(),
                'campaign_slug' => $campaign->slug,
                'code' => $remembered->code,
                'code_id' => $remembered->getKey(),
                'status' => 'remembered',
            ]);

            return [
                'voucher' => $this->payload($campaign, $remembered),
                'cookie' => null,
                'status' => 'remembered',
            ];
        }

        $phoneHash = $this->phoneHash($inquiry->customer_phone);
        $redeemStatus = 'issued';

        $code = DB::transaction(function () use ($campaign, $inquiry, $phoneHash, &$redeemStatus): ?VoucherCode {
            /** @var VoucherCampaign|null $lockedCampaign */
            $lockedCampaign = VoucherCampaign::query()
                ->whereKey($campaign->getKey())
                ->lockForUpdate()
                ->first();

            if (! $lockedCampaign || ! $lockedCampaign->isCurrentlyRedeemable()) {
                return null;
            }

            if ($phoneHash !== '') {
                /** @var VoucherCode|null $existingCode */
                $existingCode = VoucherCode::query()
                    ->where('voucher_campaign_id', $lockedCampaign->getKey())
                    ->where('code_set_version', $lockedCampaign->code_set_version)
                    ->where('customer_phone_hash', $phoneHash)
                    ->issued()
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();

                if ($existingCode) {
                    $redeemStatus = 'already_issued';

                    return $existingCode;
                }
            }

            /** @var VoucherCode|null $code */
            $code = VoucherCode::query()
                ->where('voucher_campaign_id', $lockedCampaign->getKey())
                ->where('code_set_version', $lockedCampaign->code_set_version)
                ->available()
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $code) {
                return null;
            }

            $code->forceFill([
                'travel_inquiry_id' => $inquiry->getKey(),
                'status' => VoucherCode::STATUS_CLAIMED,
                'cookie_token' => Str::random(64),
                'customer_phone_hash' => $phoneHash ?: null,
                'claimed_at' => now(),
            ])->save();

            return $code;
        });

        if (! $code) {
            $this->appendInquiryVoucherMeta($inquiry, [
                'campaign_id' => $campaign->getKey(),
                'campaign_slug' => $campaign->slug,
                'status' => 'out_of_codes',
            ]);

            return ['voucher' => null, 'cookie' => null, 'status' => 'out_of_codes'];
        }

        $this->appendInquiryVoucherMeta($inquiry, [
            'campaign_id' => $campaign->getKey(),
            'campaign_slug' => $campaign->slug,
            'code' => $code->code,
            'code_id' => $code->getKey(),
            'status' => $redeemStatus,
        ]);

        return [
            'voucher' => $this->payload($campaign, $code),
            'cookie' => $this->cookie($campaign, $code),
            'status' => $redeemStatus,
        ];
    }

    public function ensureGeneratedCodes(VoucherCampaign $campaign, int $quantity, string $prefix): void
    {
        $quantity = max(0, $quantity);

        if (blank($campaign->code_set_version)) {
            $campaign->forceFill(['code_set_version' => (string) Str::uuid()])->save();
        }

        $campaign->codes()
            ->whereNull('code_set_version')
            ->update(['code_set_version' => $campaign->code_set_version]);

        $existingCount = $campaign->codes()->count();

        if ($existingCount >= $quantity) {
            return;
        }

        for ($index = $existingCount; $index < $quantity; $index++) {
            $campaign->codes()->create([
                'code' => $this->uniqueGeneratedCode($campaign, $prefix),
                'code_set_version' => $campaign->code_set_version,
            ]);
        }
    }

    public function refreshGeneratedCodes(VoucherCampaign $campaign, int $quantity, string $prefix): void
    {
        DB::transaction(function () use ($campaign, $quantity, $prefix): void {
            $campaign->codes()
                ->where('status', VoucherCode::STATUS_AVAILABLE)
                ->update(['status' => VoucherCode::STATUS_DISABLED]);

            $campaign->refreshCodeSetVersion();
            $campaign->refresh();

            for ($index = 0; $index < max(0, $quantity); $index++) {
                $campaign->codes()->create([
                    'code' => $this->uniqueGeneratedCode($campaign, $prefix),
                    'code_set_version' => $campaign->code_set_version,
                ]);
            }
        });
    }

    protected function appendInquiryVoucherMeta(TravelInquiry $inquiry, array $voucherMeta): void
    {
        $inquiry->forceFill([
            'meta' => [
                ...($inquiry->meta ?? []),
                'voucher' => $voucherMeta,
            ],
        ])->save();
    }

    protected function latestRedeemableCampaign(Builder $query): ?VoucherCampaign
    {
        return $query
            ->orderByDesc('starts_at')
            ->orderByDesc('id')
            ->first();
    }

    protected function tourVoucherCtaTagDisabled(string $tag): bool
    {
        return in_array(Str::lower($tag), ['', '0', 'false', 'off', 'disabled', 'none'], true);
    }

    protected function tourVoucherCtaTagRequestsLatest(string $tag): bool
    {
        return in_array(Str::lower($tag), ['1', 'true', 'on', 'yes', 'enabled', 'latest', 'auto', 'voucher-du-lich'], true);
    }

    protected function cookie(VoucherCampaign $campaign, VoucherCode $code): Cookie
    {
        return cookie(
            $campaign->cookieName(),
            json_encode([
                'campaign_slug' => $campaign->slug,
                'code_id' => $code->getKey(),
                'token' => $code->cookie_token,
                'version' => $campaign->code_set_version,
            ], JSON_THROW_ON_ERROR),
            0,
            null,
            null,
            (bool) config('session.secure', false),
            true,
            false,
            'Lax',
        );
    }

    protected function forgetCookie(VoucherCampaign $campaign): Cookie
    {
        return cookie()->forget($campaign->cookieName());
    }

    protected function payload(VoucherCampaign $campaign, VoucherCode $code): array
    {
        $codeValidUntil = $campaign->code_valid_until;

        return [
            'campaign_slug' => $campaign->slug,
            'campaign_title' => $campaign->title,
            'code' => $code->code,
            'description' => $campaign->description,
            'frame_image_url' => $campaign->frame_image_url,
            'valid_from' => $campaign->starts_at?->toIso8601String(),
            'valid_from_label' => $campaign->starts_at?->format('d/m/Y'),
            'valid_until' => $codeValidUntil?->toIso8601String(),
            'valid_until_label' => $codeValidUntil?->format('d/m/Y'),
            'valid_until_note' => $codeValidUntil ? 'Áp dụng đến hết ngày '.$codeValidUntil->format('d/m/Y') : null,
            'download_filename' => Str::slug($campaign->slug.'-'.$code->code).'.png',
        ];
    }

    protected function rememberedCode(VoucherCampaign $campaign, Request $request): ?VoucherCode
    {
        $cookieValue = $request->cookie($campaign->cookieName());

        if (! is_string($cookieValue) || $cookieValue === '') {
            return null;
        }

        $payload = json_decode($cookieValue, true);

        if (! is_array($payload) || ($payload['version'] ?? null) !== $campaign->code_set_version) {
            return null;
        }

        $codeId = $payload['code_id'] ?? null;
        $token = $payload['token'] ?? null;

        if (! is_numeric($codeId) || blank($token)) {
            return null;
        }

        return VoucherCode::query()
            ->where('voucher_campaign_id', $campaign->getKey())
            ->whereKey((int) $codeId)
            ->where('code_set_version', $campaign->code_set_version)
            ->where('cookie_token', $token)
            ->whereIn('status', [VoucherCode::STATUS_CLAIMED, VoucherCode::STATUS_USED])
            ->first();
    }

    protected function phoneHash(?string $phone): string
    {
        $normalized = VietnamPhoneNumber::normalize((string) $phone);

        return is_string($normalized) && $normalized !== ''
            ? hash('sha256', mb_strtolower($normalized))
            : '';
    }

    protected function uniqueGeneratedCode(VoucherCampaign $campaign, string $prefix): string
    {
        $prefix = Str::of($prefix)->upper()->replaceMatches('/[^A-Z0-9]+/', '-')->trim('-')->value() ?: 'HDTRAVEL';

        do {
            $code = $prefix.'-'.Str::upper(Str::random(6));
        } while ($campaign->codes()->where('code', $code)->exists());

        return $code;
    }
}
