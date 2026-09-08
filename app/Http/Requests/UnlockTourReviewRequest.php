<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnlockTourReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_password' => ['required', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'review_password.required' => 'Vui lòng nhập mật khẩu đánh giá.',
            'review_password.max' => 'Mật khẩu đánh giá không được vượt quá :max ký tự.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'review_password' => trim((string) $this->input('review_password')),
        ]);
    }
}
