<?php

namespace App\Http\Requests;

use App\Services\Security\GoogleRecaptchaV3;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;
use Src\Domains\Cms\Enums\TravelInquirySource;

class StoreTravelInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source' => ['required', new Enum(TravelInquirySource::class)],
            'tour_id' => ['nullable', 'integer', 'exists:tours,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'inquiry_type' => ['required', Rule::in(['travel', 'customer_care', 'other'])],
            'context_title' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'adult_guest_count' => ['nullable', 'integer', 'min:0', 'max:999'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'expected_destination' => [
                Rule::requiredIf(fn (): bool => $this->input('submission_mode') === 'modal'
                    && filled($this->input('voucher_campaign_slug'))
                    && $this->boolean('voucher_detail_required')),
                'nullable',
                'string',
                'max:255',
            ],
            'expected_time' => [
                Rule::requiredIf(fn (): bool => $this->input('submission_mode') === 'modal'
                    && filled($this->input('voucher_campaign_slug'))
                    && $this->boolean('voucher_detail_required')),
                'nullable',
                'string',
                'max:255',
            ],
            'travel_date' => ['nullable', 'date'],
            'party_size' => ['nullable', 'integer', 'min:0', 'max:999'],
            'message' => ['nullable', 'string', 'max:5000'],
            'page_url' => ['nullable', 'url', 'max:2048'],
            'submission_mode' => ['nullable', Rule::in(['modal', 'compact', 'inline'])],
            'voucher_campaign_slug' => ['nullable', 'string', 'max:120'],
            'voucher_detail_required' => ['nullable', 'boolean'],
            'voucher_variant' => ['nullable', 'string', 'max:120'],
            'ab_variant' => ['nullable', 'string', 'max:120'],
            'g-recaptcha-response' => ['nullable', 'string', 'max:4096'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $message = app(GoogleRecaptchaV3::class)->validateRequest($this, 'travel_inquiry');

            if ($message) {
                $validator->errors()->add('g-recaptcha-response', $message);
            }
        });
    }

    public function messages(): array
    {
        return [
            'source.required' => 'Nguồn liên hệ không được để trống.',
            'source.enum' => 'Nguồn liên hệ không hợp lệ.',
            'tour_id.integer' => 'Tour được chọn không hợp lệ.',
            'tour_id.exists' => 'Tour được chọn không tồn tại trong hệ thống.',
            'service_id.integer' => 'Dịch vụ được chọn không hợp lệ.',
            'service_id.exists' => 'Dịch vụ được chọn không tồn tại trong hệ thống.',
            'inquiry_type.required' => 'Vui lòng chọn loại thông tin.',
            'inquiry_type.in' => 'Loại thông tin không hợp lệ.',
            'context_title.string' => 'Ngữ cảnh liên hệ không hợp lệ.',
            'context_title.max' => 'Ngữ cảnh liên hệ không được vượt quá :max ký tự.',
            'subject.required' => 'Vui lòng nhập tiêu đề.',
            'subject.string' => 'Tiêu đề không hợp lệ.',
            'subject.max' => 'Tiêu đề không được vượt quá :max ký tự.',
            'customer_name.required' => 'Vui lòng nhập họ tên.',
            'customer_name.string' => 'Họ tên không hợp lệ.',
            'customer_name.max' => 'Họ tên không được vượt quá :max ký tự.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại.',
            'customer_phone.string' => 'Số điện thoại không hợp lệ.',
            'customer_phone.max' => 'Số điện thoại không được vượt quá :max ký tự.',
            'customer_email.email' => 'Email chưa đúng định dạng.',
            'customer_email.max' => 'Email không được vượt quá :max ký tự.',
            'adult_guest_count.integer' => 'Số khách người lớn phải là số nguyên.',
            'adult_guest_count.min' => 'Số khách người lớn phải lớn hơn hoặc bằng :min.',
            'adult_guest_count.max' => 'Số khách người lớn không được lớn hơn :max.',
            'company_name.string' => 'Tên công ty không hợp lệ.',
            'company_name.max' => 'Tên công ty không được vượt quá :max ký tự.',
            'address.string' => 'Địa chỉ không hợp lệ.',
            'address.max' => 'Địa chỉ không được vượt quá :max ký tự.',
            'expected_destination.required' => 'Vui lòng nhập điểm đến dự kiến.',
            'expected_destination.string' => 'Điểm đến dự kiến không hợp lệ.',
            'expected_destination.max' => 'Điểm đến dự kiến không được vượt quá :max ký tự.',
            'expected_time.required' => 'Vui lòng nhập thời gian đi dự kiến.',
            'expected_time.string' => 'Thời gian đi dự kiến không hợp lệ.',
            'expected_time.max' => 'Thời gian đi dự kiến không được vượt quá :max ký tự.',
            'travel_date.date' => 'Ngày đi chưa đúng định dạng.',
            'party_size.integer' => 'Số trẻ em phải là số nguyên.',
            'party_size.min' => 'Số trẻ em phải lớn hơn hoặc bằng :min.',
            'party_size.max' => 'Số trẻ em không được lớn hơn :max.',
            'message.string' => 'Nội dung không hợp lệ.',
            'message.max' => 'Nội dung không được vượt quá :max ký tự.',
            'page_url.url' => 'Liên kết trang chưa đúng định dạng.',
            'page_url.max' => 'Liên kết trang không được vượt quá :max ký tự.',
            'submission_mode.in' => 'Chế độ gửi không hợp lệ.',
            'voucher_campaign_slug.max' => 'Mã chiến dịch voucher không được vượt quá :max ký tự.',
            'voucher_detail_required.boolean' => 'Cấu hình form voucher không hợp lệ.',
            'voucher_variant.max' => 'Biến thể voucher không được vượt quá :max ký tự.',
            'ab_variant.max' => 'Biến thể voucher không được vượt quá :max ký tự.',
            'g-recaptcha-response.max' => 'Mã xác minh reCAPTCHA không hợp lệ.',
        ];
    }

    public function attributes(): array
    {
        return [
            'source' => 'nguồn liên hệ',
            'tour_id' => 'tour',
            'service_id' => 'dịch vụ',
            'inquiry_type' => 'loại thông tin',
            'context_title' => 'ngữ cảnh liên hệ',
            'subject' => 'tiêu đề',
            'customer_name' => 'họ tên',
            'customer_phone' => 'số điện thoại',
            'customer_email' => 'email',
            'adult_guest_count' => 'số khách người lớn',
            'company_name' => 'tên công ty',
            'address' => 'địa chỉ',
            'expected_destination' => 'điểm đến dự kiến',
            'expected_time' => 'thời gian đi dự kiến',
            'travel_date' => 'ngày đi',
            'party_size' => 'số trẻ em',
            'message' => 'nội dung',
            'page_url' => 'liên kết trang',
            'submission_mode' => 'chế độ gửi',
            'voucher_campaign_slug' => 'chiến dịch voucher',
            'voucher_detail_required' => 'cấu hình form voucher',
            'voucher_variant' => 'biến thể voucher',
            'ab_variant' => 'biến thể voucher',
            'g-recaptcha-response' => 'reCAPTCHA',
        ];
    }

    protected function prepareForValidation(): void
    {
        $voucherVariant = filled($this->input('voucher_variant'))
            ? $this->input('voucher_variant')
            : $this->input('ab_variant');

        $this->merge([
            'adult_guest_count' => filled($this->input('adult_guest_count'))
                ? trim((string) $this->input('adult_guest_count'))
                : null,
            'address' => filled($this->input('address'))
                ? trim((string) $this->input('address'))
                : null,
            'company_name' => filled($this->input('company_name'))
                ? trim((string) $this->input('company_name'))
                : null,
            'context_title' => filled($this->input('context_title'))
                ? trim((string) $this->input('context_title'))
                : null,
            'customer_email' => filled($this->input('customer_email'))
                ? trim((string) $this->input('customer_email'))
                : null,
            'customer_name' => trim((string) $this->input('customer_name')),
            'customer_phone' => trim((string) $this->input('customer_phone')),
            'expected_destination' => filled($this->input('expected_destination'))
                ? trim((string) $this->input('expected_destination'))
                : null,
            'expected_time' => filled($this->input('expected_time'))
                ? trim((string) $this->input('expected_time'))
                : null,
            'inquiry_type' => $this->input('inquiry_type', 'travel'),
            'message' => filled($this->input('message'))
                ? trim((string) $this->input('message'))
                : null,
            'page_url' => filled($this->input('page_url'))
                ? trim((string) $this->input('page_url'))
                : url()->current(),
            'party_size' => filled($this->input('party_size'))
                ? trim((string) $this->input('party_size'))
                : null,
            'source' => $this->input('source', TravelInquirySource::General->value),
            'submission_mode' => filled($this->input('submission_mode'))
                ? trim((string) $this->input('submission_mode'))
                : null,
            'subject' => trim((string) $this->input('subject')),
            'voucher_campaign_slug' => filled($this->input('voucher_campaign_slug'))
                ? trim((string) $this->input('voucher_campaign_slug'))
                : null,
            'voucher_detail_required' => $this->boolean('voucher_detail_required'),
            'voucher_variant' => filled($voucherVariant)
                ? trim((string) $voucherVariant)
                : null,
            'ab_variant' => filled($this->input('ab_variant'))
                ? trim((string) $this->input('ab_variant'))
                : null,
        ]);
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'adult_guest_count' => filled($this->input('adult_guest_count'))
                ? $this->integer('adult_guest_count')
                : null,
            'message' => $this->input('message') ?: null,
            'party_size' => filled($this->input('party_size'))
                ? $this->integer('party_size')
                : null,
        ]);
    }
}
