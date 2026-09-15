<?php

namespace Tests\Feature\SeoOptimization;

use App\Models\SeoContentCreationTask;
use App\Models\SeoOptimizationCredential;
use App\Services\SeoOptimization\ContentCreationWorkflowService;
use App\Support\LandingPageBlocks;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Service;

class ContentCreationMcpTest extends OptimizationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['admin.media.index', 'admin.services.categories.edit', 'admin.seo-optimization.approve'] as $permission) {
            $this->writer->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $this->credential->update([
            'abilities' => ['read', 'audit', 'propose', 'create'],
            'allowed_page_types' => ['service', 'service_category'],
        ]);
    }

    public function test_create_ability_exposes_creation_tools_and_creates_service_as_draft(): void
    {
        $tools = $this->rpc('tools/list')->assertOk()->json('result.tools');
        $names = array_column($tools, 'name');
        $this->assertContains('list_cms_content_creation_types', $names);
        $this->assertContains('start_cms_content_creation', $names);
        $this->assertContains('search_cms_content_media', $names);
        $this->assertContains('submit_cms_content_creation', $names);
        $this->assertContains('get_cms_content_creation', $names);

        $start = $this->callTool('start_cms_content_creation', [
            'content_type' => 'service',
            'brief' => $this->brief(),
            'idempotency_key' => 'create-service-draft-1',
        ])->assertOk();
        $this->assertFalse($start->json('result.isError'), $start->getContent());
        $taskId = $start->json('result.structuredContent.task.id');
        $leaseToken = $start->json('result.structuredContent.lease_token');

        $result = $this->callTool('submit_cms_content_creation', [
            'task_id' => $taskId,
            'lease_token' => $leaseToken,
            'payload' => $this->servicePayload(),
            'media_placements' => [],
        ])->assertOk();

        $this->assertFalse($result->json('result.isError'), $result->getContent());
        $this->assertSame('draft', $result->json('result.structuredContent.publish_state'));
        $this->assertFalse($result->json('result.structuredContent.public_content_changed'));
        $this->assertDatabaseHas('services', ['slug' => 'tu-van-tour-nhat-ban', 'status' => 'draft']);
        $this->assertDatabaseHas('seo_content_creation_tasks', ['id' => $taskId, 'status' => 'completed']);
        $this->assertSame('index,follow', Service::query()->where('slug', 'tu-van-tour-nhat-ban')->firstOrFail()->robots_directive);

        $this->callTool('get_cms_content_creation', ['task_id' => $taskId])
            ->assertOk()
            ->assertJsonPath('result.structuredContent.task.status', 'completed');
    }

    public function test_authorized_editor_can_open_content_creation_admin_list(): void
    {
        $this->actingAs($this->writer)
            ->get('/admin/seo-optimization/content-creation')
            ->assertOk()
            ->assertSee('Nội dung Codex tạo mới');
    }

    public function test_idempotency_and_completed_results_cannot_cross_credentials(): void
    {
        $start = $this->callTool('start_cms_content_creation', [
            'content_type' => 'service', 'brief' => $this->brief(), 'idempotency_key' => 'credential-isolation-1',
        ])->assertOk();
        $taskId = $start->json('result.structuredContent.task.id');
        $leaseToken = $start->json('result.structuredContent.lease_token');

        $this->reviewer->givePermissionTo(Permission::findOrCreate('admin.media.index', 'web'));
        $otherToken = 'other-content-creation-token-123456789';
        SeoOptimizationCredential::query()->create([
            'user_id' => $this->reviewer->id,
            'name' => 'Other Codex',
            'token_hash' => hash('sha256', $otherToken),
            'abilities' => ['read', 'audit', 'propose', 'create'],
            'allowed_page_types' => ['service'],
            'expires_at' => now()->addDay(),
        ]);

        $collision = $this->callToolWithToken($otherToken, 'start_cms_content_creation', [
            'content_type' => 'service', 'brief' => $this->brief(), 'idempotency_key' => 'credential-isolation-1',
        ])->assertOk();
        $this->assertTrue($collision->json('result.isError'), $collision->getContent());

        $created = $this->callTool('submit_cms_content_creation', [
            'task_id' => $taskId,
            'lease_token' => $leaseToken,
            'payload' => $this->servicePayload(),
            'media_placements' => [],
        ])->assertOk();
        $this->assertFalse($created->json('result.isError'), $created->getContent());

        $crossCredentialRetry = $this->callToolWithToken($otherToken, 'submit_cms_content_creation', [
            'task_id' => $taskId,
            'lease_token' => str_repeat('x', 64),
            'payload' => $this->servicePayload(),
            'media_placements' => [],
        ])->assertOk();
        $this->assertTrue($crossCredentialRetry->json('result.isError'), $crossCredentialRetry->getContent());
    }

    public function test_server_only_fields_are_rejected_and_do_not_create_content(): void
    {
        $start = $this->callTool('start_cms_content_creation', [
            'content_type' => 'service', 'brief' => $this->brief(), 'idempotency_key' => 'reject-publish-field-1',
        ])->assertOk();
        $payload = [...$this->servicePayload(), 'status' => 'published'];
        $result = $this->callTool('submit_cms_content_creation', [
            'task_id' => $start->json('result.structuredContent.task.id'),
            'lease_token' => $start->json('result.structuredContent.lease_token'),
            'payload' => $payload,
            'media_placements' => [],
        ])->assertOk();

        $this->assertTrue($result->json('result.isError'), $result->getContent());
        $this->assertDatabaseMissing('services', ['slug' => 'tu-van-tour-nhat-ban']);
    }

    public function test_category_payload_waits_for_human_confirmation_before_creating_taxonomy(): void
    {
        $start = $this->callTool('start_cms_content_creation', [
            'content_type' => 'service_category', 'brief' => $this->brief(), 'idempotency_key' => 'create-service-category-1',
        ])->assertOk();
        $result = $this->callTool('submit_cms_content_creation', [
            'task_id' => $start->json('result.structuredContent.task.id'),
            'lease_token' => $start->json('result.structuredContent.lease_token'),
            'payload' => [
                'name' => 'Dịch vụ visa', 'slug' => 'dich-vu-visa',
                'description' => 'Thông tin dịch vụ visa theo từng hồ sơ và điểm đến.',
            ],
            'media_placements' => [],
        ])->assertOk();

        $this->assertSame('ready_for_review', $result->json('result.structuredContent.status'));
        $this->assertDatabaseMissing('content_categories', ['taxonomy' => 'service', 'slug' => 'dich-vu-visa']);
        $task = SeoContentCreationTask::query()->findOrFail($start->json('result.structuredContent.task.id'));
        app(ContentCreationWorkflowService::class)->approveManual($task, $this->writer);
        $this->assertDatabaseHas('content_categories', ['taxonomy' => 'service', 'slug' => 'dich-vu-visa']);
    }

    public function test_uploaded_image_is_stored_as_webp_and_placed_in_cover_and_inline_content(): void
    {
        config(['filesystems.disks.public.url' => 'https://haidangtravel.test/storage']);
        $start = $this->callTool('start_cms_content_creation', [
            'content_type' => 'service', 'brief' => $this->brief(), 'idempotency_key' => 'create-service-with-media-1',
        ])->assertOk();
        $taskId = $start->json('result.structuredContent.task.id');
        $leaseToken = $start->json('result.structuredContent.lease_token');
        $upload = $this->withToken($this->testToken)->post('/mcp/seo-optimization/content-creation/'.$taskId.'/media', [
            'lease_token' => $leaseToken,
            'reference' => 'service_main',
            'image' => UploadedFile::fake()->image('service.png', 96, 64),
            'alt' => 'Tư vấn hành trình Nhật Bản',
            'prompt' => 'Ảnh minh họa tư vấn hành trình Nhật Bản, không chữ và không logo.',
        ])->assertOk();
        $mediaId = $upload->json('asset.media_id');
        $stored = Media::query()->findOrFail($mediaId);
        $this->assertSame('image/webp', $stored->mime_type);

        $payload = $this->servicePayload();
        $payload['slug'] = 'tu-van-tour-nhat-ban-co-anh';
        $payload['content'] = '<h2>Quy trình tư vấn tour Nhật Bản</h2><p>Trao đổi nhu cầu hành trình.</p>[[media:body_image]]<h2>Thông tin cần chuẩn bị</h2><p>Chuẩn bị thời gian dự kiến và nhu cầu của đoàn.</p>';
        $result = $this->callTool('submit_cms_content_creation', [
            'task_id' => $taskId,
            'lease_token' => $leaseToken,
            'payload' => $payload,
            'media_placements' => [
                ['ref' => 'cover_image', 'media_id' => $mediaId, 'slot' => 'cover', 'alt' => 'Tư vấn hành trình Nhật Bản'],
                ['ref' => 'body_image', 'media_id' => $mediaId, 'slot' => 'content', 'alt' => 'Trao đổi nhu cầu tour Nhật Bản', 'caption' => 'Ảnh minh họa tư vấn hành trình.'],
            ],
        ])->assertOk();
        $this->assertFalse($result->json('result.isError'), $result->getContent());

        $service = Service::query()->where('slug', 'tu-van-tour-nhat-ban-co-anh')->firstOrFail();
        $this->assertNotNull($service->getFirstMedia('cover'));
        $this->assertStringContainsString('<figure>', (string) $service->content);
        $this->assertStringContainsString('data-media-id="'.$mediaId.'"', (string) $service->content);
        $this->assertStringNotContainsString('[[media:', (string) $service->content);
    }

    public function test_inline_media_marker_cannot_be_replaced_inside_metadata(): void
    {
        config(['filesystems.disks.public.url' => 'https://haidangtravel.test/storage']);
        $start = $this->callTool('start_cms_content_creation', [
            'content_type' => 'service', 'brief' => $this->brief(), 'idempotency_key' => 'reject-marker-in-meta-1',
        ])->assertOk();
        $taskId = $start->json('result.structuredContent.task.id');
        $leaseToken = $start->json('result.structuredContent.lease_token');
        $upload = $this->withToken($this->testToken)->post('/mcp/seo-optimization/content-creation/'.$taskId.'/media', [
            'lease_token' => $leaseToken,
            'reference' => 'metadata_image',
            'image' => UploadedFile::fake()->image('metadata.png', 96, 64),
            'alt' => 'Ảnh không được chèn vào metadata',
        ])->assertOk();

        $payload = $this->servicePayload();
        $payload['slug'] = 'khong-chen-anh-vao-metadata';
        $payload['meta_title'] = 'Tư vấn tour [[media:metadata_image]]';
        $result = $this->callTool('submit_cms_content_creation', [
            'task_id' => $taskId,
            'lease_token' => $leaseToken,
            'payload' => $payload,
            'media_placements' => [[
                'ref' => 'metadata_image',
                'media_id' => $upload->json('asset.media_id'),
                'slot' => 'content',
                'alt' => 'Ảnh không được chèn vào metadata',
            ]],
        ])->assertOk();

        $this->assertTrue($result->json('result.isError'), $result->getContent());
        $this->assertDatabaseMissing('services', ['slug' => 'khong-chen-anh-vao-metadata']);
    }

    public function test_revoked_content_permission_blocks_later_image_upload(): void
    {
        $start = $this->callTool('start_cms_content_creation', [
            'content_type' => 'service', 'brief' => $this->brief(), 'idempotency_key' => 'revoked-upload-permission-1',
        ])->assertOk();
        $this->writer->revokePermissionTo('admin.services.edit');

        $this->withToken($this->testToken)->post('/mcp/seo-optimization/content-creation/'.$start->json('result.structuredContent.task.id').'/media', [
            'lease_token' => $start->json('result.structuredContent.lease_token'),
            'reference' => 'revoked_image',
            'image' => UploadedFile::fake()->image('revoked.png', 96, 64),
            'alt' => 'Ảnh không được upload sau khi thu hồi quyền',
        ])->assertForbidden();

        $this->assertDatabaseCount('seo_content_creation_assets', 0);
    }

    public function test_landing_block_image_is_attached_to_the_exact_block_collection(): void
    {
        config(['filesystems.disks.public.url' => 'https://haidangtravel.test/storage']);
        $this->writer->givePermissionTo(Permission::findOrCreate('admin.landing-pages.edit', 'web'));
        $this->credential->update(['allowed_page_types' => ['service', 'service_category', 'landing']]);
        $start = $this->callTool('start_cms_content_creation', [
            'content_type' => 'landing', 'brief' => $this->brief(), 'idempotency_key' => 'landing-block-media-1',
        ])->assertOk();
        $taskId = $start->json('result.structuredContent.task.id');
        $leaseToken = $start->json('result.structuredContent.lease_token');
        $upload = $this->withToken($this->testToken)->post('/mcp/seo-optimization/content-creation/'.$taskId.'/media', [
            'lease_token' => $leaseToken,
            'reference' => 'landing_hero',
            'image' => UploadedFile::fake()->image('landing-hero.png', 160, 90),
            'alt' => 'Hành trình Nhật Bản theo nhu cầu',
        ])->assertOk();
        $block = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HERO_MEDIA);
        $block['uuid'] = 'codex-hero-block';
        $block['title'] = 'Khám phá Nhật Bản theo nhu cầu';
        $block['description'] = 'Thông tin định hướng và tư vấn hành trình phù hợp.';
        $block['media_alt'] = 'Hành trình Nhật Bản theo nhu cầu';

        $result = $this->callTool('submit_cms_content_creation', [
            'task_id' => $taskId,
            'lease_token' => $leaseToken,
            'payload' => [
                'title' => 'Khám phá Nhật Bản theo nhu cầu',
                'slug' => 'kham-pha-nhat-ban-theo-nhu-cau',
                'template_key' => 'generic',
                'editor_mode' => 'blocks',
                'blocks' => [$block],
                'meta_title' => 'Khám phá Nhật Bản theo nhu cầu',
                'meta_description' => 'Khám phá Nhật Bản theo nhu cầu với thông tin định hướng, gợi ý chuẩn bị và hỗ trợ tư vấn hành trình cùng Hải Đăng Travel.',
            ],
            'media_placements' => [[
                'ref' => 'landing_hero',
                'media_id' => $upload->json('asset.media_id'),
                'slot' => 'landing_block',
                'alt' => 'Hành trình Nhật Bản theo nhu cầu',
                'block_uuid' => 'codex-hero-block',
            ]],
        ])->assertOk();

        $this->assertFalse($result->json('result.isError'), $result->getContent());
        $landing = LandingPage::query()->where('slug', 'kham-pha-nhat-ban-theo-nhu-cau')->firstOrFail();
        $this->assertFalse($landing->is_active);
        $this->assertNotNull($landing->getFirstMedia(LandingPageBlocks::mediaCollection('codex-hero-block')));
    }

    private function rpc(string $method, array $params = []): TestResponse
    {
        return $this->withToken($this->testToken)->postJson('/mcp/seo-optimization', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => (object) $params,
        ]);
    }

    private function callTool(string $name, array $arguments = []): TestResponse
    {
        return $this->rpc('tools/call', ['name' => $name, 'arguments' => (object) $arguments]);
    }

    private function callToolWithToken(string $token, string $name, array $arguments = []): TestResponse
    {
        return $this->withToken($token)->postJson('/mcp/seo-optimization', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => (object) $arguments],
        ]);
    }

    private function brief(): array
    {
        return [
            'request' => 'Tạo trang dịch vụ tư vấn tour Nhật Bản.',
            'primary_keyword' => 'tư vấn tour Nhật Bản',
            'search_intent' => 'Đăng ký tư vấn dịch vụ',
            'secondary_keywords' => ['lịch trình Nhật Bản'],
            'entities' => ['Nhật Bản', 'Hải Đăng Travel'],
            'required_topics' => ['quy trình tư vấn', 'chuẩn bị thông tin'],
            'facts' => ['Khách hàng trao đổi nhu cầu với tư vấn viên.'],
        ];
    }

    private function servicePayload(): array
    {
        return [
            'title' => 'Tư vấn tour Nhật Bản',
            'slug' => 'tu-van-tour-nhat-ban',
            'excerpt' => 'Trao đổi nhu cầu và chuẩn bị hành trình Nhật Bản phù hợp.',
            'content' => '<h2>Quy trình tư vấn tour Nhật Bản</h2><p>Hải Đăng Travel tiếp nhận nhu cầu, thời gian dự kiến và mong muốn của khách để hỗ trợ chuẩn bị hành trình.</p>',
            'faq_items' => [['question' => 'Cần chuẩn bị thông tin gì?', 'answer' => '<p>Hãy chuẩn bị thời gian dự kiến và nhu cầu của đoàn.</p>']],
            'meta_title' => 'Tư vấn tour Nhật Bản theo nhu cầu',
            'meta_description' => 'Tư vấn tour Nhật Bản theo nhu cầu, hỗ trợ chuẩn bị thông tin, định hướng hành trình và trao đổi trực tiếp cùng Hải Đăng Travel.',
            'og_title' => 'Tư vấn tour Nhật Bản',
            'og_description' => 'Trao đổi nhu cầu cho hành trình Nhật Bản cùng Hải Đăng Travel.',
        ];
    }
}
