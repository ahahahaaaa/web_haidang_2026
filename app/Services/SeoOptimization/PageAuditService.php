<?php

namespace App\Services\SeoOptimization;

use App\Support\LandingPageBlocks;
use App\Support\RichText;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Str;

class PageAuditService
{
    public const WEIGHTS = [
        'crawl' => 10,
        'keyword_ownership' => 8,
        'intent' => 10,
        'metadata' => 10,
        'people_first' => 15,
        'topic' => 10,
        'entity' => 8,
        'structure' => 7,
        'links' => 7,
        'media' => 4,
        'schema' => 4,
        'trust' => 7,
    ];

    private const DIMENSION_LABELS = [
        'crawl' => 'Khả năng thu thập dữ liệu',
        'keyword_ownership' => 'Quyền sở hữu từ khóa',
        'intent' => 'Ý định tìm kiếm',
        'metadata' => 'Metadata',
        'people_first' => 'Nội dung hữu ích',
        'topic' => 'Chủ đề bắt buộc',
        'entity' => 'Thực thể cần đề cập',
        'structure' => 'Cấu trúc nội dung',
        'links' => 'Liên kết nội bộ',
        'media' => 'Hình ảnh và alt',
        'schema' => 'Dữ liệu có cấu trúc',
        'trust' => 'Độ tin cậy',
    ];

    public function evaluate(array $snapshot, array $brief): array
    {
        $primary = trim((string) ($brief['primary_keyword'] ?? ''));
        $text = trim((string) ($snapshot['text'] ?? RichText::normalizePlain($snapshot['html'] ?? '')));
        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = count($words);
        $dimensions = [];
        $issues = [];

        if (($snapshot['classification'] ?? '') === 'DRAFT_OR_PRIVATE'
            || in_array((int) ($snapshot['http_status'] ?? 0), [401, 403], true)
            || ($snapshot['scope_violation'] ?? false) === true) {
            $issues[] = $this->issue(
                'ACCESS_SCOPE.V2',
                'P0',
                'Snapshot thuộc nội dung riêng tư hoặc nằm ngoài phạm vi public được phép audit.',
            );
        }

        $this->dimension($dimensions, 'crawl', $this->crawlScore($snapshot), 'HTTP, indexability và canonical được kiểm tra từ bản render public.');

        if ($primary === '') {
            $this->dimension($dimensions, 'keyword_ownership', null, 'Chưa có từ khóa chính được gán.', 'NEED_DATA');
            $this->dimension($dimensions, 'intent', null, 'Chưa có từ khóa chính để đối chiếu intent.', 'NEED_DATA');
            $this->dimension($dimensions, 'metadata', $this->metadataScore($snapshot, ''), 'Kiểm tra độ đầy đủ title, H1, meta description và OG; chưa đối chiếu keyword.');
        } else {
            $role = Str::upper((string) ($brief['keyword_role'] ?? 'OWNER'));
            $this->dimension($dimensions, 'keyword_ownership', $role === 'OWNER' ? 100 : 40, 'Vai trò từ khóa của trang: '.$role.'.');
            $this->dimension($dimensions, 'intent', $this->intentScore($snapshot, $primary, $brief), 'Đối chiếu từ khóa, intent, phần mở đầu và topic bắt buộc.');
            $this->dimension($dimensions, 'metadata', $this->metadataScore($snapshot, $primary), 'Kiểm tra title, H1, meta description, OG và mức dùng từ khóa tự nhiên.');
        }

        $minimumWords = $this->minimumWords((string) ($snapshot['page_type'] ?? ''));
        $peopleScore = min(100, $minimumWords > 0 ? ($wordCount / $minimumWords) * 100 : 100);
        if ($wordCount >= 80 && count($snapshot['headings'] ?? []) >= 2) {
            $peopleScore = min(100, $peopleScore + 10);
        }
        $this->dimension($dimensions, 'people_first', $peopleScore, 'Nội dung có '.$wordCount.' từ; baseline nội bộ cho loại trang là '.$minimumWords.' từ.');

        $this->coverageDimension($dimensions, 'topic', $text, $brief['required_topics'] ?? [], 'topic bắt buộc');
        $this->coverageDimension($dimensions, 'entity', $text, $brief['entities'] ?? [], 'entity');
        $this->dimension($dimensions, 'structure', $this->structureScore($snapshot), 'Kiểm tra một H1, thứ bậc heading và khả năng quét nội dung.');
        $this->dimension($dimensions, 'links', $this->linksScore($snapshot, $brief), 'Kiểm tra liên kết nội bộ hiện có và các đích bắt buộc.');
        $this->dimension($dimensions, 'media', $this->mediaScore($snapshot), 'Kiểm tra ảnh và alt theo ngữ cảnh.');
        $this->dimension($dimensions, 'schema', $this->schemaScore($snapshot), 'Kiểm tra JSON-LD parse được; facts mới vẫn cần khớp nguồn hoặc dữ liệu domain.');

        $sensitiveClaims = $this->sensitiveClaims($text);
        $baselineClaims = $this->sensitiveClaims((string) ($snapshot['_seo_original_text'] ?? $text));
        $newSensitiveClaims = array_values(array_diff($sensitiveClaims, $baselineClaims));
        $verifiedSources = collect($brief['fact_sources'] ?? [])->filter(fn ($source): bool => is_array($source) && filled($source['verified_by'] ?? null))->count();
        $trustScore = $newSensitiveClaims === [] ? 100 : ($verifiedSources > 0 ? 90 : 0);
        $trustEvidence = match (true) {
            $sensitiveClaims === [] => 'Không phát hiện claim nhạy cảm mới cần nguồn trong nội dung.',
            $newSensitiveClaims === [] => 'Claim nhạy cảm đang có thuộc baseline CMS hiện hữu; không yêu cầu tài liệu ngoài khi giữ nguyên ý nghĩa.',
            default => 'Phát hiện claim nhạy cảm mới: '.implode(', ', $newSensitiveClaims).'; có '.$verifiedSources.' nguồn đã xác minh.',
        };
        $this->dimension($dimensions, 'trust', $trustScore, $trustEvidence);

        $dimensionDetails = $this->dimensionDetails($snapshot, $brief);
        foreach ($dimensions as $name => &$dimension) {
            $dimension['details'] = $dimensionDetails[$name] ?? [];
        }
        unset($dimension);

        foreach ($dimensions as $dimension) {
            if ($dimension['score'] === null) {
                $issues[] = $this->issue(
                    $dimension['rule_id'],
                    $this->missingDataSeverity($dimension['dimension']),
                    $dimension['evidence'],
                    'NEED_DATA',
                );

                continue;
            }

            $dimensionScore = (float) $dimension['score'];
            $severity = match (true) {
                $dimension['dimension'] === 'crawl' && $dimensionScore < 100 => 'P1',
                $dimension['dimension'] === 'trust' && $dimensionScore === 0.0 => 'P1',
                $dimension['dimension'] === 'keyword_ownership' && $dimensionScore < 100 => 'P1',
                $dimension['dimension'] === 'intent' && $dimensionScore < 50 => 'P1',
                $dimensionScore < 80 => 'P2',
                $dimensionScore < 100 => 'P3',
                default => null,
            };

            if ($severity !== null) {
                $issues[] = $this->issue($dimension['rule_id'], $severity, $dimension['evidence']);
            }
        }

        $assessed = count(array_filter($dimensions, fn (array $dimension): bool => $dimension['score'] !== null));
        $missingDimensions = array_values(array_map(fn (array $dimension): array => [
            'rule_id' => $dimension['rule_id'],
            'dimension' => $dimension['dimension'],
            'label' => self::DIMENSION_LABELS[$dimension['dimension']] ?? $dimension['dimension'],
            'severity' => $this->missingDataSeverity($dimension['dimension']),
            'evidence' => $dimension['evidence'],
        ], array_filter($dimensions, fn (array $dimension): bool => $dimension['score'] === null)));
        $score = $assessed === count(self::WEIGHTS)
            ? round(array_sum(array_map(fn (array $dimension): float => self::WEIGHTS[$dimension['dimension']] * (float) $dimension['score'] / 100, $dimensions)), 1)
            : null;
        $hasP0 = collect($issues)->contains(fn (array $issue): bool => $issue['severity'] === 'P0');
        $hasP1 = collect($issues)->contains(fn (array $issue): bool => $issue['severity'] === 'P1');
        if ($score !== null && $hasP0) {
            $score = min($score, 49.9);
        } elseif ($score !== null && $hasP1) {
            $score = min($score, 79.9);
        }

        $grade = $score === null ? null : $this->grade($score);
        $acceptance = $score !== null && $score >= 90 && ! $hasP0 && ! $hasP1 ? 'PASS' : 'ACTION_REQUIRED';
        $accessibility = $this->accessibilityScore($snapshot);
        $searchConsole = $this->searchConsoleReadiness($snapshot);

        return [
            'status' => $score === null ? ($primary === '' ? 'unmapped' : 'partial') : Str::lower($this->scoreBand($score)),
            'score' => $score,
            'grade' => $grade,
            'score_band' => $score === null ? null : $this->scoreBand($score),
            'acceptance_status' => $acceptance,
            'dimensions' => array_values($dimensions),
            'issues' => $issues,
            'assessed_dimensions' => $assessed,
            'total_dimensions' => count(self::WEIGHTS),
            'assessment_coverage' => round($assessed / count(self::WEIGHTS) * 100, 1),
            'missing_dimensions' => $missingDimensions,
            'companion_scores' => [
                'accessibility' => ['score' => $accessibility, 'grade' => $this->grade($accessibility), 'standard' => 'content-level WCAG/ADA readiness'],
                'search_console_readiness' => ['score' => $searchConsole, 'grade' => $this->grade($searchConsole), 'standard' => 'technical eligibility; not live GSC performance data'],
            ],
            'rule_version' => config('seo_optimization.rule_version'),
            'scope' => 'server_rendered_seo_quality',
            'notice' => 'Điểm SEO là thước đo nội bộ theo rule có bằng chứng; không phải điểm RankMath chính thức hoặc cam kết thứ hạng Google.',
        ];
    }

    public function candidate(array $snapshot, array $patch): array
    {
        $candidate = $snapshot;
        $blockChanges = $patch[ContentWriteContractService::BLOCK_CHANGES] ?? [];
        $attributePatch = $patch;
        unset($attributePatch[ContentWriteContractService::BLOCK_CHANGES]);
        $candidate['_seo_original_text'] = trim(
            (string) ($snapshot['text'] ?? '').' '.implode(' ', array_filter($snapshot['source_fields'] ?? [], 'is_string')),
        );
        $candidate['source_fields'] = array_replace($snapshot['source_fields'] ?? [], $attributePatch);
        $candidate['title'] = (string) ($attributePatch['meta_title'] ?? $attributePatch['title'] ?? $attributePatch['name'] ?? $snapshot['title'] ?? '');
        $candidate['h1'] = (string) ($attributePatch['title'] ?? $attributePatch['name'] ?? $snapshot['h1'] ?? '');
        $candidate['meta_description'] = (string) ($attributePatch['meta_description'] ?? $snapshot['meta_description'] ?? '');

        $richFields = collect($snapshot['field_contracts'] ?? [])
            ->filter(fn (array $definition): bool => in_array($definition['kind'] ?? null, ['rich_html', 'manual_html'], true))
            ->keys();
        if ($richFields->isEmpty()) {
            $richFields = collect(['content', 'body', 'intro_content']);
        }
        $shouldParseRich = $richFields->contains(fn (string $field): bool => array_key_exists($field, $attributePatch));
        $rich = collect($candidate['source_fields'])->only($richFields->all())->filter(fn ($value): bool => is_string($value) && trim($value) !== '')->implode("\n");
        $blockPlain = [];
        $blockFaq = [];
        $units = collect($snapshot['content_units'] ?? [])->keyBy('uuid');
        foreach ($blockChanges as $change) {
            $unit = $units->get((string) ($change['uuid'] ?? ''), []);
            foreach ($change['changes'] ?? [] as $field => $value) {
                $kind = data_get($unit, 'fields.'.$field.'.kind', 'plain_text');
                if ($field === 'media_alt' && is_string($value)) {
                    if (($candidate['media'] ?? []) !== []) {
                        $candidate['media'][0]['alt'] = $value;
                    }
                } elseif (in_array($kind, ['rich_html', 'manual_html'], true) && is_string($value)) {
                    $rich .= "\n".$value;
                    $shouldParseRich = true;
                } elseif ($kind === 'faq' && is_array($value)) {
                    $blockFaq = [...$blockFaq, ...$value];
                } elseif (is_string($value)) {
                    $blockPlain[] = $value;
                }
                if ($field === 'title' && in_array($change['type'] ?? null, [
                    LandingPageBlocks::TYPE_HERO_SLIDER,
                    LandingPageBlocks::TYPE_HERO_MEDIA,
                    LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE,
                ], true)) {
                    $candidate['h1'] = $value;
                }
            }
        }
        if ($rich !== '' && $shouldParseRich) {
            $parsed = $this->parseFragment($rich, (string) ($snapshot['url'] ?? ''));
            $candidate = array_replace($candidate, $parsed);
            $candidate['h1'] = (string) ($candidate['h1'] ?? $snapshot['h1'] ?? '');
            $candidate['h1_count'] = (int) ($snapshot['h1_count'] ?? 1);
        }
        if ($blockPlain !== []) {
            $candidate['text'] = trim(($candidate['text'] ?? '').' '.RichText::normalizePlain(implode(' ', $blockPlain)));
        }

        if (array_key_exists('cover_alt', $patch) && ($candidate['media'] ?? []) !== []) {
            $candidate['media'][0]['alt'] = $patch['cover_alt'];
        }
        $faqItems = [...(is_array($attributePatch['faq_items'] ?? null) ? $attributePatch['faq_items'] : []), ...$blockFaq];
        if ($faqItems !== []) {
            $faqText = collect($faqItems)->flatMap(fn ($item): array => is_array($item)
                ? [(string) ($item['question'] ?? ''), RichText::normalizePlain((string) ($item['answer'] ?? ''))]
                : [])->implode(' ');
            $candidate['text'] = trim(($candidate['text'] ?? '').' '.$faqText);
            if ($faqText !== '') {
                $candidate['headings'][] = ['level' => 2, 'text' => 'Câu hỏi thường gặp'];
            }
        }

        return $candidate;
    }

    /**
     * Return the exact inputs behind each metric for the stored task snapshot and its proposed candidate.
     * Scores are intentionally not recalculated here so old proposals keep their historical rule result.
     *
     * @return array<string, array{before:array<int, array{label:string,value:mixed}>,after:array<int, array{label:string,value:mixed}>}>
     */
    public function comparisonDetails(array $snapshot, array $brief, array $patch): array
    {
        $before = $this->dimensionDetails($snapshot, $brief);
        $after = $this->dimensionDetails($this->candidate($snapshot, $patch), $brief);

        return collect(array_keys(self::WEIGHTS))->mapWithKeys(fn (string $dimension): array => [
            $dimension => [
                'before' => $before[$dimension] ?? [],
                'after' => $after[$dimension] ?? [],
            ],
        ])->all();
    }

    public function grade(float $score): string
    {
        return match (true) {
            $score >= 95 => 'A+',
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 65 => 'C',
            $score >= 50 => 'D',
            default => 'F',
        };
    }

    private function scoreBand(float $score): string
    {
        return match (true) {
            $score >= 90 => 'PASS',
            $score >= 80 => 'IMPROVE',
            $score >= 65 => 'WEAK',
            default => 'FAIL',
        };
    }

    /** @return array<string, array<int, array{label:string,value:mixed}>> */
    private function dimensionDetails(array $snapshot, array $brief): array
    {
        $text = trim((string) ($snapshot['text'] ?? RichText::normalizePlain($snapshot['html'] ?? '')));
        $opening = implode(' ', array_slice(preg_split('/\s+/u', $text) ?: [], 0, 160));
        $primary = trim((string) ($brief['primary_keyword'] ?? ''));
        $topics = $this->coverageDetails($text, $brief['required_topics'] ?? []);
        $entities = $this->coverageDetails($text, $brief['entities'] ?? []);
        $requiredLinks = array_values(array_filter(array_map('strval', $brief['required_internal_links'] ?? [])));
        $currentLinks = collect($snapshot['internal_links'] ?? [])->map(function ($link): string {
            $link = is_array($link) ? $link : [];
            $anchor = trim((string) ($link['anchor'] ?? ''));
            $url = trim((string) ($link['url'] ?? ''));

            return trim(($anchor !== '' ? $anchor.' — ' : '').$url);
        })->filter()->values()->all();
        $headings = collect($snapshot['headings'] ?? [])->map(function ($heading): string {
            $heading = is_array($heading) ? $heading : [];

            return 'H'.(int) ($heading['level'] ?? 0).': '.trim((string) ($heading['text'] ?? ''));
        })->filter(fn (string $heading): bool => ! str_ends_with($heading, ': '))->values()->all();
        $media = collect($snapshot['media'] ?? [])->map(function ($item): string {
            $item = is_array($item) ? $item : [];
            $alt = trim((string) ($item['alt'] ?? ''));
            $src = trim((string) ($item['src'] ?? ''));

            return ($alt !== '' ? $alt : '[Thiếu alt]').($src !== '' ? ' — '.$src : '');
        })->values()->all();
        $schemaTypes = [];
        $this->collectSchemaTypes($snapshot['structured_data'] ?? [], $schemaTypes);
        $schemaErrors = collect($snapshot['schema_errors'] ?? [])->map(
            fn ($error): string => is_string($error) ? $error : json_encode($error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        )->filter()->values()->all();
        $sensitiveClaims = $this->sensitiveClaims($text);
        $baselineClaims = $this->sensitiveClaims((string) ($snapshot['_seo_original_text'] ?? $text));
        $newSensitiveClaims = array_values(array_diff($sensitiveClaims, $baselineClaims));
        $verifiedSources = collect($brief['fact_sources'] ?? [])->filter(
            fn ($source): bool => is_array($source) && filled($source['verified_by'] ?? null),
        )->count();

        return [
            'crawl' => $this->detailRows([
                'URL public' => (string) ($snapshot['url'] ?? ''),
                'HTTP status' => (string) ($snapshot['http_status'] ?? 'N/A'),
                'Phân loại index' => (string) ($snapshot['classification'] ?? 'N/A'),
                'Canonical' => (string) ($snapshot['canonical'] ?? ''),
                'Robots' => (string) ($snapshot['robots'] ?? ''),
            ]),
            'keyword_ownership' => $this->detailRows([
                'Từ khóa chính' => $primary,
                'Vai trò từ khóa' => Str::upper((string) ($brief['keyword_role'] ?? 'OWNER')),
            ]),
            'intent' => $this->detailRows([
                'Từ khóa chính' => $primary,
                'Ý định tìm kiếm' => (string) ($brief['search_intent'] ?? ''),
                'Title' => (string) ($snapshot['title'] ?? ''),
                'H1' => (string) ($snapshot['h1'] ?? ''),
                '160 từ mở đầu' => $this->excerpt($opening, 1200),
                'Topic đã có' => $topics['found'],
                'Topic còn thiếu' => $topics['missing'],
            ]),
            'metadata' => $this->detailRows([
                'Title' => (string) ($snapshot['title'] ?? ''),
                'H1' => (string) ($snapshot['h1'] ?? ''),
                'Meta description' => (string) ($snapshot['meta_description'] ?? ''),
                'Từ khóa chính' => $primary,
            ]),
            'people_first' => $this->detailRows([
                'Số từ' => count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []),
                'Mức tối thiểu theo loại trang' => $this->minimumWords((string) ($snapshot['page_type'] ?? '')),
                'Số heading' => count($snapshot['headings'] ?? []),
                'Trích nội dung' => $this->excerpt($text),
            ]),
            'topic' => $this->detailRows([
                'Topic bắt buộc' => $topics['required'],
                'Đã tìm thấy' => $topics['found'],
                'Còn thiếu' => $topics['missing'],
            ]),
            'entity' => $this->detailRows([
                'Entity bắt buộc' => $entities['required'],
                'Đã tìm thấy' => $entities['found'],
                'Còn thiếu' => $entities['missing'],
            ]),
            'structure' => $this->detailRows([
                'Số H1' => (int) ($snapshot['h1_count'] ?? 0),
                'H1' => (string) ($snapshot['h1'] ?? ''),
                'Thứ tự heading' => $headings,
            ]),
            'links' => $this->detailRows([
                'Liên kết bắt buộc' => $requiredLinks,
                'Liên kết nội bộ đang có' => $currentLinks,
            ]),
            'media' => $this->detailRows([
                'Số ảnh' => count($snapshot['media'] ?? []),
                'Ảnh và alt' => $media,
            ]),
            'schema' => $this->detailRows([
                'Loại schema' => array_values(array_unique($schemaTypes)),
                'Số khối JSON-LD' => count($snapshot['structured_data'] ?? []),
                'Lỗi schema' => $schemaErrors,
            ]),
            'trust' => $this->detailRows([
                'Claim nhạy cảm hiện có' => $sensitiveClaims,
                'Claim nhạy cảm mới' => $newSensitiveClaims,
                'Nguồn đã xác minh' => $verifiedSources,
            ]),
        ];
    }

    /** @return array{required:array<int, string>,found:array<int, string>,missing:array<int, string>} */
    private function coverageDetails(string $text, array $terms): array
    {
        $required = array_values(array_unique(array_filter(array_map(
            fn ($term): string => trim((string) $term),
            $terms,
        ))));
        $found = array_values(array_filter($required, fn (string $term): bool => $this->contains($text, $term)));

        return [
            'required' => $required,
            'found' => $found,
            'missing' => array_values(array_diff($required, $found)),
        ];
    }

    /** @return array<int, array{label:string,value:mixed}> */
    private function detailRows(array $values): array
    {
        return collect($values)->map(fn ($value, string $label): array => [
            'label' => $label,
            'value' => $value,
        ])->values()->all();
    }

    private function excerpt(string $value, int $limit = 700): string
    {
        return Str::limit(trim(preg_replace('/\s+/u', ' ', $value) ?? ''), $limit);
    }

    /** @param array<int, string> $types */
    private function collectSchemaTypes(mixed $node, array &$types): void
    {
        if (! is_array($node)) {
            return;
        }
        if (isset($node['@type'])) {
            foreach ((array) $node['@type'] as $type) {
                if (is_string($type) && trim($type) !== '') {
                    $types[] = trim($type);
                }
            }
        }
        foreach ($node as $value) {
            if (is_array($value)) {
                $this->collectSchemaTypes($value, $types);
            }
        }
    }

    private function dimension(array &$dimensions, string $name, ?float $score, string $evidence, ?string $status = null): void
    {
        $score = $score === null ? null : round(max(0, min(100, $score)), 1);
        $dimensions[$name] = [
            'rule_id' => Str::upper($name).'.V2',
            'dimension' => $name,
            'weight' => self::WEIGHTS[$name],
            'score' => $score,
            'status' => $status ?? match (true) {
                $score === null => 'UNASSESSED',
                $score >= 90 => 'PASS',
                $score >= 70 => 'IMPROVE',
                default => 'FAIL',
            },
            'evidence' => $evidence,
        ];
    }

    private function coverageDimension(array &$dimensions, string $name, string $text, array $terms, string $label): void
    {
        $terms = array_values(array_filter(array_map(fn ($term): string => trim((string) $term), $terms)));
        if ($terms === []) {
            $this->dimension($dimensions, $name, null, 'Chưa có danh sách '.$label.' trong keyword brief.', 'NEED_DATA');

            return;
        }

        $found = array_values(array_filter($terms, fn (string $term): bool => $this->contains($text, $term)));
        $this->dimension($dimensions, $name, count($found) / count($terms) * 100, 'Tìm thấy '.count($found).'/'.count($terms).' '.$label.'.');
    }

    private function missingDataSeverity(string $dimension): string
    {
        return in_array($dimension, ['topic', 'entity', 'links', 'metadata'], true) ? 'P2' : 'P1';
    }

    private function crawlScore(array $snapshot): float
    {
        $score = 0;
        $score += (int) ($snapshot['http_status'] ?? 0) === 200 ? 35 : 0;
        $score += ($snapshot['classification'] ?? '') === 'INDEXABLE' ? 25 : 0;
        $score += filled($snapshot['canonical'] ?? null) ? 15 : 0;
        $score += rtrim((string) ($snapshot['canonical'] ?? ''), '/') === rtrim((string) ($snapshot['url'] ?? ''), '/') ? 25 : 0;

        return $score;
    }

    private function intentScore(array $snapshot, string $primary, array $brief): float
    {
        $score = 0;
        $score += $this->contains((string) ($snapshot['title'] ?? ''), $primary) ? 25 : 0;
        $score += $this->contains((string) ($snapshot['h1'] ?? ''), $primary) ? 25 : 0;
        $opening = implode(' ', array_slice(preg_split('/\s+/u', (string) ($snapshot['text'] ?? '')) ?: [], 0, 160));
        $score += $this->contains($opening, $primary) ? 25 : 0;
        $topics = array_values(array_filter($brief['required_topics'] ?? []));
        $score += $topics === [] ? 0 : 25 * count(array_filter($topics, fn ($topic): bool => $this->contains((string) ($snapshot['text'] ?? ''), (string) $topic))) / count($topics);

        return $score;
    }

    private function metadataScore(array $snapshot, string $primary): float
    {
        $fields = [(string) ($snapshot['title'] ?? ''), (string) ($snapshot['h1'] ?? ''), (string) ($snapshot['meta_description'] ?? '')];
        $score = count(array_filter($fields, fn (string $value): bool => trim($value) !== '')) / 3 * 60;
        if ($primary !== '') {
            $score += count(array_filter($fields, fn (string $value): bool => $this->contains($value, $primary))) / 3 * 40;
        }

        return $score;
    }

    private function structureScore(array $snapshot): float
    {
        $score = (int) ($snapshot['h1_count'] ?? 0) === 1 ? 50 : 0;
        $levels = array_column($snapshot['headings'] ?? [], 'level');
        $jump = false;
        foreach ($levels as $index => $level) {
            if ($index > 0 && $level > $levels[$index - 1] + 1) {
                $jump = true;
            }
        }
        $score += $jump ? 0 : 30;
        $score += count($levels) >= 2 ? 20 : 0;

        return $score;
    }

    private function linksScore(array $snapshot, array $brief): float
    {
        $links = array_column($snapshot['internal_links'] ?? [], 'url');
        $required = array_values(array_filter($brief['required_internal_links'] ?? []));
        if ($required === []) {
            return count($links) >= 2 ? 100 : (count($links) === 1 ? 70 : 0);
        }

        $found = collect($required)->filter(fn ($requiredUrl): bool => collect($links)->contains(fn ($url): bool => rtrim((string) $url, '/') === rtrim((string) $requiredUrl, '/') || parse_url((string) $url, PHP_URL_PATH) === $requiredUrl))->count();

        return $found / count($required) * 100;
    }

    private function mediaScore(array $snapshot): float
    {
        $media = $snapshot['media'] ?? [];
        if ($media === []) {
            return 40;
        }

        return count(array_filter($media, fn (array $item): bool => trim((string) ($item['alt'] ?? '')) !== '')) / count($media) * 100;
    }

    private function schemaScore(array $snapshot): float
    {
        if (($snapshot['schema_errors'] ?? []) !== []) {
            return 0;
        }

        return ($snapshot['structured_data'] ?? []) !== [] ? 100 : 0;
    }

    private function minimumWords(string $pageType): int
    {
        return match ($pageType) {
            'blog_post' => 800,
            'tour' => 650,
            'service' => 500,
            'landing', 'home', 'about' => 450,
            'tour_category', 'destination', 'country', 'region', 'service_category', 'blog_category' => 350,
            default => 250,
        };
    }

    private function sensitiveClaims(string $text): array
    {
        return array_values(array_filter(
            ['miễn visa', 'miễn thị thực', 'hoàn tiền', 'cam kết', 'bảo đảm', 'đảm bảo', 'rẻ nhất', 'tốt nhất', 'hàng đầu'],
            fn (string $claim): bool => $this->contains($text, $claim),
        ));
    }

    private function accessibilityScore(array $snapshot): float
    {
        $media = $snapshot['media'] ?? [];
        $mediaScore = $media === [] ? 100 : count(array_filter($media, fn (array $item): bool => trim((string) ($item['alt'] ?? '')) !== '')) / count($media) * 40;
        if ($media === []) {
            $mediaScore = 40;
        }
        $headings = $snapshot['headings'] ?? [];
        $headingScore = (int) ($snapshot['h1_count'] ?? 0) === 1 ? 20 : 0;
        $jump = false;
        foreach (array_column($headings, 'level') as $index => $level) {
            if ($index > 0 && $level > ($headings[$index - 1]['level'] ?? $level) + 1) {
                $jump = true;
            }
        }
        $headingScore += $jump ? 0 : 20;
        $links = $snapshot['internal_links'] ?? [];
        $descriptive = count(array_filter($links, fn (array $link): bool => ! in_array(Str::lower(trim((string) ($link['anchor'] ?? ''))), ['', 'xem thêm', 'tại đây', 'click here'], true)));
        $linkScore = $links === [] ? 20 : $descriptive / count($links) * 20;

        return round(min(100, $mediaScore + $headingScore + $linkScore), 1);
    }

    private function searchConsoleReadiness(array $snapshot): float
    {
        $score = 0;
        $score += (int) ($snapshot['http_status'] ?? 0) === 200 ? 25 : 0;
        $score += ($snapshot['classification'] ?? '') === 'INDEXABLE' ? 25 : 0;
        $score += filled($snapshot['canonical'] ?? null) && rtrim((string) $snapshot['canonical'], '/') === rtrim((string) ($snapshot['url'] ?? ''), '/') ? 25 : 0;
        $score += ! Str::contains(Str::lower((string) ($snapshot['robots'] ?? '')), 'noindex') ? 25 : 0;

        return (float) $score;
    }

    private function issue(string $rule, string $severity, string $message, ?string $code = null): array
    {
        return ['rule_id' => $rule, 'severity' => $severity, 'code' => $code, 'message' => $message];
    }

    private function parseFragment(string $html, string $url): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML('<?xml encoding="UTF-8"><main>'.$html.'</main>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $main = $xpath->query('//main')->item(0);
        $headings = [];
        foreach ($xpath->query('.//h2 | .//h3 | .//h4 | .//h5 | .//h6', $main) as $node) {
            $headings[] = ['level' => (int) substr($node->nodeName, 1), 'text' => trim($node->textContent)];
        }
        $media = [];
        foreach ($xpath->query('.//img', $main) as $node) {
            $media[] = ['src' => $node->getAttribute('src'), 'alt' => $node->getAttribute('alt'), 'caption' => ''];
        }
        $links = [];
        foreach ($xpath->query('.//a[@href]', $main) as $node) {
            $href = trim($node->getAttribute('href'));
            if ($href !== '' && (str_starts_with($href, '/') || parse_url($href, PHP_URL_HOST) === parse_url($url, PHP_URL_HOST))) {
                $links[] = ['url' => $href, 'anchor' => trim($node->textContent), 'rel' => $node->getAttribute('rel')];
            }
        }

        return [
            'text' => RichText::normalizePlain($html),
            'headings' => [['level' => 1, 'text' => ''], ...$headings],
            'internal_links' => $links,
            'media' => $media,
        ];
    }

    private function contains(string $text, string $term): bool
    {
        return $term !== '' && Str::contains(Str::lower($text), Str::lower($term));
    }
}
