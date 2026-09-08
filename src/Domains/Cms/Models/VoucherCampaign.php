<?php

namespace Src\Domains\Cms\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VoucherCampaign extends Model
{
    protected $table = 'voucher_campaigns';

    protected $fillable = [
        'landing_page_id',
        'title',
        'slug',
        'description',
        'frame_image_url',
        'code_prefix',
        'code_quantity',
        'code_set_version',
        'starts_at',
        'ends_at',
        'code_valid_until',
        'is_active',
        'meta',
        'codes_refreshed_at',
    ];

    protected $attributes = [
        'is_active' => true,
        'code_quantity' => 0,
    ];

    protected function casts(): array
    {
        return [
            'code_quantity' => 'integer',
            'code_valid_until' => 'datetime',
            'codes_refreshed_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
            'meta' => 'array',
            'starts_at' => 'datetime',
        ];
    }

    public function codes(): HasMany
    {
        return $this->hasMany(VoucherCode::class, 'voucher_campaign_id');
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'landing_page_id');
    }

    public function scopeRedeemable(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $nested): void {
                $nested->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $nested): void {
                $nested->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function cookieName(): string
    {
        return 'haidang_voucher_'.Str::slug($this->slug, '_');
    }

    public function isCurrentlyRedeemable(): bool
    {
        $now = now();

        return $this->is_active
            && (! $this->starts_at || $this->starts_at->lessThanOrEqualTo($now))
            && (! $this->ends_at || $this->ends_at->greaterThanOrEqualTo($now));
    }

    public function isCodeUsageOpen(): bool
    {
        return ! $this->code_valid_until || $this->code_valid_until->greaterThanOrEqualTo(now());
    }

    public function refreshCodeSetVersion(): void
    {
        $this->forceFill([
            'code_set_version' => (string) Str::uuid(),
            'codes_refreshed_at' => now(),
        ])->save();
    }
}
