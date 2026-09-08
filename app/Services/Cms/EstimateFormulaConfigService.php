<?php

namespace App\Services\Cms;

use App\Support\EstimatePageContent;

class EstimateFormulaConfigService
{
    public function __construct(
        protected ?string $rootPath = null,
    ) {
        $this->rootPath ??= base_path('skills/cost-estimator');
    }

    public function loadForm(): array
    {
        $formula = $this->readJson('examples/formula.json');
        $pricing = $this->readJson('configs/default-pricing.json');
        $fieldCatalog = $this->readJson('configs/field-catalog.json');
        $levels = $this->readJson('configs/estimation-levels.json');
        $fieldReference = EstimatePageContent::estimatorFieldReference();
        $allFieldKeys = collect(data_get($fieldCatalog, 'groups', []))
            ->flatMap(fn (array $group) => data_get($group, 'fields', []))
            ->merge(collect(data_get($levels, 'levels', []))->flatMap(fn (array $level) => data_get($level, 'fields', [])))
            ->merge(array_keys(data_get($fieldCatalog, 'fields', [])))
            ->merge(array_keys($fieldReference))
            ->map(fn (mixed $key) => trim((string) $key))
            ->filter()
            ->unique()
            ->values();

        return [
            'formula_meta' => [
                'code' => trim((string) data_get($formula, 'meta.code')),
                'name' => trim((string) data_get($formula, 'meta.name')),
                'version' => trim((string) data_get($formula, 'meta.version')),
                'supports_levels_text' => implode(', ', data_get($formula, 'meta.supports_levels', [])),
            ],
            'derived_rows' => collect(data_get($formula, 'derived', []))
                ->map(fn (mixed $expression, string $key) => [
                    'key' => $key,
                    'expression' => trim((string) $expression),
                ])
                ->values()
                ->all(),
            'components' => collect(data_get($formula, 'components', []))
                ->map(fn (array $component) => $this->componentToFormRow($component))
                ->values()
                ->all(),
            'pricing_meta' => [
                'code' => trim((string) data_get($pricing, 'meta.code')),
                'version' => trim((string) data_get($pricing, 'meta.version')),
            ],
            'packages' => collect(data_get($pricing, 'packages', []))
                ->map(fn (array $package) => [
                    'key' => trim((string) data_get($package, 'key')),
                    'label' => trim((string) data_get($package, 'label')),
                    'unit_price' => trim((string) data_get($package, 'unit_price')),
                    'currency' => trim((string) data_get($package, 'currency', 'VND')),
                ])
                ->values()
                ->all(),
            'levels' => collect(data_get($levels, 'levels', []))
                ->map(fn (array $level, string $code) => [
                    'code' => $code,
                    'name' => trim((string) data_get($level, 'name')),
                    'purpose' => trim((string) data_get($level, 'purpose')),
                    'extends' => trim((string) data_get($level, 'extends')),
                    'fields_text' => implode("\n", data_get($level, 'fields', [])),
                ])
                ->values()
                ->all(),
            'field_groups' => collect(data_get($fieldCatalog, 'groups', []))
                ->map(fn (array $group) => [
                    'key' => trim((string) data_get($group, 'key')),
                    'label' => trim((string) data_get($group, 'label')),
                    'fields_text' => implode("\n", data_get($group, 'fields', [])),
                ])
                ->values()
                ->all(),
            'field_entries' => $allFieldKeys
                ->map(function (string $key) use ($fieldCatalog, $fieldReference) {
                    $field = data_get($fieldCatalog, "fields.{$key}", []);
                    $reference = data_get($fieldReference, $key, []);

                    return [
                        'key' => $key,
                        'type' => $this->resolveFieldType($field, $reference),
                        'required_levels_text' => implode(', ', data_get($field, 'required_levels', [])),
                        'guide_title' => array_key_exists('guide_title', $field)
                            ? trim((string) data_get($field, 'guide_title'))
                            : trim((string) data_get($reference, 'guide_title')),
                        'guide_content' => array_key_exists('guide_content', $field)
                            ? trim((string) data_get($field, 'guide_content'))
                            : trim((string) data_get($reference, 'guide_content')),
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    public function saveForm(array $form): void
    {
        $formula = [
            'meta' => [
                'code' => trim((string) data_get($form, 'formula_meta.code')),
                'version' => trim((string) data_get($form, 'formula_meta.version')),
                'name' => trim((string) data_get($form, 'formula_meta.name')),
                'supports_levels' => $this->parseList(data_get($form, 'formula_meta.supports_levels_text')),
            ],
            'derived' => collect(data_get($form, 'derived_rows', []))
                ->reduce(function (array $carry, array $row) {
                    $key = trim((string) data_get($row, 'key'));
                    $expression = trim((string) data_get($row, 'expression'));

                    if ($key !== '' && $expression !== '') {
                        $carry[$key] = $expression;
                    }

                    return $carry;
                }, []),
            'components' => collect(data_get($form, 'components', []))
                ->map(fn (array $component) => $this->normalizeComponent($component))
                ->filter()
                ->values()
                ->all(),
        ];

        $pricing = [
            'meta' => [
                'code' => trim((string) data_get($form, 'pricing_meta.code')),
                'version' => trim((string) data_get($form, 'pricing_meta.version')),
            ],
            'packages' => collect(data_get($form, 'packages', []))
                ->map(function (array $package) {
                    $key = trim((string) data_get($package, 'key'));
                    $label = trim((string) data_get($package, 'label'));

                    if ($key === '' || $label === '') {
                        return null;
                    }

                    return [
                        'key' => $key,
                        'label' => $label,
                        'unit_price' => $this->normalizeNumber(data_get($package, 'unit_price')),
                        'currency' => trim((string) data_get($package, 'currency', 'VND')) ?: 'VND',
                    ];
                })
                ->filter()
                ->values()
                ->all(),
        ];

        $fieldCatalog = [
            'groups' => collect(data_get($form, 'field_groups', []))
                ->map(function (array $group) {
                    $key = trim((string) data_get($group, 'key'));
                    $label = trim((string) data_get($group, 'label'));

                    if ($key === '' || $label === '') {
                        return null;
                    }

                    return [
                        'key' => $key,
                        'label' => $label,
                        'fields' => $this->parseList(data_get($group, 'fields_text')),
                    ];
                })
                ->filter()
                ->values()
                ->all(),
            'fields' => collect(data_get($form, 'field_entries', []))
                ->reduce(function (array $carry, array $field) {
                    $key = trim((string) data_get($field, 'key'));

                    if ($key === '') {
                        return $carry;
                    }

                    $fieldPayload = [
                        'type' => trim((string) data_get($field, 'type', 'text')) ?: 'text',
                    ];

                    $requiredLevels = $this->parseList(data_get($field, 'required_levels_text'));

                    if ($requiredLevels !== []) {
                        $fieldPayload['required_levels'] = $requiredLevels;
                    }

                    $fieldPayload['guide_title'] = trim((string) data_get($field, 'guide_title'));
                    $fieldPayload['guide_content'] = trim((string) data_get($field, 'guide_content'));

                    $carry[$key] = $fieldPayload;

                    return $carry;
                }, []),
        ];

        $levels = [
            'levels' => collect(data_get($form, 'levels', []))
                ->reduce(function (array $carry, array $level) {
                    $code = trim((string) data_get($level, 'code'));

                    if ($code === '') {
                        return $carry;
                    }

                    $payload = [
                        'name' => trim((string) data_get($level, 'name')),
                        'purpose' => trim((string) data_get($level, 'purpose')),
                        'fields' => $this->parseList(data_get($level, 'fields_text')),
                    ];

                    $extends = trim((string) data_get($level, 'extends'));

                    if ($extends !== '') {
                        $payload['extends'] = $extends;
                    }

                    $carry[$code] = $payload;

                    return $carry;
                }, []),
        ];

        $this->writeJson('examples/formula.json', $formula);
        $this->writeJson('configs/default-pricing.json', $pricing);
        $this->writeJson('configs/field-catalog.json', $fieldCatalog);
        $this->writeJson('configs/estimation-levels.json', $levels);
    }

    protected function componentToFormRow(array $component): array
    {
        $coefficientMode = 'fixed';

        if (array_key_exists('coefficient_from_expression', $component)) {
            $coefficientMode = 'expression';
        }

        if (array_key_exists('coefficient_from_option', $component)) {
            $coefficientMode = 'option';
        }

        return [
            'key' => trim((string) data_get($component, 'key')),
            'section' => trim((string) data_get($component, 'section')),
            'label' => trim((string) data_get($component, 'label')),
            'condition' => trim((string) data_get($component, 'condition')),
            'area' => trim((string) data_get($component, 'area')),
            'coefficient_mode' => $coefficientMode,
            'coefficient' => array_key_exists('coefficient', $component)
                ? trim((string) data_get($component, 'coefficient'))
                : '',
            'coefficient_expression' => trim((string) data_get($component, 'coefficient_from_expression')),
            'option_field' => trim((string) data_get($component, 'option_field')),
            'coefficient_options_text' => $this->formatCoefficientOptions(data_get($component, 'coefficient_from_option', [])),
        ];
    }

    protected function normalizeComponent(array $component): ?array
    {
        $key = trim((string) data_get($component, 'key'));
        $section = trim((string) data_get($component, 'section'));
        $label = trim((string) data_get($component, 'label'));
        $area = trim((string) data_get($component, 'area'));

        if ($key === '' || $section === '' || $label === '' || $area === '') {
            return null;
        }

        $payload = [
            'key' => $key,
            'section' => $section,
            'label' => $label,
            'area' => $area,
        ];

        $condition = trim((string) data_get($component, 'condition'));

        if ($condition !== '') {
            $payload['condition'] = $condition;
        }

        $mode = trim((string) data_get($component, 'coefficient_mode', 'fixed'));

        if ($mode === 'expression') {
            $payload['coefficient_from_expression'] = trim((string) data_get($component, 'coefficient_expression'));

            return $payload;
        }

        if ($mode === 'option') {
            $payload['coefficient_from_option'] = $this->parseCoefficientOptions(
                trim((string) data_get($component, 'coefficient_options_text')),
            );
            $payload['option_field'] = trim((string) data_get($component, 'option_field'));

            return $payload;
        }

        $payload['coefficient'] = $this->normalizeNumber(data_get($component, 'coefficient'));

        return $payload;
    }

    protected function parseCoefficientOptions(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->reduce(function (array $carry, string $line) {
                $segments = preg_split('/=|:/', $line, 2) ?: [];
                $key = trim((string) ($segments[0] ?? ''));
                $number = trim((string) ($segments[1] ?? ''));

                if ($key !== '' && $number !== '') {
                    $carry[$key] = $this->normalizeNumber($number);
                }

                return $carry;
            }, []);
    }

    protected function formatCoefficientOptions(mixed $value): string
    {
        if (! is_array($value)) {
            return '';
        }

        return collect($value)
            ->map(fn (mixed $coefficient, string $key) => $key.'='.$coefficient)
            ->implode("\n");
    }

    protected function parseList(mixed $value): array
    {
        return collect(preg_split('/[\r\n,]+/', trim((string) $value)) ?: [])
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeNumber(mixed $value): int|float
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        $number = (float) $normalized;

        return fmod($number, 1.0) === 0.0 ? (int) $number : round($number, 4);
    }

    protected function resolveFieldType(array $field, array $reference): string
    {
        $catalogType = trim((string) data_get($field, 'type', ''));

        if ($catalogType !== '') {
            return $catalogType;
        }

        $referenceType = trim((string) data_get($reference, 'type', ''));

        if ($referenceType !== '') {
            return $referenceType;
        }

        return collect(data_get($reference, 'options', []))->isNotEmpty() ? 'select' : 'text';
    }

    protected function readJson(string $relativePath): array
    {
        $path = $this->path($relativePath);

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    protected function writeJson(string $relativePath, array $payload): void
    {
        $path = $this->path($relativePath);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $path,
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );
    }

    protected function path(string $relativePath): string
    {
        return rtrim((string) $this->rootPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($relativePath, DIRECTORY_SEPARATOR);
    }
}
