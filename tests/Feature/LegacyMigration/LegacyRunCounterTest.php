<?php

namespace Tests\Feature\LegacyMigration;

use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use App\Modules\LegacyMigration\Services\LegacyRunCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LegacyRunCounterTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('runStates')]
    public function test_refresh_uses_current_run_state_not_a_stale_worker_instance(string $currentState, array $statuses, string $expectedState): void
    {
        $run = LegacyMigrationRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => (string) Str::uuid(),
            'schema_version' => 'haidang-legacy-content.v1',
            'status' => 'receiving',
            'expected_chunks' => 1,
            'expected_urls' => 99,
            'started_at' => now(),
        ]);
        LegacyMigrationRun::query()->whereKey($run->id)->update(['status' => $currentState, 'expected_urls' => count($statuses)]);

        foreach ($statuses as $index => $status) {
            $path = '/tin-tuc/bai-'.$index;
            LegacyStagedUrl::query()->create([
                'run_id' => $run->id,
                'raw_url' => 'https://haidangtravel.com'.$path,
                'raw_path' => $path,
                'normalized_path' => $path,
                'path_hash' => hash('sha256', $path),
                'route_kind' => 'blog_detail',
                'payload_json' => [],
                'status' => $status,
            ]);
        }

        $updates = 0;
        LegacyMigrationRun::updating(function (LegacyMigrationRun $model) use ($run, &$updates): void {
            if ($model->id === $run->id) {
                $updates++;
            }
        });

        $result = app(LegacyRunCounter::class)->refresh($run);

        $this->assertSame($run, $result);
        $this->assertSame($expectedState, $result->status);
        $this->assertSame(count($statuses), $result->expected_urls);
        $this->assertSame(count($statuses), $result->staged_urls);
        foreach (['mapped', 'casted', 'blocked', 'failed'] as $status) {
            $this->assertSame(count(array_filter($statuses, fn (string $value): bool => $value === $status)), $result->{$status.'_urls'});
        }
        $this->assertSame(1, $updates);
        $this->assertSame(in_array($expectedState, ['completed', 'completed_with_errors'], true), $result->finished_at !== null);
    }

    public static function runStates(): array
    {
        return [
            'receiving stays receiving' => ['receiving', ['casted', 'failed'], 'receiving'],
            'finalized current state' => ['ready_for_mapping', ['mapped', 'pending'], 'ready_for_mapping'],
            'all terminal' => ['ready_for_mapping', ['casted', 'blocked'], 'completed'],
            'terminal and failures' => ['ready_for_mapping', ['casted', 'failed'], 'completed_with_errors'],
            'retried failure is queued' => ['completed_with_errors', ['casted', 'queued'], 'ready_for_mapping'],
        ];
    }

    public function test_recount_preserves_existing_completion_time(): void
    {
        $run = LegacyMigrationRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => (string) Str::uuid(),
            'schema_version' => 'haidang-legacy-content.v1',
            'status' => 'completed',
            'expected_chunks' => 0,
            'expected_urls' => 0,
            'started_at' => '2026-01-02 03:04:05',
            'finished_at' => '2026-01-03 04:05:06',
        ]);

        $result = app(LegacyRunCounter::class)->refresh($run);

        $this->assertSame('completed', $result->status);
        $this->assertSame('2026-01-03 04:05:06', $result->finished_at->format('Y-m-d H:i:s'));
    }

    public function test_finalized_run_with_a_declared_url_mismatch_completes_against_actual_staging(): void
    {
        $run = LegacyMigrationRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => (string) Str::uuid(),
            'schema_version' => 'haidang-legacy-content.v1',
            'status' => 'ready_for_mapping',
            'expected_chunks' => 1,
            'expected_urls' => 2,
            'error_text' => 'Đã finalize với 1 URL staging trên 2 URL khai báo.',
            'started_at' => now(),
            'finalized_at' => now(),
        ]);
        $path = '/tin-tuc/url-duy-nhat';
        LegacyStagedUrl::query()->create([
            'run_id' => $run->id,
            'raw_url' => 'https://haidangtravel.com'.$path,
            'raw_path' => $path,
            'normalized_path' => $path,
            'path_hash' => hash('sha256', $path),
            'route_kind' => 'blog_detail',
            'payload_json' => [],
            'status' => 'casted',
        ]);

        $result = app(LegacyRunCounter::class)->refresh($run);

        $this->assertSame('completed', $result->status);
        $this->assertSame(2, $result->expected_urls);
        $this->assertSame(1, $result->staged_urls);
        $this->assertSame('Đã finalize với 1 URL staging trên 2 URL khai báo.', $result->error_text);
        $this->assertNotNull($result->finished_at);
    }
}
