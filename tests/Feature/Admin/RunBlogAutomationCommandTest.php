<?php

namespace Tests\Feature\Admin;

use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Src\Domains\Cms\Models\BlogPost;
use Tests\TestCase;

class RunBlogAutomationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_blog_post_from_reference_links_via_command(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        config()->set('services.openai.api_key', 'test-key');
        config()->set('seo_ai.base_url', 'https://api.openai.test/v1');

        Http::fake([
            'https://example.com/doi-thu/*' => Http::response($this->referenceHtml(), 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]),
            'https://api.openai.test/v1/responses' => Http::response([
                'output_text' => json_encode([
                    'title' => 'Bài blog tạo từ automation command',
                    'slug' => 'bai-blog-tao-tu-automation-command',
                    'excerpt' => 'Bản tóm tắt do luồng automation tạo ra.',
                    'content' => '<h2>Tổng quan</h2><p>Nội dung đã được biên tập lại từ nguồn tham chiếu.</p>',
                    'meta_title' => 'Bài blog tạo từ automation command',
                    'meta_description' => 'Meta description do automation tạo.',
                    'og_title' => 'Bài blog tạo từ automation command',
                    'og_description' => 'OG description do automation tạo.',
                    'author_name' => 'Ban biên tập',
                    'cover_alt' => 'Cover alt automation',
                    'robots_directive' => 'index,follow',
                    'status' => 'draft',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ], 200),
        ]);

        $tempPath = base_path('storage/framework/testing/blog-automation-test.json');
        File::ensureDirectoryExists(dirname($tempPath));
        File::put($tempPath, json_encode([
            'title' => 'Bài blog tạo từ automation command',
            'content_category_slug' => 'kien-thuc-xay-dung',
            'reference_urls' => [
                'https://example.com/doi-thu/bai-1',
                'https://example.com/doi-thu/bai-2',
            ],
            'max_references' => 2,
            'status' => 'draft',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $exitCode = Artisan::call('blog:automation:run', [
            'configPath' => $tempPath,
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertDatabaseHas('blog_posts', [
            'slug' => 'bai-blog-tao-tu-automation-command',
            'status' => 'draft',
        ]);

        $post = BlogPost::query()->where('slug', 'bai-blog-tao-tu-automation-command')->firstOrFail();

        $this->assertSame('Bài blog tạo từ automation command', $post->title);
        $this->assertSame('Ban biên tập', $post->author_name);
        $this->assertStringContainsString('Nội dung đã được biên tập lại', (string) $post->content);
    }

    protected function referenceHtml(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="vi">
<head>
    <title>AI và camera cho công trường</title>
    <meta name="description" content="Bài đối thủ về AI camera và dashboard tiến độ.">
</head>
<body>
    <article>
        <h1>AI và camera cho công trường</h1>
        <h2>Quan sát tiến độ</h2>
        <p>Doanh nghiệp sử dụng camera để đối chiếu tiến độ thi công theo từng mốc.</p>
        <h2>Cảnh báo sớm</h2>
        <p>Hệ thống có thể phát hiện các điểm lệch tiến độ và hỗ trợ quản lý rủi ro.</p>
    </article>
</body>
</html>
HTML;
    }
}
