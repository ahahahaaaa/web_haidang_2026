<?php

namespace Tests\Feature;

use App\Support\FrontsiteMedia;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Domains\Cms\Models\SiteSetting;
use Tests\TestCase;

class FrontsiteFaviconTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_favicon_from_theme_settings_media_collection(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $settings = SiteSetting::query()->findOrFail(1);
        $settings
            ->addMedia(UploadedFile::fake()->image('theme-favicon.png', 64, 64))
            ->usingName('Theme favicon')
            ->usingFileName('theme-favicon.png')
            ->toMediaCollection('favicon', 'public');

        $settings = $settings->fresh('media');
        $faviconUrl = FrontsiteMedia::modelUrl(
            $settings,
            'favicon',
            FrontsiteMedia::SIZE_FULL,
            'favicon_url',
        );

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<link rel="icon" href="'.$faviconUrl.'">', false)
            ->assertSee('<link rel="apple-touch-icon" href="'.$faviconUrl.'">', false);
    }
}
