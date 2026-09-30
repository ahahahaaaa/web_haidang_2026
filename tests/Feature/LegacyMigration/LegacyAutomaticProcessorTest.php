<?php

namespace Tests\Feature\LegacyMigration;

use App\Models\User;
use App\Modules\LegacyMigration\Exceptions\LegacyImageDownloadFailure;
use App\Modules\LegacyMigration\Jobs\ProcessLegacyMigrationUrl;
use App\Modules\LegacyMigration\Models\LegacyCastAudit;
use App\Modules\LegacyMigration\Models\LegacyMigrationRedirect;
use App\Modules\LegacyMigration\Models\LegacyMigrationRun;
use App\Modules\LegacyMigration\Models\LegacyObjectMap;
use App\Modules\LegacyMigration\Models\LegacyStagedObject;
use App\Modules\LegacyMigration\Models\LegacyStagedUrl;
use App\Modules\LegacyMigration\Services\LegacyAutomaticProcessor;
use App\Modules\LegacyMigration\Services\LegacyBlogMediaImporter;
use App\Modules\LegacyMigration\Services\LegacyImageDownloader;
use App\Modules\LegacyMigration\Services\LegacyRunCounter;
use App\Services\Admin\MediaLibraryUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\PublicUrlMapping;
use Tests\TestCase;

class LegacyAutomaticProcessorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
    }

    #[DataProvider('unavailableImageErrors')]
    public function test_unavailable_images_are_removed_without_blocking_the_page(string $reason, bool $retryable): void
    {
        Storage::fake('public');
        $imageUrl = 'https://images.example.test/unavailable.jpg?signature=secret';
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($imageUrl, $reason, $retryable): void {
            $mock->shouldReceive('download')->once()->with($imageUrl)->andThrow(new LegacyImageDownloadFailure('Không tải được ảnh nguồn.', $imageUrl, $reason, $retryable));
        });
        [$run, $source] = $this->blogSource(1, $imageUrl, content: '<p>Nội dung cần giữ.</p><img src="'.$imageUrl.'" alt="Ảnh nguồn">');
        $payload = $source->payload_json;
        $url = $this->sourceUrl($run, $source, '/tin-tuc/giu-url-du-anh-loi');
        $actor = $this->superAdmin();

        (new ProcessLegacyMigrationUrl($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true))->handle(app(LegacyAutomaticProcessor::class));

        $target = BlogPost::query()->sole();
        $audit = $url->fresh()->latestMediaAudit;
        $this->assertTrue((bool) config('legacy_migration.media.skip_failed_images'));
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame('completed', $run->fresh()->status);
        $this->assertSame('warning', $audit->status);
        $this->assertSame(1, $audit->after_json['skipped']);
        $this->assertSame($reason, $audit->after_json['warnings'][0]['image_error_code']);
        $this->assertSame('https://images.example.test/unavailable.jpg', $audit->after_json['warnings'][0]['image_source']);
        $this->assertStringNotContainsString('signature=secret', json_encode($audit->after_json));
        $this->assertStringContainsString('Nội dung cần giữ.', $target->content);
        $this->assertStringNotContainsString('<img', $target->content);
        $this->assertStringNotContainsString('/images/legacy-migration-placeholder.svg', $target->content);
        $this->assertSame('removed', $audit->after_json['warnings'][0]['content_action']);
        $this->assertNull($target->cover_image_url);
        $this->assertArrayNotHasKey('removed_sources', $audit->after_json);
        $this->assertSame(0, Media::query()->count());
        $this->assertSame(0, LegacyCastAudit::query()->where('status', 'failed')->count());
        $this->assertSame('2018-03-04 05:06:07', $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $target->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('/tin-tuc/giu-url-du-anh-loi', PublicUrlMapping::query()->sole()->source_path);
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertFileExists(public_path('images/legacy-migration-placeholder.svg'));
    }

    public static function unavailableImageErrors(): array
    {
        return [['dns', true], ['curl_35', true], ['curl_60', false], ['http_403', false], ['http_404', false], ['invalid_mime', false], ['image_decode', false]];
    }

    #[DataProvider('legacyFallbackSources')]
    public function test_legacy_image_uses_other_domain_then_reuses_media_without_new_downloads(string $rawSource): void
    {
        Storage::fake('public');
        $primary = 'https://tour.org.vn/image/anh%20cu.png?width=460&signature=secret';
        $fallback = 'https://haidangtravel.com/image/anh%20cu.png?width=460&signature=secret';
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($primary, $fallback): void {
            $mock->shouldReceive('download')->once()->with($primary)->andThrow(new LegacyImageDownloadFailure('HTTP 404', $primary, 'http_404'));
            $mock->shouldReceive('download')->once()->with($fallback)->andReturn(UploadedFile::fake()->image('restored.png', 16, 12));
        });
        [$run, $source] = $this->blogSource(1, content: '<p>Nội dung gốc.</p><img src="'.$rawSource.'">');
        $payload = $source->payload_json;
        $url = $this->sourceUrl($run, $source, '/tin-tuc/nguon-du-phong');
        $actor = $this->superAdmin();
        $processor = app(LegacyAutomaticProcessor::class);

        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);
        $target = BlogPost::query()->sole();
        $library = Media::query()->where('collection_name', 'library')->sole();
        $this->assertSame($fallback, $library->getCustomProperty('legacy_source_url'));
        $this->assertStringContainsString($library->getUrl(), $target->content);
        $this->assertSame('completed', $url->fresh()->latestMediaAudit->status);

        $processor->process($url->id, $actor->id, 'blog', 'reuse_existing', 'cast_preserve_url', 'overwrite', true, true);
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame(1, $url->fresh()->latestMediaAudit->after_json['reused']);
        $this->assertSame(2, Media::query()->count());
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('2018-03-04 05:06:07', $target->fresh()->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('/tin-tuc/nguon-du-phong', PublicUrlMapping::query()->sole()->source_path);
    }

    public static function legacyFallbackSources(): array
    {
        return [
            ['http://www.haidangtravel.com/image/anh cu.png?width=460&amp;signature=secret'],
            ['https://tour.org.vn/image/anh cu.png?width=460&amp;signature=secret'],
            ['/image/anh cu.png?width=460&amp;signature=secret'],
        ];
    }

    public function test_both_legacy_sources_fail_then_picture_is_removed_but_text_good_images_and_source_remain(): void
    {
        Storage::fake('public');
        $bad = 'https://haidangtravel.com/image/deleted.jpg?signature=secret&a=1';
        $primary = 'https://tour.org.vn/image/deleted.jpg?signature=secret&a=1';
        $good = 'https://external.example.test/good.jpg';
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($primary, $bad, $good): void {
            $mock->shouldReceive('download')->once()->with($primary)->andThrow(new LegacyImageDownloadFailure('HTTP 404', $primary, 'http_404'));
            $mock->shouldReceive('download')->once()->with($bad)->andThrow(new LegacyImageDownloadFailure('DNS', $bad, 'dns', true));
            $mock->shouldReceive('download')->once()->with($good)->andReturn(UploadedFile::fake()->image('good.jpg', 16, 12));
        });
        $content = '<p>Giữ mô tả.</p><a href="'.$bad.'"><picture><source srcset="'.$bad.' 2x"><img src="'.str_replace('&', '&amp;', $bad).'" srcset="'.$bad.' 2x"></picture></a><p>Giữ chú thích.</p><img src="'.$good.'">';
        [$run, $source] = $this->blogSource(1, content: $content);
        $payload = $source->payload_json;
        $url = $this->sourceUrl($run, $source, '/tin-tuc/bo-anh-hong');

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $target = BlogPost::query()->sole();
        $audit = $url->fresh()->latestMediaAudit;
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertStringContainsString('Giữ mô tả.', $target->content);
        $this->assertStringContainsString('Giữ chú thích.', $target->content);
        $this->assertStringNotContainsString('deleted.jpg', $target->content);
        $this->assertStringNotContainsString('<a', $target->content);
        $this->assertSame(1, substr_count($target->content, '<img'));
        $this->assertStringContainsString(Media::query()->where('collection_name', 'library')->sole()->getUrl(), $target->content);
        $this->assertSame('removed', $audit->after_json['warnings'][0]['content_action']);
        $this->assertStringContainsString('https://tour.org.vn/image/deleted.jpg', $audit->after_json['warnings'][0]['detail']);
        $this->assertStringContainsString('https://haidangtravel.com/image/deleted.jpg', $audit->after_json['warnings'][0]['detail']);
        $this->assertStringNotContainsString('signature=secret', json_encode($audit->after_json));
        $this->assertArrayNotHasKey('removed_sources', $audit->after_json);
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('2018-03-04 05:06:07', $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $target->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_direct_import_removes_failed_external_picture_without_altering_text_or_link_url(): void
    {
        Storage::fake('public');
        $bad = 'https://external.example.test/missing.jpg?a=1&b=2';
        $content = '<p>URL tham khảo: '.$bad.'</p><a href="'.$bad.'">Giữ link tham khảo</a><figure><picture><source srcset="'.$bad.' 2x"><img src="'.str_replace('&', '&amp;', $bad).'"></picture><figcaption>Giữ chú thích.</figcaption></figure>';
        [$run, $source] = $this->blogSource(1, content: $content);
        $target = BlogPost::query()->create(['title' => 'Bài hiện có', 'slug' => 'bai-hien-co', 'status' => 'published', 'content' => $content]);
        $beforeDates = [$target->created_at, $target->updated_at];
        $this->partialMock(LegacyImageDownloader::class, fn (MockInterface $mock) => $mock->shouldReceive('download')->once()->with($bad)->andThrow(new LegacyImageDownloadFailure('HTTP 403', $bad, 'http_403')));

        $result = app(LegacyBlogMediaImporter::class)->import($target, $source);

        $target->refresh();
        $this->assertStringNotContainsString('<img', $target->content);
        $this->assertStringNotContainsString('<picture', $target->content);
        $this->assertStringNotContainsString('<source', $target->content);
        $this->assertStringNotContainsString('placeholder.svg', $target->content);
        $this->assertStringContainsString('Giữ link tham khảo', $target->content);
        $this->assertStringContainsString('Giữ chú thích.', $target->content);
        $this->assertStringContainsString('href="'.str_replace('&', '&amp;', $bad).'"', $target->content);
        $this->assertSame(1, $result['skipped']);
        $this->assertNull($target->cover_image_url);
        $this->assertEquals($beforeDates, [$target->created_at, $target->updated_at]);
        $this->assertSame($content, $source->fresh()->payload_json['attributes']['content']);
    }

    public function test_legacy_fallback_does_not_bypass_restricted_host_allowlist(): void
    {
        Storage::fake('public');
        config()->set('legacy_migration.media.allow_any_public_host', false);
        config()->set('legacy_migration.media.allowed_hosts', ['tour.org.vn']);
        $primary = 'https://tour.org.vn/image/missing.jpg';
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($primary): void {
            $mock->shouldReceive('download')->once()->with($primary)->andThrow(new LegacyImageDownloadFailure('HTTP 404', $primary, 'http_404'));
            $mock->shouldNotReceive('download')->with('https://haidangtravel.com/image/missing.jpg');
        });
        [$run, $source] = $this->blogSource(1, content: '<p>Giữ bài.</p><img src="'.$primary.'">');
        $url = $this->sourceUrl($run, $source, '/tin-tuc/kiem-tra-allowlist');

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertStringNotContainsString('<img', BlogPost::query()->sole()->content);
    }

    #[DataProvider('localCaErrors')]
    public function test_local_ca_configuration_failure_keeps_placeholder_without_trying_alternate_source(string $reason): void
    {
        Storage::fake('public');
        $primary = 'https://tour.org.vn/image/good.jpg';
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($primary, $reason): void {
            $mock->shouldReceive('download')->once()->with($primary)->andThrow(new LegacyImageDownloadFailure('Lỗi cấu hình CA trên server tải.', $primary, $reason));
            $mock->shouldNotReceive('download')->with('https://haidangtravel.com/image/good.jpg');
        });
        [$run, $source] = $this->blogSource(1, content: '<p>Nội dung.</p><img src="'.$primary.'">');
        $url = $this->sourceUrl($run, $source, '/tin-tuc/ca-local');

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame('placeholder', $url->fresh()->latestMediaAudit->after_json['warnings'][0]['content_action']);
        $this->assertStringContainsString('/images/legacy-migration-placeholder.svg', BlogPost::query()->sole()->content);
    }

    public static function localCaErrors(): array
    {
        return [['ca_bundle'], ['curl_77']];
    }

    public function test_default_image_limit_accepts_more_than_twenty_remote_images(): void
    {
        Storage::fake('public');
        $this->assertSame(100, config('legacy_migration.media.max_images_per_object'));
        $urls = collect(range(1, 21))->map(fn (int $index): string => 'https://tour.org.vn/image/photo-'.$index.'.jpg');
        $content = $urls->map(fn (string $url): string => '<img src="'.$url.'">')->implode('');
        [$run, $source] = $this->blogSource(1, content: $content);
        $url = $this->sourceUrl($run, $source, '/tin-tuc/hon-hai-muoi-anh');
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($urls): void {
            foreach ($urls as $index => $imageUrl) {
                $mock->shouldReceive('download')->once()->with($imageUrl)->andReturn(UploadedFile::fake()->image('photo-'.$index.'.jpg', 16, 12));
            }
        });

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $audit = $url->fresh()->latestMediaAudit;
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame(21, $audit->after_json['imported']);
        $this->assertSame(0, $audit->after_json['skipped']);
        $this->assertSame('completed', $audit->status);
        $this->assertSame(21, Media::query()->where('collection_name', 'library')->count());
        $this->assertStringNotContainsString('/images/legacy-migration-placeholder.svg', BlogPost::query()->sole()->content);
    }

    public function test_mixed_images_continue_after_a_failure_and_use_a_real_cover(): void
    {
        Storage::fake('public');
        $bad = 'https://images.example.test/deleted.jpg?a=1&b=2';
        $good = 'https://images.example.test/good.jpg';
        $content = '<p>Nội dung.</p><img src="'.str_replace('&', '&amp;', $bad).'" alt="Ảnh mất"><img src="'.$good.'" alt="Ảnh tốt">';
        [$run, $source] = $this->blogSource(1, $bad, content: $content);
        $url = $this->sourceUrl($run, $source, '/tin-tuc/anh-tot-va-anh-loi');
        $upload = UploadedFile::fake()->image('good.jpg', 32, 24);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($bad, $good, $upload): void {
            $mock->shouldReceive('download')->once()->with($bad)->andThrow(new LegacyImageDownloadFailure('HTTP 404', $bad, 'http_404'));
            $mock->shouldReceive('download')->once()->with($good)->andReturn($upload);
        });

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $target = BlogPost::query()->sole();
        $library = Media::query()->where('collection_name', 'library')->sole();
        $this->assertSame(1, $url->fresh()->latestMediaAudit->after_json['skipped']);
        $this->assertStringNotContainsString('/images/legacy-migration-placeholder.svg', $target->content);
        $this->assertStringNotContainsString('Ảnh mất', $target->content);
        $this->assertSame(1, substr_count($target->content, '<img'));
        $this->assertStringContainsString($library->getUrl(), $target->content);
        $this->assertStringNotContainsString('&amp;b=2', $target->content);
        $this->assertSame($library->id, $target->getFirstMedia('cover')->getCustomProperty('source_library_media_id'));
    }

    public function test_removed_image_can_be_restored_later_without_recreating_the_blog_or_changing_source(): void
    {
        Storage::fake('public');
        $imageUrl = 'https://images.example.test/image/restored.jpg';
        [$run, $source] = $this->blogSource(1, $imageUrl, content: '<p>Nguồn gốc.</p><img src="'.$imageUrl.'">');
        $payload = $source->payload_json;
        $url = $this->sourceUrl($run, $source, '/tin-tuc/kiem-tra-anh-sau');
        $actor = $this->superAdmin();
        $upload = UploadedFile::fake()->image('restored.jpg', 32, 24);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($imageUrl, $upload): void {
            $mock->shouldReceive('download')->once()->with($imageUrl)->andThrow(new LegacyImageDownloadFailure('DNS', $imageUrl, 'dns', true));
            $mock->shouldReceive('download')->once()->with($imageUrl)->andReturn($upload);
        });
        $processor = app(LegacyAutomaticProcessor::class);
        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);
        $target = BlogPost::query()->sole();
        $id = $target->id;
        $warnings = $url->fresh()->latestMediaAudit->id;
        $this->assertStringNotContainsString('<img', $target->content);

        $processor->process($url->id, $actor->id, 'blog', 'reuse_existing', 'cast_preserve_url', 'overwrite', true, true);

        $target->refresh();
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame((string) $id, $url->fresh()->target_id);
        $this->assertSame('completed', $url->fresh()->latestMediaAudit->status);
        $this->assertSame('warning', LegacyCastAudit::query()->findOrFail($warnings)->status);
        $this->assertStringNotContainsString('/images/legacy-migration-placeholder.svg', $target->content);
        $this->assertStringContainsString(Media::query()->where('collection_name', 'library')->sole()->getUrl(), $target->content);
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('2018-03-04 05:06:07', $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $target->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('/tin-tuc/kiem-tra-anh-sau', PublicUrlMapping::query()->sole()->source_path);
    }

    public function test_invalid_inline_images_and_over_limit_images_use_placeholder_without_leaking_base64(): void
    {
        Storage::fake('public');
        config()->set('legacy_migration.media.max_images_per_object', 1);
        $bad = 'data:image/png;base64,not-a-valid-image';
        $excess = $this->largeInlinePng(12, 8);
        [$run, $source] = $this->blogSource(1, content: '<img src="'.$bad.'"><img src="'.$excess.'">');
        $url = $this->sourceUrl($run, $source, '/tin-tuc/anh-nhung-loi');

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame(2, $url->fresh()->latestMediaAudit->after_json['skipped']);
        $this->assertSame('image_count_limit', $url->fresh()->latestMediaAudit->after_json['warnings'][1]['image_error_code']);
        $this->assertSame(2, substr_count(BlogPost::query()->sole()->content, '/images/legacy-migration-placeholder.svg'));
        $this->assertStringNotContainsString('data:image/', json_encode($url->fresh()->latestMediaAudit->after_json));
        $this->assertSame(0, Media::query()->count());
    }

    public function test_exhausted_object_download_budget_uses_placeholder_without_more_http_requests(): void
    {
        config()->set('legacy_migration.media.max_download_seconds_per_object', 0);
        $this->partialMock(LegacyImageDownloader::class, fn (MockInterface $mock) => $mock->shouldNotReceive('download'));
        [$run, $source] = $this->blogSource(1, 'https://tour.org.vn/image/slow.jpg', content: '<img src="https://tour.org.vn/image/slow.jpg">');
        $url = $this->sourceUrl($run, $source, '/tin-tuc/het-ngan-sach-tai-anh');

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame('object_download_budget', $url->fresh()->latestMediaAudit->after_json['warnings'][0]['image_error_code']);
        $this->assertSame('placeholder', $url->fresh()->latestMediaAudit->after_json['warnings'][0]['content_action']);
        $this->assertStringContainsString('/images/legacy-migration-placeholder.svg', BlogPost::query()->sole()->content);
    }

    public function test_placeholder_policy_does_not_swallow_media_storage_failures(): void
    {
        Storage::fake('public');
        $imageUrl = 'https://tour.org.vn/image/good.jpg';
        $upload = UploadedFile::fake()->image('good.jpg', 32, 24);
        $this->partialMock(LegacyImageDownloader::class, fn (MockInterface $mock) => $mock->shouldReceive('download')->once()->with($imageUrl)->andReturn($upload));
        $this->mock(MediaLibraryUploader::class, fn (MockInterface $mock) => $mock->shouldReceive('uploadToLibrary')->once()->andThrow(new RuntimeException('Media storage unavailable.')));
        [$run, $source] = $this->blogSource(1, $imageUrl);
        $url = $this->sourceUrl($run, $source, '/tin-tuc/loi-storage');
        $actor = $this->superAdmin();
        try {
            (new ProcessLegacyMigrationUrl($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true))->handle(app(LegacyAutomaticProcessor::class));
            $this->fail('Storage failures must still fail the job.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Media storage unavailable.', $exception->getMessage());
        }
        $this->assertSame('failed', $url->fresh()->status);
        $this->assertSame(0, LegacyCastAudit::query()->where('status', 'warning')->count());
        $this->assertEmpty(BlogPost::query()->sole()->content);
    }

    public function test_a_relative_content_source_already_in_manifest_is_not_replaced_by_a_second_failure(): void
    {
        Storage::fake('public');
        $relative = '/image/good.jpg';
        $absolute = 'https://tour.org.vn/image/good.jpg';
        [$run, $source] = $this->blogSource(1, $absolute, content: '<img src="'.$relative.'">');
        $payload = $source->payload_json;
        $payload['media'][0]['source'] = $relative;
        $source->update(['payload_json' => $payload]);
        $upload = UploadedFile::fake()->image('good.jpg', 32, 24);
        $this->partialMock(LegacyImageDownloader::class, fn (MockInterface $mock) => $mock->shouldReceive('download')->once()->with($absolute)->andReturn($upload));
        $url = $this->sourceUrl($run, $source, '/tin-tuc/anh-trung-manifest');

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $this->assertSame(0, $url->fresh()->latestMediaAudit->after_json['skipped']);
        $this->assertSame('completed', $url->fresh()->latestMediaAudit->status);
        $this->assertStringContainsString(Media::query()->where('collection_name', 'library')->sole()->getUrl(), BlogPost::query()->sole()->content);
    }

    public function test_failed_image_placeholders_do_not_replace_an_existing_administrator_cover(): void
    {
        Storage::fake('public');
        $bad = 'https://images.example.test/image/missing.jpg';
        [$run, $source] = $this->blogSource(1, $bad, content: '<img src="'.$bad.'">');
        $target = BlogPost::query()->create(['title' => 'Bài đã có', 'slug' => 'bai-da-co', 'status' => 'published']);
        $cover = $target->addMedia(UploadedFile::fake()->image('admin.jpg', 32, 24))->toMediaCollection('cover');
        $original = $cover->refresh()->getRawOriginal();
        $url = $this->sourceUrl($run, $source, '/tin-tuc/giu-cover');
        $url->update(['target_type' => 'blog_post', 'target_id' => (string) $target->id]);
        $this->partialMock(LegacyImageDownloader::class, fn (MockInterface $mock) => $mock->shouldReceive('download')->once()->with($bad)->andThrow(new LegacyImageDownloadFailure('HTTP 404', $bad, 'http_404')));

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'reuse_existing', 'cast_preserve_url', 'overwrite', true, true);

        $this->assertSame($cover->id, $target->fresh()->getFirstMedia('cover')->id);
        $this->assertSame($original, $cover->fresh()->getRawOriginal());
        $this->assertNull($target->fresh()->cover_image_url);
        $this->assertSame('warning', $url->fresh()->latestMediaAudit->status);
    }

    public function test_corrupt_downloaded_png_is_removed_and_cleans_the_temporary_file(): void
    {
        Storage::fake('public');
        $imageUrl = 'https://images.example.test/image/corrupt.png';
        $valid = UploadedFile::fake()->image('valid.png', 32, 24);
        $upload = UploadedFile::fake()->createWithContent('corrupt.png', substr($valid->get(), 0, 33));
        $path = $upload->getPathname();
        [$run, $source] = $this->blogSource(1, $imageUrl, content: '<img src="'.$imageUrl.'">');
        $url = $this->sourceUrl($run, $source, '/tin-tuc/anh-hong-placeholder');
        $this->partialMock(LegacyImageDownloader::class, fn (MockInterface $mock) => $mock->shouldReceive('download')->once()->with($imageUrl)->andReturn($upload));

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame('image_decode', $url->fresh()->latestMediaAudit->after_json['warnings'][0]['image_error_code']);
        $this->assertSame(0, Media::query()->count());
        $this->assertFileDoesNotExist($path);
        $this->assertStringNotContainsString('<img', BlogPost::query()->sole()->content);
    }

    public function test_failed_html_encoded_manifest_source_also_replaces_the_decoded_content_source(): void
    {
        Storage::fake('public');
        $encoded = 'https://images.example.test/image/missing.jpg?a=1&amp;b=2';
        $decoded = 'https://images.example.test/image/missing.jpg?a=1&b=2';
        [$run, $source] = $this->blogSource(1, $encoded, content: '<img src="'.$decoded.'">');
        $url = $this->sourceUrl($run, $source, '/tin-tuc/anh-encoded-manifest');
        $this->partialMock(LegacyImageDownloader::class, fn (MockInterface $mock) => $mock->shouldReceive('download')->once()->with($decoded)->andThrow(new LegacyImageDownloadFailure('HTTP 404', $decoded, 'http_404')));

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $this->assertSame(1, $url->fresh()->latestMediaAudit->after_json['skipped']);
        $this->assertStringNotContainsString('<img', BlogPost::query()->sole()->content);
        $this->assertStringNotContainsString('missing.jpg', BlogPost::query()->sole()->content);
    }

    public function test_downloader_security_validation_still_rejects_the_source_but_does_not_block_the_page(): void
    {
        Storage::fake('public');
        $urlImage = 'https://images.example.test/private-image.jpg';
        [$run, $source] = $this->blogSource(1, $urlImage, content: '<img src="'.$urlImage.'">');
        $url = $this->sourceUrl($run, $source, '/tin-tuc/nguon-khong-an-toan');
        $this->partialMock(LegacyImageDownloader::class, fn (MockInterface $mock) => $mock->shouldReceive('download')->once()->with($urlImage)->andThrow(new InvalidArgumentException('Nguồn ảnh trỏ tới IP nội bộ hoặc IP dành riêng; chỉ cho phép máy chủ Internet công khai.')));

        app(LegacyAutomaticProcessor::class)->process($url->id, $this->superAdmin()->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame('image_validation', $url->fresh()->latestMediaAudit->after_json['warnings'][0]['image_error_code']);
        $this->assertSame(0, Media::query()->count());
        $this->assertStringNotContainsString('<img', BlogPost::query()->sole()->content);
    }

    public function test_create_new_blog_is_idempotent_across_two_urls_and_preserves_source_timestamps(): void
    {
        config()->set('frontsite_seo.canonical_url', 'https://haidangtravel.com');
        $actor = $this->superAdmin();
        [$run, $source] = $this->blogSource(expectedUrls: 2);
        $firstUrl = $this->sourceUrl($run, $source, '/tin-tuc/bai-viet-migrate');
        $secondUrl = $this->sourceUrl($run, $source, '/blog-cu/bai-viet-migrate');
        $processor = app(LegacyAutomaticProcessor::class);

        foreach ([$firstUrl, $secondUrl] as $url) {
            $processor->process(
                $url->id,
                $actor->id,
                'blog',
                'create_new',
                'cast_and_redirect',
                'overwrite',
                false,
            );
        }

        $post = BlogPost::query()->sole();
        $this->assertSame('bai-viet-migrate', $post->slug);
        $this->assertSame('published', $post->status);
        $this->assertSame('2018-03-04 05:06:07', $post->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $post->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('2018-03-04 05:06:07', $post->published_at->format('Y-m-d H:i:s'));
        $this->assertSame('https://haidangtravel.com/bai-viet/bai-viet-migrate', $post->canonical_url);
        $this->assertSame(1, LegacyObjectMap::query()->count());
        $this->assertSame(2, LegacyMigrationRedirect::query()->count());
        $this->assertSame(2, PublicUrlMapping::query()->where('mode', PublicUrlMapping::MODE_REDIRECT)->where('status_code', 301)->count());
        $this->assertSame(2, LegacyStagedUrl::query()->where('status', 'casted')->count());
        $this->assertSame('completed', $run->fresh()->status);

        $processor->process(
            $firstUrl->id,
            $actor->id,
            'blog',
            'create_new',
            'cast_and_redirect',
            'overwrite',
            false,
        );
        $this->assertSame(1, BlogPost::query()->count());
    }

    public function test_recount_failure_after_commit_does_not_mark_a_successful_cast_as_failed(): void
    {
        $actor = $this->superAdmin();
        [$run, $source] = $this->blogSource(expectedUrls: 1);
        $url = $this->sourceUrl($run, $source, '/tin-tuc/bai-viet-migrate');
        Exceptions::fake();
        $this->partialMock(LegacyRunCounter::class, function (MockInterface $mock): void {
            $mock->shouldReceive('refresh')->twice()->andThrow(new RuntimeException('Recount temporarily unavailable.'));
        });
        $job = new ProcessLegacyMigrationUrl($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', false);

        $job->handle(app(LegacyAutomaticProcessor::class));

        $url->refresh();
        $post = BlogPost::query()->sole();
        $this->assertSame('casted', $url->status);
        $this->assertNull($url->error_text);
        $this->assertNotNull($url->casted_at);
        $this->assertSame((string) $post->id, $url->target_id);
        $this->assertSame('/tin-tuc/bai-viet-migrate', PublicUrlMapping::query()->sole()->source_path);
        $this->assertSame('2018-03-04 05:06:07', $post->created_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, LegacyCastAudit::query()->where('status', 'failed')->count());
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Recount temporarily unavailable.');

        $result = (new LegacyRunCounter)->refresh($run);
        $this->assertSame(1, $result->casted_urls);
        $this->assertSame('completed', $result->status);
    }

    public function test_blog_media_is_uploaded_once_rewritten_and_attached_as_cover_without_changing_timestamps(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        config()->set('legacy_migration.media.allow_any_public_host', false);
        config()->set('legacy_migration.media.allowed_hosts', ['tour.org.vn']);
        config()->set('legacy_migration.media.download_source_remap_from_hosts', ['haidangtravel.com', 'www.haidangtravel.com']);
        config()->set('legacy_migration.media.download_source_remap_to_host', 'tour.org.vn');
        $sourceUrl = 'https://haidangtravel.com/image/legacy-cover.jpg';
        $downloadUrl = 'https://tour.org.vn/image/legacy-cover.jpg';
        $upload = UploadedFile::fake()->image('legacy-cover.jpg', 32, 32);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($downloadUrl, $upload): void {
            $mock->shouldReceive('download')->once()->with($downloadUrl)->andReturn($upload);
        });
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $sourceUrl);
        $sourcePayload = $source->payload_json;
        $post = BlogPost::query()->create([
            'title' => 'Bài viết migrate',
            'slug' => 'bai-viet-migrate',
            'status' => 'published',
            'content' => '<p><img src="'.$sourceUrl.'" alt="Ảnh cũ"></p>',
            'published_at' => '2018-03-04 05:06:07',
        ]);
        BlogPost::withoutTimestamps(function () use ($post): void {
            $post->forceFill([
                'created_at' => '2018-03-04 05:06:07',
                'updated_at' => '2020-05-06 07:08:09',
            ])->save();
        });

        $importer = app(LegacyBlogMediaImporter::class);
        $first = $importer->import($post, $source);
        $second = $importer->import($post->fresh(), $source);
        $post->refresh();
        $libraryMedia = Media::query()->where('collection_name', 'library')->sole();

        $this->assertSame(1, $first['imported']);
        $this->assertSame(1, $second['reused']);
        $this->assertSame(1, Media::query()->where('collection_name', 'library')->count());
        $this->assertSame(1, Media::query()->where('collection_name', 'cover')->count());
        $this->assertSame($downloadUrl, data_get($libraryMedia->custom_properties, 'legacy_source_url'));
        $this->assertStringContainsString($libraryMedia->getUrl(), (string) $post->content);
        $this->assertStringNotContainsString($sourceUrl, (string) $post->content);
        $this->assertSame($sourcePayload, $source->fresh()->payload_json);
        $this->assertSame('2018-03-04 05:06:07', $post->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $post->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_blog_media_remaps_only_exact_legacy_hosts_and_preserves_path_and_query(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        config()->set('legacy_migration.media.allow_any_public_host', true);
        config()->set('legacy_migration.media.download_source_remap_from_hosts', ['haidangtravel.com', 'www.haidangtravel.com']);
        config()->set('legacy_migration.media.download_source_remap_to_host', 'tour.org.vn');
        $rawSourceUrl = 'http://www.haidangtravel.com/image/th%C3%A1ng-9.jpg?width=460&amp;quality=100';
        $downloadUrl = 'https://tour.org.vn/image/th%C3%A1ng-9.jpg?width=460&quality=100';
        $upload = UploadedFile::fake()->image('legacy-remapped.jpg', 32, 32);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($downloadUrl, $upload): void {
            $mock->shouldReceive('download')->once()->with($downloadUrl)->andReturn($upload);
        });
        [$run, $source] = $this->blogSource(
            expectedUrls: 1,
            mediaUrl: $rawSourceUrl,
            content: '<p><img src="'.$rawSourceUrl.'" alt="Ảnh nguồn cũ"></p>',
        );
        $post = BlogPost::query()->create([
            'title' => 'Bài viết remap nguồn ảnh',
            'slug' => 'bai-viet-remap-nguon-anh',
            'status' => 'published',
            'content' => '<p><img src="'.$rawSourceUrl.'" alt="Ảnh nguồn cũ"></p>',
            'published_at' => '2018-03-04 05:06:07',
        ]);

        $result = app(LegacyBlogMediaImporter::class)->import($post, $source);
        $libraryMedia = Media::query()->where('collection_name', 'library')->sole();

        $this->assertSame(1, $result['imported']);
        $this->assertSame($downloadUrl, data_get($libraryMedia->custom_properties, 'legacy_source_url'));
        $this->assertStringContainsString($libraryMedia->getUrl(), (string) $post->fresh()->content);
    }

    #[DataProvider('imagePathSpaceCases')]
    public function test_blog_media_encodes_only_path_spaces_and_preserves_encoded_path_and_query(string $sourceUrl, string $downloadUrl): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        config()->set('legacy_migration.media.allow_any_public_host', true);
        config()->set('legacy_migration.media.download_source_remap_from_hosts', ['haidangtravel.com', 'www.haidangtravel.com']);
        config()->set('legacy_migration.media.download_source_remap_to_host', 'tour.org.vn');
        $upload = UploadedFile::fake()->image('legacy-path-spaces.jpg', 32, 32);
        $this->assertSame(parse_url($downloadUrl, PHP_URL_HOST), (new LegacyImageDownloader)->validatedHost($downloadUrl));
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($downloadUrl, $upload): void {
            $mock->shouldReceive('download')->once()->with($downloadUrl)->andReturn($upload);
        });
        $content = '<p><img src="'.$sourceUrl.'" alt="Ảnh tên file có dấu cách"></p>';
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $sourceUrl, content: $content);
        $payload = $source->payload_json;
        $post = BlogPost::query()->create([
            'title' => 'Bài viết tên ảnh có dấu cách',
            'slug' => 'bai-viet-ten-anh-co-dau-cach',
            'status' => 'published',
            'content' => $content,
        ]);

        $importer = app(LegacyBlogMediaImporter::class);
        $first = $importer->import($post, $source);
        $second = $importer->import($post->fresh(), $source);
        $media = Media::query()->where('collection_name', 'library')->sole();

        $this->assertSame(1, $first['imported']);
        $this->assertSame(1, $second['reused']);
        $this->assertSame($downloadUrl, $media->getCustomProperty('legacy_source_url'));
        $this->assertStringContainsString($media->getUrl(), (string) $post->fresh()->content);
        $this->assertStringNotContainsString($sourceUrl, (string) $post->fresh()->content);
        $this->assertSame($payload, $source->fresh()->payload_json);
    }

    public static function imagePathSpaceCases(): array
    {
        return [
            'original plane image' => [
                'https://haidangtravel.com/image/blog/du-lich-da-lat-thang-12-di chuyen bang-may-bay.jpg',
                'https://tour.org.vn/image/blog/du-lich-da-lat-thang-12-di%20chuyen%20bang-may-bay.jpg',
            ],
            'original coach image' => [
                'https://haidangtravel.com/image/blog/du-lich-da-lat-thang-12-di chuyen-bang-xe-khach.jpg',
                'https://tour.org.vn/image/blog/du-lich-da-lat-thang-12-di%20chuyen-bang-xe-khach.jpg',
            ],
            'protocol relative www and signed query' => [
                '//www.haidangtravel.com/image/th%C3%A1ng%209 hai.jpg?sig=a+b%2Fc&amp;key=x%20y',
                'https://tour.org.vn/image/th%C3%A1ng%209%20hai.jpg?sig=a+b%2Fc&key=x%20y',
            ],
            'external host and existing encoding' => [
                'http://images.example.test/image/anh%20cu hai%2Fba.jpg?sig=a+b%2Fc&amp;key=x%20y',
                'http://images.example.test/image/anh%20cu%20hai%2Fba.jpg?sig=a+b%2Fc&key=x%20y',
            ],
            'legacy standard port and fragment' => [
                'http://haidangtravel.com:80/image/anh cu.jpg?key=x%20y#photo',
                'https://tour.org.vn/image/anh%20cu.jpg?key=x%20y',
            ],
            'external standard port and fragment' => [
                'https://images.example.test:443/image/anh cu.jpg?key=x%20y#photo',
                'https://images.example.test:443/image/anh%20cu.jpg?key=x%20y',
            ],
            'unicode filename' => [
                'https://haidangtravel.com/image/ảnh cũ.jpg',
                'https://tour.org.vn/image/%E1%BA%A3nh%20c%C5%A9.jpg',
            ],
        ];
    }

    #[DataProvider('unsafeImageUrlCases')]
    public function test_path_space_normalization_does_not_bypass_unsafe_url_checks(string $sourceUrl): void
    {
        config()->set('legacy_migration.media.skip_failed_images', false);
        config()->set('legacy_migration.media.allow_any_public_host', true);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('download');
        });
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $sourceUrl);
        $post = BlogPost::query()->create(['title' => 'Bài nguồn ảnh không hợp lệ', 'slug' => 'bai-nguon-anh-khong-hop-le', 'status' => 'published']);

        $this->expectException(InvalidArgumentException::class);

        app(LegacyBlogMediaImporter::class)->prepare($post, $source);
    }

    public static function unsafeImageUrlCases(): array
    {
        return [
            'space in hostname' => ['https://haidangtravel .com/image/anh cu.jpg'],
            'space in query' => ['https://haidangtravel.com/image/anh cu.jpg?sig=a b'],
            'credentials' => ['https://user:password@haidangtravel.com/image/anh cu.jpg'],
            'nonstandard port' => ['https://haidangtravel.com:8443/image/anh cu.jpg'],
            'wrong scheme port' => ['http://haidangtravel.com:443/image/anh cu.jpg'],
            'tab in path' => ["https://haidangtravel.com/image/anh\tcu.jpg"],
            'newline in path' => ["https://haidangtravel.com/image/anh\ncu.jpg"],
            'backslash in path' => ['https://haidangtravel.com/image/anh\\cu.jpg'],
            'encoded length exceeds limit' => ['https://haidangtravel.com/image/'.str_repeat('a ', 700).'.jpg'],
        ];
    }

    public function test_retry_with_path_spaces_reuses_existing_blog_and_preserves_source_url_and_timestamps(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        config()->set('legacy_migration.media.allow_any_public_host', true);
        config()->set('legacy_migration.media.download_source_remap_from_hosts', ['haidangtravel.com', 'www.haidangtravel.com']);
        config()->set('legacy_migration.media.download_source_remap_to_host', 'tour.org.vn');
        $sourceUrl = 'https://haidangtravel.com/image/blog/du-lich-da-lat-thang-12-di chuyen bang-may-bay.jpg';
        $downloadUrl = 'https://tour.org.vn/image/blog/du-lich-da-lat-thang-12-di%20chuyen%20bang-may-bay.jpg';
        $upload = UploadedFile::fake()->image('retry-existing-blog.jpg', 32, 32);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($downloadUrl, $upload): void {
            $mock->shouldReceive('download')->once()->with($downloadUrl)->andReturn($upload);
        });
        $actor = $this->superAdmin();
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $sourceUrl, content: '<p>Nội dung gốc.</p><img src="'.$sourceUrl.'" alt="Ảnh gốc">');
        $payload = $source->payload_json;
        $post = BlogPost::query()->create(['title' => 'Target đã tạo', 'slug' => 'target-da-tao', 'status' => 'published']);
        $oldPath = '/tin-tuc/da-lat-thang-12';
        $url = $this->sourceUrl($run, $source, $oldPath);
        $url->update(['target_type' => 'blog_post', 'target_id' => (string) $post->id, 'mapping_mode' => 'cast_preserve_url', 'merge_policy' => 'overwrite']);
        $processor = app(LegacyAutomaticProcessor::class);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $processor->process($url->id, $actor->id, 'blog', 'reuse_existing', 'cast_preserve_url', 'overwrite', true, true);
        }

        $post->refresh();
        $media = Media::query()->where('collection_name', 'library')->sole();
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame((string) $post->id, $url->fresh()->target_id);
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame($oldPath, $url->fresh()->normalized_path);
        $this->assertSame($oldPath, PublicUrlMapping::query()->sole()->source_path);
        $this->assertStringContainsString('Nội dung gốc.', (string) $post->content);
        $this->assertStringContainsString($media->getUrl(), (string) $post->content);
        $this->assertStringNotContainsString($sourceUrl, (string) $post->content);
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('2018-03-04 05:06:07', $post->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $post->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_blog_media_does_not_remap_a_lookalike_legacy_hostname(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        config()->set('legacy_migration.media.allow_any_public_host', true);
        config()->set('legacy_migration.media.download_source_remap_from_hosts', ['haidangtravel.com', 'www.haidangtravel.com']);
        config()->set('legacy_migration.media.download_source_remap_to_host', 'tour.org.vn');
        $sourceUrl = 'https://haidangtravel.com.example.test/image/not-legacy.jpg';
        $upload = UploadedFile::fake()->image('not-legacy.jpg', 32, 32);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($sourceUrl, $upload): void {
            $mock->shouldReceive('download')->once()->with($sourceUrl)->andReturn($upload);
        });
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $sourceUrl);
        $post = BlogPost::query()->create([
            'title' => 'Bài viết nguồn ảnh tương tự',
            'slug' => 'bai-viet-nguon-anh-tuong-tu',
            'status' => 'published',
            'content' => '<p><img src="'.$sourceUrl.'" alt="Ảnh host khác"></p>',
            'published_at' => '2018-03-04 05:06:07',
        ]);

        app(LegacyBlogMediaImporter::class)->import($post, $source);

        $this->assertSame(
            $sourceUrl,
            data_get(Media::query()->where('collection_name', 'library')->sole()->custom_properties, 'legacy_source_url'),
        );
    }

    public function test_blog_media_accepts_any_public_host_and_decodes_html_entities_in_source_url(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        config()->set('legacy_migration.media.allow_any_public_host', true);
        config()->set('legacy_migration.media.allowed_hosts', ['haidangtravel.com']);
        $rawSourceUrl = 'https://tour.org.vn/image/legacy-cover.jpg?width=460&amp;quality=100';
        $normalizedSourceUrl = 'https://tour.org.vn/image/legacy-cover.jpg?width=460&quality=100';
        $upload = UploadedFile::fake()->image('legacy-cover.jpg', 32, 32);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($normalizedSourceUrl, $upload): void {
            $mock->shouldReceive('download')->once()->with($normalizedSourceUrl)->andReturn($upload);
        });
        [$run, $source] = $this->blogSource(
            expectedUrls: 1,
            mediaUrl: $rawSourceUrl,
            content: '<p><img src="'.$rawSourceUrl.'" alt="Ảnh ngoài host legacy"></p>',
        );
        $post = BlogPost::query()->create([
            'title' => 'Bài viết migrate nguồn ảnh ngoài',
            'slug' => 'bai-viet-migrate-nguon-anh-ngoai',
            'status' => 'published',
            'content' => '<p><img src="'.$rawSourceUrl.'" alt="Ảnh ngoài host legacy"></p>',
            'published_at' => '2018-03-04 05:06:07',
        ]);

        $result = app(LegacyBlogMediaImporter::class)->import($post, $source);
        $post->refresh();
        $libraryMedia = Media::query()->where('collection_name', 'library')->sole();

        $this->assertSame(1, $result['imported']);
        $this->assertSame($normalizedSourceUrl, data_get($libraryMedia->custom_properties, 'legacy_source_url'));
        $this->assertStringContainsString($libraryMedia->getUrl(), (string) $post->content);
        $this->assertStringNotContainsString($rawSourceUrl, (string) $post->content);
    }

    public function test_remote_blog_media_rejects_images_over_the_pixel_limit_before_upload(): void
    {
        config()->set('legacy_migration.media.skip_failed_images', false);
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        config()->set('legacy_migration.media.allow_any_public_host', true);
        config()->set('legacy_migration.media.max_remote_pixels', 100);
        $sourceUrl = 'https://images.example.test/large-compressed-image.jpg';
        $upload = UploadedFile::fake()->image('large-compressed-image.jpg', 32, 32);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($sourceUrl, $upload): void {
            $mock->shouldReceive('download')->once()->with($sourceUrl)->andReturn($upload);
        });
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $sourceUrl);
        $post = BlogPost::query()->create([
            'title' => 'Bài viết ảnh quá lớn',
            'slug' => 'bai-viet-anh-qua-lon',
            'status' => 'published',
            'content' => '<p><img src="'.$sourceUrl.'" alt="Ảnh quá lớn"></p>',
            'published_at' => '2018-03-04 05:06:07',
        ]);

        try {
            app(LegacyBlogMediaImporter::class)->import($post, $source);
            $this->fail('Ảnh vượt giới hạn pixel phải bị từ chối trước khi upload.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('vượt quá giới hạn 100 pixels', $exception->getMessage());
        }

        $this->assertSame(0, Media::query()->count());
    }

    public function test_base64_image_in_blog_content_is_uploaded_deduplicated_and_rewritten_before_sanitize(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $base64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        $dataUri = 'data:image/png;base64,'.$base64;
        $content = '<p>Trước ảnh</p><p><img src="'.$dataUri.'" alt="Ảnh nhúng cũ"></p><p>Sau ảnh</p>';
        [$run, $source] = $this->blogSource(expectedUrls: 1, content: $content);
        $url = $this->sourceUrl($run, $source, '/tin-tuc/bai-co-anh-nhung');
        $actor = $this->superAdmin();
        $processor = app(LegacyAutomaticProcessor::class);

        $processor->process(
            $url->id,
            $actor->id,
            'blog',
            'create_new',
            'cast_and_redirect',
            'overwrite',
            true,
        );

        $post = BlogPost::query()->sole();
        $libraryMedia = Media::query()->where('collection_name', 'library')->sole();
        $this->assertStringContainsString($libraryMedia->getUrl(), (string) $post->content);
        $this->assertStringNotContainsString('data:image/', (string) $post->content);
        $this->assertSame(hash('sha256', base64_decode($base64)), data_get($libraryMedia->custom_properties, 'legacy_inline_sha256'));
        $this->assertSame(1, Media::query()->where('collection_name', 'cover')->count());
        $this->assertSame('2018-03-04 05:06:07', $post->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $post->updated_at->format('Y-m-d H:i:s'));

        $processor->process(
            $url->id,
            $actor->id,
            'blog',
            'create_new',
            'cast_and_redirect',
            'overwrite',
            true,
        );

        $this->assertSame(1, Media::query()->where('collection_name', 'library')->count());
        $this->assertSame(1, Media::query()->where('collection_name', 'cover')->count());
        $this->assertSame('casted', $url->fresh()->status);
    }

    public function test_invalid_embedded_image_is_saved_as_a_concise_url_error_and_audit(): void
    {
        config()->set('legacy_migration.media.skip_failed_images', false);
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $dataUri = 'data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        [$run, $source] = $this->blogSource(
            expectedUrls: 1,
            content: '<p><img src="'.$dataUri.'" alt="SVG"></p>',
        );
        $url = $this->sourceUrl($run, $source, '/tin-tuc/anh-nhung-khong-hop-le');
        $actor = $this->superAdmin();
        $job = new ProcessLegacyMigrationUrl(
            $url->id,
            $actor->id,
            'blog',
            'create_new',
            'cast_and_redirect',
            'overwrite',
            true,
        );

        $job->handle(app(LegacyAutomaticProcessor::class));

        $url->refresh();
        $audit = LegacyCastAudit::query()->where('staged_url_id', $url->id)->where('status', 'failed')->sole();
        $this->assertSame('failed', $url->status);
        $this->assertSame('Ảnh nhúng trong nội dung phải là JPEG, PNG, WebP hoặc AVIF dạng base64 hợp lệ.', $url->error_text);
        $this->assertSame($url->error_text, $audit->error_text);
        $this->assertStringNotContainsString('data:image/', (string) $audit->error_text);
        $this->assertSame(0, Media::query()->count());
    }

    public function test_two_large_inline_pngs_with_an_empty_manifest_survive_cast_and_retry(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $first = $this->largeInlinePng(692, 463);
        $second = $this->largeInlinePng(692, 361);
        $content = '<p>Cung đình Huế</p><img src="'.$first.'" alt="Ảnh thứ nhất"><img src="'.$second.'" alt="Ảnh thứ hai">';
        $this->assertGreaterThan(3_000_000, strlen($content));
        [$run, $source] = $this->blogSource(expectedUrls: 1, content: $content);
        $payload = $source->payload_json;
        $url = $this->sourceUrl($run, $source, '/tin-tuc/kien-truc-nghe-thuat-cung-dinh-hue');
        $actor = $this->superAdmin();
        $processor = app(LegacyAutomaticProcessor::class);

        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);
        $target = BlogPost::query()->sole();
        $before = $target->getRawOriginal();
        $this->assertSame(2, substr_count($target->content, '<img'));
        $this->assertStringNotContainsString('data:image/', $target->content);
        foreach (Media::query()->where('collection_name', 'library')->get() as $media) {
            $this->assertStringContainsString($media->getUrl(), $target->content);
            $this->assertFileExists($media->getPath());
        }
        $this->assertSame(2, Media::query()->where('collection_name', 'library')->count());
        $this->assertSame(1, Media::query()->where('collection_name', 'cover')->count());
        $this->assertSame('/tin-tuc/kien-truc-nghe-thuat-cung-dinh-hue', PublicUrlMapping::query()->sole()->source_path);
        $this->assertSame('2018-03-04 05:06:07', $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $target->updated_at->format('Y-m-d H:i:s'));

        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true, reuseExistingTarget: true);

        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame($before, $target->fresh()->getRawOriginal());
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame(2, Media::query()->where('collection_name', 'library')->count());
        $this->assertSame(1, PublicUrlMapping::query()->count());
        $audit = LegacyCastAudit::query()->where('action', 'import_media')->latest('id')->firstOrFail();
        $this->assertSame(2, $audit->after_json['reused']);
        $this->assertArrayNotHasKey('replacements', $audit->after_json);
    }

    public function test_inline_images_are_not_silently_removed_when_media_import_is_disabled(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $data = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';
        [$run, $source] = $this->blogSource(expectedUrls: 1, content: '<p>Nội dung</p><img src="'.$data.'">');
        $url = $this->sourceUrl($run, $source, '/tin-tuc/anh-nhung-can-media');
        $actor = $this->superAdmin();
        $processor = app(LegacyAutomaticProcessor::class);
        $job = new ProcessLegacyMigrationUrl($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', false);

        $job->handle($processor);

        $target = BlogPost::query()->sole();
        $this->assertSame('failed', $url->fresh()->status);
        $this->assertStringContainsString('Bật tải ảnh về Media Library', $url->fresh()->error_text);
        $this->assertSame(0, Media::query()->count());
        $this->assertEmpty($target->content);

        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true, reuseExistingTarget: true);

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame((string) $target->id, $url->fresh()->target_id);
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame(1, substr_count($target->fresh()->content, '<img'));
    }

    #[DataProvider('avifSources')]
    public function test_avif_sources_are_converted_to_real_webp_in_library_and_cover(bool $embedded): void
    {
        if (! function_exists('imageavif') || ! (gd_info()['AVIF Support'] ?? false)) {
            $this->markTestSkipped('GD AVIF support is required for this conversion test.');
        }

        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        config()->set('media-library.image_driver', 'gd');
        $image = imagecreatetruecolor(32, 24);
        ob_start();
        imageavif($image);
        $binary = ob_get_clean();
        $imageUrl = $embedded ? 'data:image/avif;base64,'.base64_encode($binary) : 'https://tour.org.vn/image/old.avif';
        if (! $embedded) {
            $upload = UploadedFile::fake()->createWithContent('old.avif', $binary);
            $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($imageUrl, $upload): void {
                $mock->shouldReceive('download')->once()->with($imageUrl)->andReturn($upload);
            });
        }
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $embedded ? null : $imageUrl, content: '<img src="'.$imageUrl.'">');
        $payload = $source->payload_json;
        $url = $this->sourceUrl($run, $source, '/tin-tuc/anh-avif');
        $actor = $this->superAdmin();

        app(LegacyAutomaticProcessor::class)->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        $target = BlogPost::query()->sole();
        $media = Media::query()->where('collection_name', 'library')->sole();
        $cover = $target->getFirstMedia('cover');
        $this->assertSame('image/webp', $media->mime_type);
        $this->assertSame('image/webp', (new \finfo(FILEINFO_MIME_TYPE))->file($media->getPath()));
        $this->assertNotFalse(imagecreatefromwebp($media->getPath()));
        $this->assertSame('image/webp', $cover->mime_type);
        $this->assertFileExists($cover->getPath('small'));
        $this->assertStringContainsString($media->getUrl(), $target->content);
        $this->assertStringNotContainsString($imageUrl, $target->content);
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('casted', $url->fresh()->status);
    }

    public static function avifSources(): array
    {
        return [[false], [true]];
    }

    public function test_broken_png_failure_records_the_image_source_and_does_not_cast_or_create_media(): void
    {
        config()->set('legacy_migration.media.skip_failed_images', false);
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        config()->set('media-library.image_driver', 'gd');
        $imageUrl = 'https://tour.org.vn/image/broken.png?signature=secret';
        $valid = UploadedFile::fake()->image('valid.png', 32, 24);
        $upload = UploadedFile::fake()->createWithContent('broken.png', substr(file_get_contents($valid->getPathname()), 0, 33));
        $fallbackUpload = UploadedFile::fake()->createWithContent('broken-fallback.png', substr(file_get_contents($valid->getPathname()), 0, 33));
        $temporary = $upload->getPathname();
        $fallbackTemporary = $fallbackUpload->getPathname();
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($imageUrl, $upload, $fallbackUpload): void {
            $mock->shouldReceive('download')->once()->with($imageUrl)->andReturn($upload);
            $mock->shouldReceive('download')->once()->with('https://haidangtravel.com/image/broken.png?signature=secret')->andReturn($fallbackUpload);
        });
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $imageUrl, content: '<img src="'.$imageUrl.'">');
        $payload = $source->payload_json;
        $url = $this->sourceUrl($run, $source, '/tin-tuc/anh-png-hong');
        $actor = $this->superAdmin();

        (new ProcessLegacyMigrationUrl($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true))->handle(app(LegacyAutomaticProcessor::class));

        $audit = LegacyCastAudit::query()->where('status', 'failed')->sole();
        $this->assertStringContainsString('Không thể giải mã đầy đủ ảnh PNG', $url->fresh()->error_text);
        $this->assertSame('failed', $url->fresh()->status);
        $this->assertSame('https://haidangtravel.com/image/broken.png', $audit->after_json['image_source']);
        $this->assertSame('image_decode', $audit->after_json['image_error_code']);
        $this->assertFalse($audit->after_json['retryable']);
        $this->assertEmpty(BlogPost::query()->sole()->content);
        $this->assertSame(0, Media::query()->count());
        $this->assertSame(0, PublicUrlMapping::query()->count());
        $this->assertFileDoesNotExist($temporary);
        $this->assertFileDoesNotExist($fallbackTemporary);
        $this->assertSame($payload, $source->fresh()->payload_json);
    }

    public function test_source_image_override_repairs_failed_url_without_reimporting_or_recreating_its_target(): void
    {
        config()->set('legacy_migration.media.skip_failed_images', false);
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $old = 'https://tour.org.vn/image/deleted.png';
        $replacement = 'https://cdn.example.test/restored.png';
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $old, content: '<p>Nội dung nguồn</p><img src="'.$old.'">');
        $payload = $source->payload_json;
        $url = $this->sourceUrl($run, $source, '/tin-tuc/giu-nguyen-url-cu');
        $actor = $this->superAdmin();
        $upload = UploadedFile::fake()->image('restored.png', 32, 24);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($old, $replacement, $upload): void {
            $mock->shouldReceive('download')->once()->with($old)->andThrow(new LegacyImageDownloadFailure('HTTP 404', $old, 'http_404'));
            $mock->shouldReceive('download')->once()->with('https://haidangtravel.com/image/deleted.png')->andThrow(new LegacyImageDownloadFailure('HTTP 404', 'https://haidangtravel.com/image/deleted.png', 'http_404'));
            $mock->shouldReceive('download')->once()->with($replacement)->andReturn($upload);
        });
        $processor = app(LegacyAutomaticProcessor::class);

        (new ProcessLegacyMigrationUrl($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true))->handle($processor);
        $targetId = $url->fresh()->target_id;
        $this->assertSame('failed', $url->fresh()->status);
        config()->set('legacy_migration.media.source_url_overrides', [$old => $replacement]);

        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true, reuseExistingTarget: true);

        $target = BlogPost::query()->sole();
        $media = Media::query()->where('collection_name', 'library')->sole();
        $this->assertSame($targetId, (string) $target->id);
        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame($replacement, $media->getCustomProperty('legacy_source_url'));
        $this->assertStringContainsString($media->getUrl(), $target->content);
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('2018-03-04 05:06:07', $target->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $target->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('/tin-tuc/giu-nguyen-url-cu', PublicUrlMapping::query()->sole()->source_path);
    }

    public function test_retry_regenerates_missing_conversions_of_the_migrated_cover_without_duplicating_media(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        [$run, $source] = $this->blogSource(expectedUrls: 1, content: '<img src="'.$this->largeInlinePng(12, 8).'">');
        $url = $this->sourceUrl($run, $source, '/tin-tuc/khoi-phuc-cover');
        $actor = $this->superAdmin();
        $processor = app(LegacyAutomaticProcessor::class);
        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);
        $target = BlogPost::query()->sole();
        $cover = $target->getFirstMedia('cover');
        Storage::disk($cover->conversions_disk)->delete($cover->getPathRelativeToRoot('small'));
        $this->assertFileDoesNotExist($cover->getPath('small'));
        $before = $target->getRawOriginal();

        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true, reuseExistingTarget: true);

        $this->assertSame($cover->id, $target->fresh()->getFirstMedia('cover')->id);
        $this->assertFileExists($cover->getPath('small'));
        $this->assertSame(2, Media::query()->count());
        $this->assertSame($before, $target->fresh()->getRawOriginal());
    }

    public function test_queue_retries_a_transient_image_failure_using_the_same_target_and_source(): void
    {
        config()->set('legacy_migration.media.skip_failed_images', false);
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $imageUrl = 'https://images.example.test/image/temporary.png';
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $imageUrl, content: '<img src="'.$imageUrl.'">');
        $payload = $source->payload_json;
        $url = $this->sourceUrl($run, $source, '/tin-tuc/thu-lai-ket-noi');
        $actor = $this->superAdmin();
        $upload = UploadedFile::fake()->image('temporary.png', 32, 24);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($imageUrl, $upload): void {
            $mock->shouldReceive('download')->once()->with($imageUrl)->andThrow(new LegacyImageDownloadFailure('Lỗi bắt tay TLS.', $imageUrl, 'curl_35', true));
            $mock->shouldReceive('download')->once()->with($imageUrl)->andReturn($upload);
        });
        $processor = app(LegacyAutomaticProcessor::class);
        $job = new ProcessLegacyMigrationUrl($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);

        try {
            $job->handle($processor);
            $this->fail('Transient failure must be returned to the queue for retry.');
        } catch (LegacyImageDownloadFailure $exception) {
            $this->assertTrue($exception->retryable);
        }

        $this->assertSame('failed', $url->fresh()->status);
        $targetId = $url->fresh()->target_id;
        $audit = LegacyCastAudit::query()->where('status', 'failed')->sole();
        $this->assertSame('curl_35', $audit->after_json['image_error_code']);
        $job->handle($processor);

        $this->assertSame('casted', $url->fresh()->status);
        $this->assertSame($targetId, $url->fresh()->target_id);
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame(1, LegacyCastAudit::query()->where('status', 'failed')->count());
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('2018-03-04 05:06:07', BlogPost::query()->sole()->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('/tin-tuc/thu-lai-ket-noi', PublicUrlMapping::query()->sole()->source_path);
    }

    public function test_retry_repairs_a_corrupt_cached_png_and_migrated_cover_in_place(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        $imageUrl = 'https://tour.org.vn/image/old-cached.png';
        $firstUpload = UploadedFile::fake()->image('old-cached.png', 32, 24);
        $secondUpload = UploadedFile::fake()->image('old-cached.png', 32, 24);
        $this->partialMock(LegacyImageDownloader::class, function (MockInterface $mock) use ($imageUrl, $firstUpload, $secondUpload): void {
            $mock->shouldReceive('download')->once()->with($imageUrl)->andReturn($firstUpload);
            $mock->shouldReceive('download')->once()->with($imageUrl)->andReturn($secondUpload);
        });
        [$run, $source] = $this->blogSource(expectedUrls: 1, mediaUrl: $imageUrl, content: '<img src="'.$imageUrl.'">');
        $payload = $source->payload_json;
        $url = $this->sourceUrl($run, $source, '/tin-tuc/sua-cache-anh-hong');
        $actor = $this->superAdmin();
        $processor = app(LegacyAutomaticProcessor::class);
        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);
        $target = BlogPost::query()->sole();
        $libraryMedia = Media::query()->where('collection_name', 'library')->sole();
        $cover = $target->getFirstMedia('cover');
        $broken = substr(file_get_contents($libraryMedia->getPath()), 0, 33);
        Storage::disk($libraryMedia->disk)->put($libraryMedia->getPathRelativeToRoot(), $broken);
        Storage::disk($cover->disk)->put($cover->getPathRelativeToRoot(), $broken);
        Storage::disk($cover->conversions_disk)->delete($cover->getPathRelativeToRoot('small'));

        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true, reuseExistingTarget: true);

        $newLibraryMedia = Media::query()->where('collection_name', 'library')->latest('id')->firstOrFail();
        $this->assertNotSame($libraryMedia->id, $newLibraryMedia->id);
        $this->assertSame(1, BlogPost::query()->count());
        $this->assertSame(1, Media::query()->where('collection_name', 'cover')->count());
        $this->assertSame($cover->id, $target->fresh()->getFirstMedia('cover')->id);
        $this->assertNotFalse(@imagecreatefromstring(file_get_contents($cover->getPath())));
        $this->assertFileExists($cover->getPath('small'));
        $this->assertSame($newLibraryMedia->id, $cover->fresh()->getCustomProperty('source_library_media_id'));
        $this->assertStringContainsString($newLibraryMedia->getUrl(), $target->fresh()->content);
        $this->assertSame($payload, $source->fresh()->payload_json);
        $this->assertSame('2018-03-04 05:06:07', $target->fresh()->created_at->format('Y-m-d H:i:s'));
        $this->assertSame('2020-05-06 07:08:09', $target->fresh()->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('casted', $url->fresh()->status);
    }

    public function test_media_retry_does_not_replace_an_administrator_cover(): void
    {
        Storage::fake('public');
        config()->set('media-library.disk_name', 'public');
        [$run, $source] = $this->blogSource(expectedUrls: 1, content: '<img src="'.$this->largeInlinePng(12, 8).'">');
        $url = $this->sourceUrl($run, $source, '/tin-tuc/giu-cover-quan-tri');
        $actor = $this->superAdmin();
        $processor = app(LegacyAutomaticProcessor::class);
        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true);
        $target = BlogPost::query()->sole();
        $cover = $target->addMedia(UploadedFile::fake()->image('admin-cover.jpg', 32, 24))->toMediaCollection('cover');
        $beforeHash = hash_file('sha256', $cover->getPath());
        $before = $cover->refresh()->getRawOriginal();

        $processor->process($url->id, $actor->id, 'blog', 'create_new', 'cast_preserve_url', 'overwrite', true, reuseExistingTarget: true);

        $this->assertSame($cover->id, $target->fresh()->getFirstMedia('cover')->id);
        $this->assertSame($beforeHash, hash_file('sha256', $cover->getPath()));
        $this->assertSame($before, $cover->fresh()->getRawOriginal());
    }

    private function largeInlinePng(int $width, int $height): string
    {
        $chunk = fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('H*', hash('crc32b', $type.$data));
        $scanlines = str_repeat("\x00".str_repeat("\x22\x44\x66\xff", $width), $height);
        $png = "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 6, 0, 0, 0))
            .$chunk('IDAT', gzcompress($scanlines, 0))
            .$chunk('IEND', '');

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function test_validation_error_details_are_persisted_instead_of_the_generic_exception_message(): void
    {
        [$run, $source] = $this->blogSource(expectedUrls: 1);
        $url = $this->sourceUrl($run, $source, '/tin-tuc/loi-tai-anh');
        $actor = $this->superAdmin();
        $exception = ValidationException::withMessages([
            'image_url' => 'Không tải được ảnh gốc từ website cũ.',
        ]);

        app(LegacyAutomaticProcessor::class)->markFailed($url->id, $actor->id, $exception);

        $this->assertSame('Không tải được ảnh gốc từ website cũ.', $url->fresh()->error_text);
        $this->assertSame(
            'Không tải được ảnh gốc từ website cũ.',
            LegacyCastAudit::query()->where('staged_url_id', $url->id)->where('status', 'failed')->sole()->error_text,
        );
    }

    public function test_published_blog_renders_at_the_exact_original_url(): void
    {
        $actor = $this->superAdmin();
        [$run, $source] = $this->blogSource(expectedUrls: 1);
        $url = $this->sourceUrl($run, $source, '/tin-tuc/bai-viet-giu-url');

        app(LegacyAutomaticProcessor::class)->process(
            $url->id,
            $actor->id,
            'blog',
            'create_new',
            'cast_preserve_url',
            'overwrite',
            false,
        );

        $mapping = PublicUrlMapping::query()->sole();
        $this->assertSame(PublicUrlMapping::MODE_RENDER, $mapping->mode);
        $this->assertSame('/tin-tuc/bai-viet-giu-url', $mapping->source_path);
        $this->assertSame(0, LegacyMigrationRedirect::query()->count());

        $this->get('/tin-tuc/bai-viet-giu-url')
            ->assertOk()
            ->assertHeader('X-Public-URL-Mapping', 'render-target')
            ->assertSee('Nội dung cũ.')
            ->assertSee(url('/tin-tuc/bai-viet-giu-url'), false);

        $deepPath = '/tin-tuc/kinh-nghiem/bai-viet-giu-url';
        PublicUrlMapping::query()->create([
            'source_hash' => hash('sha256', $deepPath),
            'source_path' => $deepPath,
            'mode' => PublicUrlMapping::MODE_RENDER,
            'target_type' => $mapping->target_type,
            'target_id' => $mapping->target_id,
            'target_path' => $mapping->target_path,
            'status_code' => 200,
            'is_active' => true,
        ]);

        $this->get($deepPath)
            ->assertOk()
            ->assertHeader('X-Public-URL-Mapping', 'render-target');
    }

    public function test_draft_blog_is_created_without_a_public_redirect(): void
    {
        $actor = $this->superAdmin();
        [$run, $source] = $this->blogSource(expectedUrls: 1, published: false);
        $url = $this->sourceUrl($run, $source, '/tin-tuc/bai-viet-nhap');

        app(LegacyAutomaticProcessor::class)->process(
            $url->id,
            $actor->id,
            'blog',
            'create_new',
            'cast_and_redirect',
            'overwrite',
            false,
        );

        $this->assertSame('draft', BlogPost::query()->sole()->status);
        $this->assertSame(0, LegacyMigrationRedirect::query()->count());
        $this->assertSame('casted', $url->fresh()->status);
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

    /** @return array{LegacyMigrationRun, LegacyStagedObject} */
    private function blogSource(int $expectedUrls, ?string $mediaUrl = null, bool $published = true, string $content = '<p>Nội dung cũ.</p>'): array
    {
        $run = LegacyMigrationRun::query()->create([
            'uuid' => (string) Str::uuid(),
            'source_system' => 'haidangtravel_legacy',
            'source_run_id' => (string) Str::uuid(),
            'schema_version' => 'haidang-legacy-content.v1',
            'status' => 'ready_for_mapping',
            'expected_chunks' => 1,
            'expected_urls' => $expectedUrls,
            'started_at' => now(),
        ]);
        $media = $mediaUrl ? [['field' => 'images', 'source' => $mediaUrl, 'url' => $mediaUrl]] : [];
        $source = LegacyStagedObject::query()->create([
            'run_id' => $run->id,
            'object_key' => 'blog:3210',
            'object_type' => 'blog',
            'legacy_id' => '3210',
            'is_partial' => false,
            'checksum' => str_repeat('d', 64),
            'payload_json' => [
                'attributes' => [
                    'title' => 'Bài viết migrate',
                    'slug' => 'bai-viet-migrate',
                    'content' => $content,
                    'publish' => $published ? 1 : 0,
                    'created_at' => '2018-03-04 05:06:07',
                    'updated_at' => '2020-05-06 07:08:09',
                ],
                'relationships' => [],
                'media' => $media,
            ],
            'source_created_at' => '2018-03-04 05:06:07',
            'source_updated_at' => '2020-05-06 07:08:09',
        ]);

        return [$run, $source];
    }

    private function sourceUrl(LegacyMigrationRun $run, LegacyStagedObject $source, string $path): LegacyStagedUrl
    {
        return LegacyStagedUrl::query()->create([
            'run_id' => $run->id,
            'raw_path' => $path,
            'normalized_path' => $path,
            'path_hash' => hash('sha256', $path),
            'route_kind' => 'blog_detail',
            'root_object_key' => $source->object_key,
            'payload_json' => [],
            'status' => 'queued',
        ]);
    }
}
