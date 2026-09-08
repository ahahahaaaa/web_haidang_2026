<?php

namespace Tests\Feature\SeoOptimization;

use App\Models\SeoOptimizationAsset;
use App\Models\SeoOptimizationEvent;
use App\Services\Admin\MediaLibraryUploader;
use App\Services\SeoOptimization\OptimizationAutomationService;
use App\Services\SeoOptimization\OptimizationMediaService;
use App\Services\SeoOptimization\OptimizationPolicyService;
use App\Services\SeoOptimization\PageRegistryService;
use App\Services\SeoOptimization\PublicImageDownloader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AutomationIntegrationTest extends OptimizationTestCase
{
    private array $row;

    private bool $loseAck = false;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['media-library.disk_name' => 'public', 'filesystems.disks.public.visibility' => 'public',
            'seo_optimization.spreadsheet_id' => 'test-workbook',
            'seo_optimization.sheet_endpoint' => 'https://script.google.com/macros/s/test-deployment/exec',
            'seo_optimization.sheet_secret' => str_repeat('test-secret-', 4)]);
        $this->writer->givePermissionTo(Permission::findOrCreate('admin.media.index', 'web'));
        $this->credential->update(['abilities' => ['read', 'audit', 'propose', 'automate']]);
        $this->row = ['row_id' => 'row-test', 'site_id' => $this->page->site_id, 'page_id' => $this->page->id,
            'locale' => 'vi', 'page_type' => 'service', 'url' => 'https://haidangtravel.test'.$this->page->path,
            'enabled' => true, 'status' => 'READY', 'priority' => 'P2', 'primary_keyword' => 'tư vấn Đà Nẵng',
            'search_intent' => 'LOCAL_SERVICE', 'secondary_keywords' => [], 'semantic_terms' => [], 'entities' => [],
            'required_topics' => [], 'required_internal_links' => [], 'fact_sources' => [], 'notes' => '',
            'image_url' => '', 'image_prompt' => '', 'image_alt' => '', 'row_revision' => str_repeat('a', 64),
            'task_id' => null, 'proposal_id' => null];
        Http::fake(function ($request) {
            $body = $request->data();
            $this->assertSame(hash_hmac('sha256', $body['timestamp']."\n".$body['nonce']."\n".$body['payload'], config('seo_optimization.sheet_secret')), $body['signature']);
            $payload = json_decode($body['payload'], true);
            if ($payload['operation'] === 'event') {
                if ($this->loseAck && $payload['event']['status'] !== 'CLAIMED') {
                    return Http::response(['ok' => false], 503);
                }
                $this->row['status'] = $payload['event']['status'];
                $this->row['task_id'] = $payload['event']['task_id'];
                $data = ['ack' => true, 'event_id' => $payload['event_id']];
            } else {
                $data = ['row' => $this->row];
            }

            return Http::response(['ok' => true, 'spreadsheet_id' => 'test-workbook', 'data' => $data]);
        });
    }

    public function test_sheet_image_proposal_and_preview_do_not_change_public_content(): void
    {
        [$proposal, $asset] = $this->preparedProposal('preview');
        $result = app(OptimizationAutomationService::class)->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);
        $this->assertSame('in_review', $result['status']);
        $this->assertFalse($result['public_content_changed']);
        $this->assertSame('synced', $result['sheet_sync_status']);
        $this->assertSame('PREVIEW', $this->row['status']);
        $this->assertStringNotContainsString($asset['url'], $this->service->fresh()->content);
    }

    public function test_http_automation_tools_and_completion_follow_server_policy(): void
    {
        $response = $this->withToken($this->testToken)->postJson('/mcp/seo-optimization', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => (object) []])->assertOk();
        $this->assertCount(11, $response->json('result.tools'));
        [$proposal] = $this->preparedProposal('always_publish');
        $this->withToken($this->testToken)->postJson('/mcp/seo-optimization/sync', ['proposal_id' => $proposal->id, 'content_hash' => str_repeat('0', 64)])->assertUnprocessable();
        $this->assertNull($proposal->fresh()->applied_at);
        $this->withToken($this->testToken)->postJson('/mcp/seo-optimization/sync', ['proposal_id' => $proposal->id, 'content_hash' => $proposal->content_hash])->assertOk()->assertJsonPath('public_content_changed', true)->assertJsonPath('status', 'applied');
        $this->credential->update(['abilities' => ['read', 'audit', 'propose']]);
        $this->withToken($this->testToken)->postJson('/mcp/seo-optimization/sync', ['proposal_id' => $proposal->id, 'content_hash' => $proposal->content_hash])->assertForbidden();
    }

    public function test_http_generated_image_upload_requires_media_permission(): void
    {
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);
        $payload = ['lease_token' => $lease['lease_token'], 'alt' => 'Ảnh minh họa hành trình', 'prompt' => 'Minh họa hành trình du lịch', 'image' => UploadedFile::fake()->image('illustration.png', 64, 64)];
        $this->withToken($this->testToken)->post('/mcp/seo-optimization/media/'.$lease['task_id'], $payload, ['Accept' => 'application/json'])->assertOk()->assertJsonPath('status', 'ready');
        $this->writer->revokePermissionTo('admin.media.index');
        $payload['image'] = UploadedFile::fake()->image('illustration.png', 64, 64);
        $this->withToken($this->testToken)->post('/mcp/seo-optimization/media/'.$lease['task_id'], $payload, ['Accept' => 'application/json'])->assertForbidden();
    }

    public function test_auto_publish_and_lost_ack_retry_apply_only_once(): void
    {
        [$proposal, $asset] = $this->preparedProposal('always_publish');
        $this->loseAck = true;
        $automation = app(OptimizationAutomationService::class);
        $first = $automation->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);
        $this->assertTrue($first['public_content_changed']);
        $this->assertSame('pending', $first['sheet_sync_status']);
        $this->assertStringContainsString($asset['url'], $this->service->fresh()->content);
        $this->loseAck = false;
        $retry = $automation->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);
        $this->assertSame('synced', $retry['sheet_sync_status']);
        $this->assertSame('PUBLISHED', $this->row['status'], json_encode($proposal->fresh()->qa, JSON_UNESCAPED_UNICODE));
        $this->assertSame(1, SeoOptimizationEvent::query()->where('proposal_id', $proposal->id)->where('event', 'proposal.applied')->count());
    }

    public function test_changed_sheet_blocks_completion(): void
    {
        [$proposal] = $this->preparedProposal('always_publish');
        $this->row['row_revision'] = str_repeat('b', 64);
        try {
            app(OptimizationAutomationService::class)->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);
            $this->fail('Changed Sheet must block publication.');
        } catch (ValidationException) {
            $this->assertNull($proposal->fresh()->applied_at);
        }
    }

    public function test_tampered_media_manifest_blocks_completion(): void
    {
        [$proposal] = $this->preparedProposal('always_publish');
        $asset = SeoOptimizationAsset::query()->firstOrFail();
        $asset->update(['manifest' => [...$asset->manifest, 'url' => 'https://invalid.example/image.jpg']]);
        $this->expectException(HttpException::class);
        app(OptimizationAutomationService::class)->complete($proposal->id, $proposal->content_hash, $this->writer, $this->credential->id);
    }

    public function test_fake_image_is_rejected_without_creating_asset(): void
    {
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);
        try {
            app(OptimizationMediaService::class)->upload($lease['task_id'], $lease['lease_token'], UploadedFile::fake()->createWithContent('fake.png', '<script>bad</script>'), 'Ảnh minh họa', 'Minh họa hành trình', $this->writer, $this->credential->id);
            $this->fail('Invalid image must fail.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('seo_optimization_assets', 0);
        }
    }

    public function test_same_domain_image_reuses_existing_media(): void
    {
        config(['filesystems.disks.public.url' => 'https://haidangtravel.test/storage']);
        Storage::fake('public', ['url' => 'https://haidangtravel.test/storage']);
        $original = app(MediaLibraryUploader::class)->uploadToLibrary(UploadedFile::fake()->image('existing.png', 64, 64), 'Ảnh hiện có', 'Hành trình');
        $this->row['image_url'] = $original->getUrl();
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);
        $ready = app(OptimizationMediaService::class)->prepare($lease['task_id'], $lease['lease_token'], $this->writer, $this->credential->id);
        $this->assertSame('same_site', $ready['asset']['source_type']);
        $this->assertSame($original->id, $ready['asset']['media_id']);
        $this->assertDatabaseCount('media', 1);
    }

    public function test_external_image_uses_downloader_and_stages_media(): void
    {
        $this->row['image_url'] = 'https://images.example/actual.png';
        $this->partialMock(PublicImageDownloader::class, function ($mock) {
            $mock->shouldReceive('download')->once()->with('https://images.example/actual.png')->andReturn(UploadedFile::fake()->image('actual.png', 64, 64));
        });
        app(OptimizationPolicyService::class)->save($this->reviewer, 'preview', ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);
        $ready = app(OptimizationMediaService::class)->prepare($lease['task_id'], $lease['lease_token'], $this->writer, $this->credential->id);
        $this->assertSame('imported', $ready['asset']['source_type']);
        $this->assertSame($this->row['image_url'], $ready['asset']['source_url']);
        $this->assertSame($lease['source_version'], app(PageRegistryService::class)->descriptor($this->page->fresh())['source_version']);
        $this->assertDatabaseCount('media', 1);
    }

    private function preparedProposal(string $mode): array
    {
        app(OptimizationPolicyService::class)->save($this->reviewer, $mode, ['service'], null);
        $lease = app(OptimizationAutomationService::class)->claimNext($this->writer, $this->credential->id);
        $media = app(OptimizationMediaService::class);
        $prepared = $media->prepare($lease['task_id'], $lease['lease_token'], $this->writer, $this->credential->id);
        $this->assertSame('generation_required', $prepared['status']);
        $this->assertNotEmpty($prepared['suggested_alt']);
        $ready = $media->upload($lease['task_id'], $lease['lease_token'], UploadedFile::fake()->image('illustration.png', 64, 64), 'Ảnh minh họa hành trình', 'Minh họa hành trình du lịch', $this->writer, $this->credential->id);
        $this->assertSame($lease['source_version'], app(PageRegistryService::class)->descriptor($this->page->fresh())['source_version']);
        $this->assertSame($ready['asset_id'], $media->prepare($lease['task_id'], $lease['lease_token'], $this->writer, $this->credential->id)['asset_id']);
        $asset = $ready['asset'];
        $patch = ['content' => $this->service->content.'<p><img src="'.$asset['url'].'" alt="Ảnh minh họa hành trình"></p><p>Ảnh minh họa được tạo bằng AI.</p>'];
        $proposal = $this->workflow->submit($lease['task_id'], $lease['lease_token'], $this->payload($lease, $patch), $this->writer, $this->credential->id, 'submit-sheet-test');
        $this->assertSame('in_review', $proposal->status);

        return [$proposal, $asset];
    }
}
