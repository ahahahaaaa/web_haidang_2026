<?php

namespace Src\Domains\Cms\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoucherCode extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_CLAIMED = 'claimed';

    public const STATUS_USED = 'used';

    public const STATUS_DISABLED = 'disabled';

    protected $table = 'voucher_codes';

    protected $fillable = [
        'voucher_campaign_id',
        'travel_inquiry_id',
        'code',
        'code_set_version',
        'status',
        'cookie_token',
        'customer_phone_hash',
        'claimed_at',
        'used_by',
        'used_at',
        'used_note',
    ];

    protected $attributes = [
        'status' => self::STATUS_AVAILABLE,
    ];

    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(VoucherCampaign::class, 'voucher_campaign_id');
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(TravelInquiry::class, 'travel_inquiry_id');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeIssued(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_CLAIMED, self::STATUS_USED]);
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Chưa phát',
            self::STATUS_CLAIMED => 'Đã phát',
            self::STATUS_USED => 'Đã sử dụng',
            self::STATUS_DISABLED => 'Đã vô hiệu',
        ];
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }
}
