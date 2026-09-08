<?php

namespace App\Http\Requests;

use App\Services\Security\GoogleRecaptchaV3;
use Illuminate\Foundation\Http\FormRequest;

class RedeemCustomerGiftRequest extends FormRequest
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
            'phone' => ['required', 'string', 'min:8', 'max:20', 'regex:/^[0-9+\s().-]+$/'],
            'gift_id' => ['required', 'integer', 'min:1'],
            'gift_name' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'amount' => ['nullable', 'integer', 'min:1', 'max:999'],
            'expire' => ['nullable', 'date_format:Y-m-d'],
            'lookup_token' => ['nullable', 'string', 'max:2000'],
            'g-recaptcha-response' => ['nullable', 'string', 'max:4096'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $message = app(GoogleRecaptchaV3::class)->validateRequest($this, 'customer_loyalty_redeem');

            if ($message) {
                $validator->errors()->add('redemption', $message);
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $phone = trim((string) $this->input('phone', ''));

        if ($phone !== '') {
            $phone = preg_replace('/[^\d+]/', '', $phone) ?: $phone;
        }

        $this->merge([
            'phone' => $phone,
            'amount' => $this->input('amount') ?: 1,
        ]);
    }
}
