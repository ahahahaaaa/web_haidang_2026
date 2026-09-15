<?php

namespace Tests\Feature\SeoOptimization;

use App\Models\User;
use App\Services\SeoOptimization\ContentCreationWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Src\Domains\Cms\Models\LandingPage;
use Tests\TestCase;

class ContentCreationWriterTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_direct_content_type_is_written_with_a_non_public_lifecycle(): void
    {
        $writer = app(ContentCreationWriter::class);
        $actor = User::factory()->create();

        $country = $writer->create('country', $writer->prepare('country', $this->taxonomyPayload('Nhật Bản', 'nhat-ban')), $actor);
        $region = $writer->create('region', $writer->prepare('region', $this->taxonomyPayload('Đông Bắc Á', 'dong-bac-a')), $actor);
        $destinationPayload = $this->taxonomyPayload('Tokyo', 'tokyo');
        $destinationPayload['country_id'] = $country->id;
        $destinationPayload['region_id'] = $region->id;

        $created = [
            $country,
            $region,
            $writer->create('destination', $writer->prepare('destination', $destinationPayload), $actor),
            $writer->create('tour_category', $writer->prepare('tour_category', $this->taxonomyPayload('Tour mùa hoa', 'tour-mua-hoa')), $actor),
            $writer->create('blog_post', $writer->prepare('blog_post', $this->articlePayload('Kinh nghiệm du lịch Tokyo', 'kinh-nghiem-du-lich-tokyo')), $actor),
            $writer->create('service', $writer->prepare('service', $this->articlePayload('Tư vấn visa Nhật Bản', 'tu-van-visa-nhat-ban')), $actor),
            $writer->create('tour', $writer->prepare('tour', [
                ...$this->articlePayload('Tour Nhật Bản mùa hoa', 'tour-nhat-ban-mua-hoa'),
                'scope' => 'international',
                'duration_days' => 5,
                'duration_nights' => 4,
                'itinerary' => [['title' => 'Ngày đầu', 'content' => '<p>Đón khách và bắt đầu hành trình.</p>']],
                'inclusions' => ['Tư vấn chương trình'],
                'tour_terms_items' => [['question' => 'Điều kiện', 'answer' => '<p>Thông tin được xác nhận khi tư vấn.</p>']],
            ]), $actor),
        ];

        foreach ($created as $model) {
            $this->assertSame('draft', $model->status);
            $this->assertNull($model->published_at ?? null);
        }

        $landing = $writer->create('landing', $writer->prepare('landing', [
            'title' => 'Cẩm nang Nhật Bản',
            'slug' => 'cam-nang-nhat-ban',
            'template_key' => 'generic',
            'editor_mode' => 'blocks',
            'blocks' => [[
                'uuid' => 'intro-block', 'type' => 'rich_text', 'is_enabled' => true,
                'title' => 'Chuẩn bị hành trình', 'body' => '<h2>Thông tin cần biết</h2><p>Nội dung hướng dẫn cho hành trình.</p>',
            ]],
            'meta_title' => 'Cẩm nang Nhật Bản cho hành trình',
            'meta_description' => 'Cẩm nang Nhật Bản giúp chuẩn bị thông tin, định hướng lịch trình và các lưu ý cần thiết trước hành trình.',
        ]), $actor);

        $this->assertInstanceOf(LandingPage::class, $landing);
        $this->assertFalse($landing->is_active);
        $this->assertNull($landing->page_key);

        $htmlLanding = $writer->create('landing', $writer->prepare('landing', [
            'title' => 'Cẩm nang Osaka',
            'slug' => 'cam-nang-osaka',
            'template_key' => 'generic',
            'editor_mode' => 'html',
            'body' => '<section><h2>Chuẩn bị hành trình Osaka</h2><p>Nội dung HTML thủ công an toàn.</p></section>',
            'meta_title' => 'Cẩm nang Osaka cho hành trình',
            'meta_description' => 'Cẩm nang Osaka giúp chuẩn bị thông tin, định hướng lịch trình và các lưu ý cần thiết trước khi bắt đầu hành trình.',
        ]), $actor);
        $this->assertFalse($htmlLanding->is_active);
        $this->assertSame(LandingPage::EDITOR_MODE_HTML, $htmlLanding->editor_mode);
        $this->assertStringContainsString('Chuẩn bị hành trình Osaka', (string) $htmlLanding->body);
    }

    public function test_manual_landing_html_rejects_non_http_external_urls(): void
    {
        $this->expectException(ValidationException::class);

        app(ContentCreationWriter::class)->prepare('landing', [
            'title' => 'Landing không an toàn',
            'slug' => 'landing-khong-an-toan',
            'template_key' => 'generic',
            'editor_mode' => 'html',
            'body' => '<h2>Nội dung</h2><a href="file:///etc/passwd">Mở tệp</a>',
            'meta_title' => 'Landing không an toàn',
            'meta_description' => 'Mô tả đủ dài cho landing nhưng chứa một liên kết không được phép trong phần HTML thủ công của nội dung.',
        ]);
    }

    private function articlePayload(string $title, string $slug): array
    {
        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => 'Thông tin hữu ích giúp người đọc chuẩn bị hành trình phù hợp.',
            'content' => '<h2>Thông tin cần biết</h2><p>Nội dung hướng dẫn dựa trên dữ kiện đã xác minh.</p>',
            'faq_items' => [['question' => 'Cần chuẩn bị gì?', 'answer' => '<p>Chuẩn bị nhu cầu và thời gian dự kiến.</p>']],
            'meta_title' => $title,
            'meta_description' => 'Thông tin chi tiết và hướng dẫn cần thiết giúp người đọc chuẩn bị hành trình phù hợp theo nhu cầu thực tế.',
        ];
    }

    private function taxonomyPayload(string $name, string $slug): array
    {
        return [
            'name' => $name,
            'slug' => $slug,
            'excerpt' => 'Tổng quan điểm đến và lựa chọn hành trình phù hợp.',
            'content' => '<h2>Tổng quan</h2><p>Thông tin định hướng giúp người đọc lựa chọn hành trình.</p>',
            'meta_title' => $name.' | Hải Đăng Travel',
            'meta_description' => 'Khám phá thông tin tổng quan, gợi ý chuẩn bị và lựa chọn hành trình phù hợp cùng Hải Đăng Travel.',
        ];
    }
}
