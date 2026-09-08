<?php

namespace App\Support;

use App\Services\Estimator\EstimateCatalogService;
use Illuminate\Support\Str;

class EstimatePageContent
{
    public static function defaultFaqItems(): array
    {
        return [
            [
                'question' => 'Tôi nên bắt đầu dùng trang dự toán từ bước nào?',
                'answer' => 'Bắt đầu bằng việc chọn cấp dự toán phù hợp ở đầu trang, sau đó nhập các thông số công trình bắt buộc trong khối Estimate Builder để hệ thống tính diện tích quy đổi và gợi ý mức đầu tư.',
            ],
            [
                'question' => 'Trước khi nhập liệu tôi cần chuẩn bị những thông tin gì?',
                'answer' => 'Bạn nên chuẩn bị loại công trình, kích thước cơ bản, số tầng hoặc quy mô sử dụng, mục tiêu đầu tư và những yêu cầu đặc thù về vật liệu, tiến độ hoặc vận hành để kết quả bám sát nhu cầu hơn.',
            ],
            [
                'question' => 'Khi nào tôi nên chọn cấp dự toán có yêu cầu key?',
                'answer' => 'Hãy chọn cấp có key khi bạn cần đầu ra chuyên sâu hơn để so sánh phương án, trình duyệt nội bộ hoặc phục vụ vòng rà soát kỹ thuật thay vì chỉ cần một khung đầu tư sơ bộ.',
            ],
            [
                'question' => 'Sau khi nhập xong hệ thống sẽ trả về những gì?',
                'answer' => 'Trang sẽ hiển thị bảng diện tích quy đổi, bảng giá theo gói đang chọn, tóm tắt input quan trọng và phần mô tả đầu ra để bạn kiểm tra lại trước khi mở popup gửi yêu cầu dự toán.',
            ],
            [
                'question' => 'Các bước gửi yêu cầu dự toán hoàn chỉnh diễn ra như thế nào?',
                'answer' => 'Bước 1 là chọn đúng cấp dự toán, bước 2 là nhập đủ thông số, bước 3 là rà lại breakdown và bảng giá, bước 4 là mở popup xác nhận thông tin liên hệ, và bước 5 là gửi yêu cầu để hệ thống lưu và chuyển tới bộ phận phụ trách.',
            ],
        ];
    }

    public static function estimatorFieldReference(): array
    {
        return collect(self::estimatorFieldMeta())
            ->mapWithKeys(function (array $meta, string $fieldKey) {
                return [$fieldKey => array_merge($meta, [
                    'guide_title' => self::defaultEstimatorGuideTitle($fieldKey, $meta),
                    'guide_content' => self::defaultEstimatorGuideContent($fieldKey, $meta),
                ])];
            })
            ->all();
    }

    public static function defaultConfig(): array
    {
        return [
            'hero_slider' => [
                'eyebrow' => 'LANDING DỰ TOÁN',
                'title' => 'Hero slider trang dự toán',
                'description' => 'Quản lý slide đầu trang để giới thiệu cách dùng bảng dự toán, phạm vi áp dụng và CTA chính ngay khi khách hàng vừa vào trang.',
                'slides' => [
                    self::heroSlide(
                        eyebrow: 'DỰ TOÁN NHÀ PHỐ - BIỆT THỰ',
                        title: 'Nhập đúng <strong>thông số công trình</strong> để hệ thống tính ngay diện tích quy đổi',
                        description: 'Phù hợp khi cần xem nhanh hình học công trình, breakdown diện tích và khung kết quả theo 3 cấp dự toán.',
                        primaryLabel: 'Bắt đầu nhập thông số',
                        primaryUrl: '#estimate-builder',
                        secondaryLabel: 'Xem bảng dự toán',
                        secondaryUrl: '#estimate-results',
                        textEffect: 'animate__fadeInUp',
                    ),
                    self::heroSlide(
                        eyebrow: 'CẤU HÌNH THEO TỪNG LEVEL',
                        title: 'Chọn đúng <strong>cấp nhập liệu</strong> trước khi gửi yêu cầu dự toán',
                        description: 'Level 1 cho báo giá nhanh, Level 2 mở rộng điều kiện thương mại, Level 3 phục vụ dự toán kỹ thuật và có thể yêu cầu key.',
                        primaryLabel: 'Chọn level phù hợp',
                        primaryUrl: '#estimate-builder',
                        secondaryLabel: 'Mở hướng dẫn',
                        secondaryUrl: '#estimate-builder',
                        textEffect: 'animate__fadeInLeft',
                    ),
                ],
            ],
            'highlights' => [
                'eyebrow' => 'LANDING DỰ TOÁN',
                'title' => 'Nhập thông số công trình và chọn đúng cấp dự toán trước khi gửi yêu cầu',
                'description' => 'Trang này giúp khách hàng chuẩn hóa đầu vào, chọn mức độ chi tiết mong muốn và gửi brief chính xác hơn cho đội ngũ phụ trách.',
                'items' => [
                    self::highlightItem('03', 'Cấp dự toán'),
                    self::highlightItem('01', 'Popup xác nhận thông tin'),
                    self::highlightItem('Email + Collection', 'Kênh nhận kết quả'),
                ],
            ],
            'inputs' => [
                'eyebrow' => 'THÔNG SỐ ĐẦU VÀO',
                'title' => 'Những dữ liệu cần có trước khi xin dự toán',
                'description' => 'Bạn có thể cấu hình lại danh sách thông số trong CMS để phù hợp từng chiến dịch landing page.',
                'fields' => [
                    self::inputField(
                        slug: 'project_type',
                        label: 'Loại công trình',
                        type: 'select',
                        placeholder: 'Chọn loại công trình',
                        help: 'Ví dụ: nhà phố, biệt thự, văn phòng, nhà xưởng.',
                        optionsText: "Nhà phố\nBiệt thự\nVăn phòng\nNhà xưởng\nShowroom",
                    ),
                    self::inputField(
                        slug: 'project_scale',
                        label: 'Quy mô diện tích',
                        type: 'number',
                        placeholder: 'Ví dụ 250',
                        help: 'Nhập diện tích dự kiến tính theo m2.',
                    ),
                    self::inputField(
                        slug: 'handover_target',
                        label: 'Mốc bàn giao mong muốn',
                        type: 'text',
                        placeholder: 'Ví dụ Quý 4/2026',
                        help: 'Cho biết mốc thời gian bạn muốn chốt kế hoạch triển khai.',
                    ),
                    self::inputField(
                        slug: 'investment_goal',
                        label: 'Mục tiêu đầu tư',
                        type: 'select',
                        placeholder: 'Chọn mục tiêu đầu tư',
                        help: 'Giúp đội ngũ chọn đúng cách bóc tách và mức độ chi tiết.',
                        optionsText: "Sơ bộ để ra quyết định nhanh\nCần bóc khối lượng để so sánh phương án\nCần bộ dự toán trình ban lãnh đạo",
                    ),
                    self::inputField(
                        slug: 'special_requirements',
                        label: 'Yêu cầu đặc thù',
                        type: 'textarea',
                        placeholder: 'Ví dụ yêu cầu về vật liệu, pháp lý, tiến độ, tiêu chuẩn vận hành.',
                        help: 'Thông tin này giúp đội ngũ hiểu rõ hơn những điểm cần lưu ý.',
                        required: false,
                    ),
                ],
            ],
            'tiers' => [
                'eyebrow' => '03 CẤP DỰ TOÁN',
                'title' => 'Chọn mức độ chi tiết đúng với nhu cầu phê duyệt',
                'description' => 'Hai cấp cao hơn có thể yêu cầu mã key do bộ phận quản trị cấp phát.',
                'items' => [
                    self::tierItem(
                        code: 'co-ban',
                        estimatorLevel: 'level_1',
                        name: 'Dự toán cơ bản',
                        badge: 'Mở',
                        description: 'Phù hợp khi cần khung đầu tư sơ bộ và định hướng bước tiếp theo.',
                        requiresKey: false,
                        keyLabel: 'Không yêu cầu key',
                        keyHelp: 'Gói này không cần mã kích hoạt.',
                        resultTitle: 'Bộ kết quả sơ bộ theo thông số bạn vừa nhập',
                        resultSummary: 'Nhận khung phạm vi, mức độ ưu tiên và gợi ý cách chuẩn bị brief cho bước tiếp theo.',
                        resultBulletsText: "Tóm tắt nhu cầu từ form đầu vào\nGợi ý hướng triển khai phù hợp\nDanh sách dữ liệu cần bổ sung để chốt phương án",
                        deliveryText: 'Kết quả được lưu vào collection và gửi email cho bộ phận phụ trách.',
                        buttonLabel: 'Yêu cầu dự toán cơ bản',
                    ),
                    self::tierItem(
                        code: 'chuyen-sau',
                        estimatorLevel: 'level_2',
                        name: 'Dự toán nâng cao',
                        badge: 'Cần key',
                        description: 'Phù hợp khi cần bóc tách kỹ hơn để so sánh phương án và xem cấu phần theo công năng.',
                        requiresKey: true,
                        keyLabel: 'Mã key dự toán nâng cao',
                        keyHelp: 'Bạn vẫn có thể nhập liệu và xem preview công khai; key chỉ cần ở bước gửi yêu cầu chính thức.',
                        resultTitle: 'Bộ kết quả nâng cao theo công năng, nội thất và hạng mục đặc biệt',
                        resultSummary: 'Bao gồm diện tích quy đổi, cây thành phần nhà, preset phòng, add-on nội thất và tóm tắt bảng giá đang áp dụng.',
                        resultBulletsText: "Breakdown diện tích quy đổi theo công thức nền\nCấu phần nhà theo taxonomy kỹ thuật\nTóm tắt nội thất từng phòng và add-on đã chọn",
                        deliveryText: 'Yêu cầu sẽ được lưu nội bộ và chuyển tới email của bộ phận thẩm định.',
                        buttonLabel: 'Yêu cầu dự toán nâng cao',
                    ),
                    self::tierItem(
                        code: 'cao-cap',
                        estimatorLevel: 'level_3',
                        name: 'Dự toán nội bộ',
                        badge: 'Cần key',
                        description: 'Phù hợp cho bóc tách kỹ thuật - thương mại nội bộ với control về bảng giá, chiết khấu và phụ thu.',
                        requiresKey: true,
                        keyLabel: 'Mã key dự toán nội bộ',
                        keyHelp: 'Mã key do bộ phận quản lý cấp và chỉ cần khi gửi phiếu nội bộ chính thức.',
                        resultTitle: 'Bộ kết quả nội bộ với control kỹ thuật và thương mại',
                        resultSummary: 'Bao gồm breakdown cấu phần, preset phòng, bảng giá nội bộ, chiết khấu, phụ thu và VAT theo kịch bản đang chọn.',
                        resultBulletsText: "Breakdown component tree và room program\nBảng giá nội bộ theo price book đang áp dụng\nTác động của discount, surcharge và VAT",
                        deliveryText: 'Kết quả sẽ được lưu collection, gắn nhãn cấp cao và chuyển email cho nhóm phụ trách.',
                        buttonLabel: 'Yêu cầu dự toán nội bộ',
                    ),
                ],
            ],
            'result' => [
                'eyebrow' => 'KẾT QUẢ',
                'title' => 'Nội dung phản hồi sẽ thay đổi theo cấp dự toán bạn chọn',
                'description' => 'Khối này chỉ hiển thị text preview để khách hàng hiểu mình sẽ nhận được gì sau khi gửi yêu cầu.',
                'empty_state_title' => 'Chọn một cấp dự toán để xem trước kết quả',
                'empty_state_description' => 'Hệ thống sẽ hiển thị phần mô tả đầu ra tương ứng với cấp dự toán đang được chọn.',
            ],
            'popup' => [
                'eyebrow' => 'XÁC NHẬN THÔNG TIN',
                'title' => 'Điền thông tin chính xác để gửi yêu cầu dự toán',
                'description' => 'Popup này dùng để xác nhận thông tin khách hàng và chuyển đúng brief tới email hoặc collection nội bộ.',
                'button_label' => 'Gửi yêu cầu dự toán',
                'success_message' => 'Yêu cầu dự toán đã được ghi nhận. Đội ngũ sẽ phản hồi sớm nhất có thể.',
                'privacy_note' => 'Thông tin được lưu vào hệ thống nội bộ và chỉ dùng cho mục đích xử lý yêu cầu dự toán.',
            ],
            'delivery' => [
                'send_email' => true,
                'send_customer_email' => true,
                'store_collection' => true,
                'notice' => 'Yêu cầu sẽ được lưu vào collection nội bộ, gửi email cho bộ phận phụ trách và đồng thời gửi phiếu tiếp nhận cho khách hàng nếu hệ thống đã bật các kênh này.',
            ],
        ];
    }

    public static function allowedTierCodes(array $config): array
    {
        return collect(data_get($config, 'tiers.items', []))
            ->pluck('code')
            ->filter(fn ($code) => trim((string) $code) !== '')
            ->values()
            ->all();
    }

    public static function findTier(array $config, ?string $code): ?array
    {
        $code = trim((string) $code);

        if ($code === '') {
            return null;
        }

        foreach (data_get($config, 'tiers.items', []) as $tier) {
            if ((string) data_get($tier, 'code') === $code) {
                return $tier;
            }
        }

        return null;
    }

    public static function inputPayloadErrors(array $config, mixed $payload): array
    {
        $payloadMap = self::payloadMap($payload);
        $errors = [];

        foreach (data_get($config, 'inputs.fields', []) as $field) {
            $slug = (string) data_get($field, 'slug');
            $label = (string) data_get($field, 'label', $slug);
            $value = trim((string) ($payloadMap[$slug] ?? ''));
            $type = (string) data_get($field, 'type', 'text');
            $required = (bool) data_get($field, 'required', true);

            if ($required && $value === '') {
                $errors[] = 'Vui lòng nhập '.$label.'.';

                continue;
            }

            if ($type === 'select' && $value !== '') {
                $allowed = collect(data_get($field, 'options', []))
                    ->pluck('value')
                    ->filter(fn ($option) => trim((string) $option) !== '')
                    ->values()
                    ->all();

                if ($allowed !== [] && ! in_array($value, $allowed, true)) {
                    $errors[] = 'Giá trị đã chọn cho '.$label.' không hợp lệ.';
                }
            }
        }

        return $errors;
    }

    public static function normalizeConfig(array $config): array
    {
        return self::prepareConfig($config);
    }

    public static function normalizeSubmittedInputs(array $config, mixed $payload): array
    {
        $payloadMap = self::payloadMap($payload);

        return collect(data_get($config, 'inputs.fields', []))
            ->map(function (array $field) use ($payloadMap) {
                $slug = (string) data_get($field, 'slug');
                $rawValue = trim((string) ($payloadMap[$slug] ?? ''));
                $displayValue = $rawValue;

                if ((string) data_get($field, 'type') === 'select') {
                    $matched = collect(data_get($field, 'options', []))
                        ->first(function (array $option) use ($rawValue) {
                            return (string) data_get($option, 'value') === $rawValue;
                        });

                    $displayValue = $matched
                        ? (string) data_get($matched, 'label', $rawValue)
                        : $rawValue;
                }

                return [
                    'label' => (string) data_get($field, 'label', $slug),
                    'slug' => $slug,
                    'type' => (string) data_get($field, 'type', 'text'),
                    'value' => $rawValue,
                    'display_value' => $displayValue,
                ];
            })
            ->filter(fn (array $field) => $field['value'] !== '')
            ->values()
            ->all();
    }

    public static function estimatorInputPayloadErrors(array $config, mixed $payload, string $level): array
    {
        $ui = self::estimatorUi($config);
        $payloadMap = self::payloadMapDetailed($payload);
        $visibleFields = collect(self::estimatorVisibleFieldKeys($level))
            ->mapWithKeys(fn (string $key) => [$key => data_get($ui, "fields.{$key}")])
            ->filter()
            ->all();
        $errors = [];

        foreach ($visibleFields as $key => $field) {
            $label = (string) data_get($field, 'label', $key);
            $type = (string) data_get($field, 'type', 'text');
            $value = $payloadMap[$key] ?? null;
            $requiredLevels = collect(data_get($field, 'required_levels', []))
                ->map(fn ($item) => (string) $item)
                ->all();

            if (in_array($level, $requiredLevels, true) && self::estimatorValueEmpty($value, $type)) {
                $errors[] = 'Vui lòng nhập '.$label.'.';

                continue;
            }

            if (self::estimatorValueEmpty($value, $type)) {
                continue;
            }

            if (in_array($type, ['number', 'integer'], true) && ! is_numeric($value)) {
                $errors[] = $label.' phải là số hợp lệ.';

                continue;
            }

            if ($type === 'select') {
                $allowed = collect(data_get($field, 'options', []))
                    ->pluck('value')
                    ->map(fn ($item) => (string) $item)
                    ->all();

                if ($allowed !== [] && ! in_array((string) $value, $allowed, true)) {
                    $errors[] = 'Giá trị đã chọn cho '.$label.' không hợp lệ.';
                }

                continue;
            }

            if ($type === 'multiselect') {
                $selectedValues = is_array($value) ? $value : [];
                $allowed = collect(data_get($field, 'options', []))
                    ->pluck('value')
                    ->map(fn ($item) => (string) $item)
                    ->all();

                foreach ($selectedValues as $selectedValue) {
                    if ($allowed !== [] && ! in_array((string) $selectedValue, $allowed, true)) {
                        $errors[] = 'Giá trị đã chọn cho '.$label.' không hợp lệ.';
                        break;
                    }
                }
            }
        }

        $hasTerrace = filter_var($payloadMap['has_terrace'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $length = is_numeric($payloadMap['length'] ?? null) ? (float) $payloadMap['length'] : null;
        $width = is_numeric($payloadMap['width'] ?? null) ? (float) $payloadMap['width'] : null;
        $tumArea = is_numeric($payloadMap['tum_area'] ?? null) ? (float) $payloadMap['tum_area'] : null;
        $terraceArea = is_numeric($payloadMap['terrace_area'] ?? null) ? (float) $payloadMap['terrace_area'] : null;

        if ($hasTerrace && $length !== null && $width !== null && $tumArea !== null && $terraceArea !== null) {
            $terraceRooftopArea = round(($length * $width * 1.25), 2);

            if (abs(($tumArea + $terraceArea) - $terraceRooftopArea) > 0.01) {
                $errors[] = 'Tổng diện tích tum và sân thượng phải bằng diện tích sân thượng quy đổi (125% diện tích sàn xây dựng).';
            }
        }

        return array_values(array_unique($errors));
    }

    public static function estimatorLevelForTier(array $config, ?string $tierCode): string
    {
        $tierCode = trim((string) $tierCode);
        $tierItems = collect(data_get($config, 'tiers.items', []))->values();
        $matchedTier = $tierItems->first(fn (array $tier) => (string) data_get($tier, 'code') === $tierCode);

        if (is_array($matchedTier) && filled(data_get($matchedTier, 'estimator_level'))) {
            return (string) data_get($matchedTier, 'estimator_level');
        }

        $fallbackLevels = ['level_1', 'level_2', 'level_3'];
        $index = $tierItems->search(fn (array $tier) => (string) data_get($tier, 'code') === $tierCode);

        return $fallbackLevels[(int) $index] ?? 'level_1';
    }

    public static function estimatorUi(array $config): array
    {
        $fieldCatalog = self::loadEstimatorJson('configs/field-catalog.json');
        $levelCatalog = self::loadEstimatorJson('configs/estimation-levels.json');
        $pricing = self::loadEstimatorJson('configs/default-pricing.json');
        $formula = self::loadEstimatorJson('examples/formula.json');
        $catalog = app(EstimateCatalogService::class)->uiPayload();
        $fieldMeta = self::estimatorFieldReference();
        $levels = data_get($levelCatalog, 'levels', []);
        $levelCodes = array_keys($levels);
        $allFieldKeys = collect($levels)
            ->flatMap(function (array $definition, string $levelCode) use ($levels) {
                return self::estimatorVisibleFieldKeys($levelCode, $levels);
            })
            ->merge(array_keys($fieldMeta))
            ->unique()
            ->values();

        $fields = $allFieldKeys
            ->mapWithKeys(function (string $fieldKey) use ($fieldCatalog, $fieldMeta, $levels, $levelCodes) {
                $catalog = data_get($fieldCatalog, "fields.{$fieldKey}", []);
                $meta = $fieldMeta[$fieldKey] ?? [];
                $requiredLevels = collect(data_get($catalog, 'required_levels', []))
                    ->map(fn ($item) => (string) $item)
                    ->values()
                    ->all();
                $visibleLevels = collect($levelCodes)
                    ->filter(fn (string $levelCode) => in_array($fieldKey, self::estimatorVisibleFieldKeys($levelCode, $levels), true))
                    ->values()
                    ->all();

                return [$fieldKey => [
                    'key' => $fieldKey,
                    'label' => (string) data_get($meta, 'label', self::humanizeEstimatorKey($fieldKey)),
                    'type' => (string) data_get($catalog, 'type', data_get($meta, 'type', 'text')),
                    'unit' => (string) data_get($meta, 'unit', ''),
                    'placeholder' => (string) data_get($meta, 'placeholder', ''),
                    'help' => (string) data_get($meta, 'help', ''),
                    'step' => data_get($meta, 'step'),
                    'min' => data_get($meta, 'min'),
                    'max' => data_get($meta, 'max'),
                    'options' => data_get($meta, 'options', []),
                    'required_levels' => $requiredLevels,
                    'visible_levels' => $visibleLevels,
                    'column_span' => (int) data_get($meta, 'column_span', 1),
                    'illustration_label' => (string) data_get($meta, 'illustration_label', ''),
                    'depends_on' => data_get($meta, 'depends_on'),
                    'guide_title' => (string) self::catalogValue($catalog, 'guide_title', data_get($meta, 'guide_title', '')),
                    'guide_content' => (string) self::catalogValue($catalog, 'guide_content', data_get($meta, 'guide_content', '')),
                ]];
            })
            ->all();

        $tierItems = collect(data_get($config, 'tiers.items', []))->values();
        $resolvedLevels = collect($levels)
            ->map(function (array $definition, string $levelCode) use ($tierItems, $levels) {
                $tierIndex = collect(['level_1', 'level_2', 'level_3'])->search($levelCode);
                $tier = is_array($tierItems->get((int) $tierIndex)) ? $tierItems->get((int) $tierIndex) : [];

                return [
                    'code' => $levelCode,
                    'name' => (string) data_get($definition, 'name', Str::headline($levelCode)),
                    'purpose' => (string) data_get($definition, 'purpose', ''),
                    'field_keys' => self::estimatorVisibleFieldKeys($levelCode, $levels),
                    'tier_code' => (string) data_get($tier, 'code', $levelCode),
                    'tier_name' => (string) data_get($tier, 'name', data_get($definition, 'name', Str::headline($levelCode))),
                    'tier_description' => (string) data_get($tier, 'description', ''),
                    'requires_key' => (bool) data_get($tier, 'requires_key', false),
                    'badge' => (string) data_get($tier, 'badge', ''),
                    'button_label' => (string) data_get($tier, 'button_label', ''),
                ];
            })
            ->values()
            ->all();

        $groups = collect(data_get($fieldCatalog, 'groups', []))
            ->map(function (array $group) use ($fields) {
                $fieldKeys = collect(data_get($group, 'fields', []))
                    ->filter(fn (string $fieldKey) => array_key_exists($fieldKey, $fields))
                    ->values()
                    ->all();

                return [
                    'key' => (string) data_get($group, 'key'),
                    'label' => (string) data_get($group, 'label'),
                    'fields' => $fieldKeys,
                ];
            })
            ->filter(fn (array $group) => $group['fields'] !== [])
            ->values()
            ->all();

        return [
            'formula' => $formula,
            'pricing' => $pricing,
            'catalog' => data_get($catalog, 'tree', []),
            'catalog_items' => data_get($catalog, 'items', []),
            'catalog_version' => data_get($catalog, 'catalog_version', 'seed-v1'),
            'room_templates' => data_get($catalog, 'room_templates', []),
            'room_types' => data_get($catalog, 'room_types', []),
            'room_template_version' => data_get($catalog, 'room_template_version', 'seed-v1'),
            'price_books' => data_get($catalog, 'price_books', []),
            'dynamic_dependencies' => self::estimatorDynamicDependencies($catalog),
            'levels' => $resolvedLevels,
            'groups' => $groups,
            'fields' => $fields,
        ];
    }

    public static function estimatorVisibleFieldKeys(string $levelCode, ?array $levels = null): array
    {
        $levels = $levels ?: data_get(self::loadEstimatorJson('configs/estimation-levels.json'), 'levels', []);
        $definition = data_get($levels, $levelCode, []);
        $extends = (string) data_get($definition, 'extends', '');
        $fieldKeys = [];

        if ($extends !== '') {
            $fieldKeys = array_merge($fieldKeys, self::estimatorVisibleFieldKeys($extends, $levels));
        }

        $fieldKeys = array_merge($fieldKeys, collect(data_get($definition, 'fields', []))
            ->map(fn ($item) => (string) $item)
            ->all());

        return array_values(array_unique(array_filter($fieldKeys)));
    }

    public static function normalizeEstimatorSubmittedInputs(array $config, mixed $payload, string $level): array
    {
        $ui = self::estimatorUi($config);
        $payloadMap = self::payloadMapDetailed($payload);

        return collect(self::estimatorVisibleFieldKeys($level))
            ->map(function (string $fieldKey) use ($payloadMap, $ui) {
                $field = data_get($ui, "fields.{$fieldKey}");

                if (! is_array($field)) {
                    return null;
                }

                $rawValue = $payloadMap[$fieldKey] ?? null;

                if (self::estimatorValueEmpty($rawValue, (string) data_get($field, 'type', 'text'))) {
                    return null;
                }

                return [
                    'label' => (string) data_get($field, 'label', $fieldKey),
                    'slug' => $fieldKey,
                    'type' => (string) data_get($field, 'type', 'text'),
                    'value' => $rawValue,
                    'display_value' => self::estimatorDisplayValue($field, $rawValue),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function prepareConfig(?array $config): array
    {
        $defaults = self::defaultConfig();

        if (! is_array($config) || $config === []) {
            return $defaults;
        }

        $preparedFields = collect(is_array(data_get($config, 'inputs.fields')) ? array_values(data_get($config, 'inputs.fields')) : [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                $options = self::prepareOptions(
                    data_get($item, 'options'),
                    data_get($item, 'options_text'),
                );

                return [
                    'uuid' => self::uuidValue(data_get($item, 'uuid')),
                    'slug' => self::slugValue(data_get($item, 'slug'), data_get($item, 'label')),
                    'label' => self::stringValue(data_get($item, 'label')),
                    'type' => self::fieldType(data_get($item, 'type')),
                    'placeholder' => self::stringValue(data_get($item, 'placeholder')),
                    'help' => self::stringValue(data_get($item, 'help')),
                    'required' => self::booleanValue(data_get($item, 'required'), true),
                    'options' => $options,
                    'options_text' => self::optionsToText($options),
                ];
            })
            ->values()
            ->all();

        if ($preparedFields === []) {
            $preparedFields = data_get($defaults, 'inputs.fields', []);
        }

        $defaultTiers = collect(data_get($defaults, 'tiers.items', []))->values();
        $configuredTiers = collect(is_array(data_get($config, 'tiers.items')) ? array_values(data_get($config, 'tiers.items')) : [])
            ->filter(fn ($item) => is_array($item))
            ->values();

        $preparedTiers = $defaultTiers
            ->map(function (array $defaultTier, int $index) use ($configuredTiers) {
                $item = is_array($configuredTiers->get($index)) ? $configuredTiers->get($index) : [];
                $bullets = self::prepareLines(
                    data_get($item, 'result_bullets'),
                    data_get($item, 'result_bullets_text'),
                );

                if ($bullets === []) {
                    $bullets = data_get($defaultTier, 'result_bullets', []);
                }

                return [
                    'uuid' => self::uuidValue(data_get($item, 'uuid', data_get($defaultTier, 'uuid'))),
                    'code' => self::slugValue(data_get($item, 'code', data_get($defaultTier, 'code')), data_get($defaultTier, 'code')),
                    'estimator_level' => self::stringValue(data_get($item, 'estimator_level', data_get($defaultTier, 'estimator_level'))),
                    'name' => self::stringValue(data_get($item, 'name', data_get($defaultTier, 'name'))),
                    'badge' => self::stringValue(data_get($item, 'badge', data_get($defaultTier, 'badge'))),
                    'description' => self::stringValue(data_get($item, 'description', data_get($defaultTier, 'description'))),
                    'requires_key' => self::booleanValue(data_get($item, 'requires_key'), (bool) data_get($defaultTier, 'requires_key')),
                    'key_label' => self::stringValue(data_get($item, 'key_label', data_get($defaultTier, 'key_label'))),
                    'key_help' => self::stringValue(data_get($item, 'key_help', data_get($defaultTier, 'key_help'))),
                    'result_title' => self::stringValue(data_get($item, 'result_title', data_get($defaultTier, 'result_title'))),
                    'result_summary' => self::stringValue(data_get($item, 'result_summary', data_get($defaultTier, 'result_summary'))),
                    'result_bullets' => $bullets,
                    'result_bullets_text' => self::linesToText($bullets),
                    'delivery_text' => self::stringValue(data_get($item, 'delivery_text', data_get($defaultTier, 'delivery_text'))),
                    'button_label' => self::stringValue(data_get($item, 'button_label', data_get($defaultTier, 'button_label'))),
                ];
            })
            ->values()
            ->all();

        $defaultHeroSlides = collect(data_get($defaults, 'hero_slider.slides', []))->values();
        $configuredHeroSlides = collect(is_array(data_get($config, 'hero_slider.slides')) ? array_values(data_get($config, 'hero_slider.slides')) : [])
            ->filter(fn ($item) => is_array($item))
            ->values();

        $preparedHeroSlides = ($configuredHeroSlides->isNotEmpty() ? $configuredHeroSlides : $defaultHeroSlides)
            ->map(function (array $item, int $index) use ($defaultHeroSlides) {
                $fallback = is_array($defaultHeroSlides->get($index)) ? $defaultHeroSlides->get($index) : self::heroSlide();

                return [
                    'uuid' => self::uuidValue(data_get($item, 'uuid', data_get($fallback, 'uuid'))),
                    'eyebrow' => self::stringValue(data_get($item, 'eyebrow', data_get($fallback, 'eyebrow'))),
                    'title' => RichText::sanitizeInline(data_get($item, 'title', data_get($fallback, 'title'))),
                    'description' => RichText::sanitizeInline(data_get($item, 'description', data_get($fallback, 'description'))),
                    'primary_label' => self::stringValue(data_get($item, 'primary_label', data_get($fallback, 'primary_label'))),
                    'primary_url' => self::stringValue(data_get($item, 'primary_url', data_get($fallback, 'primary_url'))),
                    'secondary_label' => self::stringValue(data_get($item, 'secondary_label', data_get($fallback, 'secondary_label'))),
                    'secondary_url' => self::stringValue(data_get($item, 'secondary_url', data_get($fallback, 'secondary_url'))),
                    'image_alt' => self::stringValue(data_get($item, 'image_alt', data_get($fallback, 'image_alt'))),
                    'text_effect' => self::heroEffectValue(data_get($item, 'text_effect', data_get($fallback, 'text_effect'))),
                ];
            })
            ->values()
            ->all();

        return [
            'hero_slider' => [
                'eyebrow' => self::stringValue(data_get($config, 'hero_slider.eyebrow', data_get($defaults, 'hero_slider.eyebrow'))),
                'title' => self::stringValue(data_get($config, 'hero_slider.title', data_get($defaults, 'hero_slider.title'))),
                'description' => self::stringValue(data_get($config, 'hero_slider.description', data_get($defaults, 'hero_slider.description'))),
                'slides' => $preparedHeroSlides,
            ],
            'highlights' => [
                'eyebrow' => self::stringValue(data_get($config, 'highlights.eyebrow', data_get($defaults, 'highlights.eyebrow'))),
                'title' => self::stringValue(data_get($config, 'highlights.title', data_get($defaults, 'highlights.title'))),
                'description' => self::stringValue(data_get($config, 'highlights.description', data_get($defaults, 'highlights.description'))),
                'items' => collect(is_array(data_get($config, 'highlights.items')) ? array_values(data_get($config, 'highlights.items')) : data_get($defaults, 'highlights.items', []))
                    ->filter(fn ($item) => is_array($item))
                    ->map(fn (array $item) => [
                        'uuid' => self::uuidValue(data_get($item, 'uuid')),
                        'value' => self::stringValue(data_get($item, 'value')),
                        'label' => self::stringValue(data_get($item, 'label')),
                    ])
                    ->values()
                    ->all(),
            ],
            'inputs' => [
                'eyebrow' => self::stringValue(data_get($config, 'inputs.eyebrow', data_get($defaults, 'inputs.eyebrow'))),
                'title' => self::stringValue(data_get($config, 'inputs.title', data_get($defaults, 'inputs.title'))),
                'description' => self::stringValue(data_get($config, 'inputs.description', data_get($defaults, 'inputs.description'))),
                'fields' => $preparedFields,
            ],
            'tiers' => [
                'eyebrow' => self::stringValue(data_get($config, 'tiers.eyebrow', data_get($defaults, 'tiers.eyebrow'))),
                'title' => self::stringValue(data_get($config, 'tiers.title', data_get($defaults, 'tiers.title'))),
                'description' => self::stringValue(data_get($config, 'tiers.description', data_get($defaults, 'tiers.description'))),
                'items' => $preparedTiers,
            ],
            'result' => [
                'eyebrow' => self::stringValue(data_get($config, 'result.eyebrow', data_get($defaults, 'result.eyebrow'))),
                'title' => self::stringValue(data_get($config, 'result.title', data_get($defaults, 'result.title'))),
                'description' => self::stringValue(data_get($config, 'result.description', data_get($defaults, 'result.description'))),
                'empty_state_title' => self::stringValue(data_get($config, 'result.empty_state_title', data_get($defaults, 'result.empty_state_title'))),
                'empty_state_description' => self::stringValue(data_get($config, 'result.empty_state_description', data_get($defaults, 'result.empty_state_description'))),
            ],
            'popup' => [
                'eyebrow' => self::stringValue(data_get($config, 'popup.eyebrow', data_get($defaults, 'popup.eyebrow'))),
                'title' => self::stringValue(data_get($config, 'popup.title', data_get($defaults, 'popup.title'))),
                'description' => self::stringValue(data_get($config, 'popup.description', data_get($defaults, 'popup.description'))),
                'button_label' => self::stringValue(data_get($config, 'popup.button_label', data_get($defaults, 'popup.button_label'))),
                'success_message' => self::stringValue(data_get($config, 'popup.success_message', data_get($defaults, 'popup.success_message'))),
                'privacy_note' => self::stringValue(data_get($config, 'popup.privacy_note', data_get($defaults, 'popup.privacy_note'))),
            ],
            'delivery' => [
                'send_email' => self::booleanValue(data_get($config, 'delivery.send_email'), (bool) data_get($defaults, 'delivery.send_email', true)),
                'send_customer_email' => self::booleanValue(data_get($config, 'delivery.send_customer_email'), (bool) data_get($defaults, 'delivery.send_customer_email', true)),
                'store_collection' => self::booleanValue(data_get($config, 'delivery.store_collection'), (bool) data_get($defaults, 'delivery.store_collection', true)),
                'notice' => self::stringValue(data_get($config, 'delivery.notice', data_get($defaults, 'delivery.notice'))),
            ],
        ];
    }

    protected static function booleanValue(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    protected static function fieldType(mixed $value): string
    {
        $value = trim((string) $value);

        return in_array($value, ['number', 'select', 'text', 'textarea'], true)
            ? $value
            : 'text';
    }

    public static function heroEffectOptions(): array
    {
        return [
            ['label' => 'Fade up', 'value' => 'animate__fadeInUp'],
            ['label' => 'Fade down', 'value' => 'animate__fadeInDown'],
            ['label' => 'Fade', 'value' => 'animate__fadeIn'],
            ['label' => 'Zoom in', 'value' => 'animate__zoomIn'],
            ['label' => 'Slide left', 'value' => 'animate__fadeInLeft'],
            ['label' => 'Slide right', 'value' => 'animate__fadeInRight'],
        ];
    }

    public static function heroSlideCollection(string $uuid): string
    {
        return 'estimate-hero-slide-'.Str::slug($uuid);
    }

    protected static function estimatorDisplayValue(array $field, mixed $value): string
    {
        $type = (string) data_get($field, 'type', 'text');
        $unit = trim((string) data_get($field, 'unit', ''));

        if ($type === 'boolean') {
            return filter_var($value, FILTER_VALIDATE_BOOL) ? 'Có' : 'Không';
        }

        if ($type === 'select') {
            $matched = collect(data_get($field, 'options', []))
                ->first(fn (array $option) => (string) data_get($option, 'value') === (string) $value);

            return $matched
                ? (string) data_get($matched, 'label', $value)
                : trim((string) $value);
        }

        if ($type === 'multiselect') {
            $values = is_array($value) ? $value : [];

            return collect(data_get($field, 'options', []))
                ->filter(fn (array $option) => in_array((string) data_get($option, 'value'), array_map('strval', $values), true))
                ->pluck('label')
                ->filter()
                ->join(', ');
        }

        if (is_array($value)) {
            return implode(', ', $value);
        }

        $resolved = trim((string) $value);

        if ($resolved === '' || $unit === '') {
            return $resolved;
        }

        return $resolved.' '.$unit;
    }

    protected static function estimatorFieldMeta(): array
    {
        return [
            'length' => ['label' => 'Chiều dài', 'unit' => 'm', 'placeholder' => 'Ví dụ 5', 'help' => 'Đơn vị mét (m).', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'width' => ['label' => 'Chiều rộng', 'unit' => 'm', 'placeholder' => 'Ví dụ 12', 'help' => 'Đơn vị mét (m).', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'floors' => ['label' => 'Số tầng (bao gồm tầng trệt)', 'unit' => 'tầng', 'placeholder' => 'Ví dụ 4', 'help' => 'Tính cả tầng trệt trong tổng số tầng.', 'step' => 1, 'min' => 1, 'column_span' => 2],
            'has_terrace' => ['label' => 'Có sân thượng', 'type' => 'boolean', 'help' => 'Khi chọn Có, hệ thống sẽ hiển thị diện tích tum và sân thượng, đồng thời tự điền theo diện tích sân thượng quy đổi bằng 125% diện tích sàn.', 'options' => self::booleanOptions(), 'column_span' => 1],
            'garden_type' => ['label' => 'Loại sân vườn / sân ngoài trời', 'help' => 'Chọn đúng loại sân để hệ số quy đổi bám theo tài liệu.', 'options' => self::optionList(['Không có' => 'none', 'Sân thông thường' => 'standard_yard', 'Sân BTCT có móng, đà kiềng' => 'btct_yard']), 'column_span' => 1],
            'garden_area' => ['label' => 'Diện tích sân vườn', 'unit' => 'm2', 'placeholder' => 'Ví dụ 20', 'help' => 'Bỏ trống nếu lấy theo diện tích footprint.', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'front_yard_area' => ['label' => 'Diện tích sân trước', 'unit' => 'm2', 'placeholder' => 'Ví dụ 12', 'help' => 'Sân trước sẽ tính hệ số 50% theo business rules mặc định.', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'back_yard_area' => ['label' => 'Diện tích sân sau', 'unit' => 'm2', 'placeholder' => 'Ví dụ 10', 'help' => 'Sân sau sẽ tính hệ số 50% theo business rules mặc định.', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'has_mezzanine' => ['label' => 'Có lửng', 'type' => 'boolean', 'options' => self::booleanOptions(), 'column_span' => 1],
            'mezzanine_area' => ['label' => 'Diện tích lửng', 'unit' => 'm2', 'placeholder' => 'Ví dụ 36', 'step' => 0.1, 'min' => 0, 'column_span' => 1, 'depends_on' => ['field' => 'has_mezzanine', 'value' => true]],
            'void_area' => ['label' => 'Diện tích thông tầng lửng', 'unit' => 'm2', 'placeholder' => 'Ví dụ 12', 'help' => 'Theo rule đơn giản, phần thông tầng sẽ dùng hệ số 50% khi > 8m2.', 'step' => 0.1, 'min' => 0, 'column_span' => 1, 'depends_on' => ['field' => 'has_mezzanine', 'value' => true]],
            'tum_area' => ['label' => 'Diện tích tum', 'unit' => 'm2', 'placeholder' => 'Ví dụ 20', 'help' => 'Chỉ hiển thị khi bật Có sân thượng. Giá trị mặc định được điền theo cụm sân thượng quy đổi nhưng có thể chỉnh tay.', 'step' => 0.1, 'min' => 0, 'column_span' => 1, 'depends_on' => ['field' => 'has_terrace', 'value' => true]],
            'terrace_area' => ['label' => 'Diện tích sân thượng', 'unit' => 'm2', 'placeholder' => 'Ví dụ 30', 'help' => 'Chỉ hiển thị khi bật Có sân thượng. Tổng diện tích tum và sân thượng phải bằng diện tích sân thượng quy đổi (125% diện tích sàn).', 'step' => 0.1, 'min' => 0, 'column_span' => 1, 'depends_on' => ['field' => 'has_terrace', 'value' => true]],
            'terrace_cover_type' => ['label' => 'Sân thượng có mái che', 'options' => self::optionList(['Không mái che' => 'uncovered', 'Có mái che' => 'covered']), 'column_span' => 1],
            'balcony_area' => ['label' => 'Diện tích ban công', 'unit' => 'm2', 'placeholder' => 'Ví dụ 12', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'balcony_open_sides' => ['label' => 'Số mặt thoáng ban công', 'options' => self::optionList(['1 mặt thoáng' => '1', '2 mặt thoáng' => '2', '3 mặt thoáng' => '3']), 'column_span' => 1],
            'roof_area' => ['label' => 'Diện tích mái riêng', 'unit' => 'm2', 'placeholder' => 'Ví dụ 75', 'help' => 'Nếu bỏ trống, hệ thống sẽ fallback theo footprint và hệ số dốc mái.', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'roof_slope_factor' => ['label' => 'Hệ số dốc mái', 'placeholder' => 'Ví dụ 1.1', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'roof_count' => ['label' => 'Số mái', 'unit' => 'mái', 'placeholder' => 'Ví dụ 2', 'step' => 1, 'min' => 1, 'column_span' => 1],
            'roof_type' => ['label' => 'Loại mái', 'options' => self::optionList(['Mái bằng BTCT không lát gạch' => 'btct_no_tile', 'Mái bằng BTCT có lát gạch' => 'btct_tile', 'Mái tôn' => 'metal_sheet', 'Mái ngói kèo sắt hộp mạ kẽm' => 'tile_steel_frame', 'Mái ngói BTCT 1-2 mái' => 'tile_btct_1_2', 'Mái ngói BTCT từ 3 mái / dốc lớn' => 'tile_btct_3_plus']), 'column_span' => 1],
            'foundation_type' => ['label' => 'Loại móng', 'options' => self::optionList(['Móng băng 1 phương / móng cọc' => 'strip_or_pile', 'Móng băng 2 phương' => 'two_way_strip', 'Móng bè' => 'raft', 'Móng đơn' => 'isolated']), 'column_span' => 1],
            'has_concrete_ground' => ['label' => 'Có đổ bê tông nền trệt', 'type' => 'boolean', 'options' => self::booleanOptions(), 'column_span' => 1],
            'has_basement' => ['label' => 'Có tầng hầm', 'type' => 'boolean', 'options' => self::booleanOptions(), 'column_span' => 1],
            'basement_area' => ['label' => 'Diện tích hầm', 'unit' => 'm2', 'placeholder' => 'Ví dụ 60', 'step' => 0.1, 'min' => 0, 'column_span' => 1, 'depends_on' => ['field' => 'has_basement', 'value' => true]],
            'basement_type' => ['label' => 'Chiều sâu hầm', 'options' => self::optionList(['Hầm sâu 1.5m - 2m' => 'depth_1_5_2', 'Hầm sâu 2m - 2.5m' => 'depth_2_2_5', 'Hầm sâu 2.5m - dưới 3m' => 'depth_2_5_3']), 'column_span' => 1, 'depends_on' => ['field' => 'has_basement', 'value' => true]],
            'building_type' => ['label' => 'Loại công trình', 'options' => self::optionList(['Nhà phố' => 'townhouse', 'Biệt thự' => 'villa', 'Văn phòng' => 'office', 'Shophouse' => 'shophouse']), 'column_span' => 1],
            'usage_type' => ['label' => 'Công năng chính', 'options' => self::optionList(['Nhà ở gia đình' => 'residential', 'Cho thuê / căn hộ dịch vụ' => 'rental', 'Kinh doanh / shophouse' => 'commercial', 'Văn phòng làm việc' => 'office']), 'column_span' => 1],
            'bedroom_count' => ['label' => 'Số phòng ngủ', 'unit' => 'phòng', 'placeholder' => 'Ví dụ 4', 'step' => 1, 'min' => 0, 'column_span' => 1],
            'wc_count' => ['label' => 'Số WC', 'unit' => 'phòng', 'placeholder' => 'Ví dụ 5', 'step' => 1, 'min' => 0, 'column_span' => 1],
            'special_structure' => ['label' => 'Kết cấu / công năng đặc biệt', 'options' => self::optionList(['Không có' => 'none', 'Thông tầng lớn' => 'large_void', 'Console / ban công lớn' => 'large_cantilever', 'Không gian thông rộng' => 'wide_open_space']), 'column_span' => 1],
            'finish_package' => ['label' => 'Gói hoàn thiện', 'options' => self::optionList(['Tiêu chuẩn' => 'standard', 'Tiêu chuẩn +' => 'standard_plus', 'Cao cấp' => 'premium']), 'column_span' => 1],
            'floor_finish_type' => ['label' => 'Hoàn thiện sàn', 'options' => self::optionList(['Gạch ceramic / porcelain cơ bản' => 'tile_basic', 'Đá / gạch cao cấp' => 'tile_premium', 'Sàn gỗ / sàn kỹ thuật' => 'wood']), 'column_span' => 1],
            'door_type' => ['label' => 'Loại cửa chính', 'options' => self::optionList(['Nhôm kính' => 'aluminum_glass', 'Gỗ công nghiệp' => 'engineered_wood', 'Gỗ tự nhiên' => 'natural_wood', 'Thép chống cháy / cửa kỹ thuật' => 'technical_steel']), 'column_span' => 1],
            'sanitary_package' => ['label' => 'Thiết bị vệ sinh', 'options' => self::optionList(['Cơ bản' => 'basic', 'Khá' => 'standard', 'Cao cấp' => 'premium']), 'column_span' => 1],
            'electrical_package' => ['label' => 'Thiết bị điện', 'options' => self::optionList(['Cơ bản' => 'basic', 'Khá' => 'standard', 'Cao cấp / thông minh' => 'premium']), 'column_span' => 1],
            'facade_finish_type' => ['label' => 'Mức độ hoàn thiện mặt tiền', 'options' => self::optionList(['Đơn giản' => 'simple', 'Khá' => 'standard', 'Phức tạp / nhiều lớp vật liệu' => 'complex']), 'column_span' => 1],
            'location_zone' => ['label' => 'Khu vực xây dựng', 'options' => self::optionList(['Nội thành TP.HCM' => 'hcm_inner', 'Ngoại thành TP.HCM' => 'hcm_outer', 'Tỉnh lân cận' => 'nearby_province']), 'column_span' => 1],
            'district_zone' => ['label' => 'Khu vực quận / huyện', 'options' => self::optionList(['Trung tâm' => 'central', 'Cận trung tâm' => 'inner_ring', 'Ngoại vi' => 'outer_ring']), 'column_span' => 1],
            'access_condition' => ['label' => 'Điều kiện tiếp cận thi công', 'options' => self::optionList(['Mặt tiền rộng' => 'frontage', 'Hẻm nhỏ' => 'small_alley', 'Hẻm hẹp / khó tiếp cận' => 'narrow_alley']), 'column_span' => 1],
            'material_staging_condition' => ['label' => 'Điều kiện tập kết vật tư', 'options' => self::optionList(['Thuận lợi' => 'easy', 'Hạn chế' => 'limited', 'Rất khó / phải trung chuyển' => 'difficult']), 'column_span' => 1],
            'adjacent_house_condition' => ['label' => 'Nhà liền kề xung quanh', 'options' => self::optionList(['Thoáng 2 bên' => 'open', 'Một bên giáp nhà' => 'semi_attached', 'Hai bên sát nhà hiện hữu' => 'attached']), 'column_span' => 1],
            'soil_type' => ['label' => 'Loại đất nền', 'options' => self::optionList(['Đất tốt' => 'good', 'Đất trung bình' => 'medium', 'Đất yếu' => 'weak']), 'column_span' => 1],
            'has_elevator' => ['label' => 'Có thang máy', 'type' => 'boolean', 'options' => self::booleanOptions(), 'column_span' => 1],
            'has_pool' => ['label' => 'Có hồ bơi', 'type' => 'boolean', 'options' => self::booleanOptions(), 'column_span' => 1],
            'elevator_service_floors' => ['label' => 'Số tầng phục vụ của thang máy', 'unit' => 'tầng', 'placeholder' => 'Mặc định theo tổng số tầng', 'step' => 1, 'min' => 1, 'column_span' => 1, 'depends_on' => ['field' => 'has_elevator', 'value' => true]],
            'pool_area' => ['label' => 'Diện tích hồ bơi', 'unit' => 'm2', 'placeholder' => 'Ví dụ 28', 'step' => 0.1, 'min' => 0, 'column_span' => 1, 'depends_on' => ['field' => 'has_pool', 'value' => true]],
            'pool_type' => ['label' => 'Loại hồ bơi', 'options' => self::optionList(['Hồ skimmer tiêu chuẩn' => 'skimmer', 'Hồ overflow' => 'overflow', 'Jacuzzi / plunge pool' => 'jacuzzi']), 'column_span' => 1, 'depends_on' => ['field' => 'has_pool', 'value' => true]],
            'soil_class' => ['label' => 'Phân lớp địa chất', 'options' => self::optionList(['Lớp A' => 'class_a', 'Lớp B' => 'class_b', 'Lớp C' => 'class_c']), 'column_span' => 1],
            'groundwater_level' => ['label' => 'Mực nước ngầm', 'options' => self::optionList(['Thấp' => 'low', 'Trung bình' => 'medium', 'Cao' => 'high']), 'column_span' => 1],
            'pile_type' => ['label' => 'Loại cọc', 'options' => self::optionList(['Cọc ép' => 'precast', 'Cọc khoan nhồi' => 'bored', 'Cọc vít' => 'screw']), 'column_span' => 1],
            'pile_depth' => ['label' => 'Độ sâu cọc', 'unit' => 'm', 'placeholder' => 'Ví dụ 18', 'help' => 'Đơn vị mét.', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'pile_count' => ['label' => 'Số lượng cọc', 'unit' => 'cọc', 'placeholder' => 'Ví dụ 24', 'step' => 1, 'min' => 0, 'column_span' => 1],
            'retaining_wall' => ['label' => 'Có tường vây / retaining wall', 'type' => 'boolean', 'options' => self::booleanOptions(), 'column_span' => 1],
            'floor_height' => ['label' => 'Chiều cao tầng điển hình', 'unit' => 'm', 'placeholder' => 'Ví dụ 3.6', 'help' => 'Đơn vị mét.', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'long_span_structure' => ['label' => 'Có kết cấu nhịp lớn', 'type' => 'boolean', 'options' => self::booleanOptions(), 'column_span' => 1],
            'contract_scope' => ['label' => 'Phạm vi hợp đồng', 'type' => 'multiselect', 'options' => self::optionList(['Thiết kế' => 'design', 'Thi công phần thô' => 'shell', 'Thi công hoàn thiện' => 'finishing', 'Nội thất' => 'interior']), 'column_span' => 2],
            'include_permit' => ['label' => 'Bao gồm xin phép xây dựng', 'type' => 'boolean', 'options' => self::booleanOptions(), 'column_span' => 1],
            'include_pile' => ['label' => 'Bao gồm phần cọc', 'type' => 'boolean', 'options' => self::booleanOptions(), 'column_span' => 1],
            'include_completion' => ['label' => 'Bao gồm hoàn công', 'type' => 'boolean', 'options' => self::booleanOptions(), 'column_span' => 1],
            'vat_mode' => ['label' => 'VAT', 'options' => self::optionList(['Chưa bao gồm VAT' => 'exclude', 'Đã bao gồm VAT' => 'include']), 'column_span' => 1],
            'discount_percent' => ['label' => 'Chiết khấu', 'unit' => '%', 'placeholder' => 'Ví dụ 3', 'help' => 'Đơn vị phần trăm (%).', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'surcharge_percent' => ['label' => 'Phụ thu', 'unit' => '%', 'placeholder' => 'Ví dụ 5', 'help' => 'Đơn vị phần trăm (%).', 'step' => 0.1, 'min' => 0, 'column_span' => 1],
            'internal_notes' => ['label' => 'Ghi chú nội bộ', 'type' => 'textarea', 'placeholder' => 'Ghi chú thương mại hoặc kỹ thuật nội bộ', 'column_span' => 2],
            'price_set' => ['label' => 'Bảng giá áp dụng', 'options' => self::optionList(['Bảng giá nâng cao 2026' => 'advanced-2026-v1', 'Bảng giá nội bộ 2026' => 'internal-2026-v1']), 'column_span' => 1],
            'formula_set' => ['label' => 'Bộ công thức áp dụng', 'options' => self::optionList(['Mặc định' => 'default']), 'column_span' => 1],
        ];
    }

    protected static function estimatorDynamicDependencies(array $catalog): array
    {
        return [
            'fields' => [
                'elevator_service_floors' => ['field' => 'has_elevator', 'value' => true, 'default_from' => 'floors'],
                'pool_area' => ['field' => 'has_pool', 'value' => true],
                'pool_type' => ['field' => 'has_pool', 'value' => true],
            ],
            'sections' => [
                'room_program' => [
                    'label' => 'Công năng & nội thất',
                    'visible_levels' => ['level_2', 'level_3'],
                    'room_types' => data_get($catalog, 'room_types', []),
                ],
                'design_options' => [
                    'label' => 'Lựa chọn thiết kế',
                    'visible_levels' => ['level_2', 'level_3'],
                ],
                'special_addons' => [
                    'label' => 'Add-on đặc biệt',
                    'visible_levels' => ['level_2', 'level_3'],
                ],
                'internal_controls' => [
                    'label' => 'Kiểm soát nội bộ',
                    'visible_levels' => ['level_3'],
                ],
            ],
        ];
    }

    protected static function defaultEstimatorGuideTitle(string $fieldKey, array $meta): string
    {
        $explicit = trim((string) data_get($meta, 'guide_title', ''));

        if ($explicit !== '') {
            return $explicit;
        }

        return (string) data_get($meta, 'label', self::humanizeEstimatorKey($fieldKey));
    }

    protected static function defaultEstimatorGuideContent(string $fieldKey, array $meta): string
    {
        $explicit = trim((string) data_get($meta, 'guide_content', ''));

        if ($explicit !== '') {
            return $explicit;
        }

        $override = self::estimatorGuideOverride($fieldKey);

        if ($override !== null) {
            return $override;
        }

        $help = trim((string) data_get($meta, 'help', ''));
        $label = (string) data_get($meta, 'label', self::humanizeEstimatorKey($fieldKey));
        $type = (string) data_get($meta, 'type', 'text');
        $unit = trim((string) data_get($meta, 'unit', ''));
        $placeholder = trim((string) data_get($meta, 'placeholder', ''));
        $options = collect(data_get($meta, 'options', []))
            ->pluck('label')
            ->map(fn (mixed $option) => trim((string) $option))
            ->filter()
            ->values()
            ->all();
        $segments = [];

        if ($help !== '') {
            $segments[] = $help;
        }

        if ($type === 'boolean') {
            $segments[] = 'Chọn "Có" hoặc "Không" theo đúng hiện trạng hoặc nhu cầu của công trình.';
        } elseif (in_array($type, ['number', 'integer'], true)) {
            $segments[] = $unit !== ''
                ? 'Nhập giá trị cho '.$label.' theo đơn vị '.$unit.' để hệ thống tính đúng.'
                : 'Nhập giá trị số phù hợp với hồ sơ công trình.';
        } elseif ($options !== []) {
            $segments[] = 'Chọn phương án phù hợp nhất với thực tế thi công hoặc nhu cầu sử dụng.';
        }

        if ($placeholder !== '') {
            $segments[] = 'Ví dụ: '.$placeholder.'.';
        }

        if ($options !== []) {
            $segments[] = 'Các lựa chọn thường dùng: '.implode(', ', $options).'.';
        }

        return trim(implode(' ', array_values(array_unique(array_filter($segments)))));
    }

    public static function heroSlide(
        ?string $eyebrow = '',
        ?string $title = '',
        ?string $description = '',
        ?string $primaryLabel = 'Bắt đầu nhập thông số',
        ?string $primaryUrl = '#estimate-builder',
        ?string $secondaryLabel = 'Xem bảng dự toán',
        ?string $secondaryUrl = '#estimate-results',
        ?string $imageAlt = '',
        ?string $textEffect = 'animate__fadeInUp',
    ): array {
        return [
            'uuid' => (string) Str::uuid(),
            'eyebrow' => self::stringValue($eyebrow),
            'title' => RichText::sanitizeInline($title),
            'description' => RichText::sanitizeInline($description),
            'primary_label' => self::stringValue($primaryLabel),
            'primary_url' => self::stringValue($primaryUrl),
            'secondary_label' => self::stringValue($secondaryLabel),
            'secondary_url' => self::stringValue($secondaryUrl),
            'image_alt' => self::stringValue($imageAlt),
            'text_effect' => self::heroEffectValue($textEffect),
        ];
    }

    protected static function estimatorGuideOverride(string $fieldKey): ?string
    {
        $guides = [
            'length' => "Đây là chiều dài phủ bì của công trình trên một sàn điển hình. Anh/chị nên lấy theo kích thước xây dựng thực tế từ mép tường ngoài đến mép tường ngoài, không lấy theo kích thước lọt lòng bên trong.\n\nNếu nhà có hình dạng không vuông vức hoặc bị tóp hậu, hãy nhập kích thước đại diện gần đúng nhất rồi ghi rõ thêm trong phần mô tả nhu cầu để đội ngũ kiểm tra lại khi bóc tách.",
            'width' => "Đây là chiều ngang phủ bì của công trình. Trường này quyết định trực tiếp diện tích footprint nên chỉ cần lệch vài chục centimet là tổng dự toán có thể thay đổi khá rõ.\n\nTrong thực tế thi công, anh/chị nên ưu tiên kích thước theo hồ sơ xin phép hoặc mặt bằng sơ bộ mới nhất.",
            'floors' => "Nhập tổng số tầng có sàn sử dụng, tính cả tầng trệt. Nếu công trình có lửng, tum, sân thượng hay hầm thì các phần đó khai báo ở trường riêng để hệ số tính chính xác hơn.\n\nNếu chưa chốt phương án cuối, anh/chị cứ nhập phương án đang nghiêng về nhất để hệ thống ra khung ngân sách tham khảo.",
            'has_terrace' => "Chọn mục này khi phương án có sân thượng. Khi bật “Có sân thượng”, hệ thống sẽ hiển thị hai ô diện tích tum và diện tích sân thượng rồi tự điền giá trị mặc định theo diện tích sân thượng quy đổi bằng 125% diện tích sàn.\n\nAnh/chị vẫn có thể chỉnh tay hai giá trị này, nhưng tổng diện tích tum và sân thượng luôn phải bằng diện tích sân thượng quy đổi để tránh sai lệch khối lượng.",
            'garden_type' => "Trường này giúp phân biệt sân ngoài trời là sân hoàn thiện thông thường hay sân có kết cấu bê tông cốt thép, móng và đà kiềng. Hai phương án này chênh nhau khá nhiều về khối lượng kết cấu.\n\nNếu sân chỉ lát gạch hoặc hoàn thiện nhẹ, thường chọn sân thông thường. Nếu bên dưới có móng, dầm hoặc đổ bê tông chịu lực, nên chọn phương án BTCT.",
            'garden_area' => "Đây là phần diện tích sân cần tính riêng ngoài footprint nhà chính. Khi sân được thi công thực tế nhưng không cùng kết cấu với nhà, nên tách riêng để chi phí phản ánh đúng hơn.\n\nNếu anh/chị chưa rõ, có thể để trống và bổ sung sau. Hệ thống sẽ ưu tiên lấy phần dữ liệu chắc chắn trước.",
            'front_yard_area' => "Đây là diện tích sân trước nhà, thường dùng cho khoảng lùi, chỗ để xe hoặc tiểu cảnh. Tách riêng sân trước giúp bóc chi phí sát thực tế hơn thay vì cộng gộp vào diện tích nhà.\n\nAnh/chị nên nhập phần diện tích thực sự thi công hoàn thiện, không tính phần vỉa hè hay đất ngoài ranh xây dựng.",
            'back_yard_area' => "Đây là diện tích sân sau hoặc khoảng trống phía cuối nhà cần thi công nền, lát hoặc hoàn thiện. Với nhà phố có chừa sân sau lấy sáng, phần này nên tách riêng để hệ số không bị đội lên như sàn nhà chính.\n\nNếu công trình không có sân sau, có thể để trống.",
            'has_mezzanine' => "Chọn mục này khi công trình có tầng lửng thực tế trong phương án xây dựng. Lửng thường làm tăng khối lượng sàn, cột, dầm, cầu thang và hoàn thiện nên ảnh hưởng trực tiếp đến dự toán.\n\nNếu mới đang cân nhắc giữa phương án có lửng và không lửng, anh/chị nên thử cả hai để so sánh chênh lệch ngân sách.",
            'mezzanine_area' => "Đây là diện tích sàn lửng thực tế, không nhất thiết bằng toàn bộ diện tích tầng trệt. Phần lửng càng lớn thì chi phí phần thô, hoàn thiện và hệ thống kỹ thuật càng tăng rõ.\n\nAnh/chị nên nhập theo diện tích sàn sử dụng dự kiến của lửng, không cộng phần thông tầng bị chừa rỗng.",
            'void_area' => "Đây là phần diện tích chừa thông tầng trong khu vực lửng hoặc khu vực sinh hoạt cao tầng. Về thực tế, phần này không làm sàn nhưng vẫn ảnh hưởng đến giải pháp kiến trúc, lan can, mặt dựng và cảm giác không gian.\n\nNếu chưa chốt chính xác, anh/chị chỉ cần nhập diện tích ước tính phần rỗng để hệ thống áp hệ số phù hợp.",
            'tum_area' => "Tum là phần khối xây nhô lên trên mái để che thang, phòng kỹ thuật hoặc kho nhỏ. Dù diện tích không lớn, tum vẫn là một hạng mục kết cấu và hoàn thiện riêng nên nên khai báo tách biệt.\n\nTrường này chỉ hiển thị khi bật “Có sân thượng”. Hệ thống sẽ điền mặc định theo công thức, nhưng anh/chị có thể chỉnh tay nếu muốn phân lại tỷ trọng tum trong tổng diện tích sân thượng quy đổi.",
            'terrace_area' => "Đây là diện tích sân thượng sử dụng thực tế. Sân thượng có thể chỉ hoàn thiện chống thấm, lát gạch đơn giản hoặc kết hợp mái che, tiểu cảnh, khu giặt phơi nên mức chi phí có thể khác nhau khá nhiều.\n\nTrường này chỉ hiển thị khi bật “Có sân thượng”. Anh/chị có thể chỉnh tay diện tích, nhưng tổng diện tích sân thượng và tum phải bằng diện tích sân thượng quy đổi 125% sàn để hệ thống tính đúng.",
            'terrace_cover_type' => "Trường này cho biết sân thượng có làm mái che hay không. Có mái che thường phát sinh thêm cột, kèo, mái và hoàn thiện liên quan nên ngân sách sẽ cao hơn sân thượng để trống.\n\nNgay cả khi bật “Có sân thượng” để hệ thống tự tách tum và sân thượng, anh/chị vẫn nên chọn đúng tình trạng mái che vì phần mái che và tum đều bám theo tổng diện tích sân thượng quy đổi.",
            'balcony_area' => "Đây là tổng diện tích ban công nhô ra ngoài mặt đứng công trình. Ban công ảnh hưởng đến sàn console, lan can, chống thấm và hoàn thiện mặt ngoài nên nên tính riêng.\n\nNếu mỗi tầng có ban công khác nhau, anh/chị có thể nhập diện tích trung bình hoặc tổng diện tích dự kiến ở giai đoạn đầu.",
            'balcony_open_sides' => "Số mặt thoáng của ban công ảnh hưởng đến mức độ hoàn thiện lan can, mặt ngoài và đôi khi cả giải pháp chống thấm, thoát nước. Ban công càng mở nhiều mặt thì phần hoàn thiện và chi tiết kỹ thuật càng tăng.\n\nNếu chưa chắc, hãy chọn phương án giống bố cục mặt tiền hiện tại nhất.",
            'roof_area' => "Đây là diện tích mái khi anh/chị muốn tính riêng thay vì để hệ thống tự suy ra từ footprint. Trường này nên dùng khi mái có đua lớn, chồng mái hoặc hình dạng đặc biệt khiến diện tích mái thực lớn hơn sàn bên dưới.\n\nNếu chưa có số chính xác, anh/chị có thể để trống để hệ thống lấy theo footprint và hệ số dốc mái.",
            'roof_slope_factor' => "Hệ số dốc mái dùng khi mái nghiêng hoặc mái có nhiều mặt dốc, giúp quy đổi từ diện tích chiếu bằng sang diện tích thi công thực tế. Mái càng dốc hoặc càng nhiều lớp mái, hệ số này thường càng cao.\n\nNếu anh/chị chưa có thiết kế mái chi tiết, cứ giữ mức gần đúng để có khung đầu tư sơ bộ trước.",
            'roof_count' => "Số mái phản ánh số lớp hoặc số phần mái cần thi công. Với mái phức tạp nhiều cấp, nhiều khối giao nhau, chi phí thường không chỉ tăng theo diện tích mà còn tăng theo độ phức tạp thi công.\n\nNhà mái đơn giản thường chỉ cần 1; nhà chồng mái hoặc phân khối rõ rệt mới nên tăng thêm.",
            'roof_type' => "Loại mái là một trong những trường ảnh hưởng mạnh đến hệ số quy đổi. Mái bằng BTCT, mái tôn hay mái ngói đều có cấu tạo, vật tư và khối lượng nhân công rất khác nhau.\n\nNếu chưa chốt vật liệu cuối cùng, anh/chị nên chọn phương án đang nghiêng về nhất để hệ thống ra dự toán gần sát thực tế.",
            'foundation_type' => "Loại móng quyết định phần lớn chi phí nền móng của công trình. Móng đơn, móng băng, móng bè hay móng cọc có mức độ an toàn và chi phí khác nhau tùy tải trọng công trình và điều kiện đất.\n\nNếu chưa có hồ sơ khảo sát địa chất, anh/chị có thể chọn phương án đang được tư vấn sơ bộ, nhưng nên hiểu đây mới là mức dự toán tham khảo ban đầu.",
            'has_concrete_ground' => "Chọn “Có” khi nền tầng trệt có đổ bê tông nền hoặc cấu tạo nền hoàn chỉnh theo giải pháp thi công. Hạng mục này làm phát sinh thêm phần vật tư và nhân công ở nền trệt.\n\nNếu trệt để nền đất, nền tạm hoặc chưa chốt giải pháp, có thể chọn “Không” để tránh cộng thừa chi phí.",
            'has_basement' => "Chọn mục này khi công trình có tầng hầm hoặc bán hầm thực tế. Hầm là hạng mục làm chi phí tăng mạnh vì liên quan đến đào đất, kết cấu giữ thành, chống thấm, thông gió và tổ chức thi công khó hơn nhà không hầm.\n\nNếu chỉ có cốt nền thấp hoặc hố kỹ thuật nhỏ thì chưa xem là tầng hầm.",
            'basement_area' => "Đây là diện tích tầng hầm thực tế nếu hầm không trùng hoàn toàn với footprint phía trên. Nhiều công trình có hầm lớn hơn hoặc nhỏ hơn thân nhà, nên nhập riêng sẽ giúp dự toán sát hơn.\n\nNếu chưa có số riêng, anh/chị có thể để hệ thống tạm lấy theo footprint.",
            'basement_type' => "Chiều sâu hầm ảnh hưởng trực tiếp đến khối lượng đào đất, biện pháp chống sạt, chống thấm và xử lý kết cấu. Hầm càng sâu thì chi phí thi công thường tăng rất rõ, đặc biệt ở khu dân cư đông nhà sát vách.\n\nAnh/chị nên chọn mức gần đúng theo cốt sàn hầm so với mặt đường hoặc sân hiện hữu.",
            'building_type' => "Loại công trình giúp hệ thống nhận diện mức độ tiêu chuẩn hoàn thiện, kết cấu và nhu cầu kỹ thuật thường gặp. Nhà phố, biệt thự, văn phòng hay shophouse thường có cách tính khác nhau về mặt công năng và mức đầu tư.\n\nNếu công trình pha trộn nhiều công năng, hãy chọn loại chiếm tỷ trọng chính rồi ghi rõ thêm trong mô tả.",
            'finish_package' => "Gói hoàn thiện phản ánh mặt bằng chất lượng vật tư và mức đầu tư tổng thể mà anh/chị đang hướng đến. Cùng một diện tích nhưng gói tiêu chuẩn và gói cao cấp có thể chênh rất đáng kể.\n\nNếu chưa khóa vật liệu chi tiết, anh/chị nên chọn theo ngân sách mục tiêu để dự toán ra sát nhu cầu thực tế hơn.",
            'location_zone' => "Khu vực xây dựng ảnh hưởng đến giá nhân công, vận chuyển vật tư, điều phối tổ đội và nhiều chi phí gián tiếp khác. Công trình trong nội thành, ngoại thành hay tỉnh lân cận thường có đơn giá triển khai khác nhau.\n\nAnh/chị nên chọn đúng khu vực thực thi công, không chọn theo địa chỉ văn phòng hay nơi ở hiện tại.",
            'access_condition' => "Điều kiện tiếp cận thi công cho biết xe vật tư, máy móc và đội thi công vào công trình thuận lợi hay khó khăn. Mặt bằng càng khó tiếp cận thì thời gian và chi phí vận chuyển thủ công càng tăng.\n\nĐây là trường rất quan trọng với nhà trong hẻm nhỏ, khu dân cư đông hoặc tuyến cấm tải.",
            'soil_type' => "Loại đất nền là cơ sở sơ bộ để ước lượng độ phức tạp của phần móng. Đất yếu thường kéo theo giải pháp móng an toàn hơn, thời gian thi công dài hơn và chi phí nền móng cao hơn.\n\nNếu chưa có khảo sát địa chất, anh/chị có thể chọn theo đánh giá sơ bộ của đơn vị tư vấn hoặc kinh nghiệm các nhà lân cận.",
            'has_elevator' => "Nếu công trình có thang máy, cần chừa hố thang, đà lintel, kết cấu liên quan và đôi khi phát sinh thêm yêu cầu kỹ thuật ở điện, hoàn thiện. Vì vậy đây là hạng mục nên khai báo sớm để dự toán không bị thiếu.\n\nNếu mới chỉ dự tính chừa ô thang cho tương lai, anh/chị nên ghi rõ trong phần mô tả.",
            'has_pool' => "Hồ bơi là hạng mục làm tăng đáng kể chi phí kết cấu, chống thấm, MEP và hoàn thiện. Dù là hồ trong nhà hay ngoài trời, nên khai báo sớm để hệ thống không bỏ sót phần khối lượng lớn này.\n\nNếu mới đang cân nhắc, anh/chị có thể thử cả hai phương án để so sánh chênh lệch đầu tư.",
            'soil_class' => "Phân lớp địa chất dùng cho cấp dự toán sâu hơn, khi cần nhìn kỹ hơn điều kiện nền đất thay vì chỉ đánh giá tốt, trung bình hay yếu. Trường này thường được dùng khi đã có thông tin khảo sát hoặc tư vấn nền móng tương đối rõ.\n\nNếu chưa có dữ liệu địa chất, anh/chị có thể để theo mức gần đúng hoặc trao đổi thêm với đội ngũ kỹ thuật.",
            'groundwater_level' => "Mực nước ngầm là yếu tố rất quan trọng nếu công trình có hầm hoặc nền móng sâu. Nước ngầm cao thường làm tăng yêu cầu chống thấm, hạ mực nước, gia cố thành hố và kiểm soát thi công.\n\nNếu khu đất thường xuyên ẩm, gần sông rạch hoặc vùng trũng, nên chọn mức trung bình đến cao để dự toán an toàn hơn.",
            'pile_type' => "Loại cọc phản ánh giải pháp truyền tải trọng xuống nền đất bên dưới. Cọc ép, cọc khoan nhồi hay cọc vít có biện pháp thi công, thiết bị và đơn giá khác nhau.\n\nNếu hồ sơ kết cấu chưa chốt, anh/chị nên chọn phương án đang được tư vấn sơ bộ để có khung ngân sách phù hợp.",
            'pile_depth' => "Độ sâu cọc là chiều dài làm việc dự kiến của cọc xuống nền đất. Cọc càng sâu thì vật tư, thời gian thi công và chi phí nền móng càng tăng.\n\nNếu chưa có số chính thức, anh/chị nhập mức ước tính đang được tư vấn để hệ thống ra dự toán tạm sát hơn.",
            'pile_count' => "Số lượng cọc cho biết tổng số cọc dự kiến của công trình. Chỉ số này ảnh hưởng trực tiếp đến chi phí cọc, đài cọc và thời gian thi công nền móng.\n\nNếu hiện mới có số cọc sơ bộ theo một phương án, cứ nhập để hệ thống ra khung chi phí trước.",
            'retaining_wall' => "Tường vây hoặc retaining wall thường xuất hiện ở công trình có hầm, taluy hoặc nền chênh cốt cần giữ đất. Đây là hạng mục kỹ thuật nặng, chi phí không nhỏ nên cần khai báo ngay nếu đã có trong phương án.\n\nNếu công trình bình thường không có hầm và không cần giữ đất, thường chọn “Không”.",
            'floor_height' => "Chiều cao tầng điển hình ảnh hưởng đến khối lượng tường, cột, vách, cầu thang, mặt dựng và hoàn thiện. Tầng càng cao thì chi phí phần thô và hoàn thiện thường càng tăng.\n\nAnh/chị nên nhập chiều cao sàn tới sàn dự kiến, không phải chiều cao thông thủy sử dụng.",
            'long_span_structure' => "Chọn “Có” nếu công trình có nhịp lớn, ít cột, không gian thông rộng như showroom, văn phòng mở hoặc gara lớn. Kết cấu nhịp lớn thường kéo theo dầm, sàn và giải pháp chịu lực phức tạp hơn nhà ở thông thường.\n\nNếu mặt bằng chia phòng nhỏ, nhịp cột bình thường thì thường chọn “Không”.",
            'contract_scope' => "Phạm vi hợp đồng cho biết anh/chị đang muốn dự toán phần nào: thiết kế, phần thô, hoàn thiện hay nội thất. Chọn đúng phạm vi giúp đội ngũ không cộng nhầm những hạng mục anh/chị chưa cần ở giai đoạn này.\n\nNếu muốn nhìn toàn cảnh ngân sách, anh/chị có thể chọn nhiều phần cùng lúc.",
            'vat_mode' => "Trường này cho biết số tiền anh/chị đang muốn nhìn là đã bao gồm VAT hay chưa. Đây là khác biệt thương mại, không làm thay đổi khối lượng nhưng ảnh hưởng trực tiếp đến tổng tiền cần chuẩn bị.\n\nNếu chưa rõ chế độ xuất hóa đơn, anh/chị có thể để theo phương án mặc định rồi chốt lại ở bước thương mại.",
            'discount_percent' => "Chiết khấu là tỷ lệ giảm giá dự kiến áp trên kết quả trước VAT hoặc theo cơ chế thương mại đang áp dụng. Trường này chỉ nên nhập khi đã có chính sách cụ thể hoặc đang mô phỏng một kịch bản thương mại rõ ràng.\n\nNếu chưa áp dụng, hãy để 0 để giữ kết quả trung tính.",
            'surcharge_percent' => "Phụ thu dùng cho các trường hợp có chi phí cộng thêm do điều kiện thi công, tiến độ gấp, vật tư đặc biệt hoặc yêu cầu riêng. Đây là lớp điều chỉnh thương mại sau khi đã có khối lượng cơ bản.\n\nNếu chưa phát sinh yếu tố đặc biệt, anh/chị nên để 0 để không làm đội kết quả không cần thiết.",
            'price_set' => "Đây là bộ bảng giá mà hệ thống đang dùng để quy đổi tổng m2 ra giá trị tiền. Với người dùng frontsite, nếu chưa có chỉ định riêng từ admin thì thường nên giữ mặc định.\n\nTrường này hữu ích hơn ở cấp dự toán nội bộ khi cần so sánh nhiều kịch bản giá.",
            'formula_set' => "Đây là bộ công thức hệ số mà hệ thống dùng để tính diện tích quy đổi. Thông thường frontsite chỉ dùng bộ mặc định đã được kỹ thuật kiểm tra.\n\nAnh/chị chỉ nên đổi khi đội vận hành thông báo có phiên bản công thức riêng cho chiến dịch hoặc nhóm công trình đặc thù.",
        ];

        return $guides[$fieldKey] ?? null;
    }

    protected static function catalogValue(array $catalog, string $key, mixed $fallback): mixed
    {
        return array_key_exists($key, $catalog) ? $catalog[$key] : $fallback;
    }

    protected static function estimatorValueEmpty(mixed $value, string $type): bool
    {
        if ($type === 'multiselect') {
            return ! is_array($value) || $value === [];
        }

        if (is_bool($value)) {
            return false;
        }

        return $value === null || trim((string) $value) === '';
    }

    protected static function highlightItem(string $value, string $label): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'value' => $value,
            'label' => $label,
        ];
    }

    protected static function inputField(
        string $slug,
        string $label,
        string $type,
        string $placeholder,
        string $help,
        string $optionsText = '',
        bool $required = true,
    ): array {
        $options = self::prepareOptions(null, $optionsText);

        return [
            'uuid' => (string) Str::uuid(),
            'slug' => $slug,
            'label' => $label,
            'type' => $type,
            'placeholder' => $placeholder,
            'help' => $help,
            'required' => $required,
            'options' => $options,
            'options_text' => self::optionsToText($options),
        ];
    }

    protected static function linesToText(array $lines): string
    {
        return implode("\n", array_map(static fn ($line) => trim((string) $line), $lines));
    }

    protected static function optionsToText(array $options): string
    {
        return implode("\n", array_map(function (array $option) {
            $label = trim((string) data_get($option, 'label'));
            $value = trim((string) data_get($option, 'value'));

            return $value !== '' && $value !== Str::slug($label)
                ? $label.'|'.$value
                : $label;
        }, $options));
    }

    protected static function payloadMap(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $resolved = [];

        foreach ($payload as $key => $item) {
            if (is_array($item) && filled($item['slug'] ?? null)) {
                $resolved[(string) $item['slug']] = trim((string) ($item['value'] ?? ''));

                continue;
            }

            if (is_string($key)) {
                $resolved[$key] = trim((string) $item);
            }
        }

        return $resolved;
    }

    protected static function payloadMapDetailed(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $resolved = [];

        foreach ($payload as $key => $item) {
            if (is_array($item) && filled($item['slug'] ?? null)) {
                $resolved[(string) $item['slug']] = $item['value'] ?? null;

                continue;
            }

            if (is_string($key)) {
                $resolved[$key] = $item;
            }
        }

        return $resolved;
    }

    protected static function prepareLines(mixed $lines, mixed $linesText = null): array
    {
        if (is_array($lines)) {
            return collect($lines)
                ->map(fn ($line) => trim((string) $line))
                ->filter()
                ->values()
                ->all();
        }

        $linesText = trim((string) $linesText);

        if ($linesText === '') {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $linesText) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    protected static function prepareOptions(mixed $options, mixed $optionsText = null): array
    {
        if (is_array($options)) {
            return collect($options)
                ->filter(fn ($option) => is_array($option))
                ->map(function (array $option) {
                    $label = trim((string) data_get($option, 'label'));
                    $value = trim((string) data_get($option, 'value'));

                    if ($label === '' && $value === '') {
                        return null;
                    }

                    $label = $label !== '' ? $label : $value;
                    $value = $value !== '' ? $value : Str::slug($label);

                    return [
                        'label' => $label,
                        'value' => $value,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        $optionsText = trim((string) $optionsText);

        if ($optionsText === '') {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', $optionsText) ?: [])
            ->map(function (string $line) {
                $segments = array_map('trim', explode('|', $line, 2));
                $label = $segments[0] ?? '';
                $value = $segments[1] ?? Str::slug($label);

                if ($label === '' && $value === '') {
                    return null;
                }

                return [
                    'label' => $label !== '' ? $label : $value,
                    'value' => $value !== '' ? $value : Str::slug($label),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected static function slugValue(mixed $value, mixed $fallback = null): string
    {
        $resolved = trim((string) $value);
        $resolved = $resolved !== '' ? Str::slug($resolved) : '';

        if ($resolved !== '') {
            return $resolved;
        }

        $fallback = trim((string) $fallback);

        return $fallback !== ''
            ? (Str::slug($fallback) ?: 'field')
            : 'field';
    }

    protected static function stringValue(mixed $value): string
    {
        return trim((string) $value);
    }

    protected static function tierItem(
        string $code,
        string $estimatorLevel,
        string $name,
        string $badge,
        string $description,
        bool $requiresKey,
        string $keyLabel,
        string $keyHelp,
        string $resultTitle,
        string $resultSummary,
        string $resultBulletsText,
        string $deliveryText,
        string $buttonLabel,
    ): array {
        $bullets = self::prepareLines(null, $resultBulletsText);

        return [
            'uuid' => (string) Str::uuid(),
            'code' => $code,
            'estimator_level' => $estimatorLevel,
            'name' => $name,
            'badge' => $badge,
            'description' => $description,
            'requires_key' => $requiresKey,
            'key_label' => $keyLabel,
            'key_help' => $keyHelp,
            'result_title' => $resultTitle,
            'result_summary' => $resultSummary,
            'result_bullets' => $bullets,
            'result_bullets_text' => self::linesToText($bullets),
            'delivery_text' => $deliveryText,
            'button_label' => $buttonLabel,
        ];
    }

    protected static function heroEffectValue(mixed $value): string
    {
        $value = trim((string) $value);
        $allowed = collect(self::heroEffectOptions())->pluck('value')->all();

        return in_array($value, $allowed, true)
            ? $value
            : 'animate__fadeInUp';
    }

    protected static function uuidValue(mixed $value): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : (string) Str::uuid();
    }

    protected static function booleanOptions(): array
    {
        return self::optionList([
            'Không' => 'false',
            'Có' => 'true',
        ]);
    }

    protected static function humanizeEstimatorKey(string $fieldKey): string
    {
        return Str::headline(str_replace('_', ' ', $fieldKey));
    }

    protected static function loadEstimatorJson(string $relativePath): array
    {
        $path = base_path('skills/cost-estimator/'.ltrim($relativePath, '/'));

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    protected static function optionList(array $options): array
    {
        return collect($options)
            ->map(fn (string $value, string $label) => [
                'label' => $label,
                'value' => $value,
            ])
            ->values()
            ->all();
    }
}
