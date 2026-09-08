<?php

namespace Tests\Unit;

use App\Support\ReviewContent;
use PHPUnit\Framework\TestCase;

class ReviewContentTest extends TestCase
{
    public function test_normalize_items_keeps_plain_safe_review_content_and_builds_schema(): void
    {
        $items = ReviewContent::normalizeItems([
            [
                'author_name' => '<strong>Anh Minh</strong>',
                'author_title' => 'Nhóm 4 khách',
                'title' => 'Lịch đi gọn',
                'content' => '<script>alert(1)</script><p>Tour đi gọn và <strong>dễ theo</strong>.</p>',
                'rating_value' => '4.8',
                'published_at' => '2026-04-15',
            ],
        ]);

        $schema = ReviewContent::reviewSchema($items);
        $aggregate = ReviewContent::aggregate($items);

        $this->assertSame('Anh Minh', data_get($items, '0.author_name'));
        $this->assertSame('Tour đi gọn và dễ theo.', data_get($items, '0.content'));
        $this->assertSame('2026-04-15', data_get($items, '0.published_at'));
        $this->assertSame('Review', data_get($schema, '0.@type'));
        $this->assertSame('4.8', data_get($schema, '0.reviewRating.ratingValue'));
        $this->assertSame(1, data_get($aggregate, 'count'));
        $this->assertSame(4.8, data_get($aggregate, 'average_value'));
    }

    public function test_aggregate_prefers_real_review_items_over_fake_rating_summary(): void
    {
        $items = ReviewContent::normalizeItems([
            [
                'author_name' => 'Anh Minh',
                'content' => 'Tour đi gọn.',
                'rating_value' => 5,
            ],
            [
                'author_name' => 'Chị Lan',
                'content' => 'Tư vấn rõ.',
                'rating_value' => 4,
            ],
        ]);

        $aggregate = ReviewContent::aggregate($items, 4.9, 214);

        $this->assertSame(2, data_get($aggregate, 'count'));
        $this->assertSame(4.5, data_get($aggregate, 'average_value'));
    }

    public function test_aggregate_uses_fake_rating_summary_when_no_real_reviews_exist(): void
    {
        $aggregate = ReviewContent::aggregate([], 4.9, 214);

        $this->assertSame(214, data_get($aggregate, 'count'));
        $this->assertSame(4.9, data_get($aggregate, 'average_value'));
    }
}
