<?php

namespace Tests\Feature\LegacyMigration;

use App\Http\Middleware\ResolvePublicUrlMapping;
use App\Models\User;
use App\Modules\LegacyMigration\Models\LegacyCastAudit;
use App\Modules\LegacyMigration\Models\LegacyMigrationRedirect;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyObjectMap;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use App\Modules\LegacyMigration\Services\LegacyContentCaster;
use App\Modules\LegacyMigration\Services\LegacyTimestampNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\PublicUrlMapping;
use Src\Domains\Cms\Models\Tour;
use Tests\TestCase;

class LegacyContentCasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_preserved_blog_alias_can_be_converted_to_301_without_recasting_the_target(): void
    {
        config()->set('public_url_mappings.enabled', true);
        config()->set('frontsite_seo.canonical_url', 'https://haidangtravel.com');
        $actor = User::factory()->create();
        $target = BlogPost::query()->create([
            'title' => 'Du lịch Mộc Châu tháng 5',
            'slug' => 'du-lich-moc-chau-thang-5',
            'content' => '<p>Nội dung đã migrate.</p>',
            'status' => 'published',
            'published_at' => '2020-05-01 08:00:00',
            'canonical_url' => 'https://haidangtravel.com/tin-tuc/du-lich-moc-chau-thang-5',
            'created_at' => '2019-01-02 03:04:05',
            'updated_at' => '2021-02-03 04:05:06',
        ]);
        $run = $this->migrationRun();
        $url = LegacyStagedUrl::query()->create([
            'run_id' => $run->id,
            'raw_url' => 'https://haidangtravel.com/tin-tuc/du-lich-moc-chau-thang-5',
            'raw_path' => '/tin-tuc/du-lich-moc-chau-thang-5',
            'normalized_path' => '/tin-tuc/du-lich-moc-chau-thang-5',
            'path_hash' => hash('sha256', '/tin-tuc/du-lich-moc-chau-thang-5'),
            'route_kind' => 'blog_detail',
            'payload_json' => [],
            'mapping_mode' => 'cast_preserve_url',
            'merge_policy' => 'overwrite',
            'timestamp_policy' => 'source',
            'target_type' => 'blog_post',
            'target_id' => (string) $target->id,
            'target_path' => '/bai-viet/du-lich-moc-chau-thang-5',
            'redirect_code' => 301,
            'status' => 'casted',
            'mapped_by' => $actor->id,
            'mapped_at' => now()->subMinute(),
            'casted_at' => now(),
        ]);
        PublicUrlMapping::query()->create([
            'source_hash' => hash('sha256', $url->normalized_path),
            'source_path' => $url->normalized_path,
            'mode' => PublicUrlMapping::MODE_RENDER,
            'target_type' => 'blog_post',
            'target_id' => (string) $target->id,
            'target_path' => $url->target_path,
            'status_code' => 200,
            'is_active' => true,
            'origin' => 'legacy_migration',
            'created_by' => $actor->id,
        ]);
        $beforeTarget = $target->getRawOriginal();
        $beforeUrl = $url->getRawOriginal();

        app(LegacyContentCaster::class)->convertPreservedUrlToRedirect($url, $actor);

        $target->refresh();
        $url->refresh();
        $this->assertSame('<p>Nội dung đã migrate.</p>', $target->content);
        $this->assertSame($beforeTarget['created_at'], $target->getRawOriginal('created_at'));
        $this->assertSame($beforeTarget['updated_at'], $target->getRawOriginal('updated_at'));
        $this->assertSame('https://haidangtravel.com/bai-viet/du-lich-moc-chau-thang-5', $target->canonical_url);
        $this->assertSame('casted', $url->status);
        $this->assertSame('cast_and_redirect', $url->mapping_mode);
        $this->assertSame($beforeUrl['casted_at'], $url->getRawOriginal('casted_at'));
        $mapping = PublicUrlMapping::query()->where('source_path', $url->normalized_path)->firstOrFail();
        $this->assertSame(PublicUrlMapping::MODE_REDIRECT, $mapping->mode);
        $this->assertSame(301, $mapping->status_code);
        $this->assertDatabaseHas('legacy_cast_audits', ['staged_url_id' => $url->id, 'action' => 'promote_redirect', 'status' => 'completed']);
        $this->get('/tin-tuc/du-lich-moc-chau-thang-5')
            ->assertRedirect('/bai-viet/du-lich-moc-chau-thang-5')
            ->assertHeader('X-Public-URL-Mapping', 'redirect');
        $this->get('/bai-viet/du-lich-moc-chau-thang-5')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://haidangtravel.com/bai-viet/du-lich-moc-chau-thang-5">', false);

        app(LegacyContentCaster::class)->convertPreservedUrlToRedirect($url, $actor);
        $this->assertSame(1, LegacyCastAudit::query()->where('action', 'promote_redirect')->count());
    }

    public function test_confirmed_mapping_casts_into_existing_tour_with_exact_source_timestamps(): void
    {
        config()->set('legacy_migration.enabled', true);
        $actor = User::factory()->create();
        $target = Tour::query()->create([
            'title' => 'Tour đang có',
            'slug' => 'tour-dang-co',
            'status' => 'published',
            'scope' => 'domestic',
            'content' => null,
        ]);
        $oldCreatedAt = $target->created_at->copy();
        $run = $this->migrationRun();
        $source = LegacyStagedObject::query()->create([
            'run_id' => $run->id,
            'object_key' => 'tour:125',
            'object_type' => 'tour',
            'legacy_id' => '125',
            'is_partial' => false,
            'checksum' => str_repeat('a', 64),
            'payload_json' => [
                'key' => 'tour:125',
                'type' => 'tour',
                'legacy_id' => 125,
                'partial' => false,
                'attributes' => [
                    'title' => 'Tour nguồn không ghi đè title',
                    'description' => '<script>alert(1)</script><p>Nội dung nguồn.</p>',
                    'created_at' => '2020-01-02 03:04:05',
                    'updated_at' => '2021-02-03 04:05:06',
                ],
                'relationships' => [],
                'media' => [],
                'checksum' => str_repeat('a', 64),
            ],
            'source_created_at' => '2020-01-02 03:04:05',
            'source_updated_at' => '2021-02-03 04:05:06',
        ]);
        $url = LegacyStagedUrl::query()->create([
            'run_id' => $run->id,
            'raw_path' => '/tour-da-lat-cu',
            'normalized_path' => '/tour-da-lat-cu',
            'path_hash' => hash('sha256', '/tour-da-lat-cu'),
            'route_kind' => 'tour_detail',
            'root_object_key' => $source->object_key,
            'payload_json' => [],
            'mapping_mode' => 'cast_and_redirect',
            'merge_policy' => 'fill_blanks',
            'timestamp_policy' => 'source',
            'target_type' => 'tour',
            'target_id' => (string) $target->id,
            'target_route' => 'tour',
            'target_path' => '/chuong-trinh/tour-dang-co',
            'redirect_code' => 301,
            'status' => 'mapped',
            'mapped_by' => $actor->id,
            'mapped_at' => now(),
        ]);

        app(LegacyContentCaster::class)->cast($url, $actor);

        $target->refresh();
        $this->assertSame('Tour đang có', $target->title);
        $this->assertStringContainsString('Nội dung nguồn.', (string) $target->content);
        $this->assertStringNotContainsString('<script', (string) $target->content);
        $this->assertSame('2020-01-02 03:04:05', $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2021-02-03 04:05:06', $target->updated_at->format('Y-m-d H:i:s'));
        $this->assertNotEquals($oldCreatedAt->format('Y-m-d H:i:s'), $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame(1, LegacyObjectMap::query()->count());
        $this->assertSame(1, LegacyMigrationRedirect::query()->count());
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame('completed', $run->fresh()->status);
        $audit = LegacyCastAudit::query()->where('target_type', 'tour')->firstOrFail();
        $this->assertSame($oldCreatedAt->format('Y-m-d H:i:s'), $audit->previous_target_created_at->format('Y-m-d H:i:s'));
        config()->set('legacy_migration.enabled', false);
        $this->get('/tour-da-lat-cu')->assertRedirect('/chuong-trinh/tour-dang-co');

        app(LegacyContentCaster::class)->cast($url, $actor);
        $this->assertSame(1, LegacyCastAudit::query()->where('target_type', 'tour')->count());
    }

    public function test_redirect_only_never_changes_target_timestamps(): void
    {
        config()->set('legacy_migration.enabled', true);
        $actor = User::factory()->create();
        $target = Tour::query()->create([
            'title' => 'Tour hiện hữu',
            'slug' => 'tour-hien-huu',
            'status' => 'published',
            'scope' => 'domestic',
        ]);
        $timestamps = [$target->created_at->format('Y-m-d H:i:s.u'), $target->updated_at->format('Y-m-d H:i:s.u')];
        $run = $this->migrationRun();
        $url = LegacyStagedUrl::query()->create([
            'run_id' => $run->id,
            'raw_path' => '/noi-dung-cu',
            'normalized_path' => '/noi-dung-cu',
            'path_hash' => hash('sha256', '/noi-dung-cu'),
            'route_kind' => 'unresolved',
            'payload_json' => [],
            'mapping_mode' => 'redirect_only',
            'target_type' => 'tour',
            'target_id' => (string) $target->id,
            'target_path' => '/chuong-trinh/tour-hien-huu',
            'redirect_code' => 301,
            'status' => 'mapped',
            'mapped_by' => $actor->id,
            'mapped_at' => now(),
        ]);

        app(LegacyContentCaster::class)->cast($url, $actor);

        $target->refresh();
        $this->assertSame($timestamps, [$target->created_at->format('Y-m-d H:i:s.u'), $target->updated_at->format('Y-m-d H:i:s.u')]);
        $this->assertSame(0, LegacyObjectMap::query()->count());
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame('completed', $run->fresh()->status);
        $this->get('/noi-dung-cu')
            ->assertRedirect('/chuong-trinh/tour-hien-huu')
            ->assertHeader('X-Public-URL-Mapping', 'redirect');
    }

    public function test_source_timestamp_accepts_iso_offset_and_rejects_invalid_calendar_date(): void
    {
        $normalizer = app(LegacyTimestampNormalizer::class);

        $this->assertSame(
            '2026-09-15 09:00:00',
            $normalizer->nullable('2026-09-15T09:00:00+07:00')?->format('Y-m-d H:i:s'),
        );

        $this->expectException(InvalidArgumentException::class);
        $normalizer->nullable('2026-02-31 09:00:00');
    }

    public function test_redirect_only_rejects_an_unpublished_target(): void
    {
        $actor = User::factory()->create();
        $target = Tour::query()->create([
            'title' => 'Tour nháp',
            'slug' => 'tour-nhap',
            'status' => 'draft',
            'scope' => 'domestic',
        ]);
        $run = $this->migrationRun();
        $url = LegacyStagedUrl::query()->create([
            'run_id' => $run->id,
            'raw_path' => '/trang-cu-khong-con-noi-dung',
            'normalized_path' => '/trang-cu-khong-con-noi-dung',
            'path_hash' => hash('sha256', '/trang-cu-khong-con-noi-dung'),
            'route_kind' => 'unresolved',
            'payload_json' => [],
            'mapping_mode' => 'redirect_only',
            'target_type' => 'tour',
            'target_id' => (string) $target->id,
            'target_path' => '/chuong-trinh/tour-nhap',
            'redirect_code' => 301,
            'status' => 'mapped',
            'mapped_by' => $actor->id,
            'mapped_at' => now(),
        ]);

        try {
            app(LegacyContentCaster::class)->cast($url, $actor);
            $this->fail('Redirect tới page chưa publish phải bị từ chối.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('chưa được publish/active', $exception->getMessage());
        }

        $this->assertSame(0, PublicUrlMapping::query()->count());
        $this->assertSame('failed', $url->fresh()->status);
    }

    public function test_redirect_middleware_does_not_turn_homepage_path_into_invalid_double_slash(): void
    {
        config()->set('public_url_mappings.enabled', true);
        $request = Request::create('/');

        $response = app(ResolvePublicUrlMapping::class)->handle(
            $request,
            fn () => response('frontsite'),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('frontsite', $response->getContent());
    }

    private function migrationRun(): LegacyMigrationRun
    {
        return LegacyMigrationRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => (string) Str::uuid(),
            'schema_version' => 'haidang-legacy-content.v1',
            'status' => 'ready_for_mapping',
            'expected_chunks' => 1,
            'expected_urls' => 1,
            'started_at' => Carbon::now(),
        ]);
    }
}
