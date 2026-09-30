<?php

namespace Tests\Feature\LegacyMigration;

use App\Models\User;
use App\Modules\LegacyMigration\Jobs\ProcessLegacyMigrationUrl;
use App\Modules\LegacyMigration\Livewire\Admin\MigrationManager;
use App\Modules\LegacyMigration\Models\LegacyCastAudit;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyObjectMap;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use App\Modules\LegacyMigration\Services\LegacyAutomaticProcessor;
use App\Modules\LegacyMigration\Services\LegacyQueueRecovery;
use Illuminate\Bus\UniqueLock;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\PublicUrlMapping;
use Tests\TestCase;

class LegacyQueueRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('queue.default', 'database');
        config()->set('queue.connections.database.connection', null);
        config()->set('queue.connections.database.table', 'jobs');
        config()->set('legacy_migration.queue', 'default');
        config()->set('frontsite_cache.enabled', false);
    }

    public function test_recovery_preserves_target_source_and_url_then_casts_into_the_same_blog(): void
    {
        [$run, $source, $url, $post] = $this->stuckUrl();
        $actor = $this->superAdmin();
        $sourceBefore = $source->getRawOriginal();
        $targetBefore = $post->getRawOriginal();
        $urlBefore = $url->only(['payload_json', 'raw_path', 'raw_url', 'normalized_path', 'path_hash', 'root_object_key', 'mapped_at', 'casted_at', 'mapping_mode', 'merge_policy']);
        $mapBefore = LegacyObjectMap::query()->sole()->getRawOriginal();
        $staleJob = $this->job($url, $actor);
        $unique = new UniqueLock(Cache::store());
        $this->assertTrue($unique->acquire($staleJob));

        $result = app(LegacyQueueRecovery::class)->recover($run, $actor, 'blog', false);

        $this->assertSame(1, $result['recovered']);
        $this->assertSame($sourceBefore, $source->fresh()->getRawOriginal());
        $this->assertSame($targetBefore, $post->fresh()->getRawOriginal());
        $this->assertEquals($urlBefore, $url->fresh()->only(array_keys($urlBefore)));
        $this->assertSame($mapBefore, LegacyObjectMap::query()->sole()->getRawOriginal());
        $this->assertSame((string) $post->id, $url->fresh()->target_id);
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertDatabaseCount('jobs', 1);
        $job = $this->queuedJob();
        $this->assertTrue($job->reuseExistingTarget);
        $this->assertSame('reuse_existing', $job->strategy);
        $this->assertSame('cast_preserve_url', $job->mappingMode);
        $this->assertSame('overwrite', $job->mergePolicy);
        $this->assertFalse($job->importMedia);
        $audit = LegacyCastAudit::query()->where('action', 'recover_queue')->sole();
        $this->assertSame('completed', $audit->status);
        $this->assertTrue($audit->after_json['reuse_existing_target']);
        $this->assertNotEmpty($audit->after_json['queue_job_id']);

        $job->handle(app(LegacyAutomaticProcessor::class));

        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame((string) $post->id, $url->fresh()->target_id);
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertStringContainsString('Nội dung nguồn cần khôi phục.', $post->fresh()->content);
        $this->assertSame('2018-03-04 05:06:07', $post->fresh()->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $post->fresh()->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame($sourceBefore['payload_json'], $source->fresh()->getRawOriginal('payload_json'));
        $mapping = PublicUrlMapping::query()->sole();
        $this->assertSame($urlBefore['normalized_path'], $mapping->source_path);
        $this->assertSame(PublicUrlMapping::MODE_RENDER, $mapping->mode);
        $this->get($urlBefore['normalized_path'])->assertOk()->assertSee('Nội dung nguồn cần khôi phục.');
    }

    public function test_recovery_uses_redirect_default_only_when_an_old_queued_url_has_no_saved_mapping_mode(): void
    {
        [$run, , $url] = $this->stuckUrl();
        LegacyStagedUrl::withoutTimestamps(fn () => $url->forceFill(['mapping_mode' => null])->save());

        app(LegacyQueueRecovery::class)->recover($run, $this->superAdmin(), 'blog', false);

        $this->assertSame('cast_and_redirect', $this->queuedJob()->mappingMode);
    }

    public function test_orphan_processing_row_can_be_recovered_from_existing_object_map(): void
    {
        [$run, , $url, $post] = $this->stuckUrl('processing');
        LegacyStagedUrl::withoutTimestamps(fn () => $url->forceFill(['target_id' => null, 'target_type' => null, 'target_path' => null, 'updated_at' => now()->subMinutes(10)])->save());

        $result = app(LegacyQueueRecovery::class)->recover($run, $this->superAdmin(), 'blog', true);

        $this->assertSame(1, $result['recovered']);
        $this->assertSame((string) $post->id, $url->fresh()->target_id);
        $this->assertSame('queued', $url->fresh()->status);
        $this->assertTrue($this->queuedJob()->importMedia);
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame(1, LegacyObjectMap::query()->count());
    }

    public function test_database_worker_processes_recovered_job_and_releases_unique_lock(): void
    {
        [$run, , $url, $post] = $this->stuckUrl();
        $actor = $this->superAdmin();
        app(LegacyQueueRecovery::class)->recover($run, $actor, 'blog', false);

        app('queue.worker')->runNextJob('database', 'default', new WorkerOptions(sleep: 0, maxTries: 3, force: true));

        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertStringContainsString('Nội dung nguồn cần khôi phục.', $post->fresh()->content);
        $unique = new UniqueLock(Cache::store());
        $job = $this->job($url, $actor);
        $this->assertTrue($unique->acquire($job));
        $unique->release($job);
    }

    public function test_pending_delayed_and_reserved_jobs_are_never_duplicated_or_removed(): void
    {
        $actor = $this->superAdmin();

        foreach (['pending', 'delayed', 'reserved'] as $state) {
            [$run, , $url] = $this->stuckUrl();
            $id = Queue::connection('database')->push($this->job($url, $actor), '', 'other-queue');
            DB::table('jobs')->where('id', $id)->update([
                'reserved_at' => $state === 'reserved' ? now()->subHour()->timestamp : null,
                'available_at' => $state === 'delayed' ? now()->addHour()->timestamp : now()->timestamp,
            ]);
            $before = $url->getRawOriginal();

            $result = app(LegacyQueueRecovery::class)->recover($run, $actor, 'blog', false);

            $this->assertSame(0, $result['recovered']);
            $this->assertSame(1, $result['existing_jobs']);
            $this->assertSame($before, $url->fresh()->getRawOriginal());
            $this->assertDatabaseHas('jobs', ['id' => $id, 'queue' => 'other-queue']);
        }

        $this->assertDatabaseCount('jobs', 3);
        $this->assertSame(0, LegacyCastAudit::query()->count());
    }

    public function test_active_execution_lock_is_not_released_even_if_url_is_stale(): void
    {
        [$run, , $url] = $this->stuckUrl('processing');
        $actor = $this->superAdmin();
        $job = $this->job($url, $actor);
        $lock = Cache::lock($job->executionLockKey(), 100);
        $this->assertTrue($lock->get());
        $unique = new UniqueLock(Cache::store());
        $this->assertTrue($unique->acquire($job));

        try {
            $result = app(LegacyQueueRecovery::class)->recover($run, $actor, 'blog', false);
            $this->assertSame(1, $result['active']);
            $this->assertSame(0, $result['recovered']);
            $this->assertFalse($unique->acquire($job));
            $this->assertDatabaseCount('jobs', 0);
            $this->assertSame('processing', $url->fresh()->status);
        } finally {
            $lock->release();
            $unique->release($job);
        }
    }

    public function test_repeated_recovery_does_not_queue_twice_even_after_url_becomes_stale_again(): void
    {
        [$run, , $url] = $this->stuckUrl();
        $actor = $this->superAdmin();
        $recovery = app(LegacyQueueRecovery::class);
        $this->assertSame(1, $recovery->recover($run, $actor, 'blog', false)['recovered']);
        $url->refresh()->forceFill(['updated_at' => now()->subMinutes(10)])->save();

        $result = $recovery->recover($run, $actor, 'blog', false);

        $this->assertSame(1, $result['existing_jobs']);
        $this->assertSame(0, $result['recovered']);
        $this->assertDatabaseCount('jobs', 1);
        $this->assertSame(1, BlogPost::query()->count());
    }

    public function test_job_arriving_after_initial_queue_scan_is_detected_again_under_url_lock(): void
    {
        [$run, , $url] = $this->stuckUrl();
        $actor = $this->superAdmin();
        $inserted = false;
        DB::listen(function (QueryExecuted $query) use ($url, $actor, &$inserted): void {
            if (! $inserted && str_starts_with($query->sql, 'select') && str_contains($query->sql, 'from "legacy_staged_urls"')) {
                $inserted = true;
                Queue::connection('database')->push($this->job($url, $actor), '', 'default');
            }
        });

        $result = app(LegacyQueueRecovery::class)->recover($run, $actor, 'blog', false);

        $this->assertTrue($inserted);
        $this->assertSame(1, $result['existing_jobs']);
        $this->assertSame(0, $result['recovered']);
        $this->assertDatabaseCount('jobs', 1);
    }

    public function test_recovery_job_refuses_to_create_a_new_blog_if_target_disappears_before_execution(): void
    {
        [$run, $source, $url, $post] = $this->stuckUrl();
        $actor = $this->superAdmin();
        $sourceBefore = $source->payload_json;
        app(LegacyQueueRecovery::class)->recover($run, $actor, 'blog', false);
        $post->delete();

        $this->queuedJob()->handle(app(LegacyAutomaticProcessor::class));

        $this->assertSame(0, BlogPost::query()->count());
        $this->assertSame('failed', $url->fresh()->status);
        $this->assertSame((string) $post->id, $url->fresh()->target_id);
        $this->assertSame($sourceBefore, $source->fresh()->payload_json);
        $this->assertSame(0, PublicUrlMapping::query()->count());
    }

    public function test_worker_without_reuse_flag_fails_closed_instead_of_matching_a_different_blog(): void
    {
        [$run, , $url, $post] = $this->stuckUrl();
        $before = $post->getRawOriginal();
        app(LegacyQueueRecovery::class)->recover($run, $this->superAdmin(), 'blog', false);
        $job = $this->queuedJob();
        $job->reuseExistingTarget = false;

        $job->handle(app(LegacyAutomaticProcessor::class));

        $this->assertSame('failed', $url->fresh()->status);
        $this->assertSame($before, $post->fresh()->getRawOriginal());
        $this->assertSame((string) $post->id, $url->fresh()->target_id);
        $this->assertSame(1, BlogPost::query()->count());
    }

    public function test_recovery_preserves_saved_redirect_code_and_mapping_actor_during_cast(): void
    {
        [$run, , $url, $post] = $this->stuckUrl();
        $mappingActor = $this->superAdmin();
        $recoveryActor = $this->superAdmin();
        LegacyStagedUrl::withoutTimestamps(fn () => $url->forceFill([
            'mapping_mode' => 'cast_and_redirect', 'redirect_code' => 302, 'mapped_by' => $mappingActor->id,
        ])->save());
        app(LegacyQueueRecovery::class)->recover($run, $recoveryActor, 'blog', false);

        $this->queuedJob()->handle(app(LegacyAutomaticProcessor::class));

        $this->assertSame(302, $url->fresh()->redirect_code);
        $this->assertSame($mappingActor->id, $url->fresh()->mapped_by);
        $this->assertSame(302, PublicUrlMapping::query()->sole()->status_code);
        $this->assertSame((string) $post->id, $url->fresh()->target_id);
    }

    public function test_source_type_scope_does_not_recover_other_types(): void
    {
        [$run, , $url] = $this->stuckUrl();

        $result = app(LegacyQueueRecovery::class)->recover($run, $this->superAdmin(), 'tour', false);

        $this->assertSame(0, $result['recovered']);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame('queued', $url->fresh()->status);
    }

    public function test_cutoff_cannot_be_shorter_than_worker_retry_and_overlap_lock(): void
    {
        $recovery = app(LegacyQueueRecovery::class);
        config()->set('legacy_migration.recovery.stale_after_seconds', 1);
        $this->assertSame(300, $recovery->staleSeconds());
        config()->set('queue.connections.database.retry_after', 400);
        $this->assertSame(500, $recovery->staleSeconds());
        config()->set('legacy_migration.recovery.stale_after_seconds', 900);
        $this->assertSame(900, $recovery->staleSeconds());
    }

    public function test_recent_or_completed_rows_are_not_recovered(): void
    {
        [$run, , $url] = $this->stuckUrl();
        $actor = $this->superAdmin();
        $recovery = app(LegacyQueueRecovery::class);
        $url->forceFill(['updated_at' => now()])->save();
        $this->assertSame(0, $recovery->recover($run, $actor, 'blog', false)['recovered']);

        foreach (['casted', 'failed', 'blocked', 'pending'] as $status) {
            $url->forceFill(['status' => $status, 'updated_at' => now()->subMinutes(10)])->save();
            $this->assertSame(0, $recovery->recover($run, $actor, 'blog', false)['recovered']);
        }

        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_deleted_target_does_not_fall_back_to_another_map_or_create_a_blog(): void
    {
        [$run, , $url, $post] = $this->stuckUrl();
        $post->delete();
        $replacement = BlogPost::query()->create(['title' => 'Bài khác', 'slug' => 'bai-khac', 'status' => 'published']);
        LegacyObjectMap::query()->sole()->update(['target_id' => (string) $replacement->id]);
        $before = $url->getRawOriginal();

        $result = app(LegacyQueueRecovery::class)->recover($run, $this->superAdmin(), 'blog', false);

        $this->assertSame(1, $result['invalid']);
        $this->assertSame(0, $result['recovered']);
        $this->assertSame($before, $url->fresh()->getRawOriginal());
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame('failed', LegacyCastAudit::query()->sole()->status);
    }

    public function test_missing_map_or_partial_source_is_refused_without_creating_target(): void
    {
        [$run, $source, $url] = $this->stuckUrl();
        $actor = $this->superAdmin();
        LegacyStagedUrl::withoutTimestamps(fn () => $url->forceFill(['target_id' => null, 'target_type' => null, 'updated_at' => now()->subMinutes(10)])->save());
        LegacyObjectMap::query()->delete();
        $this->assertSame(1, app(LegacyQueueRecovery::class)->recover($run, $actor, 'blog', false)['invalid']);
        $source->update(['is_partial' => true]);
        $this->assertSame(1, app(LegacyQueueRecovery::class)->recover($run, $actor, 'blog', false)['invalid']);
        $this->assertDatabaseCount('jobs', 0);
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame('queued', $url->fresh()->status);
    }

    public function test_unsupported_queue_or_unverifiable_payload_stops_recovery(): void
    {
        [$run, , $url] = $this->stuckUrl();
        $actor = $this->superAdmin();
        $before = $url->getRawOriginal();
        $recovery = app(LegacyQueueRecovery::class);

        foreach (['sync', 'missing-table', 'malformed-payload', 'encrypted-command'] as $case) {
            config()->set('queue.default', $case === 'sync' ? 'sync' : 'database');
            config()->set('queue.connections.database.table', $case === 'missing-table' ? 'absent_jobs' : 'jobs');
            DB::table('jobs')->delete();

            if (in_array($case, ['malformed-payload', 'encrypted-command'], true)) {
                DB::table('jobs')->insert([
                    'queue' => 'default', 'attempts' => 0, 'available_at' => now()->timestamp, 'created_at' => now()->timestamp,
                    'payload' => $case === 'malformed-payload' ? 'ProcessLegacyMigrationUrl invalid JSON' : json_encode([
                        'displayName' => ProcessLegacyMigrationUrl::class,
                        'data' => ['commandName' => ProcessLegacyMigrationUrl::class, 'command' => 'encrypted-command'],
                    ]),
                ]);
            }

            try {
                $recovery->recover($run, $actor, 'blog', false);
                $this->fail('Recovery should stop for '.$case);
            } catch (InvalidArgumentException $exception) {
                $this->assertNotEmpty($exception->getMessage());
            }

            $this->assertSame($before, $url->fresh()->getRawOriginal());
            $this->assertSame(1, BlogPost::query()->count());
        }
    }

    public function test_saved_mapping_policy_is_used_not_the_new_create_blog_settings(): void
    {
        [$run, , $url] = $this->stuckUrl();
        LegacyStagedUrl::withoutTimestamps(fn () => $url->forceFill(['mapping_mode' => 'cast_and_redirect', 'merge_policy' => 'fill_blanks', 'updated_at' => now()->subMinutes(10)])->save());

        Livewire::actingAs($this->superAdmin())->test(MigrationManager::class, ['run' => $run])
            ->set('automaticStrategy', 'create_new')
            ->set('automaticMappingMode', 'cast_only')
            ->set('automaticMergePolicy', 'overwrite')
            ->set('automaticImportMedia', false)
            ->call('recoverStuckUrls')
            ->assertHasNoErrors()
            ->assertSee('Khôi phục URL bị kẹt và xử lý lại');

        $job = $this->queuedJob();
        $this->assertSame('cast_and_redirect', $job->mappingMode);
        $this->assertSame('fill_blanks', $job->mergePolicy);
        $this->assertTrue($job->reuseExistingTarget);
        $this->assertSame(1, BlogPost::query()->count());
    }

    public function test_single_url_recovery_is_scoped_to_current_run(): void
    {
        [$run, , $url] = $this->stuckUrl();
        [, , $otherUrl] = $this->stuckUrl();
        $actor = $this->superAdmin();
        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->set('automaticImportMedia', false)
            ->call('recoverStuckUrl', $url->id)->assertHasNoErrors();
        $this->assertSame($url->id, $this->queuedJob()->urlId);
        $this->assertDatabaseCount('jobs', 1);
        $this->expectException(ModelNotFoundException::class);
        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('recoverStuckUrl', $otherUrl->id);
    }

    public function test_recovery_rechecks_role_and_finalize_state(): void
    {
        [$run] = $this->stuckUrl();
        $actor = $this->superAdmin();
        $component = Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run]);
        $actor->removeRole('super_admin');
        $component->call('recoverStuckUrls')->assertStatus(403);
        $actor->assignRole('super_admin');
        $run->update(['status' => 'receiving']);
        Livewire::actingAs($actor)->test(MigrationManager::class, ['run' => $run])
            ->call('recoverStuckUrls')->assertHasErrors('queueRecovery');
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_failed_queue_push_rolls_back_status_and_target_changes_and_releases_unique_lock(): void
    {
        [$run, , $url] = $this->stuckUrl('processing');
        $actor = $this->superAdmin();
        $before = $url->getRawOriginal();
        Queue::shouldReceive('connection')->with('database')->once()->andReturnSelf();
        Queue::shouldReceive('push')->once()->andReturn(null);

        $result = app(LegacyQueueRecovery::class)->recover($run, $actor, 'blog', false);

        $this->assertSame(1, $result['invalid']);
        $this->assertSame($before, $url->fresh()->getRawOriginal());
        $this->assertDatabaseCount('jobs', 0);
        $unique = new UniqueLock(Cache::store());
        $job = $this->job($url, $actor);
        $this->assertTrue($unique->acquire($job));
        $unique->release($job);
    }

    public function test_old_serialized_job_without_recovery_property_still_has_safe_default(): void
    {
        [$run, , $url] = $this->stuckUrl();
        $serialized = serialize($this->job($url, $this->superAdmin()));
        $this->assertStringNotContainsString('reuseExistingTarget', $serialized);
        $job = unserialize($serialized, ['allowed_classes' => [ProcessLegacyMigrationUrl::class]]);
        $this->assertFalse($job->reuseExistingTarget);
        $this->assertNotEmpty($run->id);
    }

    private function job(LegacyStagedUrl $url, User $actor): ProcessLegacyMigrationUrl
    {
        return new ProcessLegacyMigrationUrl($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', false);
    }

    private function queuedJob(): ProcessLegacyMigrationUrl
    {
        $payload = json_decode(DB::table('jobs')->sole()->payload, true, 512, JSON_THROW_ON_ERROR);

        return unserialize($payload['data']['command'], ['allowed_classes' => [ProcessLegacyMigrationUrl::class]]);
    }

    private function superAdmin(): User
    {
        $permission = Permission::findOrCreate('access admin panel', 'web');
        $role = Role::findOrCreate('super_admin', 'web');
        $role->givePermissionTo($permission);
        $actor = User::factory()->create(['email_verified_at' => now()]);
        $actor->assignRole($role);

        return $actor;
    }

    /** @return array{LegacyMigrationRun, LegacyStagedObject, LegacyStagedUrl, BlogPost} */
    private function stuckUrl(string $status = 'queued'): array
    {
        $run = LegacyMigrationRun::query()->create([
            'uuid' => (string) Str::uuid(), 'source_system' => 'haidangtravel_legacy', 'source_run_id' => (string) Str::uuid(),
            'schema_version' => 'haidang-legacy-content.v1', 'status' => 'ready_for_mapping',
            'expected_chunks' => 1, 'expected_urls' => 1, 'started_at' => now(),
        ]);
        $legacyId = (string) $run->id;
        $source = LegacyStagedObject::query()->create([
            'run_id' => $run->id, 'object_key' => 'blog:'.$legacyId, 'object_type' => 'blog', 'legacy_id' => $legacyId,
            'is_partial' => false, 'checksum' => str_repeat('d', 64),
            'payload_json' => [
                'attributes' => [
                    'title' => 'Bài viết nguồn', 'slug' => 'bai-nguon', 'content' => '<p>Nội dung nguồn cần khôi phục.</p>',
                    'publish' => 1, 'created_at' => '2018-03-04 05:06:07', 'updated_at' => '2020-05-06 07:08:09',
                ],
                'relationships' => [], 'media' => [],
            ],
            'source_created_at' => '2018-03-04 05:06:07', 'source_updated_at' => '2020-05-06 07:08:09',
        ]);
        $post = BlogPost::query()->create(['title' => 'Target đã tạo', 'slug' => 'target-cu-'.$legacyId, 'status' => 'published']);
        LegacyObjectMap::query()->create([
            'source_identity' => LegacyObjectMap::identity($run->source_system, 'blog', $source->legacy_id, null),
            'source_system' => $run->source_system, 'object_type' => 'blog', 'legacy_id' => $source->legacy_id,
            'last_checksum' => $source->checksum,
            'target_type' => 'blog_post', 'target_id' => (string) $post->id, 'is_partial' => false,
            'first_run_id' => $run->id, 'last_run_id' => $run->id,
        ]);
        $path = '/tin-tuc/URL-Cu-'.$legacyId;
        $url = LegacyStagedUrl::query()->create([
            'run_id' => $run->id, 'raw_path' => $path, 'normalized_path' => $path, 'raw_url' => 'https://haidangtravel.com'.$path,
            'path_hash' => hash('sha256', $path), 'route_kind' => 'blog_detail', 'root_object_key' => $source->object_key,
            'payload_json' => ['source' => 'unchanged'], 'status' => $status, 'target_type' => 'blog_post', 'target_id' => (string) $post->id,
            'target_path' => '/bai-viet/'.$post->slug, 'mapping_mode' => 'cast_preserve_url', 'merge_policy' => 'overwrite',
            'timestamp_policy' => 'source', 'mapped_at' => now()->subDay(), 'casted_at' => now()->subDay(),
            'error_text' => 'Lỗi trước đó',
        ]);
        $url->forceFill(['updated_at' => now()->subMinutes(10)])->save();

        return [$run->refresh(), $source->refresh(), $url->refresh(), $post->refresh()];
    }
}
