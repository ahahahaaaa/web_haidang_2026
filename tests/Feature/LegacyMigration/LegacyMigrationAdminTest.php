<?php

namespace Tests\Feature\LegacyMigration;

use App\Models\User;
use App\Modules\LegacyMigration\Exceptions\LegacyImageCountLimitExceeded;
use App\Modules\LegacyMigration\Exceptions\LegacyImageDownloadFailure;
use App\Modules\LegacyMigration\Jobs\ProcessLegacyMigrationUrl;
use App\Modules\LegacyMigration\Livewire\Admin\MigrationManager;
use App\Modules\LegacyMigration\Models\LegacyCastAudit;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use App\Modules\LegacyMigration\Services\LegacyAutomaticProcessor;
use App\Modules\LegacyMigration\Services\LegacyImageDownloader;
use App\Modules\LegacyMigration\Services\LegacyMediaRetry;
use Illuminate\Bus\UniqueLock;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Mockery\MockInterface;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\PublicUrlMapping;
use Src\Domains\Cms\Models\Tour;
use Tests\TestCase;

class LegacyMigrationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    public function test_redirect_is_default_and_existing_preserved_urls_can_be_converted_without_reimporting_content(): void
    {
        config()->set('frontsite_seo.canonical_url', 'https://haidangtravel.com');
        $run = $this->makeRun();
        $url = $this->makeRetryableMediaUrl($run, 1901, '/tin-tuc/url-cu-can-hop-nhat');
        $target = BlogPost::query()->findOrFail($url->target_id);
        $target->update([
            'slug' => 'url-cms-moi',
            'canonical_url' => 'https://haidangtravel.com/tin-tuc/url-cu-can-hop-nhat',
        ]);
        $target->timestamps = false;
        $target->forceFill(['created_at' => '2018-01-02 03:04:05', 'updated_at' => '2020-02-03 04:05:06'])->save();
        $target = $target->fresh();
        $targetBefore = $target->getRawOriginal();
        $urlBefore = $url->fresh()->getRawOriginal();
        $sourceBefore = $url->rootObject()->getRawOriginal();

        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->assertSet('automaticMappingMode', 'cast_and_redirect')
            ->assertSet('mappingMode', 'cast_and_redirect')
            ->assertViewHas('preservedUrlRedirectCount', 1)
            ->assertSee('Chuyển 1 URL sang 301')
            ->call('convertPreservedUrlsToRedirects')
            ->assertHasNoErrors()
            ->assertViewHas('preservedUrlRedirectCount', 0);

        $target->refresh();
        $url->refresh();
        $this->assertSame($targetBefore['content'], $target->getRawOriginal('content'));
        $this->assertSame($targetBefore['created_at'], $target->getRawOriginal('created_at'));
        $this->assertSame($targetBefore['updated_at'], $target->getRawOriginal('updated_at'));
        $this->assertSame('https://haidangtravel.com'.$url->target_path, $target->canonical_url);
        $this->assertSame($sourceBefore, $url->rootObject()->getRawOriginal());
        $this->assertSame($urlBefore['casted_at'], $url->getRawOriginal('casted_at'));
        $this->assertSame('casted', $url->status);
        $this->assertSame('cast_and_redirect', $url->mapping_mode);
        $this->assertSame('/bai-viet/url-cms-moi', $url->target_path);
        $this->assertDatabaseHas('public_url_mappings', [
            'source_path' => '/tin-tuc/url-cu-can-hop-nhat',
            'target_path' => $url->target_path,
            'mode' => PublicUrlMapping::MODE_REDIRECT,
            'status_code' => 301,
            'is_active' => 1,
        ]);
        $audit = LegacyCastAudit::query()->where('staged_url_id', $url->id)->where('action', 'promote_redirect')->sole();
        $this->assertSame('/bai-viet/media-retry-1901', $audit->before_json['target_path']);
        $this->assertSame('/bai-viet/url-cms-moi', $audit->after_json['target_path']);
    }

    public function test_image_count_limit_filter_recognizes_old_and_new_warnings_but_only_latest_results(): void
    {
        $run = $this->makeRun(4);
        $new = $this->makeRetryableMediaUrl($run, 2001, '/tin-tuc/gioi-han-moi');
        $old = $this->makeRetryableMediaUrl($run, 2002, '/tin-tuc/gioi-han-cu');
        $repaired = $this->makeRetryableMediaUrl($run, 2003, '/tin-tuc/da-tai-du');
        $dns = $this->makeRetryableMediaUrl($run, 2004, '/tin-tuc/loi-dns');
        $this->mediaWarning($new, 'image_count_limit');
        $this->mediaWarning($old, 'image_validation');
        $this->mediaWarning($repaired, 'image_validation');
        $this->mediaWarning($dns, 'dns', 'Nguồn ảnh không phân giải được DNS/IP.');
        LegacyCastAudit::query()->create(['run_id' => $run->id, 'staged_url_id' => $repaired->id, 'action' => 'import_media', 'status' => 'completed', 'after_json' => ['warnings' => []]]);
        $this->mediaWarning($this->makeRetryableMediaUrl($this->makeRun(), 2005, '/tin-tuc/khac-phien'), 'image_count_limit');

        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->set('eligibilityFilter', 'image_count_limit')
            ->assertViewHas('urls', fn ($urls) => $urls->pluck('id')->all() === [$new->id, $old->id])
            ->assertViewHas('mediaRetryCount', 2)
            ->assertSee('Tải lại ảnh 2 URL theo bộ lọc')
            ->call('setPage', 2)->assertSet('eligibilityFilter', 'image_count_limit')
            ->set('search', 'gioi-han-cu')->assertViewHas('mediaRetryCount', 1);
    }

    public function test_filtered_media_retry_respects_search_and_preserves_saved_target_mapping_and_source(): void
    {
        $this->configureMediaRetryQueue();
        $run = $this->makeRun(2);
        $url = $this->makeRetryableMediaUrl($run, 2101, '/tin-tuc/can-tai-lai');
        $other = $this->makeRetryableMediaUrl($run, 2102, '/tin-tuc/giu-nguyen');
        $url->update(['mapping_mode' => 'cast_and_redirect', 'redirect_code' => 308]);
        $audit = $this->mediaWarning($url, 'image_validation');
        $this->mediaWarning($other, 'image_count_limit');
        $source = $url->rootObject()->getRawOriginal();
        $before = $url->fresh()->getRawOriginal();
        $target = BlogPost::query()->findOrFail($url->target_id)->getRawOriginal();

        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->set('eligibilityFilter', 'image_count_limit')->set('search', 'can-tai-lai')
            ->set('automaticMappingMode', 'cast_only')->set('automaticImportMedia', false)
            ->call('queueMediaRetry')->assertHasNoErrors()
            ->assertViewHas('mediaRetryCount', 0);

        $this->assertSame('queued', $url->fresh()->status);
        $this->assertSame('casted', $other->fresh()->status);
        foreach (['target_type', 'target_id', 'target_path', 'mapping_mode', 'redirect_code', 'mapped_at', 'casted_at', 'raw_url', 'normalized_path', 'payload_json'] as $field) {
            $this->assertSame($before[$field], $url->fresh()->getRawOriginal($field));
        }
        $this->assertSame($source, $url->rootObject()->getRawOriginal());
        $this->assertSame($target, BlogPost::query()->findOrFail($url->target_id)->getRawOriginal());
        $this->assertSame('warning', $audit->fresh()->status);
        $job = $this->queuedMediaJobs()->sole();
        $this->assertSame($url->id, $job->urlId);
        $this->assertTrue($job->reuseExistingTarget && $job->importMedia);
        $this->assertSame('reuse_existing', $job->strategy);
        $this->assertSame('cast_and_redirect', $job->mappingMode);
        $this->assertSame('overwrite', $job->mergePolicy);
    }

    public function test_casted_media_warning_checkboxes_retry_exact_selection_and_reject_foreign_ids(): void
    {
        $this->configureMediaRetryQueue();
        $run = $this->makeRun(2);
        $first = $this->makeRetryableMediaUrl($run, 2201, '/tin-tuc/anh-mot');
        $second = $this->makeRetryableMediaUrl($run, 2202, '/tin-tuc/anh-hai');
        $foreign = $this->makeRetryableMediaUrl($this->makeRun(), 2203, '/tin-tuc/anh-khac-phien');
        foreach ([$first, $second, $foreign] as $url) {
            $this->mediaWarning($url, 'image_count_limit');
        }

        $component = Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->set('eligibilityFilter', 'image_count_limit')
            ->call('togglePageSelection', [$first->id, $second->id, $foreign->id])
            ->assertSet('selectedUrlIds', [(string) $first->id, (string) $second->id])
            ->set('selectedUrlIds', [(string) $first->id, (string) $foreign->id])
            ->call('queueMediaRetry', true)->assertHasErrors('mediaRetry');
        $this->assertDatabaseCount('jobs', 0);
        $component->call('clearSelection')->call('toggleUrlSelection', $second->id)
            ->assertSet('selectedUrlIds', [(string) $second->id])
            ->assertSee('Tải lại ảnh 1 URL đã chọn')
            ->call('queueMediaRetry', true)->assertHasNoErrors()->assertSet('selectedUrlIds', []);
        $this->assertSame('casted', $first->fresh()->status);
        $this->assertSame('queued', $second->fresh()->status);
        $this->assertSame('casted', $foreign->fresh()->status);
        $job = $this->queuedMediaJobs()->sole();
        $this->assertSame($second->id, $job->urlId);
        $this->assertTrue($job->reuseExistingTarget);
    }

    public function test_filtered_media_retry_queues_all_matching_pages_not_only_current_page(): void
    {
        $this->configureMediaRetryQueue();
        $run = $this->makeRun(26);
        for ($index = 1; $index <= 26; $index++) {
            $url = $this->makeRetryableMediaUrl($run, 2600 + $index, '/tin-tuc/nhieu-trang-'.$index);
            $this->mediaWarning($url, 'image_count_limit');
        }

        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->set('eligibilityFilter', 'image_count_limit')
            ->call('setPage', 2)->assertViewHas('urls', fn ($urls) => $urls->count() === 1)
            ->assertViewHas('mediaRetryCount', 26)
            ->call('queueMediaRetry')->assertHasNoErrors();

        $this->assertSame(26, LegacyStagedUrl::query()->where('run_id', $run->id)->where('status', 'queued')->count());
        $this->assertDatabaseCount('jobs', 26);
    }

    public function test_media_retry_skips_active_urls_and_deleted_targets_and_does_not_queue_twice(): void
    {
        $this->configureMediaRetryQueue();
        $run = $this->makeRun(4);
        $good = $this->makeRetryableMediaUrl($run, 2301, '/tin-tuc/anh-cho-tai');
        $queued = $this->makeRetryableMediaUrl($run, 2302, '/tin-tuc/anh-da-queue');
        $processing = $this->makeRetryableMediaUrl($run, 2303, '/tin-tuc/anh-dang-tai');
        $deleted = $this->makeRetryableMediaUrl($run, 2304, '/tin-tuc/target-da-xoa');
        foreach ([$good, $queued, $processing, $deleted] as $url) {
            $this->mediaWarning($url, 'image_count_limit');
        }
        $queued->update(['status' => 'queued']);
        $processing->update(['status' => 'processing']);
        BlogPost::query()->findOrFail($deleted->target_id)->delete();

        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->set('eligibilityFilter', 'image_count_limit')
            ->call('queueMediaRetry')->assertHasNoErrors()
            ->call('queueMediaRetry')->assertHasNoErrors();
        $this->assertSame('queued', $good->fresh()->status);
        $this->assertSame('processing', $processing->fresh()->status);
        $this->assertSame('casted', $deleted->fresh()->status);
        $this->assertSame(3, BlogPost::query()->count());
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_media_retry_requires_warning_filter_finalized_run_and_current_super_admin(): void
    {
        $this->configureMediaRetryQueue();
        $run = $this->makeRun();
        $url = $this->makeRetryableMediaUrl($run, 2401, '/tin-tuc/kiem-tra-quyen-tai');
        $this->mediaWarning($url, 'image_count_limit');
        $actor = $this->makeSuperAdmin();
        $component = Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('queueMediaRetry')->assertHasErrors('eligibilityFilter')
            ->set('eligibilityFilter', 'image_count_limit');
        $run->update(['status' => 'receiving']);
        $component->call('queueMediaRetry')->assertHasErrors('mediaRetry');
        $actor->removeRole('super_admin');
        $component->call('queueMediaRetry')->assertStatus(403);
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_media_retry_keeps_casted_state_while_unique_or_execution_lock_is_still_held(): void
    {
        $this->configureMediaRetryQueue();
        $run = $this->makeRun(2);
        $actor = $this->makeSuperAdmin();
        foreach (['unique', 'execution'] as $index => $kind) {
            $url = $this->makeRetryableMediaUrl($run, 2701 + $index, '/tin-tuc/worker-chua-nha-'.$kind);
            $this->mediaWarning($url, 'image_count_limit');
            $job = new ProcessLegacyMigrationUrl($url->id, $actor->id, 'blog', 'reuse_existing', 'cast_preserve_url', 'overwrite', true, true);
            $key = $kind === 'unique' ? UniqueLock::getKey($job) : $job->executionLockKey();
            $lock = Cache::lock($key, 100);
            $this->assertTrue($lock->get());
            $result = app(LegacyMediaRetry::class)->queue($run, $actor, [$url->id]);

            $this->assertSame(['queued' => 0, 'skipped' => 1], $result);
            $this->assertSame('casted', $url->fresh()->status);
            $this->assertFalse(Cache::lock($key, 100)->get());
            $lock->release();
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(0, LegacyCastAudit::query()->where('action', 'retry_media')->count());
    }

    public function test_media_retry_queue_push_failure_rolls_back_state_and_releases_only_its_owned_locks(): void
    {
        $this->configureMediaRetryQueue();
        $run = $this->makeRun(2);
        $actor = $this->makeSuperAdmin();
        $queueManager = Queue::getFacadeRoot();
        foreach ([false, true] as $index => $throws) {
            $url = $this->makeRetryableMediaUrl($run, 2801 + $index, '/tin-tuc/queue-khong-tao-'.$index);
            $this->mediaWarning($url, 'image_count_limit');
            $before = $url->getRawOriginal();
            $queue = \Mockery::mock(\Illuminate\Contracts\Queue\Queue::class);
            $expectation = $queue->shouldReceive('push')->once();
            if ($throws) {
                $expectation->andThrow(new \RuntimeException('Queue write failed.'));
            } else {
                $expectation->andReturn(null);
            }
            Queue::shouldReceive('connection')->with('database')->once()->andReturn($queue);
            try {
                app(LegacyMediaRetry::class)->queue($run, $actor, [$url->id]);
                $this->fail('Failed enqueue must not commit URL state.');
            } catch (\InvalidArgumentException|\RuntimeException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            } finally {
                Queue::swap($queueManager);
            }
            $this->assertSame($before, $url->fresh()->getRawOriginal());
            $job = new ProcessLegacyMigrationUrl($url->id, $actor->id, 'blog', 'reuse_existing', 'cast_preserve_url', 'overwrite', true, true);
            foreach ([UniqueLock::getKey($job), $job->executionLockKey()] as $key) {
                $lock = Cache::lock($key, 100);
                $this->assertTrue($lock->get());
                $lock->release();
            }
        }
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(0, LegacyCastAudit::query()->where('action', 'retry_media')->count());
    }

    public function test_media_retry_refuses_non_database_queue_without_changing_page_state(): void
    {
        $run = $this->makeRun();
        $url = $this->makeRetryableMediaUrl($run, 2901, '/tin-tuc/queue-khong-an-toan');
        $this->mediaWarning($url, 'image_count_limit');
        config()->set('queue.default', 'sync');

        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->set('eligibilityFilter', 'image_count_limit')
            ->assertSee('Tải lại ảnh an toàn cần queue database')
            ->call('queueMediaRetry')->assertHasErrors('mediaRetry');
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_raising_limit_and_filtered_retry_restores_images_to_same_blog_and_old_url(): void
    {
        Storage::fake('public');
        $this->configureMediaRetryQueue();
        config()->set('legacy_migration.media.max_images_per_object', 1);
        $run = $this->makeRun();
        $first = 'https://haidangtravel.com/image/kept.jpg';
        $second = 'https://haidangtravel.com/image/over-limit.jpg';
        $url = $this->makeBlogUrl($run, 'blog:2501', '/tin-tuc/tai-lai-anh-gioi-han', '<p>Nội dung nguồn.</p><img src="'.$first.'"><img src="'.$second.'">');
        $payload = $url->rootObject()->payload_json;
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock): void {
            $mock->shouldReceive('download')->once()->with('https://tour.org.vn/image/kept.jpg')->andReturn(UploadedFile::fake()->image('kept.jpg', 16, 12));
            $mock->shouldReceive('download')->once()->with('https://tour.org.vn/image/over-limit.jpg')->andReturn(UploadedFile::fake()->image('restored.jpg', 16, 12));
        });
        $actor = $this->makeSuperAdmin();
        $processor = app(LegacyAutomaticProcessor::class);
        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);
        $id = BlogPost::query()->sole()->id;
        $this->assertStringContainsString('/images/legacy-migration-placeholder.svg', BlogPost::query()->sole()->content);
        $this->assertSame('image_count_limit', $url->fresh()->latestMediaAudit->after_json['warnings'][0]['image_error_code']);
        config()->set('legacy_migration.media.max_images_per_object', 100);

        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->set('eligibilityFilter', 'image_count_limit')->call('queueMediaRetry')->assertHasNoErrors();
        $job = $this->queuedMediaJobs()->sole();
        app('queue.worker')->runNextJob('database', 'default', new WorkerOptions(sleep: 0, maxTries: 3, force: true));
        $this->assertDatabaseCount('jobs', 0);
        $unique = new UniqueLock(Cache::store());
        $this->assertTrue($unique->acquire($job));
        $unique->release($job);

        $target = BlogPost::query()->sole();
        $this->assertSame($id, $target->id);
        $this->assertStringNotContainsString('/images/legacy-migration-placeholder.svg', $target->content);
        $this->assertStringContainsString('Nội dung nguồn.', $target->content);
        $this->assertSame(2, Media::query()->where('collection_name', 'library')->count());
        $this->assertSame(1, $url->fresh()->latestMediaAudit->after_json['reused']);
        $this->assertSame('2020-01-02 03:04:05', $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2021-02-03 04:05:06', $target->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame($payload, $url->rootObject()->payload_json);
        $this->assertSame('/tin-tuc/tai-lai-anh-gioi-han', PublicUrlMapping::query()->sole()->source_path);
        $this->assertSame(0, LegacyStagedUrl::query()->where('run_id', $run->id)->withImageCountLimitWarning()->count());
    }

    public function test_media_review_filter_uses_latest_media_result_and_popup_lists_skipped_sources(): void
    {
        $run = $this->makeRun(2);
        $warningUrl = $this->makeBlogUrl($run, 'blog:1104', '/tin-tuc/anh-can-kiem-tra');
        $repaired = $this->makeBlogUrl($run, 'blog:2806', '/tin-tuc/anh-da-phuc-hoi');
        foreach ([$warningUrl, $repaired] as $url) {
            $url->update(['status' => 'casted']);
            LegacyCastAudit::query()->create([
                'run_id' => $run->id, 'staged_url_id' => $url->id, 'action' => 'import_media', 'status' => 'warning',
                'after_json' => ['skipped' => 1, 'warnings' => [['image_source' => 'https://image.plo.vn/missing.jpg', 'image_error_code' => 'dns', 'error_text' => 'Nguồn ảnh không phân giải được DNS/IP.']]],
            ]);
        }
        LegacyCastAudit::query()->create(['run_id' => $run->id, 'staged_url_id' => $repaired->id, 'action' => 'import_media', 'status' => 'completed', 'after_json' => ['skipped' => 0, 'warnings' => []]]);
        LegacyCastAudit::query()->create(['run_id' => $run->id, 'staged_url_id' => $warningUrl->id, 'action' => 'recover_queue', 'status' => 'completed']);

        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->set('eligibilityFilter', 'media_review')
            ->assertSee('1 ảnh cần kiểm tra')
            ->assertViewHas('urls', fn ($urls) => $urls->pluck('id')->all() === [$warningUrl->id])
            ->call('selectUrl', $warningUrl->id)
            ->assertSee('Ảnh cần kiểm tra — bài vẫn được xử lý')
            ->assertSee('https://image.plo.vn/missing.jpg')
            ->assertSee('Nguồn ảnh không phân giải được DNS/IP.');
    }

    public function test_manual_cast_removes_unavailable_remote_image_and_preserves_existing_target_source_dates_and_url(): void
    {
        Storage::fake('public');
        $run = $this->makeRun();
        $bad = 'https://external.example.test/deleted.jpg?signature=secret';
        $url = $this->makeBlogUrl($run, 'blog:1105', '/tin-tuc/bo-anh-mat', '<p>Nội dung gốc cần giữ.</p><img src="'.$bad.'">');
        $source = $url->rootObject();
        $payload = $source->payload_json;
        $target = $this->makeBlogTarget('giu-target-khi-bo-anh');
        $this->partialMock(LegacyImageDownloader::class, fn (MockInterface $mock) => $mock->shouldReceive('download')->once()->with($bad)->andThrow(new LegacyImageDownloadFailure('HTTP 404', $bad, 'http_404')));

        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->call('selectUrl', $url->id)->set('targetType', 'blog_post')->set('targetId', (string) $target->id)
            ->set('mappingMode', 'cast_preserve_url')->set('mergePolicy', 'overwrite')
            ->call('saveAndProcessSelected')->assertHasNoErrors()
            ->set('eligibilityFilter', 'media_review')->call('selectUrl', $url->id)
            ->assertSee('Đã bỏ ảnh khỏi content; dữ liệu nguồn vẫn được giữ.');

        $target->refresh();
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame((string) $target->id, $url->fresh()->target_id);
        $this->assertStringContainsString('Nội dung gốc cần giữ.', $target->content);
        $this->assertStringNotContainsString('<img', $target->content);
        $this->assertStringNotContainsString('placeholder.svg', $target->content);
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('2020-01-02 03:04:05', $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2021-02-03 04:05:06', $target->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('/tin-tuc/bo-anh-mat', PublicUrlMapping::query()->sole()->source_path);
        $this->assertArrayNotHasKey('removed_sources', $url->fresh()->latestMediaAudit->after_json);
        $this->assertStringNotContainsString('signature=secret', json_encode($url->fresh()->latestMediaAudit->after_json));
    }

    public function test_manual_cast_completes_with_placeholder_and_retains_existing_target_and_source_dates(): void
    {
        Storage::fake('public');
        $run = $this->makeRun();
        $invalid = 'data:image/png;base64,invalid-png';
        $url = $this->makeBlogUrl($run, 'blog:1104', '/tin-tuc/giu-page-anh-loi', '<p>Nội dung gốc.</p><img src="'.$invalid.'">');
        $source = $url->rootObject();
        $payload = $source->payload_json;
        $target = $this->makeBlogTarget('giu-target-anh-loi');
        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->call('selectUrl', $url->id)->set('targetType', 'blog_post')->set('targetId', (string) $target->id)
            ->set('mappingMode', 'cast_preserve_url')->set('mergePolicy', 'overwrite')
            ->call('saveAndProcessSelected')->assertHasNoErrors()
            ->set('eligibilityFilter', 'media_review')->call('selectUrl', $url->id)
            ->assertSee('Ảnh cần kiểm tra — bài vẫn được xử lý');

        $target->refresh();
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertStringContainsString('/images/legacy-migration-placeholder.svg', $target->content);
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('2020-01-02 03:04:05', $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2021-02-03 04:05:06', $target->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('/tin-tuc/giu-page-anh-loi', PublicUrlMapping::query()->sole()->source_path);
    }

    public function test_only_super_admin_can_open_temporary_mapping_screen(): void
    {
        $permission = Permission::findOrCreate('access admin panel', 'web');
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->givePermissionTo($permission);
        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $superAdminRole->givePermissionTo($permission);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole($adminRole);
        $superAdmin = User::factory()->create(['email_verified_at' => now()]);
        $superAdmin->assignRole($superAdminRole);

        $this->actingAs($admin)->get(route('admin.legacy-migration.index'))->assertForbidden();

        $this->actingAs($superAdmin)
            ->get(route('admin.legacy-migration.index'))
            ->assertOk()
            ->assertSee('Chuyển dữ liệu website cũ')
            ->assertSee('Receiver đang tắt');

        $run = LegacyMigrationRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => (string) Str::uuid(),
            'schema_version' => 'haidang-legacy-content.v1',
            'status' => 'ready_for_mapping',
            'expected_chunks' => 1,
            'expected_urls' => 1,
            'error_text' => 'Đã finalize với 863 URL staging trên 867 URL khai báo.',
            'started_at' => now(),
            'finalized_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.legacy-migration.runs.show', $run))
            ->assertOk()
            ->assertSee('Trạng thái phiên')
            ->assertSee('Phiên đã finalize với cảnh báo đối soát')
            ->assertSee('Đã finalize với 863 URL staging trên 867 URL khai báo.');
    }

    public function test_super_admin_selects_target_then_casts_with_source_created_at(): void
    {
        $permission = Permission::findOrCreate('access admin panel', 'web');
        $role = Role::findOrCreate('super_admin', 'web');
        $role->givePermissionTo($permission);
        $actor = User::factory()->create(['email_verified_at' => now()]);
        $actor->assignRole($role);
        $target = Tour::query()->create([
            'title' => 'Tour đích',
            'slug' => 'tour-dich',
            'status' => 'published',
            'scope' => 'domestic',
        ]);
        $run = LegacyMigrationRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => (string) Str::uuid(),
            'schema_version' => 'haidang-legacy-content.v1',
            'status' => 'ready_for_mapping',
            'expected_chunks' => 1,
            'expected_urls' => 1,
            'started_at' => now(),
        ]);
        $source = LegacyStagedObject::query()->create([
            'run_id' => $run->id,
            'object_key' => 'tour:200',
            'object_type' => 'tour',
            'legacy_id' => '200',
            'is_partial' => false,
            'checksum' => str_repeat('b', 64),
            'payload_json' => [
                'attributes' => [
                    'description' => '<p>Nội dung được chọn thủ công.</p>',
                    'created_at' => '2019-04-05 06:07:08',
                    'updated_at' => '2020-05-06 07:08:09',
                ],
                'relationships' => [],
            ],
            'source_created_at' => '2019-04-05 06:07:08',
            'source_updated_at' => '2020-05-06 07:08:09',
        ]);
        $url = LegacyStagedUrl::query()->create([
            'run_id' => $run->id,
            'raw_path' => '/tour-nguon',
            'normalized_path' => '/tour-nguon',
            'path_hash' => hash('sha256', '/tour-nguon'),
            'route_kind' => 'tour_detail',
            'root_object_key' => $source->object_key,
            'payload_json' => [],
            'status' => 'pending',
        ]);

        Livewire::actingAs($actor)
            ->test(MigrationManager::class, ['run' => $run])
            ->assertSet('mappingModalOpen', false)
            ->assertSee('wire:poll.10s', false)
            ->call('selectUrl', $url->id)
            ->assertDispatched('modal-show')
            ->assertSet('mappingModalOpen', true)
            ->assertDontSee('wire:poll.10s', false)
            ->assertSee('Xem JSON object nguồn')
            ->assertSee('wire:ignore.self', false)
            ->assertDontSee('Quy tắc timestamp')
            ->set('mappingMode', 'cast_and_redirect')
            ->set('mergePolicy', 'fill_blanks')
            ->set('targetType', 'tour')
            ->set('targetId', (string) $target->id)
            ->call('saveAndProcessSelected')
            ->assertHasNoErrors()
            ->assertSet('mappingModalOpen', false)
            ->assertSee('wire:poll.10s', false)
            ->assertDispatched('modal-close');

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame('2019-04-05 06:07:08', $target->fresh()->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('completed', $run->fresh()->status);
    }

    public function test_manual_blog_cast_imports_two_content_base64_images_before_sanitize_and_reuses_the_target_on_retry(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun();
        $firstImage = $this->embeddedPng(2, 2);
        $secondImage = $this->embeddedPng(3, 2);
        $content = '<p>Trước ảnh</p><img src="'.$firstImage.'" alt="Ảnh thứ nhất"><img src="'.$secondImage.'" alt="Ảnh thứ hai"><p>Sau ảnh</p>';
        $url = $this->makeBlogUrl($run, 'blog:2897', '/tin-tuc/bai-co-hai-anh-base64', $content);
        $source = $url->rootObject();
        $payload = $source->payload_json;
        $target = $this->makeBlogTarget('blog-target-base64');
        $component = Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run]);

        $component->call('selectUrl', $url->id)
            ->set('targetType', 'blog_post')
            ->set('targetId', (string) $target->id)
            ->set('mergePolicy', 'overwrite')
            ->call('saveAndProcessSelected')
            ->assertHasNoErrors()
            ->assertDispatched('modal-close');

        $target->refresh();
        $library = Media::query()->where('collection_name', 'library')->orderBy('id')->get();
        $this->assertSame(2, $library->count());
        $this->assertSame(1, Media::query()->where('collection_name', 'cover')->count());
        $this->assertSame(2, substr_count((string) $target->content, '<img'));
        foreach ($library as $media) {
            $this->assertStringContainsString($media->getUrl(), (string) $target->content);
        }
        $this->assertStringContainsString('Trước ảnh', (string) $target->content);
        $this->assertStringContainsString('Sau ảnh', (string) $target->content);
        $this->assertStringNotContainsString('data:image/', (string) $target->content);
        $this->assertSame('2020-01-02 03:04:05', $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2021-02-03 04:05:06', $target->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame((string) $target->id, $url->fresh()->target_id);
        $mapping = PublicUrlMapping::query()->sole();
        $this->assertSame('/tin-tuc/bai-co-hai-anh-base64', $mapping->source_path);
        $this->assertSame(PublicUrlMapping::MODE_REDIRECT, $mapping->mode);
        $this->assertSame(301, $mapping->status_code);
        $this->assertSame((string) $target->id, $mapping->target_id);
        $audit = LegacyCastAudit::query()->where('action', 'import_media')->sole();
        $this->assertSame(2, $audit->after_json['imported']);
        $this->assertSame('completed', $audit->status);
        $this->assertSame((string) $target->id, $audit->target_id);
        $this->assertArrayNotHasKey('replacements', $audit->after_json);
        $beforeRetry = $target->content;

        $component->call('selectUrl', $url->id)
            ->call('saveAndProcessSelected')
            ->assertHasNoErrors();

        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame(2, Media::query()->where('collection_name', 'library')->count());
        $this->assertSame(1, Media::query()->where('collection_name', 'cover')->count());
        $this->assertSame(1, PublicUrlMapping::query()->count());
        $this->assertSame($beforeRetry, $target->fresh()->content);
        $this->assertSame($payload, $source->fresh()->payload_json);
        $retryAudit = LegacyCastAudit::query()->where('action', 'import_media')->latest('id')->firstOrFail();
        $this->assertSame(0, $retryAudit->after_json['imported']);
        $this->assertSame(2, $retryAudit->after_json['reused']);
    }

    public function test_manual_blog_cast_downloads_remote_manifest_images_and_rewrites_source_content(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        config()->set('legacy_migration.media.allow_any_public_host', true);
        config()->set('legacy_migration.media.download_source_remap_from_hosts', ['haidangtravel.com', 'www.haidangtravel.com']);
        config()->set('legacy_migration.media.download_source_remap_to_host', 'tour.org.vn');
        $sourceUrl = 'http://haidangtravel.com/image/anh cu.png?width=460&amp;crop=a%2Fb#legacy';
        $downloadUrl = 'https://tour.org.vn/image/anh%20cu.png?width=460&crop=a%2Fb';
        $upload = UploadedFile::fake()->image('remote.png', 32, 32);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($downloadUrl, $upload): void {
            $mock->shouldReceive('download')->once()->with($downloadUrl)->andReturn($upload);
        });
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun();
        $content = '<p>Nội dung ảnh từ nguồn cũ.</p><img src="'.$sourceUrl.'" alt="Ảnh remote">';
        $url = $this->makeBlogUrl($run, 'blog:2898', '/tin-tuc/bai-co-anh-remote', $content, [
            ['field' => 'content', 'source' => $sourceUrl, 'url' => $sourceUrl],
        ]);
        $source = $url->rootObject();
        $payload = $source->payload_json;
        $target = $this->makeBlogTarget('blog-target-remote');

        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('selectUrl', $url->id)
            ->set('targetType', 'blog_post')
            ->set('targetId', (string) $target->id)
            ->set('mergePolicy', 'overwrite')
            ->call('saveAndProcessSelected')
            ->assertHasNoErrors();

        $media = Media::query()->where('collection_name', 'library')->sole();
        $this->assertSame($downloadUrl, $media->custom_properties['legacy_source_url']);
        $this->assertStringContainsString($media->getUrl(), (string) $target->fresh()->content);
        $this->assertStringNotContainsString($sourceUrl, (string) $target->fresh()->content);
        $this->assertSame(1, substr_count((string) $target->fresh()->content, '<img'));
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('2020-01-02 03:04:05', $target->fresh()->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2021-02-03 04:05:06', $target->fresh()->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('/tin-tuc/bai-co-anh-remote', PublicUrlMapping::query()->sole()->source_path);
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame(1, LegacyCastAudit::query()->where('action', 'import_media')->count());
    }

    public function test_manual_blog_cast_saves_embedded_image_errors_without_changing_target_content_or_timestamps(): void
    {
        config()->set('legacy_migration.media.skip_failed_images', false);
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun();
        $invalid = 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $url = $this->makeBlogUrl($run, 'blog:2899', '/tin-tuc/bai-co-anh-loi', '<img src="'.$invalid.'">');
        $source = $url->rootObject();
        $payload = $source->payload_json;
        $target = $this->makeBlogTarget('blog-target-invalid', '<p>Nội dung đang có.</p>');
        $before = $target->getRawOriginal();
        Exceptions::fake();
        $message = 'Ảnh nhúng trong nội dung phải là JPEG, PNG, WebP hoặc AVIF dạng base64 hợp lệ.';

        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('selectUrl', $url->id)
            ->set('targetType', 'blog_post')
            ->set('targetId', (string) $target->id)
            ->set('mergePolicy', 'overwrite')
            ->call('saveAndProcessSelected')
            ->assertSee($message)
            ->assertSet('mappingModalOpen', true);

        $this->assertSame('failed', $url->fresh()->status);
        $this->assertSame($message, $url->fresh()->error_text);
        $audit = LegacyCastAudit::query()->where('status', 'failed')->sole();
        $this->assertSame($message, $audit->error_text);
        $this->assertStringNotContainsString('data:image/', $audit->error_text);
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame(0, Media::query()->count());
        $this->assertSame(0, PublicUrlMapping::query()->count());
        $this->assertSame(0, LegacyCastAudit::query()->where('action', 'import_media')->count());
    }

    public function test_manual_redirect_only_skips_embedded_media_and_preserves_the_target(): void
    {
        Storage::fake('public');
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun();
        $url = $this->makeBlogUrl($run, 'blog:2900', '/tin-tuc/chi-redirect', '<img src="data:image/png;base64,invalid">');
        $target = $this->makeBlogTarget('blog-target-redirect', '<p>Nội dung giữ nguyên.</p>');
        $before = $target->getRawOriginal();

        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('selectUrl', $url->id)
            ->set('mappingMode', 'redirect_only')
            ->set('targetType', 'blog_post')
            ->set('targetId', (string) $target->id)
            ->call('saveAndProcessSelected')
            ->assertHasNoErrors();

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertSame(0, Media::query()->count());
        $this->assertSame(0, LegacyCastAudit::query()->where('action', 'import_media')->count());
        $mapping = PublicUrlMapping::query()->sole();
        $this->assertSame('/tin-tuc/chi-redirect', $mapping->source_path);
        $this->assertSame('redirect', $mapping->mode);
        $this->assertSame(301, $mapping->status_code);
    }

    public function test_manual_save_mapping_only_does_not_import_or_sanitize_source_images(): void
    {
        Storage::fake('public');
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun();
        $url = $this->makeBlogUrl($run, 'blog:2901', '/tin-tuc/chi-luu-mapping', '<img src="data:image/png;base64,invalid">');
        $target = $this->makeBlogTarget('blog-target-only-map', '<p>Nội dung giữ nguyên.</p>');
        $before = $target->getRawOriginal();

        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('selectUrl', $url->id)
            ->set('targetType', 'blog_post')
            ->set('targetId', (string) $target->id)
            ->call('saveMapping')
            ->assertHasNoErrors();

        $this->assertSame('mapped', $url->fresh()->status);
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertSame(0, Media::query()->count());
        $this->assertSame(0, PublicUrlMapping::query()->count());
        $this->assertSame(0, LegacyCastAudit::query()->count());
    }

    public function test_manual_block_skips_embedded_media_and_does_not_cast_a_target(): void
    {
        Storage::fake('public');
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun();
        $url = $this->makeBlogUrl($run, 'blog:2902', '/tin-tuc/bo-qua', '<img src="data:image/png;base64,invalid">');
        $target = $this->makeBlogTarget('blog-target-blocked', '<p>Nội dung giữ nguyên.</p>');
        $before = $target->getRawOriginal();

        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('selectUrl', $url->id)
            ->set('targetType', 'blog_post')
            ->set('targetId', (string) $target->id)
            ->set('mappingMode', 'blocked')
            ->call('saveAndProcessSelected')
            ->assertHasNoErrors();

        $this->assertSame('blocked', $url->fresh()->status);
        $this->assertNull($url->fresh()->target_id);
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertSame(0, Media::query()->count());
        $this->assertSame(0, PublicUrlMapping::query()->count());
        $this->assertSame(0, LegacyCastAudit::query()->count());
    }

    public function test_manual_blog_cast_rejects_a_partial_root_before_importing_images(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun();
        $url = $this->makeBlogUrl($run, 'blog:2903', '/tin-tuc/root-partial-co-anh', '<img src="'.$this->embeddedPng().'">');
        $url->rootObject()->update(['is_partial' => true]);
        $target = $this->makeBlogTarget('blog-target-partial', '<p>Nội dung giữ nguyên.</p>');
        $before = $target->getRawOriginal();
        Exceptions::fake();

        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('selectUrl', $url->id)
            ->set('targetType', 'blog_post')
            ->set('targetId', (string) $target->id)
            ->call('saveAndProcessSelected')
            ->assertSee('Root object đang là bản partial; cần bản đầy đủ trước khi cast.');

        $this->assertSame('failed', $url->fresh()->status);
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertSame(0, Media::query()->count());
        $this->assertSame(0, LegacyCastAudit::query()->where('action', 'import_media')->count());
    }

    public function test_manual_blog_cast_preserves_fill_blanks_policy_for_existing_content(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun();
        $url = $this->makeBlogUrl($run, 'blog:2904', '/tin-tuc/chi-dien-field-trong', '<p>Nội dung nguồn</p><img src="'.$this->embeddedPng().'">');
        $target = $this->makeBlogTarget('blog-target-fill-blanks', '<p>Nội dung đã có phải giữ nguyên.</p>');

        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('selectUrl', $url->id)
            ->set('targetType', 'blog_post')
            ->set('targetId', (string) $target->id)
            ->set('mergePolicy', 'fill_blanks')
            ->call('saveAndProcessSelected')
            ->assertHasNoErrors();

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame('<p>Nội dung đã có phải giữ nguyên.</p>', $target->fresh()->content);
        $this->assertSame(1, Media::query()->where('collection_name', 'library')->count());
        $this->assertSame('2020-01-02 03:04:05', $target->fresh()->created_at->format('Y-m-d H:i:s'));
    }

    public function test_popup_failure_history_shows_the_image_source_and_code_without_signed_query_tokens(): void
    {
        config()->set('legacy_migration.media.skip_failed_images', false);
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun();
        $sourceUrl = 'https://tour.org.vn/image/error.png?signature=secret-token';
        $url = $this->makeBlogUrl($run, 'blog:2905', '/tin-tuc/anh-loi-tls', '<img src="'.$sourceUrl.'">', [
            ['field' => 'content', 'source' => $sourceUrl, 'url' => $sourceUrl],
        ]);
        $target = $this->makeBlogTarget('blog-target-tls', '<p>Nội dung giữ nguyên.</p>');
        $before = $target->getRawOriginal();
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($sourceUrl): void {
            $mock->shouldReceive('download')->once()->with($sourceUrl)->andThrow(new LegacyImageDownloadFailure('Không xác thực được chứng chỉ nguồn.', $sourceUrl, 'curl_60'));
            $fallback = 'https://haidangtravel.com/image/error.png?signature=secret-token';
            $mock->shouldReceive('download')->once()->with($fallback)->andThrow(new LegacyImageDownloadFailure('Không xác thực được chứng chỉ nguồn.', $fallback, 'curl_60'));
        });
        Exceptions::fake();

        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('selectUrl', $url->id)
            ->set('targetType', 'blog_post')
            ->set('targetId', (string) $target->id)
            ->call('saveAndProcessSelected')
            ->assertSet('mappingModalOpen', true)
            ->assertSee('Ảnh nguồn: https://haidangtravel.com/image/error.png')
            ->assertSee('Mã lỗi: curl_60');

        $audit = LegacyCastAudit::query()->where('status', 'failed')->sole();
        $this->assertStringNotContainsString('secret-token', json_encode($audit->after_json));
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertSame((string) $target->id, $url->fresh()->target_id);
    }

    public function test_super_admin_can_filter_blog_and_queue_default_automatic_processing(): void
    {
        Queue::fake();
        $permission = Permission::findOrCreate('access admin panel', 'web');
        $role = Role::findOrCreate('super_admin', 'web');
        $role->givePermissionTo($permission);
        $actor = User::factory()->create(['email_verified_at' => now()]);
        $actor->assignRole($role);
        $run = LegacyMigrationRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => (string) Str::uuid(),
            'schema_version' => 'haidang-legacy-content.v1',
            'status' => 'ready_for_mapping',
            'expected_chunks' => 1,
            'expected_urls' => 1,
            'started_at' => now(),
        ]);
        $source = LegacyStagedObject::query()->create([
            'run_id' => $run->id,
            'object_key' => 'blog:3210',
            'object_type' => 'blog',
            'legacy_id' => '3210',
            'is_partial' => false,
            'checksum' => str_repeat('c', 64),
            'payload_json' => [
                'attributes' => [
                    'title' => 'Bài blog nguồn',
                    'slug' => 'bai-blog-nguon',
                    'created_at' => '2020-01-02 03:04:05',
                    'updated_at' => '2021-02-03 04:05:06',
                ],
                'relationships' => [],
                'media' => [],
            ],
            'source_created_at' => '2020-01-02 03:04:05',
            'source_updated_at' => '2021-02-03 04:05:06',
        ]);
        $url = LegacyStagedUrl::query()->create([
            'run_id' => $run->id,
            'raw_path' => '/tin-tuc/bai-blog-nguon',
            'normalized_path' => '/tin-tuc/bai-blog-nguon',
            'path_hash' => hash('sha256', '/tin-tuc/bai-blog-nguon'),
            'route_kind' => 'blog_detail',
            'root_object_key' => $source->object_key,
            'payload_json' => [],
            'status' => 'pending',
        ]);

        Livewire::actingAs($actor)
            ->test(MigrationManager::class, ['run' => $run])
            ->assertSee('Xử lý tự động theo loại')
            ->assertSee('Tự động xử lý 1 URL')
            ->set('objectType', 'blog')
            ->assertSee('/tin-tuc/bai-blog-nguon')
            ->call('queueAutomaticProcessing')
            ->assertHasNoErrors();

        $this->assertSame('queued', $url->fresh()->status);
        Queue::assertPushedOn('default', ProcessLegacyMigrationUrl::class, function (ProcessLegacyMigrationUrl $job) use ($url): bool {
            return $job->urlId === $url->id
                && $job->sourceType === 'blog'
                && $job->strategy === 'create_new'
                && $job->mappingMode === 'cast_and_redirect'
                && $job->importMedia;
        });
    }

    public function test_super_admin_can_select_exact_urls_and_queue_them_in_bulk(): void
    {
        Queue::fake();
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun(2);
        $firstUrl = $this->makeBlogUrl($run, 'blog:101', '/tin-tuc/bai-thu-nhat');
        $secondUrl = $this->makeBlogUrl($run, 'blog:102', '/tin-tuc/bai-thu-hai');

        Livewire::actingAs($actor)
            ->test(MigrationManager::class, ['run' => $run])
            ->assertSee('Chọn các URL có thể xử lý trên trang này')
            ->call('toggleUrlSelection', $firstUrl->id)
            ->assertSet('selectedUrlIds', [(string) $firstUrl->id])
            ->call('openBulkProcessing')
            ->assertDispatched('modal-show')
            ->assertSet('bulkProcessingModalOpen', true)
            ->assertDontSee('wire:poll.10s', false)
            ->assertSet('automaticSourceType', 'blog')
            ->call('queueSelectedProcessing')
            ->assertHasNoErrors()
            ->assertSet('bulkProcessingModalOpen', false)
            ->assertSee('wire:poll.10s', false)
            ->assertSet('selectedUrlIds', [])
            ->assertDispatched('modal-close');

        $this->assertSame('queued', $firstUrl->fresh()->status);
        $this->assertSame('pending', $secondUrl->fresh()->status);
        Queue::assertPushed(ProcessLegacyMigrationUrl::class, 1);
        Queue::assertPushed(ProcessLegacyMigrationUrl::class, fn (ProcessLegacyMigrationUrl $job): bool => $job->urlId === $firstUrl->id);
    }

    public function test_bulk_processing_rejects_a_tampered_url_from_another_run(): void
    {
        Queue::fake();
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun();
        $otherRun = $this->makeRun();
        $validUrl = $this->makeBlogUrl($run, 'blog:201', '/tin-tuc/bai-hop-le');
        $foreignUrl = $this->makeBlogUrl($otherRun, 'blog:202', '/tin-tuc/bai-khac-phien');

        Livewire::actingAs($actor)
            ->test(MigrationManager::class, ['run' => $run])
            ->set('selectedUrlIds', [(string) $validUrl->id, (string) $foreignUrl->id])
            ->call('openBulkProcessing')
            ->assertHasErrors(['bulkSelection']);

        $this->assertSame('pending', $validUrl->fresh()->status);
        $this->assertSame('pending', $foreignUrl->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_super_admin_can_filter_the_ineligible_urls_by_non_overlapping_reason(): void
    {
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun(5);
        $eligible = $this->makeBlogUrl($run, 'blog:301', '/tin-tuc/phu-hop');
        $differentType = $this->makeBlogUrl($run, 'tour:302', '/tour-khac-loai');
        $partialRoot = $this->makeBlogUrl($run, 'blog:303', '/tin-tuc/root-partial');
        $processed = $this->makeBlogUrl($run, 'blog:304', '/tin-tuc/da-xu-ly');

        LegacyStagedObject::query()
            ->where('run_id', $run->id)
            ->where('object_key', 'tour:302')
            ->update(['object_type' => 'tour']);
        LegacyStagedObject::query()
            ->where('run_id', $run->id)
            ->where('object_key', 'blog:303')
            ->update(['is_partial' => true]);
        $processed->update(['status' => 'queued']);
        $missingRoot = LegacyStagedUrl::query()->create([
            'run_id' => $run->id,
            'raw_url' => 'https://haidangtravel.com/khong-co-root',
            'raw_path' => '/khong-co-root',
            'normalized_path' => '/khong-co-root',
            'path_hash' => hash('sha256', '/khong-co-root'),
            'route_kind' => 'unresolved',
            'root_object_key' => null,
            'payload_json' => [],
            'status' => 'pending',
        ]);

        Livewire::actingAs($actor)
            ->test(MigrationManager::class, ['run' => $run])
            ->assertSee('Phù hợp với Bài viết blog (1)')
            ->assertSee('Không phù hợp với Bài viết blog (4)')
            ->set('objectType', 'tour')
            ->set('status', 'pending')
            ->set('eligibilityFilter', 'ineligible')
            ->assertSet('objectType', '')
            ->assertSet('status', '')
            ->assertDontSee($eligible->normalized_path)
            ->assertSee($differentType->normalized_path)
            ->assertSee($partialRoot->normalized_path)
            ->assertSee($processed->normalized_path)
            ->assertSee($missingRoot->normalized_path)
            ->set('eligibilityFilter', 'different_type')
            ->assertSee($differentType->normalized_path)
            ->assertDontSee($partialRoot->normalized_path)
            ->assertDontSee($processed->normalized_path)
            ->assertDontSee($missingRoot->normalized_path)
            ->set('eligibilityFilter', 'partial_root')
            ->assertSee($partialRoot->normalized_path)
            ->assertDontSee($differentType->normalized_path)
            ->set('eligibilityFilter', 'missing_root')
            ->assertSee($missingRoot->normalized_path)
            ->assertDontSee($partialRoot->normalized_path)
            ->set('eligibilityFilter', 'processed')
            ->assertSee($processed->normalized_path)
            ->assertDontSee($missingRoot->normalized_path)
            ->set('eligibilityFilter', 'eligible')
            ->assertSee($eligible->normalized_path)
            ->assertDontSee($processed->normalized_path);
    }

    public function test_failed_urls_show_grouped_current_error_and_persistent_audit_history(): void
    {
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun(2);
        $firstUrl = $this->makeBlogUrl($run, 'blog:401', '/tin-tuc/bai-loi-mot');
        $secondUrl = $this->makeBlogUrl($run, 'blog:402', '/tin-tuc/bai-loi-hai');
        $message = 'Không thể giữ URL gốc vì page đích chưa được publish/active.';
        $firstUrl->update(['status' => 'failed', 'error_text' => $message]);
        $secondUrl->update(['status' => 'failed', 'error_text' => $message]);
        LegacyCastAudit::query()->create([
            'run_id' => $run->id,
            'staged_url_id' => $firstUrl->id,
            'action' => 'auto_process',
            'status' => 'failed',
            'error_text' => $message,
            'actor_id' => $actor->id,
        ]);

        Livewire::actingAs($actor)
            ->test(MigrationManager::class, ['run' => $run])
            ->assertSee('Nhóm lỗi xử lý hiện tại')
            ->assertSee('2 URL')
            ->assertSee($message)
            ->call('filterFailedUrls')
            ->assertSet('status', 'failed')
            ->call('selectUrl', $firstUrl->id)
            ->assertSee('Lỗi hiện tại của URL')
            ->assertSee('Lịch sử 1 lần lỗi gần nhất')
            ->assertSee($message);
    }

    public function test_clicking_error_group_filters_current_failed_urls_and_resets_conflicting_filters_without_writes(): void
    {
        $actor = $this->makeSuperAdmin();
        $run = $this->makeRun(4);
        $first = $this->makeBlogUrl($run, 'blog:501', '/tin-tuc/loi-anh-mot');
        $second = $this->makeBlogUrl($run, 'tour:502', '/tour-loi-anh-hai');
        $different = $this->makeBlogUrl($run, 'blog:503', '/tin-tuc/loi-khac');
        $completed = $this->makeBlogUrl($run, 'blog:504', '/tin-tuc/da-xong');
        $otherRunUrl = $this->makeBlogUrl($this->makeRun(), 'blog:505', '/tin-tuc/loi-phien-khac');
        $message = 'NEED_DATA: Không tải được ảnh gốc.';
        foreach ([$first, $second, $otherRunUrl] as $url) {
            $url->update(['status' => 'failed', 'error_text' => $message]);
        }
        $different->update(['status' => 'failed', 'error_text' => 'Ảnh không đúng định dạng.']);
        $completed->update(['status' => 'casted', 'error_text' => $message]);
        LegacyStagedObject::query()->where('run_id', $run->id)->where('object_key', $second->root_object_key)->update(['object_type' => 'tour']);
        $before = LegacyStagedUrl::query()->orderBy('id')->get()->map->getRawOriginal()->all();
        Queue::fake();

        $component = Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->set('search', 'khong-khop')
            ->set('objectType', 'blog')
            ->set('eligibilityFilter', 'eligible')
            ->set('status', 'pending')
            ->set('selectedUrlIds', [(string) $first->id])
            ->call('setPage', 2)
            ->call('filterFailureGroup', $first->id, hash('sha256', $message))
            ->assertHasNoErrors()
            ->assertSet('failureFilter', $message)
            ->assertSet('search', '')
            ->assertSet('objectType', '')
            ->assertSet('eligibilityFilter', '')
            ->assertSet('status', 'failed')
            ->assertSet('selectedUrlIds', [])
            ->assertViewHas('urls', fn ($urls) => $urls->total() === 2 && $urls->currentPage() === 1)
            ->assertSee($first->normalized_path)
            ->assertSee($second->normalized_path)
            ->assertDontSee($different->normalized_path)
            ->assertDontSee($completed->normalized_path)
            ->assertDontSee($otherRunUrl->normalized_path)
            ->assertSee('Bỏ lọc nhóm lỗi')
            ->assertViewHas('failureGroups', fn ($groups) => $groups->count() === 2);

        $component->set('objectType', 'blog')->assertSet('failureFilter', $message)
            ->assertViewHas('urls', fn ($urls) => $urls->total() === 1)
            ->assertDontSee($second->normalized_path)
            ->set('search', 'loi-anh-mot')->assertSet('failureFilter', $message)
            ->assertSee($first->normalized_path);
        $this->assertSame($before, LegacyStagedUrl::query()->orderBy('id')->get()->map->getRawOriginal()->all());
        $this->assertSame(0, LegacyCastAudit::query()->count());
        Queue::assertNothingPushed();
    }

    public function test_group_filter_matches_full_error_not_truncated_text_or_like_wildcards_and_escapes_html(): void
    {
        $run = $this->makeRun(2);
        $first = $this->makeBlogUrl($run, 'blog:601', '/tin-tuc/thong-bao-dai-mot');
        $second = $this->makeBlogUrl($run, 'blog:602', '/tin-tuc/thong-bao-dai-hai');
        $prefix = str_repeat('Nguồn ảnh có dấu nháy và nội dung dài. ', 20);
        $message = $prefix.' "ảnh" \'gốc\' % _ <script>alert("x")</script> lỗi A';
        $otherMessage = $prefix.' "ảnh" \'gốc\' 123 X <script>alert("x")</script> lỗi A';
        $first->update(['status' => 'failed', 'error_text' => $message]);
        $second->update(['status' => 'failed', 'error_text' => $otherMessage]);

        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->call('filterFailureGroup', $first->id, hash('sha256', $message))
            ->assertSet('failureFilter', $message)
            ->assertViewHas('urls', fn ($urls) => $urls->total() === 1)
            ->assertSee($first->normalized_path)
            ->assertDontSee($second->normalized_path)
            ->assertDontSee('<script>alert("x")</script>', false);
    }

    public function test_clear_group_and_view_all_errors_restore_failed_list_and_other_filter_modes_remove_group(): void
    {
        $run = $this->makeRun(3);
        $first = $this->makeBlogUrl($run, 'blog:701', '/tin-tuc/nhom-mot');
        $second = $this->makeBlogUrl($run, 'blog:702', '/tin-tuc/nhom-hai');
        $pending = $this->makeBlogUrl($run, 'blog:703', '/tin-tuc/dang-cho');
        $first->update(['status' => 'failed', 'error_text' => 'Nhóm lỗi một.']);
        $second->update(['status' => 'failed', 'error_text' => 'Nhóm lỗi hai.']);
        $component = Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run]);

        foreach (['clearFailureFilter', 'filterFailedUrls'] as $action) {
            $component->call('filterFailureGroup', $first->id, hash('sha256', 'Nhóm lỗi một.'))
                ->set('search', 'nhom-mot')->call($action)
                ->assertSet('failureFilter', '')->assertSet('status', 'failed')->assertSet('search', '')
                ->assertViewHas('urls', fn ($urls) => $urls->total() === 2)
                ->assertSee($second->normalized_path)->assertDontSee($pending->normalized_path);
        }

        $component->call('filterFailureGroup', $first->id, hash('sha256', 'Nhóm lỗi một.'))
            ->set('status', 'pending')->assertSet('failureFilter', '')
            ->assertSee($pending->normalized_path)->assertDontSee($first->normalized_path)
            ->call('filterFailureGroup', $first->id, hash('sha256', 'Nhóm lỗi một.'))
            ->set('eligibilityFilter', 'eligible')->assertSet('failureFilter', '')
            ->assertSee($pending->normalized_path);
    }

    public function test_group_filter_persists_across_pagination_and_lists_all_matching_urls(): void
    {
        $run = $this->makeRun(26);
        $message = 'NEED_DATA: URL ảnh không hợp lệ.';
        $first = null;
        for ($index = 1; $index <= 26; $index++) {
            $url = $this->makeBlogUrl($run, 'blog:'.(800 + $index), '/tin-tuc/nhom-loi-'.$index);
            $url->update(['status' => 'failed', 'error_text' => $message]);
            $first ??= $url;
        }

        Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run])
            ->call('filterFailureGroup', $first->id, hash('sha256', $message))
            ->assertViewHas('urls', fn ($urls) => $urls->total() === 26 && $urls->currentPage() === 1)
            ->call('setPage', 2)->assertSet('failureFilter', $message)
            ->assertViewHas('urls', fn ($urls) => $urls->total() === 26 && $urls->currentPage() === 2 && $urls->count() === 1);
    }

    public function test_stale_group_click_reports_changed_or_resolved_error_without_switching_group(): void
    {
        $run = $this->makeRun();
        $url = $this->makeBlogUrl($run, 'blog:901', '/tin-tuc/nhom-da-thay-doi');
        $url->update(['status' => 'failed', 'error_text' => 'Lỗi lúc hiển thị.']);
        $component = Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run]);
        $url->update(['error_text' => 'Lỗi mới.']);

        $component->call('filterFailureGroup', $url->id, hash('sha256', 'Lỗi lúc hiển thị.'))
            ->assertHasErrors('failureFilter')->assertSet('failureFilter', '')
            ->assertSee('Nhóm lỗi đã thay đổi hoặc URL đã được xử lý.');
        $url->update(['status' => 'casted']);
        $component->call('filterFailureGroup', $url->id, hash('sha256', 'Lỗi mới.'))
            ->assertHasErrors('failureFilter')->assertSet('failureFilter', '');
    }

    public function test_group_from_another_run_is_rejected(): void
    {
        $run = $this->makeRun();
        $other = $this->makeBlogUrl($this->makeRun(), 'blog:1001', '/tin-tuc/nhom-phien-khac');
        $other->update(['status' => 'failed', 'error_text' => 'Lỗi phiên khác.']);
        $component = Livewire::actingAs($this->makeSuperAdmin())->test(MigrationManager::class, ['run' => $run]);

        $this->expectException(ModelNotFoundException::class);
        $component->call('filterFailureGroup', $other->id, hash('sha256', 'Lỗi phiên khác.'));
    }

    public function test_group_click_and_clear_recheck_super_admin_authorization(): void
    {
        $run = $this->makeRun();
        $url = $this->makeBlogUrl($run, 'blog:1101', '/tin-tuc/nhom-kiem-tra-quyen');
        $url->update(['status' => 'failed', 'error_text' => 'Lỗi kiểm tra quyền.']);

        foreach (['filterFailureGroup', 'clearFailureFilter'] as $action) {
            $actor = $this->makeSuperAdmin();
            $component = Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run]);
            $actor->removeRole('super_admin');
            $args = $action === 'filterFailureGroup' ? [$url->id, hash('sha256', 'Lỗi kiểm tra quyền.')] : [];
            $component->call($action, ...$args)->assertStatus(403);
        }
    }

    private function configureMediaRetryQueue(): void
    {
        config()->set('queue.default', 'database');
        config()->set('queue.connections.database.connection', null);
        config()->set('queue.connections.database.table', 'jobs');
        config()->set('legacy_migration.queue', 'default');
    }

    private function queuedMediaJobs(): Collection
    {
        return DB::table('jobs')->orderBy('id')->pluck('payload')->map(fn (string $payload) => unserialize(
            json_decode($payload, true)['data']['command'], ['allowed_classes' => [ProcessLegacyMigrationUrl::class]],
        ));
    }

    private function makeRetryableMediaUrl(LegacyMigrationRun $run, int $id, string $path): LegacyStagedUrl
    {
        $url = $this->makeBlogUrl($run, 'blog:'.$id, $path);
        $target = $this->makeBlogTarget('media-retry-'.$id, '<p>Nội dung hiện có.</p>');
        $url->update([
            'status' => 'casted', 'target_type' => 'blog_post', 'target_id' => (string) $target->id,
            'target_path' => '/bai-viet/'.$target->slug, 'mapping_mode' => 'cast_preserve_url',
            'merge_policy' => 'fill_blanks', 'mapped_at' => now(), 'casted_at' => now(),
        ]);

        return $url->refresh();
    }

    private function mediaWarning(LegacyStagedUrl $url, string $code, string $message = LegacyImageCountLimitExceeded::MESSAGE): LegacyCastAudit
    {
        return LegacyCastAudit::query()->create([
            'run_id' => $url->run_id, 'staged_url_id' => $url->id, 'action' => 'import_media', 'status' => 'warning',
            'after_json' => ['skipped' => 1, 'warnings' => [['image_error_code' => $code, 'error_text' => $message, 'image_source' => 'https://haidangtravel.com/image/over-limit.jpg']]],
        ]);
    }

    private function makeSuperAdmin(): User
    {
        $permission = Permission::findOrCreate('access admin panel', 'web');
        $role = Role::findOrCreate('super_admin', 'web');
        $role->givePermissionTo($permission);
        $actor = User::factory()->create(['email_verified_at' => now()]);
        $actor->assignRole($role);

        return $actor;
    }

    private function makeRun(int $expectedUrls = 1): LegacyMigrationRun
    {
        return LegacyMigrationRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => (string) Str::uuid(),
            'schema_version' => 'haidang-legacy-content.v1',
            'status' => 'ready_for_mapping',
            'expected_chunks' => 1,
            'expected_urls' => $expectedUrls,
            'started_at' => now(),
        ]);
    }

    private function embeddedPng(int $width = 1, int $height = 1): string
    {
        return 'data:image/png;base64,'.base64_encode(UploadedFile::fake()->image('inline.png', $width, $height)->get());
    }

    private function makeBlogTarget(string $slug, ?string $content = null): BlogPost
    {
        return BlogPost::query()->create([
            'title' => 'Bài đích đã tạo',
            'slug' => $slug,
            'status' => 'published',
            'published_at' => '2017-06-07 08:09:10',
            'content' => $content,
        ])->refresh();
    }

    private function makeBlogUrl(LegacyMigrationRun $run, string $objectKey, string $path, ?string $content = null, array $media = []): LegacyStagedUrl
    {
        $legacyId = (string) Str::after($objectKey, ':');
        LegacyStagedObject::query()->create([
            'run_id' => $run->id,
            'object_key' => $objectKey,
            'object_type' => 'blog',
            'legacy_id' => $legacyId,
            'is_partial' => false,
            'checksum' => hash('sha256', $objectKey),
            'payload_json' => [
                'attributes' => [
                    'title' => 'Bài migrate '.$legacyId,
                    'slug' => Str::afterLast($path, '/'),
                    'created_at' => '2020-01-02 03:04:05',
                    'updated_at' => '2021-02-03 04:05:06',
                    ...($content === null ? [] : ['content' => $content, 'publish' => 1]),
                ],
                'relationships' => [],
                'media' => $media,
            ],
            'source_created_at' => '2020-01-02 03:04:05',
            'source_updated_at' => '2021-02-03 04:05:06',
        ]);

        return LegacyStagedUrl::query()->create([
            'run_id' => $run->id,
            'raw_url' => 'https://haidangtravel.com'.$path,
            'raw_path' => $path,
            'normalized_path' => $path,
            'path_hash' => hash('sha256', $path),
            'route_kind' => 'blog_detail',
            'root_object_key' => $objectKey,
            'payload_json' => [],
            'status' => 'pending',
        ]);
    }
}
