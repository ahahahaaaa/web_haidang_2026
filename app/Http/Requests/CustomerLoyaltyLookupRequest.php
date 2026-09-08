<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerLoyaltyLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'min:8', 'max:20', 'regex:/^[0-9+\s().-]+$/'],
        ];
    }

    public function hasLookup(): bool
    {
        return filled($this->validated('phone'));
    }

    public function phone(): string
    {
        return (string) $this->validated('phone', '');
    }

    protected function prepareForValidation(): void
    {
        $phone = trim((string) $this->query('phone', ''));

        if ($phone !== '') {
            $phone = preg_replace('/[^\d+]/', '', $phone) ?: $phone;
        }

        $this->merge(['phone' => $phone]);
    }
}
