<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Seo\SeoPageEditor;
use App\Models\User;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Models\SeoPage;
use Src\Domains\Seo\Support\SeoSlugGenerator;
use Tests\TestCase;

class SeoPageEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_page_editor_can_regenerate_slug_from_title_when_left_blank(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = SeoPage::query()->create([
            'page_type' => SeoPageType::Destination->value,
            'title' => 'Tour Phú Quốc',
            'slug' => 'tour-phu-quoc-cu',
            'primary_keyword' => 'tour phú quốc',
            'h1' => 'Tour Phú Quốc giá tốt',
            'status' => SeoPageStatus::Draft,
        ]);

        $this->actingAs($user);

        $component = Livewire::test(SeoPageEditor::class, ['page' => $page]);

        $component->instance()->page->title = 'Khám phá Phú Quốc 4N3Đ';
        $component->instance()->page->slug = '';

        $component->instance()->save(app(SeoSlugGenerator::class));

        $page->refresh();

        $this->assertSame('kham-pha-phu-quoc-4n3d', $page->slug);
    }
}
