<?php

namespace App\Services\SeoOptimization;

use App\Support\FaqContent;
use App\Support\RichText;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Src\Domains\Cms\Models\Destination;

class ContentPatchValidator
{
    public function validate(array $patch, array $snapshot, array $brief, array $missingFacts = [], bool $restore = false): array
    {
        $allowed = $snapshot['writable_fields'] ?? [];
        if ($patch === [] || array_is_list($patch) || array_diff(array_keys($patch), $allowed)) {
            throw ValidationException::withMessages(['patch' => 'Đề xuất có trường không được phép hoặc chưa có nội dung thay đổi.']);
        }

        $before = [];
        $clean = [];
        $warnings = [];

        foreach ($patch as $field => $value) {
            if ($field === ContentWriteContractService::BLOCK_CHANGES) {
                [$blockPatch, $blockBefore] = $this->validateBlockChanges($value, $snapshot, $restore);
                if ($blockPatch !== []) {
                    $clean[$field] = $blockPatch;
                    $before[$field] = $blockBefore;
                }

                continue;
            }

            $definition = data_get($snapshot, 'field_contracts.'.$field, $this->legacyDefinition($field));
            $cleanValue = $this->cleanValue($value, $definition, $restore);
            $beforeValue = data_get($snapshot, 'source_fields.'.$field);
            if ($this->valuesEquivalent((string) ($definition['kind'] ?? ''), $beforeValue, $cleanValue)) {
                continue;
            }

            $clean[$field] = $cleanValue;
            $before[$field] = $beforeValue;

            if (($definition['kind'] ?? null) === 'slug') {
                $warnings[] = 'Thay slug phải được kiểm tra unique và tạo redirect 301 từ URL cũ khi áp dụng.';
            }
        }

        if ($clean === []) {
            throw ValidationException::withMessages(['patch' => 'Nội dung đề xuất không khác dữ liệu hiện tại.']);
        }

        $pageSource = RichText::normalizePlain(
            (string) ($snapshot['text'] ?? '').' '.$this->contentText($snapshot['source_fields'] ?? []).' '.$this->contentUnitsText($snapshot['content_units'] ?? []),
        );
        $verifiedSource = '';
        foreach ($brief['fact_sources'] ?? [] as $fact) {
            if (is_array($fact) && ! empty($fact['verified_by'])) {
                $verifiedSource .= ' '.($fact['quote'] ?? '');
            }
        }
        $source = trim($pageSource.' '.RichText::normalizePlain($verifiedSource));
        $newText = RichText::normalizePlain($this->patchContentText($clean));

        preg_match_all('/\d+(?:[.,\/-]\d+)*/u', $newText, $newNumbers);
        preg_match_all('/\d+(?:[.,\/-]\d+)*/u', $source, $oldNumbers);
        if ($unknown = array_diff(array_unique($newNumbers[0]), array_unique($oldNumbers[0]))) {
            $missingFacts[] = 'Các số liệu mới cần nguồn xác nhận: '.implode(', ', array_slice($unknown, 0, 20));
        }
        foreach (['miễn visa', 'miễn thị thực', 'hoàn tiền', 'cam kết', 'bảo đảm', 'đảm bảo', 'tốt nhất', 'rẻ nhất', 'hàng đầu', 'lớn nhất', 'dẫn đầu', 'uy tín nhất', 'số 1'] as $claim) {
            if (Str::contains(Str::lower($newText), $claim) && ! Str::contains(Str::lower($source), $claim)) {
                $missingFacts[] = 'Claim cần nguồn nghiệp vụ: '.$claim;
            }
        }
        foreach (['title', 'name'] as $shared) {
            if (array_key_exists($shared, $clean)) {
                $warnings[] = 'Thay '.$shared.' có thể ảnh hưởng H1, card, breadcrumb và schema liên quan.';
            }
        }

        return [
            'patch' => $clean,
            'before' => $before,
            'missing_facts' => array_values(array_unique($missingFacts)),
            'warnings' => array_values(array_unique($warnings)),
            'requires_human' => true,
        ];
    }

    /** @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>} */
    private function validateBlockChanges(mixed $value, array $snapshot, bool $restore): array
    {
        Validator::make(['value' => $value], [
            'value' => ['required', 'array', 'max:50'],
            'value.*' => ['required', 'array:uuid,type,changes'],
            'value.*.uuid' => ['required', 'string', 'max:100'],
            'value.*.type' => ['required', 'string', 'max:80'],
            'value.*.changes' => ['required', 'array', 'min:1', 'max:25'],
        ])->validate();

        $units = collect($snapshot['content_units'] ?? [])->keyBy('uuid');
        $seen = [];
        $clean = [];
        $before = [];

        foreach ($value as $change) {
            $uuid = (string) $change['uuid'];
            if (isset($seen[$uuid])) {
                throw ValidationException::withMessages(['patch' => 'Mỗi block chỉ được xuất hiện một lần trong block_changes.']);
            }
            $seen[$uuid] = true;
            $unit = $units->get($uuid);
            if (! is_array($unit) || (string) ($unit['type'] ?? '') !== (string) $change['type']) {
                throw ValidationException::withMessages(['patch' => 'Block LandingPage không tồn tại, đang bị ẩn hoặc đã đổi loại.']);
            }
            if (array_is_list($change['changes'])) {
                throw ValidationException::withMessages(['patch' => 'changes của block phải là object theo tên field.']);
            }

            $cleanChanges = [];
            $beforeChanges = [];
            foreach ($change['changes'] as $field => $fieldValue) {
                $definition = data_get($unit, 'fields.'.$field);
                if (! is_array($definition)) {
                    throw ValidationException::withMessages(['patch' => 'Block chứa field không được phép tối ưu: '.$field]);
                }
                $cleanValue = $this->cleanValue($fieldValue, $definition, $restore);
                $beforeValue = $definition['value'] ?? null;
                if ($this->valuesEquivalent((string) ($definition['kind'] ?? ''), $beforeValue, $cleanValue)) {
                    continue;
                }
                $cleanChanges[$field] = $cleanValue;
                $beforeChanges[$field] = $beforeValue;
            }

            if ($cleanChanges !== []) {
                $identity = ['uuid' => $uuid, 'type' => (string) $change['type']];
                $clean[] = [...$identity, 'changes' => $cleanChanges];
                $before[] = [...$identity, 'changes' => $beforeChanges];
            }
        }

        return [$clean, $before];
    }

    private function cleanValue(mixed $value, array $definition, bool $restore): mixed
    {
        $kind = (string) ($definition['kind'] ?? 'plain_text');
        if ($kind === 'faq') {
            Validator::make(['value' => $value], [
                'value' => [$restore ? 'nullable' : 'required', 'array', 'max:20'],
                'value.*' => ['array:question,answer'],
                'value.*.question' => ['required', 'string', 'max:500'],
                'value.*.answer' => ['required', 'string', 'max:5000'],
            ])->validate();

            return FaqContent::normalizeItems($value ?? []);
        }

        $max = (int) ($definition['max'] ?? config('seo_optimization.max_patch_characters', 100000));
        Validator::make(['value' => $value], [
            'value' => [$restore ? 'nullable' : 'required', 'string', 'max:'.$max],
        ])->validate();
        if ($restore && $value === null) {
            return null;
        }

        $value = (string) $value;
        $this->assertSafeHtml($value);
        if (in_array($kind, ['rich_html', 'manual_html'], true) && preg_match('/<\s*h1\b/iu', $value)) {
            throw ValidationException::withMessages(['patch' => 'Phần nội dung chỉ dùng H2–H6; H1 do trang quản lý.']);
        }

        $clean = match ($kind) {
            'rich_html' => RichText::sanitize($value),
            'manual_html' => $value,
            default => RichText::normalizePlain($value),
        };

        if ($kind === 'slug') {
            $slug = ($definition['normalizer'] ?? null) === 'country_slug'
                ? Destination::countryRootSlug($clean)
                : Str::slug($clean);
            if ($slug === '' || $slug !== $clean || mb_strlen($slug) > 180) {
                throw ValidationException::withMessages(['patch' => 'Slug phải đúng dạng kebab-case và quy ước URL của loại nội dung.']);
            }

            return $slug;
        }

        $this->assertSafeUrls($clean);

        return $clean;
    }

    private function valuesEquivalent(string $kind, mixed $before, mixed $after): bool
    {
        if ($kind === 'faq') {
            return FaqContent::normalizeItems(is_array($before) ? $before : [])
                === FaqContent::normalizeItems(is_array($after) ? $after : []);
        }

        return $before === $after;
    }

    private function assertSafeHtml(string $value): void
    {
        if (preg_match('/<\s*(script|iframe|object|embed|form|input|button|style|link|meta)\b|\bon[a-z]+\s*=|javascript\s*:|data\s*:\s*text\/html|display\s*:\s*none|visibility\s*:\s*hidden/iu', $value)) {
            throw ValidationException::withMessages(['patch' => 'Nội dung có mã hoặc thuộc tính HTML không được phép.']);
        }
    }

    private function assertSafeUrls(string $value): void
    {
        if (! preg_match_all('/(?:href|src)\s*=\s*["\']([^"\']+)/iu', $value, $matches)) {
            return;
        }
        foreach ($matches[1] as $url) {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (str_starts_with($url, '//') || ($scheme !== null && ! in_array(strtolower((string) $scheme), ['http', 'https'], true))) {
                throw ValidationException::withMessages(['patch' => 'Liên kết/ảnh phải dùng URL HTTP(S), đường dẫn nội bộ hoặc fragment.']);
            }
        }
    }

    /** @return array<string, mixed> */
    private function legacyDefinition(string $field): array
    {
        return match ($field) {
            'faq_items' => ['kind' => 'faq'],
            'content', 'body', 'intro_content' => ['kind' => 'rich_html'],
            'slug' => ['kind' => 'slug', 'max' => 180],
            'title', 'name', 'hero_title', 'meta_title' => ['kind' => 'plain_text', 'max' => 255],
            'meta_description' => ['kind' => 'plain_text', 'max' => 500],
            default => ['kind' => 'plain_text'],
        };
    }

    private function patchContentText(array $patch): string
    {
        $content = [];
        foreach ($patch as $field => $value) {
            if ($field === ContentWriteContractService::BLOCK_CHANGES) {
                foreach ($value as $change) {
                    $content[] = $change['changes'] ?? [];
                }
            } else {
                $content[] = $value;
            }
        }

        return $this->contentText($content);
    }

    private function contentUnitsText(array $units): string
    {
        return $this->contentText(collect($units)->flatMap(
            fn (array $unit): array => collect($unit['fields'] ?? [])->pluck('value')->all(),
        )->all());
    }

    private function contentText(array $values): string
    {
        $strings = [];
        array_walk_recursive($values, function (mixed $value) use (&$strings): void {
            if (is_string($value)) {
                $strings[] = $value;
            }
        });

        return implode(' ', $strings);
    }
}
