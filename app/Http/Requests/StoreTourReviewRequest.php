<?php

namespace App\Http\Requests;

use App\Services\Security\GoogleRecaptchaV3;
use App\Support\VietnamPhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

class StoreTourReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:255'],
            'author_email' => ['nullable', 'email', 'max:255'],
            'author_phone' => ['required', 'string', 'max:20'],
            'author_title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'g-recaptcha-response' => ['nullable', 'string', 'max:4096'],
            'rating_value' => ['required', 'numeric', 'between:1,5'],
            'tour_review_batch_id' => ['nullable', 'integer', 'exists:tour_review_batches,id'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $validator->errors()->has('author_phone') && ! VietnamPhoneNumber::isValid($this->input('author_phone'))) {
                $validator->errors()->add('author_phone', 'Vui lòng nhập số điện thoại Việt Nam hợp lệ.');
            }

            $message = app(GoogleRecaptchaV3::class)->validateRequest($this, 'tour_review');

            if ($message) {
                $validator->errors()->add('g-recaptcha-response', $message);
            }
        });
    }

    public function messages(): array
    {
        return [
            'author_name.required' => 'Vui lòng nhập họ tên.',
            'author_name.max' => 'Họ tên không được vượt quá :max ký tự.',
            'author_email.email' => 'Email chưa đúng định dạng.',
            'author_email.max' => 'Email không được vượt quá :max ký tự.',
            'author_phone.required' => 'Vui lòng nhập số điện thoại.',
            'author_phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'author_title.max' => 'Vai trò hoặc ngữ cảnh không được vượt quá :max ký tự.',
            'content.required' => 'Vui lòng nhập nội dung đánh giá.',
            'content.max' => 'Nội dung đánh giá không được vượt quá :max ký tự.',
            'g-recaptcha-response.max' => 'Mã xác minh reCAPTCHA không hợp lệ.',
            'rating_value.required' => 'Vui lòng chọn điểm đánh giá.',
            'rating_value.between' => 'Điểm đánh giá phải nằm trong khoảng từ 1 đến 5.',
            'tour_review_batch_id.exists' => 'Lượt đánh giá không hợp lệ.',
            'title.max' => 'Tiêu đề đánh giá không được vượt quá :max ký tự.',
        ];
    }

    public function attributes(): array
    {
        return [
            'author_name' => 'họ tên',
            'author_email' => 'email',
            'author_phone' => 'số điện thoại',
            'author_title' => 'vai trò hoặc ngữ cảnh',
            'content' => 'nội dung đánh giá',
            'g-recaptcha-response' => 'reCAPTCHA',
            'rating_value' => 'điểm đánh giá',
            'tour_review_batch_id' => 'lượt đánh giá',
            'title' => 'tiêu đề đánh giá',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'author_email' => filled($this->input('author_email')) ? trim((string) $this->input('author_email')) : null,
            'author_name' => trim((string) $this->input('author_name')),
            'author_phone' => VietnamPhoneNumber::normalize($this->input('author_phone')),
            'author_title' => filled($this->input('author_title')) ? trim((string) $this->input('author_title')) : null,
            'content' => trim((string) $this->input('content')),
            'title' => filled($this->input('title')) ? trim((string) $this->input('title')) : null,
            'tour_review_batch_id' => filled($this->input('tour_review_batch_id')) ? (int) $this->input('tour_review_batch_id') : null,
        ]);
    }
}
