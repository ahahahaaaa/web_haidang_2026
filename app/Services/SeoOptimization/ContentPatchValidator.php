<?php

namespace App\Services\SeoOptimization;

use App\Support\RichText;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            Validator::make(['value' => $value], ['value' => [$restore ? 'nullable' : 'required', 'string', 'max:'.config('seo_optimization.max_patch_characters', 100000)]])->validate();
            if ($restore && ($value === null || $value === '')) {
                $clean[$field] = $value;
                $before[$field] = $snapshot['source_fields'][$field] ?? null;

                continue;
            }
            if (preg_match('/<\s*(script|iframe|object|embed|form|input|button|style|link|meta)\b|\bon[a-z]+\s*=|javascript\s*:|data\s*:\s*text\/html|display\s*:\s*none|visibility\s*:\s*hidden/iu', $value)) {
                throw ValidationException::withMessages(['patch' => 'Nội dung có mã hoặc thuộc tính HTML không được phép.']);
            }
            $rich = in_array($field, ['content', 'body', 'description', 'intro_content']);
            if ($rich && preg_match('/<\s*h1\b/iu', $value)) {
                throw ValidationException::withMessages(['patch' => 'Phần nội dung chỉ dùng H2–H6; H1 do trang quản lý.']);
            }
            $clean[$field] = $rich ? RichText::sanitize($value) : RichText::normalizePlain($value);
            if (in_array($field, ['meta_title', 'title', 'name', 'hero_title']) && mb_strlen($clean[$field]) > 255) {
                throw ValidationException::withMessages(['patch' => 'Tiêu đề dài quá 255 ký tự.']);
            }
            if ($field === 'meta_description' && mb_strlen($clean[$field]) > 500) {
                throw ValidationException::withMessages(['patch' => 'Meta description dài quá 500 ký tự.']);
            }
            $before[$field] = $snapshot['source_fields'][$field] ?? null;
            if ($before[$field] === $clean[$field]) {
                unset($clean[$field], $before[$field]);

                continue;
            }
            if (preg_match_all('/(?:href|src)\s*=\s*["\']([^"\']+)/iu', $clean[$field], $matches)) {
                foreach ($matches[1] as $url) {
                    $scheme = parse_url($url, PHP_URL_SCHEME);
                    if (str_starts_with($url, '//') || ($scheme !== null && ! in_array(strtolower((string) $scheme), ['http', 'https']))) {
                        throw ValidationException::withMessages(['patch' => 'Liên kết/ảnh phải dùng URL HTTP(S), đường dẫn nội bộ hoặc fragment.']);
                    }
                }
            }
        }
        if ($clean === []) {
            throw ValidationException::withMessages(['patch' => 'Nội dung đề xuất không khác dữ liệu hiện tại.']);
        }
        $source = RichText::normalizePlain(implode(' ', array_filter($snapshot['source_fields'] ?? [], 'is_string')));
        foreach ($brief['fact_sources'] ?? [] as $fact) {
            if (is_array($fact) && ! empty($fact['verified_by'])) {
                $source .= ' '.($fact['quote'] ?? '');
            }
        }
        $newText = RichText::normalizePlain(implode(' ', $clean));
        preg_match_all('/\d+(?:[.,\/-]\d+)*/u', $newText, $newNumbers);
        preg_match_all('/\d+(?:[.,\/-]\d+)*/u', $source, $oldNumbers);
        if ($unknown = array_diff(array_unique($newNumbers[0]), array_unique($oldNumbers[0]))) {
            $missingFacts[] = 'Các số liệu mới cần nguồn xác nhận: '.implode(', ', array_slice($unknown, 0, 20));
        }
        foreach (['miễn visa', 'miễn thị thực', 'hoàn tiền', 'cam kết', 'bảo đảm', 'đảm bảo', 'tốt nhất', 'rẻ nhất', 'hàng đầu'] as $claim) {
            if (Str::contains(Str::lower($newText), $claim) && ! Str::contains(Str::lower($source), $claim)) {
                $missingFacts[] = 'Claim cần nguồn nghiệp vụ: '.$claim;
            }
        }
        foreach (['title', 'name', 'hero_title'] as $shared) {
            if (array_key_exists($shared, $clean)) {
                $warnings[] = 'Thay '.$shared.' có thể ảnh hưởng H1, card, breadcrumb và schema liên quan.';
            }
        }

        return ['patch' => $clean, 'before' => $before, 'missing_facts' => array_values(array_unique($missingFacts)), 'warnings' => $warnings, 'requires_human' => true];
    }
}
