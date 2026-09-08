<?php

namespace App\Services\SeoOptimization;

use App\Support\RichText;
use Illuminate\Support\Str;

class PageAuditService
{
    public const WEIGHTS = ['intent' => 15, 'metadata' => 10, 'primary' => 10, 'secondary' => 10, 'entity' => 15, 'topic' => 15, 'heading' => 5, 'links' => 10, 'schema_media' => 5, 'trust' => 5];

    public function evaluate(array $snapshot, array $brief): array
    {
        $text = $snapshot['text'] ?? RichText::normalizePlain($snapshot['html'] ?? '');
        $primary = trim($brief['primary_keyword'] ?? '');
        $issues = [];
        $dimensions = [];
        $add = function (string $dimension, ?float $score, string $status, string $evidence, array $extra = []) use (&$dimensions): void {
            $dimensions[$dimension] = ['rule_id' => Str::upper($dimension).'.BASELINE', 'dimension' => $dimension, 'weight' => self::WEIGHTS[$dimension], 'score' => $score, 'status' => $status, 'evidence' => $evidence, ...$extra];
        };
        $metadata = [(string) ($snapshot['title'] ?? ''), (string) ($snapshot['meta_description'] ?? ''), (string) ($snapshot['h1'] ?? '')];
        $metaCount = count(array_filter($metadata, fn ($v) => trim($v) !== ''));
        $add('metadata', $metaCount / 3 * 100, $metaCount === 3 ? 'PASS' : 'MISSING', 'Title, meta description và H1 hiện có '.$metaCount.'/3 trường.');
        $headingCount = $snapshot['h1_count'] ?? 0;
        $levels = array_column($snapshot['headings'] ?? [], 'level');
        $jump = false;
        foreach ($levels as $index => $level) {
            $jump = $jump || ($index > 0 && $level > $levels[$index - 1] + 1);
        }
        $add('heading', $headingCount === 1 ? ($jump ? 50 : 100) : 0, $headingCount === 1 && ! $jump ? 'PASS' : 'WEAK', 'Có '.$headingCount.' H1; '.($jump ? 'có bước nhảy heading.' : 'không phát hiện bước nhảy heading.'));
        if ($headingCount !== 1) {
            $issues[] = ['rule_id' => 'HEADING.H1', 'severity' => 'P1', 'message' => 'Trang cần có đúng một H1 trong vùng nội dung chính.'];
        }
        $primaryMatches = array_map(fn ($v) => $primary !== '' && $this->contains($v, $primary), [...$metadata, implode(' ', array_slice(preg_split('/\s+/u', $text) ?: [], 0, 150)), $text]);
        $add('primary', $primary === '' ? null : count(array_filter($primaryMatches)) / 5 * 100, $primary === '' ? 'NEED_DATA' : 'WEAK', 'Đối chiếu từ khóa chính tại title/meta/H1/mở đầu/thân bài; biến thể tự nhiên cần người đánh giá.', ['matches' => $primaryMatches]);
        foreach (['secondary' => 'secondary_keywords', 'entity' => 'entities', 'topic' => 'required_topics'] as $dimension => $key) {
            $terms = $brief[$key] ?? [];
            $found = array_values(array_filter($terms, fn ($term) => $this->contains($text, $term)));
            $missing = array_values(array_diff($terms, $found));
            $add($dimension, $terms === [] ? null : count($found) / count($terms) * 100, $terms === [] ? 'NEED_DATA' : ($missing === [] ? 'PASS' : 'MISSING'), 'Kiểm tra xuất hiện từ/cụm từ; độ sâu và đúng nghĩa cần reviewer xác nhận.', ['found' => $found, 'missing' => $missing]);
        }
        $links = array_column($snapshot['internal_links'] ?? [], 'url');
        $required = $brief['required_internal_links'] ?? [];
        $missingLinks = array_values(array_filter($required, fn ($link) => ! collect($links)->contains(fn ($url) => $url === $link || parse_url($url, PHP_URL_PATH) === $link)));
        $add('links', $required === [] ? (count($links) > 0 ? 50 : 0) : (count($required) - count($missingLinks)) / count($required) * 100, $missingLinks === [] && $links !== [] ? 'PASS' : 'MISSING', 'Đã trích '.count($links).' liên kết nội bộ; HTTP đích và độ phù hợp ngữ nghĩa cần kiểm tra riêng.', ['missing' => $missingLinks]);
        $schema = ! empty($snapshot['structured_data']) && empty($snapshot['schema_errors']);
        $media = $snapshot['media'] ?? [];
        $withAlt = count(array_filter($media, fn ($item) => trim($item['alt'] ?? '') !== ''));
        $add('schema_media', ($schema ? 50 : 0) + ($media === [] ? 0 : $withAlt / count($media) * 50), $schema ? 'PASS' : 'MISSING', 'Kiểm tra cú pháp JSON-LD và alt; schema đúng facts/visible content cần reviewer xác nhận.');
        $add('intent', null, 'NEED_DATA', 'Intent dự kiến: '.($brief['search_intent'] ?? 'chưa có').'. Chưa có đánh giá ngữ nghĩa đã được xác nhận.');
        $add('trust', null, 'NEED_DATA', 'Nguồn facts và freshness phải được người phụ trách xác nhận; không suy ra từ việc nội dung đã public.');
        if (($snapshot['http_status'] ?? 0) !== 200 || ($snapshot['classification'] ?? '') !== 'INDEXABLE') {
            $issues[] = ['rule_id' => 'TECHNICAL.INDEXABILITY', 'severity' => 'P1', 'message' => 'Trang chưa đáp ứng điều kiện public/indexable.'];
        }
        if (($snapshot['canonical'] ?? '') === '' || rtrim($snapshot['canonical'] ?? '', '/') !== rtrim($snapshot['url'] ?? '', '/')) {
            $issues[] = ['rule_id' => 'TECHNICAL.CANONICAL', 'severity' => 'P1', 'message' => 'Canonical thiếu hoặc khác URL hiện tại; cần kiểm tra owner.'];
        }
        foreach ($dimensions as $rule) {
            if (in_array($rule['status'], ['MISSING', 'WEAK']) && ($rule['score'] ?? 100) < 100) {
                $issues[] = ['rule_id' => $rule['rule_id'], 'severity' => 'P2', 'message' => $rule['evidence']];
            }
        }

        return ['status' => $primary === '' ? 'unmapped' : 'partial', 'score' => null, 'grade' => null,
            'acceptance_status' => 'ACTION_REQUIRED', 'dimensions' => $dimensions, 'issues' => $issues,
            'assessed_dimensions' => count(array_filter($dimensions, fn ($d) => $d['score'] !== null)), 'total_dimensions' => 10,
            'rule_version' => config('seo_optimization.rule_version'), 'scope' => 'technical_and_lexical',
            'notice' => 'Chưa có điểm tổng hợp: intent, độ sâu nội dung và facts cần đánh giá. Điểm từng nhóm là kiểm tra kỹ thuật/từ vựng, không cam kết thứ hạng.'];
    }

    public function grade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'PASS', $score >= 75 => 'IMPROVE', $score >= 50 => 'WEAK', default => 'FAIL'
        };
    }

    private function contains(string $text, string $term): bool
    {
        return $term !== '' && Str::contains(Str::lower($text), Str::lower($term));
    }
}
