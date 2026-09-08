<?php

namespace App\Http\Requests;

use App\Services\Estimator\EstimateCatalogService;
use App\Services\Estimator\EstimatePayloadService;
use App\Support\EstimatePageContent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Src\Domains\Cms\Models\EstimateAccessKey;
use Src\Domains\Cms\Models\LandingPage;

class StoreEstimateRequestRequest extends FormRequest
{
    protected $errorBag = 'estimateRequest';
    protected ?array $estimateConfigCache = null;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('selected_estimator_level')) {
            return;
        }

        $this->merge([
            'selected_estimator_level' => EstimatePageContent::estimatorLevelForTier(
                $this->estimateConfig(),
                (string) $this->input('selected_tier_code'),
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'selected_tier_code' => ['required', 'string', Rule::in(EstimatePageContent::allowedTierCodes($this->estimateConfig()))],
            'selected_tier_name' => ['nullable', 'string', 'max:255'],
            'selected_estimator_level' => ['required', 'string', Rule::in(['level_1', 'level_2', 'level_3'])],
            'estimate_key' => ['nullable', 'string', 'max:64'],
            'input_payload' => ['required', 'string', 'max:40000'],
            'result_payload' => ['nullable', 'string', 'max:120000'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['required', 'email', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'project_location' => ['nullable', 'string', 'max:255'],
            'project_overview' => ['required', 'string', 'max:2000'],
            'page_url' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $estimateConfig = $this->estimateConfig();
            $selectedLevel = trim((string) $this->input('selected_estimator_level'))
                ?: EstimatePageContent::estimatorLevelForTier($estimateConfig, (string) $this->input('selected_tier_code'));

            $payloadService = app(EstimatePayloadService::class);
            $decodedPayload = $payloadService->decode($this->input('input_payload', '[]'));

            if (! is_array($decodedPayload)) {
                $validator->errors()->add('input_payload', 'Không thể đọc được thông số đầu vào dự toán.');

                return;
            }

            $flatPayload = collect($payloadService->flattenForValidation($decodedPayload))
                ->map(fn (mixed $value, string $slug) => ['slug' => $slug, 'value' => $value])
                ->values()
                ->all();

            foreach (EstimatePageContent::estimatorInputPayloadErrors($estimateConfig, $flatPayload, $selectedLevel) as $message) {
                $validator->errors()->add('input_payload', $message);
            }

            if ($payloadService->isGrouped($decodedPayload)) {
                $catalog = app(EstimateCatalogService::class)->uiPayload();
                $catalogItemCodes = collect(data_get($catalog, 'items', []))->pluck('code')->filter()->all();
                $roomTemplateCodes = collect(data_get($catalog, 'room_templates', []))->pluck('code')->filter()->all();
                $roomTypeCodes = collect(data_get($catalog, 'room_types', []))->pluck('code')->filter()->all();
                $technicalInputs = is_array($decodedPayload['technical_inputs'] ?? null) ? $decodedPayload['technical_inputs'] : [];

                if (filter_var($technicalInputs['has_pool'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                    $poolArea = $technicalInputs['pool_area'] ?? null;

                    if (! is_numeric($poolArea) || (float) $poolArea <= 0) {
                        $validator->errors()->add('input_payload', 'Khi có hồ bơi, vui lòng nhập diện tích hồ bơi lớn hơn 0.');
                    }
                }

                foreach (is_array($decodedPayload['room_program'] ?? null) ? $decodedPayload['room_program'] : [] as $index => $roomRow) {
                    $roomType = trim((string) data_get($roomRow, 'room_type'));
                    $template = trim((string) data_get($roomRow, 'template'));
                    $count = data_get($roomRow, 'count');

                    if ($roomType === '' || ! in_array($roomType, $roomTypeCodes, true)) {
                        $validator->errors()->add('input_payload', 'Có dòng công năng phòng chưa chọn đúng loại phòng.');
                    }

                    if ($template !== '' && ! in_array($template, $roomTemplateCodes, true)) {
                        $validator->errors()->add('input_payload', 'Có template phòng không còn hợp lệ trong catalog.');
                    }

                    if (! is_numeric($count) || (int) $count <= 0) {
                        $validator->errors()->add('input_payload', 'Số lượng phòng phải lớn hơn 0 ở mọi dòng công năng.');
                    }

                    foreach (is_array(data_get($roomRow, 'extra_items')) ? data_get($roomRow, 'extra_items') : [] as $extraCode) {
                        if (! in_array((string) $extraCode, $catalogItemCodes, true)) {
                            $validator->errors()->add('input_payload', 'Có item nội thất add thêm không còn hợp lệ.');
                            break;
                        }
                    }
                }

                foreach (['design_options', 'special_addons'] as $sectionKey) {
                    foreach (is_array($decodedPayload[$sectionKey] ?? null) ? $decodedPayload[$sectionKey] : [] as $code) {
                        if (! in_array((string) $code, $catalogItemCodes, true)) {
                            $validator->errors()->add('input_payload', 'Có lựa chọn báo giá thành phần không còn hợp lệ trong catalog.');
                            break;
                        }
                    }
                }
            }

            $resultPayload = trim((string) $this->input('result_payload', ''));

            if ($resultPayload !== '') {
                $decodedResult = json_decode($resultPayload, true);

                if (! is_array($decodedResult)) {
                    $validator->errors()->add('result_payload', 'Không thể đọc được kết quả dự toán đã tính.');
                }
            }

            $tier = EstimatePageContent::findTier($estimateConfig, (string) $this->input('selected_tier_code'));

            if (! $tier) {
                return;
            }

            if (! (bool) data_get($tier, 'requires_key', false)) {
                return;
            }

            $normalizedCode = Str::upper(trim((string) $this->input('estimate_key')));

            if ($normalizedCode === '') {
                $validator->errors()->add('estimate_key', 'Cấp dự toán này yêu cầu mã key.');

                return;
            }

            $accessKey = EstimateAccessKey::query()->where('code', $normalizedCode)->first();

            if (! $accessKey || ! $accessKey->is_active) {
                $validator->errors()->add('estimate_key', 'Mã key không hợp lệ hoặc đang tạm tắt.');

                return;
            }

            $tierCodes = collect($accessKey->tier_codes ?? [])
                ->map(fn ($item) => Str::slug((string) $item))
                ->filter()
                ->values()
                ->all();

            if ($tierCodes !== [] && ! in_array(Str::slug((string) data_get($tier, 'code')), $tierCodes, true)) {
                $validator->errors()->add('estimate_key', 'Mã key này không áp dụng cho cấp dự toán đã chọn.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'customer_name.required' => 'Vui lòng nhập họ tên.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại.',
            'customer_email.required' => 'Vui lòng nhập email.',
            'customer_email.email' => 'Email chưa đúng định dạng.',
            'project_overview.required' => 'Vui lòng mô tả ngắn nhu cầu công trình.',
            'selected_estimator_level.required' => 'Vui lòng chọn đúng cấp nhập liệu dự toán.',
            'selected_tier_code.required' => 'Vui lòng chọn một cấp dự toán.',
        ];
    }

    protected function estimateConfig(): array
    {
        return $this->estimateConfigCache ??= EstimatePageContent::prepareConfig(
            LandingPage::query()->where('page_key', 'estimate')->first()?->estimate_config,
        );
    }
}
