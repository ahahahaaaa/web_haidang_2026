<?php

namespace App\Services\Cms;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Src\Domains\Cms\Models\EstimateAccessKey;

class EstimateKeyService
{
    public function create(string $label, array $tierCodes, ?string $notes = null): EstimateAccessKey
    {
        return EstimateAccessKey::query()->create([
            'label' => trim($label) !== '' ? trim($label) : 'Key dự toán',
            'code' => $this->generateCode(),
            'tier_codes' => array_values(array_unique(array_filter(array_map(
                static fn ($code) => Str::slug((string) $code),
                $tierCodes,
            )))),
            'notes' => trim((string) $notes) ?: null,
        ]);
    }

    public function generateCode(int $length = 12): string
    {
        do {
            $code = Str::upper(Str::random($length));
        } while (EstimateAccessKey::query()->where('code', $code)->exists());

        return $code;
    }

    public function markUsed(EstimateAccessKey $key): void
    {
        $key->forceFill([
            'last_used_at' => now(),
            'used_count' => (int) $key->used_count + 1,
        ])->save();
    }

    public function validateForTier(?string $code, string $tierCode): EstimateAccessKey
    {
        $normalizedCode = Str::upper(trim((string) $code));

        if ($normalizedCode === '') {
            throw ValidationException::withMessages([
                'estimate_key' => 'Cấp dự toán đã chọn yêu cầu mã key.',
            ]);
        }

        $key = EstimateAccessKey::query()->where('code', $normalizedCode)->first();

        if (! $key || ! $key->is_active) {
            throw ValidationException::withMessages([
                'estimate_key' => 'Mã key không hợp lệ hoặc đang tạm tắt.',
            ]);
        }

        $tierCodes = collect($key->tier_codes ?? [])
            ->map(fn ($item) => Str::slug((string) $item))
            ->filter()
            ->values()
            ->all();

        if ($tierCodes !== [] && ! in_array(Str::slug($tierCode), $tierCodes, true)) {
            throw ValidationException::withMessages([
                'estimate_key' => 'Mã key này không áp dụng cho cấp dự toán đã chọn.',
            ]);
        }

        return $key;
    }
}
