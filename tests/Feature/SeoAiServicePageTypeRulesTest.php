<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Models\SeoLink;
use Src\Domains\Seo\Models\SeoPage;
use Src\Domains\Seo\Support\SeoPromptFactory;
use Src\Domains\Seo\Support\SeoQaValidator;
use Src\Domains\Seo\Support\SeoSchemaFactory;
use Src\Domains\Seo\Support\SeoUrlBuilder;
use Tests\TestCase;

class SeoAiServicePageTypeRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_and_service_category_page_types_use_canonical_travel_paths(): void
    {
        $options = SeoPageType::adminOptions();

        $this->assertSame('Danh mục dịch vụ', $options[SeoPageType::ServiceCategory->value]);
        $this->assertSame(url('/dich-vu/danh-muc/visa'), app(SeoUrlBuilder::class)->canonicalUrl(SeoPageType::ServiceCategory, 'visa'));
        $this->assertSame(url('/dich-vu/visa'), app(SeoUrlBuilder::class)->canonicalUrl(SeoPageType::Service, 'visa'));
    }

    public function test_service_category_page_type_has_collection_schema_and_qa_rules(): void
    {
        $this->lowerSeoQaThresholds();

        $target = SeoPage::query()->create([
            'page_type' => SeoPageType::Service->value,
            'title' => 'Dịch vụ visa',
            'slug' => 'visa',
            'canonical_url' => url('/dich-vu/visa'),
            'primary_keyword' => 'dịch vụ visa',
            'h1' => 'Dịch vụ visa',
            'excerpt' => 'Dịch vụ visa hỗ trợ hồ sơ, lịch nộp và tư vấn theo từng nhu cầu du lịch.',
            'content' => 'Dịch vụ visa hỗ trợ hồ sơ du lịch, tư vấn giấy tờ và lịch trình phù hợp cho khách.',
            'status' => SeoPageStatus::Published,
        ]);

        $page = SeoPage::query()->create([
            'page_type' => SeoPageType::ServiceCategory->value,
            'title' => 'Danh mục dịch vụ visa',
            'slug' => 'visa',
            'canonical_url' => url('/dich-vu/danh-muc/visa'),
            'primary_keyword' => 'dịch vụ visa du lịch',
            'h1' => 'Dịch vụ visa du lịch',
            'excerpt' => 'Trang danh mục giúp khách chọn đúng dịch vụ visa, hồ sơ và bước tư vấn trước chuyến đi.',
            'content' => str_repeat('Danh mục dịch vụ visa giúp khách chuẩn bị hồ sơ, lịch trình, tư vấn điểm đến, vé máy bay, sim du lịch và các bước hỗ trợ trước chuyến đi. ', 4),
            'meta_title' => 'Dịch vụ visa du lịch | Hải Đăng Travel',
            'meta_description' => 'Danh mục dịch vụ visa du lịch giúp khách chuẩn bị hồ sơ, lịch trình và gửi yêu cầu tư vấn phù hợp trước chuyến đi.',
            'status' => SeoPageStatus::Generated,
        ]);

        SeoLink::query()->create([
            'source_page_id' => $page->getKey(),
            'target_page_id' => $target->getKey(),
            'anchor_text' => 'dịch vụ visa',
            'link_type' => 'related',
            'priority' => 80,
            'status' => 'suggested',
        ]);

        $schema = app(SeoSchemaFactory::class)->forPage($page);
        $report = app(SeoQaValidator::class)->validate($page->forceFill(['schema' => $schema]), 1);

        $this->assertContains('CollectionPage', $report['schema_types']);
        $this->assertContains('BreadcrumbList', $report['schema_types']);
        $this->assertContains('ItemList', $report['schema_types']);
        $this->assertSame(['CollectionPage', 'BreadcrumbList', 'ItemList'], $report['required_schema_types']);
        $this->assertSame('pass', $report['status']);
    }

    public function test_service_page_type_requires_service_schema_and_uses_service_prompt_guidance(): void
    {
        $this->lowerSeoQaThresholds();

        $page = SeoPage::query()->create([
            'page_type' => SeoPageType::Service->value,
            'title' => 'Dịch vụ vé máy bay',
            'slug' => 've-may-bay',
            'canonical_url' => url('/dich-vu/ve-may-bay'),
            'primary_keyword' => 'dịch vụ vé máy bay',
            'secondary_keywords' => ['đặt vé máy bay du lịch'],
            'h1' => 'Dịch vụ vé máy bay du lịch',
            'excerpt' => 'Dịch vụ vé máy bay hỗ trợ đặt chỗ, giữ lịch bay và tư vấn hành lý cho chuyến đi.',
            'content' => str_repeat('Dịch vụ vé máy bay hỗ trợ khách đặt chỗ, tư vấn lịch trình, hành lý, điểm đến và bước chuẩn bị trước chuyến đi. ', 4),
            'meta_title' => 'Dịch vụ vé máy bay du lịch | Hải Đăng Travel',
            'meta_description' => 'Dịch vụ vé máy bay du lịch hỗ trợ đặt chỗ, lịch bay, hành lý và gửi yêu cầu tư vấn phù hợp cho hành trình.',
            'status' => SeoPageStatus::Generated,
        ]);

        $related = SeoPage::query()->create([
            'page_type' => SeoPageType::ServiceCategory->value,
            'title' => 'Danh mục vé máy bay',
            'slug' => 've-may-bay',
            'canonical_url' => url('/dich-vu/danh-muc/ve-may-bay'),
            'primary_keyword' => 'vé máy bay',
            'h1' => 'Danh mục vé máy bay',
            'status' => SeoPageStatus::Published,
        ]);

        SeoLink::query()->create([
            'source_page_id' => $page->getKey(),
            'target_page_id' => $related->getKey(),
            'anchor_text' => 'danh mục vé máy bay',
            'link_type' => 'related',
            'priority' => 70,
            'status' => 'suggested',
        ]);

        $schema = app(SeoSchemaFactory::class)->forPage($page);
        $report = app(SeoQaValidator::class)->validate($page->forceFill(['schema' => $schema]), 1);
        $draftPrompt = app(SeoPromptFactory::class)->draft($page);

        $this->assertContains('Service', $report['schema_types']);
        $this->assertContains('BreadcrumbList', $report['schema_types']);
        $this->assertSame(['Service', 'BreadcrumbList'], $report['required_schema_types']);
        $this->assertSame('pass', $report['status']);
        $this->assertStringContainsString('real travel services', $draftPrompt);
        $this->assertStringContainsString('service scope', $draftPrompt);
    }

    protected function lowerSeoQaThresholds(): void
    {
        config([
            'seo_ai.quality_gates.meta_title_min' => 10,
            'seo_ai.quality_gates.meta_description_min' => 10,
            'seo_ai.quality_gates.service_word_count_min' => 10,
            'seo_ai.quality_gates.service_category_word_count_min' => 10,
            'seo_ai.quality_gates.min_internal_links' => 1,
        ]);
    }
}
