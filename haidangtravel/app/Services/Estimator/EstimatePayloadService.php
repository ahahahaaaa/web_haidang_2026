<?php

namespace App\Services\Estimator;

use App\Support\EstimatePageContent;

class EstimatePayloadService
{
    public function __construct(
        protected EstimateCatalogService $catalog,
    ) {
    }

    public function decode(mixed $payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($payload) ? $payload : [];
    }

    public function isGrouped(array $payload): bool
    {
        return array_key_exists('base_inputs', $payload)
            || array_key_exists('technical_inputs', $payload)
            || array_key_exists('room_program', $payload)
            || array_key_exists('design_options', $payload)
            || array_key_exists('special_addons', $payload)
            || array_key_exists('internal_controls', $payload);
    }

    public function flattenForValidation(mixed $payload): array
    {
        $decoded = $this->decode($payload);

        if (! $this->isGrouped($decoded)) {
            return collect($decoded)
                ->reduce(function (array $carry, mixed $item, mixed $key) {
                    if (is_array($item) && filled($item['slug'] ?? null)) {
                        $carry[(string) $item['slug']] = $item['value'] ?? null;
                    } elseif (is_string($key)) {
                        $carry[$key] = $item;
                    }

                    return $carry;
                }, []);
        }

        return array_merge(
            $this->ensureArray($decoded['base_inputs'] ?? []),
            $this->ensureArray($decoded['technical_inputs'] ?? []),
            $this->ensureArray($decoded['internal_controls'] ?? []),
        );
    }

    public function normalizeForStorage(array $config, mixed $payload, string $level): array
    {
        $decoded = $this->decode($payload);

        if (! $this->isGrouped($decoded)) {
            return [
                'legacy' => true,
                'flat' => EstimatePageContent::normalizeEstimatorSubmittedInputs($config, $decoded, $level),
            ];
        }

        $baseInputs = $this->normalizeSimpleGroup($config, $decoded['base_inputs'] ?? [], $level);
        $technicalInputs = $this->normalizeSimpleGroup($config, $decoded['technical_inputs'] ?? [], $level);
        $internalControls = $this->normalizeSimpleGroup($config, $decoded['internal_controls'] ?? [], $level);

        return [
            'legacy' => false,
            'base_inputs' => $baseInputs,
            'technical_inputs' => $technicalInputs,
            'room_program' => $this->normalizeRoomProgram($decoded['room_program'] ?? []),
            'design_options' => $this->normalizeCatalogSelections($decoded['design_options'] ?? []),
            'special_addons' => $this->normalizeCatalogSelections($decoded['special_addons'] ?? []),
            'internal_controls' => $internalControls,
            'flat' => array_values(array_merge($baseInputs, $technicalInputs, $internalControls)),
        ];
    }

    protected function normalizeSimpleGroup(array $config, array $group, string $level): array
    {
        return EstimatePageContent::normalizeEstimatorSubmittedInputs(
            $config,
            collect($group)
                ->map(fn (mixed $value, string $slug) => ['slug' => $slug, 'value' => $value])
                ->values()
                ->all(),
            $level,
        );
    }

    protected function normalizeRoomProgram(mixed $rows): array
    {
        return collect(is_array($rows) ? $rows : [])
            ->map(function (mixed $row) {
                if (! is_array($row)) {
                    return null;
                }

                $roomType = trim((string) ($row['room_type'] ?? ''));
                $template = trim((string) ($row['template'] ?? ''));
                $count = max(0, (int) ($row['count'] ?? 0));
                $extraItems = collect(is_array($row['extra_items'] ?? null) ? $row['extra_items'] : [])
                    ->map(function (mixed $code) {
                        $resolved = trim((string) $code);

                        if ($resolved === '') {
                            return null;
                        }

                        return [
                            'code' => $resolved,
                            'label' => $this->catalog->itemLabel($resolved) ?: $resolved,
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                if ($roomType === '' || $count <= 0) {
                    return null;
                }

                return [
                    'room_type' => $roomType,
                    'room_type_label' => $this->catalog->roomTypeLabel($roomType) ?: $roomType,
                    'count' => $count,
                    'template' => $template,
                    'template_label' => $this->catalog->roomTemplateLabel($template) ?: $template,
                    'extra_items' => $extraItems,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeCatalogSelections(mixed $codes): array
    {
        return collect(is_array($codes) ? $codes : [])
            ->map(function (mixed $code) {
                $resolved = trim((string) $code);

                if ($resolved === '') {
                    return null;
                }

                return [
                    'code' => $resolved,
                    'label' => $this->catalog->itemLabel($resolved) ?: $resolved,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function ensureArray(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
