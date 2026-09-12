<?php

namespace Tests\Unit;

use App\Services\SeoOptimization\PageAuditService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PageAuditServiceTest extends TestCase
{
    #[DataProvider('gradeBoundaries')]
    public function test_grade_boundaries(float $score, string $expected): void
    {
        $this->assertSame($expected, (new PageAuditService)->grade($score));
    }

    public static function gradeBoundaries(): array
    {
        return [
            'a plus' => [95.0, 'A+'],
            'maximum' => [100.0, 'A+'],
            'a' => [90.0, 'A'],
            'a upper edge' => [94.9, 'A'],
            'b' => [80.0, 'B'],
            'b upper edge' => [89.9, 'B'],
            'c' => [65.0, 'C'],
            'c upper edge' => [79.9, 'C'],
            'd' => [50.0, 'D'],
            'd upper edge' => [64.9, 'D'],
            'f' => [49.9, 'F'],
        ];
    }

    public function test_p1_keyword_ownership_issue_caps_an_otherwise_strong_score(): void
    {
        $report = (new PageAuditService)->evaluate($this->strongSnapshot(), [
            'primary_keyword' => 'tour Đà Nẵng',
            'keyword_role' => 'SECONDARY',
            'search_intent' => 'TRANSACTIONAL',
            'required_topics' => ['lịch trình'],
            'entities' => ['Hải Đăng Travel'],
            'required_internal_links' => ['/tour', '/lien-he'],
            'fact_sources' => [],
        ]);

        $this->assertSame(79.9, $report['score']);
        $this->assertSame('C', $report['grade']);
        $this->assertSame('ACTION_REQUIRED', $report['acceptance_status']);
        $this->assertTrue(collect($report['issues'])->contains(
            fn (array $issue): bool => $issue['rule_id'] === 'KEYWORD_OWNERSHIP.V2' && $issue['severity'] === 'P1',
        ));
    }

    public function test_missing_primary_keyword_keeps_numeric_score_unassessed(): void
    {
        $report = (new PageAuditService)->evaluate($this->strongSnapshot(), [
            'required_topics' => ['lịch trình'],
            'entities' => ['Hải Đăng Travel'],
        ]);

        $this->assertNull($report['score']);
        $this->assertNull($report['grade']);
        $this->assertSame('unmapped', $report['status']);
        $this->assertLessThan(100, $report['assessment_coverage']);
    }

    public function test_missing_topic_and_entity_are_reported_as_partial_p2_requirements(): void
    {
        $report = (new PageAuditService)->evaluate($this->strongSnapshot(), [
            'primary_keyword' => 'tour Đà Nẵng',
            'keyword_role' => 'OWNER',
            'search_intent' => 'TRANSACTIONAL',
        ]);

        $this->assertNull($report['score']);
        $this->assertNull($report['grade']);
        $this->assertSame('partial', $report['status']);
        $this->assertSame(10, $report['assessed_dimensions']);
        $this->assertSame(12, $report['total_dimensions']);
        $this->assertSame(83.3, $report['assessment_coverage']);
        $this->assertSame(
            ['Chủ đề bắt buộc', 'Thực thể cần đề cập'],
            array_column($report['missing_dimensions'], 'label'),
        );
        $this->assertSame(
            ['P2', 'P2'],
            array_column($report['missing_dimensions'], 'severity'),
        );
        $this->assertTrue(collect($report['issues'])->where('code', 'NEED_DATA')->every(
            fn (array $issue): bool => $issue['severity'] === 'P2',
        ));
    }

    public function test_private_snapshot_is_p0_and_caps_score_below_fifty(): void
    {
        $snapshot = $this->strongSnapshot();
        $snapshot['classification'] = 'DRAFT_OR_PRIVATE';
        $snapshot['http_status'] = 403;
        $report = (new PageAuditService)->evaluate($snapshot, [
            'primary_keyword' => 'tour Đà Nẵng',
            'keyword_role' => 'OWNER',
            'search_intent' => 'TRANSACTIONAL',
            'required_topics' => ['lịch trình'],
            'entities' => ['Hải Đăng Travel'],
            'required_internal_links' => ['/tour', '/lien-he'],
            'fact_sources' => [],
        ]);

        $this->assertSame(49.9, $report['score']);
        $this->assertSame('F', $report['grade']);
        $this->assertTrue(collect($report['issues'])->contains(
            fn (array $issue): bool => $issue['rule_id'] === 'ACCESS_SCOPE.V2' && $issue['severity'] === 'P0',
        ));
    }

    public function test_sensitive_claims_in_the_current_cms_snapshot_are_trusted_as_baseline(): void
    {
        $snapshot = $this->strongSnapshot();
        $snapshot['text'] .= ' Chính sách hiện tại cam kết hoàn tiền theo điều kiện đang công bố.';

        $report = (new PageAuditService)->evaluate($snapshot, $this->strongBrief());
        $trust = collect($report['dimensions'])->firstWhere('dimension', 'trust');

        $this->assertSame(100.0, $trust['score']);
        $this->assertStringContainsString('baseline CMS hiện hữu', $trust['evidence']);
    }

    public function test_new_sensitive_claim_in_a_candidate_still_requires_a_verified_source(): void
    {
        $audit = new PageAuditService;
        $snapshot = $this->strongSnapshot();
        $candidate = $audit->candidate($snapshot, [
            'content' => '<h2>Cam kết mới</h2><p>Hải Đăng Travel cam kết hoàn tiền trong mọi trường hợp.</p>',
        ]);

        $report = $audit->evaluate($candidate, $this->strongBrief());
        $trust = collect($report['dimensions'])->firstWhere('dimension', 'trust');

        $this->assertSame(0.0, $trust['score']);
        $this->assertTrue(collect($report['issues'])->contains(
            fn (array $issue): bool => $issue['rule_id'] === 'TRUST.V2' && $issue['severity'] === 'P1',
        ));
    }

    public function test_metric_details_expose_exact_before_and_after_inputs_without_recalculating_scores(): void
    {
        $audit = new PageAuditService;
        $snapshot = $this->strongSnapshot();
        $afterDescription = 'Tour Đà Nẵng trọn gói với lịch trình rõ ràng và tư vấn theo nhu cầu.';

        $comparison = $audit->comparisonDetails($snapshot, $this->strongBrief(), [
            'meta_description' => $afterDescription,
        ]);
        $beforeMetadata = collect($comparison['metadata']['before'])->keyBy('label');
        $afterMetadata = collect($comparison['metadata']['after'])->keyBy('label');

        $this->assertSame($snapshot['meta_description'], data_get($beforeMetadata->get('Meta description'), 'value'));
        $this->assertSame($afterDescription, data_get($afterMetadata->get('Meta description'), 'value'));
        $this->assertSame($snapshot['title'], data_get($beforeMetadata->get('Title'), 'value'));

        $report = $audit->evaluate($snapshot, $this->strongBrief());
        $this->assertTrue(collect($report['dimensions'])->every(
            fn (array $dimension): bool => isset($dimension['details']) && is_array($dimension['details']),
        ));
    }

    private function strongBrief(): array
    {
        return [
            'primary_keyword' => 'tour Đà Nẵng',
            'keyword_role' => 'OWNER',
            'search_intent' => 'TRANSACTIONAL',
            'required_topics' => ['lịch trình'],
            'entities' => ['Hải Đăng Travel'],
            'required_internal_links' => ['/tour', '/lien-he'],
            'fact_sources' => [],
        ];
    }

    private function strongSnapshot(): array
    {
        $paragraph = 'Tour Đà Nẵng có lịch trình rõ ràng và được Hải Đăng Travel tư vấn theo nhu cầu của khách. ';

        return [
            'url' => 'https://haidangtravel.test/tour-da-nang',
            'http_status' => 200,
            'classification' => 'INDEXABLE',
            'canonical' => 'https://haidangtravel.test/tour-da-nang',
            'robots' => 'index,follow',
            'page_type' => 'other',
            'title' => 'Tour Đà Nẵng | Hải Đăng Travel',
            'h1' => 'Tour Đà Nẵng',
            'h1_count' => 1,
            'meta_description' => 'Tour Đà Nẵng với lịch trình phù hợp, được Hải Đăng Travel tư vấn theo nhu cầu.',
            'text' => str_repeat($paragraph, 35),
            'headings' => [
                ['level' => 1, 'text' => 'Tour Đà Nẵng'],
                ['level' => 2, 'text' => 'Lịch trình'],
            ],
            'internal_links' => [
                ['url' => '/tour', 'anchor' => 'Danh sách tour'],
                ['url' => '/lien-he', 'anchor' => 'Liên hệ tư vấn'],
            ],
            'media' => [['src' => '/tour-da-nang.jpg', 'alt' => 'Tour Đà Nẵng']],
            'structured_data' => [['@type' => 'TouristTrip']],
            'schema_errors' => [],
        ];
    }
}
