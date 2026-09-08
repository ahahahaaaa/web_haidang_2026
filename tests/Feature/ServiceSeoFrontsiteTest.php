<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class ServiceSeoFrontsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_category_page_outputs_collection_page_schema_and_is_listed_in_sitemap(): void
    {
        [$category] = $this->serviceFixture();

        $this->get(route('service-categories.show', ['category' => $category->slug]))
            ->assertOk()
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('data-ai-summary', false)
            ->assertSeeText($category->name)
            ->assertSeeText('Nội dung danh mục visa du lịch')
            ->assertSeeText('Trang category service cho phép biên tập nội dung dài từ CMS.');

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('service-categories.show', ['category' => $category->slug]), false);
    }

    public function test_service_detail_outputs_service_and_faq_schema_with_visible_category_links(): void
    {
        [$category, $service] = $this->serviceFixture();

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('"@type":"Service"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('data-ai-summary', false)
            ->assertSee(route('service-categories.show', ['category' => $category->slug]), false)
            ->assertSeeText('Thi công nhà phố trọn gói thường bao gồm những phần việc nào?')
            ->assertSeeText('Những câu hỏi liên quan đến '.$service->title);
    }

    /**
     * @return array{0: \Src\Domains\Cms\Models\ContentCategory, 1: \Src\Domains\Cms\Models\Service}
     */
    protected function serviceFixture(): array
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'active_theme' => 'haidangtravel',
                'company_name' => 'Hải Đăng Travel',
                'site_name' => 'Hải Đăng Travel',
                'site_description' => 'Dịch vụ du lịch và tư vấn hành trình.',
                'seo_description' => 'Dịch vụ du lịch, visa, vé máy bay và tour đoàn.',
                'phone' => '028 1234 5678',
                'hotline' => '0909 123 456',
                'primary_email' => 'tour@example.com',
            ],
        );

        $category = ContentCategory::query()->create([
            'taxonomy' => 'service',
            'name' => 'Visa du lịch',
            'slug' => 'visa-du-lich',
            'description' => 'Nhóm dịch vụ hỗ trợ visa và hồ sơ đi nước ngoài.',
            'content' => '<h2>Nội dung danh mục visa du lịch</h2><p>Trang category service cho phép biên tập nội dung dài từ CMS.</p>',
            'sort_order' => 1,
        ]);

        $service = Service::query()->create([
            'title' => 'Hỗ trợ hồ sơ visa Hàn Quốc',
            'slug' => 'ho-tro-ho-so-visa-han-quoc',
            'excerpt' => 'Tư vấn checklist giấy tờ, lịch nộp hồ sơ và cách chuẩn bị theo từng nhóm khách.',
            'content' => '<p>Dịch vụ hỗ trợ visa giúp khách chuẩn bị hồ sơ đúng thứ tự và giảm thời gian chỉnh sửa.</p>',
            'status' => 'published',
            'content_category_id' => $category->getKey(),
            'price_note' => 'Liên hệ để nhận checklist phù hợp hồ sơ.',
            'is_featured' => true,
            'faq_items' => [
                [
                    'question' => 'Thi công nhà phố trọn gói thường bao gồm những phần việc nào?',
                    'answer' => 'Phần FAQ seed này được dùng để xác nhận schema FAQ và nội dung hiển thị công khai trên trang dịch vụ.',
                ],
            ],
            'related_questions' => [
                'Hồ sơ cần chuẩn bị trước bao lâu?',
                'Khi nào nên gửi yêu cầu tư vấn?',
            ],
        ]);

        return [$category, $service];
    }
}
