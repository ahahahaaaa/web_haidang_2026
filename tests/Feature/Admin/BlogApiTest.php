<?php

namespace Tests\Feature\Admin;

use App\Jobs\Cms\RunBlogAutomationJob;
use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class BlogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_blog_post_via_api_with_library_cover(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $libraryMedia = SiteSetting::query()
            ->findOrFail(1)
            ->addMedia(UploadedFile::fake()->image('api-blog-cover.jpg', 1400, 900))
            ->usingName('API blog cover')
            ->usingFileName('api-blog-cover.jpg')
            ->withCustomProperties(['alt' => 'Alt từ thư viện'])
            ->toMediaCollection('library', 'public');

        $response = $this->postJson(route('api.v1.admin.blogs.store'), [
            'title' => 'Bài blog tạo bằng API',
            'status' => 'draft',
            'content' => "<h2>Mở bài</h2><p>Nội dung blog từ API.</p>",
            'content_category_slug' => 'kien-thuc-xay-dung',
            'author_name' => 'API Editor',
            'cover_alt' => 'Alt cover API',
            'cover_library_media_id' => $libraryMedia->getKey(),
            'meta_title' => 'Bài blog tạo bằng API',
            'meta_description' => 'Mô tả meta cho bài blog tạo bằng API.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Bài blog tạo bằng API')
            ->assertJsonPath('data.author_name', 'API Editor')
            ->assertJsonPath('data.category.slug', 'kien-thuc-xay-dung')
            ->assertJsonPath('data.cover.alt', 'Alt cover API');

        $post = BlogPost::query()->where('slug', 'bai-blog-tao-bang-api')->firstOrFail();

        $this->assertSame('Alt cover API', $post->cover_alt);
        $this->assertSame('draft', $post->status);
        $this->assertNotNull($post->getFirstMedia('cover'));
    }

    public function test_admin_can_update_existing_blog_post_via_api(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $post = BlogPost::query()->where('slug', 'xu-huong-xay-dung-hien-dai-2026')->firstOrFail();

        $response = $this->patchJson(route('api.v1.admin.blogs.update', $post), [
            'title' => 'Blog demo đã cập nhật',
            'excerpt' => 'Phiên bản excerpt mới từ API.',
            'status' => 'published',
            'is_featured' => true,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.title', 'Blog demo đã cập nhật')
            ->assertJsonPath('data.is_featured', true)
            ->assertJsonPath('data.status', 'published');

        $post->refresh();

        $this->assertSame('Blog demo đã cập nhật', $post->title);
        $this->assertTrue($post->is_featured);
        $this->assertSame('published', $post->status);
    }

    public function test_admin_can_dispatch_blog_automation_job_via_api(): void
    {
        Queue::fake();
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $this->actingAs($user);

        $response = $this->postJson(route('api.v1.admin.blogs.automation-jobs.store'), [
            'title' => 'Bài từ automation',
            'content_category_slug' => 'kien-thuc-xay-dung',
            'reference_urls' => [
                'https://example.com/doi-thu/bai-1',
                'https://example.com/doi-thu/bai-2',
            ],
            'max_references' => 2,
            'additional_instructions' => 'Giữ giọng điệu kỹ thuật và thực tế.',
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('meta.queued', true)
            ->assertJsonPath('meta.reference_count', 2);

        Queue::assertPushed(RunBlogAutomationJob::class, function (RunBlogAutomationJob $job) use ($user): bool {
            return $job->actorId === $user->getKey()
                && count($job->payload['reference_urls'] ?? []) === 2;
        });
    }
}
