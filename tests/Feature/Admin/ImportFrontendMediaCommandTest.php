<?php

namespace Tests\Feature\Admin;

use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class ImportFrontendMediaCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_remote_images_from_frontend_html_into_library(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        Http::fake([
            'https://example.com/images/*' => Http::response($this->png(), 200, [
                'Content-Type' => 'image/png',
            ]),
        ]);

        $exitCode = Artisan::call('media:import-frontend-images', [
            '--path' => base_path('tests/Fixtures/front_end/importable'),
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertDatabaseCount('media', 2);
        $this->assertDatabaseHas('media', [
            'collection_name' => 'library',
            'name' => 'Duplicate hero',
        ]);
        $this->assertTrue(
            Media::query()
                ->where('collection_name', 'library')
                ->get()
                ->contains(fn (Media $media) => data_get($media->custom_properties, 'source_url') === 'https://example.com/images/team.png')
        );
    }

    public function test_dry_run_reports_images_without_creating_media_records(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $exitCode = Artisan::call('media:import-frontend-images', [
            '--dry-run' => true,
            '--path' => base_path('tests/Fixtures/front_end/importable'),
        ]);

        $this->assertSame(0, $exitCode, Artisan::output());
        $this->assertDatabaseCount('media', 0);
    }

    protected function png(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5Wg8kAAAAASUVORK5CYII=', true) ?: '';
    }
}
