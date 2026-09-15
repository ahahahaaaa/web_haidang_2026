<?php

namespace App\Services\SeoOptimization;

use App\Support\RichText;

class ContentCreationQualityService
{
    /** @return array<string, mixed> */
    public function assess(array $payload, array $placements, array $brief): array
    {
        $identity = trim((string) ($payload['title'] ?? $payload['name'] ?? ''));
        $metaTitle = trim((string) ($payload['meta_title'] ?? ''));
        $metaDescription = trim((string) ($payload['meta_description'] ?? $payload['description'] ?? ''));
        $content = (string) ($payload['content'] ?? $payload['body'] ?? '');
        if (is_array($payload['blocks'] ?? null)) {
            $content .= ' '.json_encode($payload['blocks'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $plain = RichText::normalizePlain($content);
        $wordCount = count(preg_split('/\s+/u', $plain, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $primaryKeyword = mb_strtolower(trim((string) ($brief['primary_keyword'] ?? '')));
        $haystack = mb_strtolower(trim($identity.' '.$metaTitle.' '.$metaDescription.' '.$plain));
        $checks = [
            ['id' => 'identity', 'label' => 'Tiêu đề/H1', 'passed' => $identity !== '', 'evidence' => $identity !== '' ? $identity : 'Thiếu tiêu đề.'],
            ['id' => 'slug', 'label' => 'Slug', 'passed' => filled($payload['slug'] ?? null), 'evidence' => (string) ($payload['slug'] ?? 'Thiếu slug.')],
            ['id' => 'meta_title', 'label' => 'SEO title', 'passed' => $metaTitle !== '' && mb_strlen($metaTitle) <= 70, 'evidence' => mb_strlen($metaTitle).' ký tự.'],
            ['id' => 'meta_description', 'label' => 'Meta description', 'passed' => $metaDescription !== '' && mb_strlen($metaDescription) >= 80 && mb_strlen($metaDescription) <= 170, 'evidence' => mb_strlen($metaDescription).' ký tự.'],
            ['id' => 'content', 'label' => 'Nội dung hữu ích', 'passed' => $wordCount >= 300 || is_array($payload['blocks'] ?? null), 'evidence' => $wordCount.' từ trong nội dung văn bản.'],
            ['id' => 'keyword', 'label' => 'Khớp keyword brief', 'passed' => $primaryKeyword !== '' && str_contains($haystack, $primaryKeyword), 'evidence' => $primaryKeyword !== '' ? 'Từ khóa chính: '.$primaryKeyword : 'Thiếu từ khóa chính.'],
            ['id' => 'media_alt', 'label' => 'Media có alt', 'passed' => collect($placements)->every(fn ($item) => trim((string) ($item['alt'] ?? '')) !== ''), 'evidence' => count($placements).' vị trí ảnh đã kiểm tra.'],
        ];
        $passed = collect($checks)->where('passed', true)->count();

        return [
            'status' => $passed === count($checks) ? 'READY' : 'IMPROVE',
            'score' => round($passed / count($checks) * 100, 1),
            'assessed_checks' => count($checks),
            'notice' => 'Đây là điểm sẵn sàng của payload trước publish; audit SEO 12 tiêu chí chỉ thực hiện được sau khi trang public/render được.',
            'checks' => $checks,
        ];
    }
}
