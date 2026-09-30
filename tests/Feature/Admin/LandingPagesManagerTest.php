<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Cms\LandingPagesManager;
use App\Models\User;
use App\Support\LandingPageBlocks;
use App\Support\TravelHomePageConfig;
use Database\Seeders\CmsBootstrapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\VoucherCampaign;
use Tests\TestCase;

class LandingPagesManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_pages_manager_hydrates_selected_page_from_query_string(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->assertSet('selectedId', $page->id)
            ->assertSet('form.page_key', $page->page_key)
            ->assertSet('form.title', $page->title);
    }

    public function test_landing_page_save_redirects_to_editor_and_syncs_legacy_fields_from_blocks(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $richText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $trustProof = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TRUST_PROOF);
        $cta = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_CTA);
        $faq = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FAQ);

        $richText['title'] = 'Giới thiệu mới';
        $richText['excerpt'] = 'Đoạn mở đầu mới';
        $richText['body'] = '<p>Nội dung body mới</p>';
        $trustProof['title'] = 'Lý do khách chốt sớm với Hải Đăng Travel';
        $trustProof['cards'][0]['highlight'] = 'Một đầu mối xử lý';
        $trustProof['cards'][0]['title'] = 'Một yêu cầu, đội ngũ theo tới cùng';
        $trustProof['cards'][0]['text'] = 'Khách không phải mở nhiều kênh riêng cho tour, visa và dịch vụ đi kèm.';
        $cta['title'] = 'CTA title';
        $cta['description'] = 'CTA excerpt';
        $cta['primary_label'] = 'Liên hệ';
        $cta['primary_url'] = '/lien-he';
        $faq['items'][0]['question'] = 'FAQ test';
        $faq['items'][0]['answer'] = 'Nội dung FAQ test';

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.title', 'Về Hải Đăng Travel cập nhật')
            ->set('form.blocks', [$richText, $trustProof, $cta, $faq])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame('Về Hải Đăng Travel cập nhật', $page->title);
        $this->assertSame('Giới thiệu mới', $page->intro_title);
        $this->assertSame('CTA title', $page->cta_title);
        $this->assertSame('FAQ test', data_get($page->faq_items, '0.question'));
        $this->assertSame(LandingPageBlocks::TYPE_TRUST_PROOF, data_get($page->blocks, '1.type'));
        $this->assertSame('Một đầu mối xử lý', data_get($page->blocks, '1.cards.0.highlight'));
        $this->assertCount(4, $page->blocks ?? []);
    }

    public function test_landing_geo_controls_are_disabled_from_env_config(): void
    {
        config(['frontsite_geo.enabled' => false]);

        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $page->update([
            'geo_config' => [
                'is_enabled' => true,
                'answer_summary' => 'GEO copy đang được giữ nguyên.',
                'decision_notes' => ['Không bị ghi đè khi flag tắt.'],
                'updated_label' => '',
            ],
            'blocks' => [
                [
                    'uuid' => 'geo-block-hidden-by-env',
                    'type' => LandingPageBlocks::TYPE_GEO_ANSWER,
                    'is_enabled' => true,
                    'title' => 'GEO answer block cũ',
                    'answer_summary' => 'Block GEO cũ không được hiện trong CMS khi flag tắt.',
                    'decision_notes' => [],
                ],
            ],
        ]);

        $this->actingAs($user);

        $component = Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->assertDontSee('FRONTSITE_GEO_ENABLED=false')
            ->assertDontSee('Bật block GEO')
            ->assertDontSee('GEO answer')
            ->assertDontSee('GEO answer block cũ')
            ->assertDontSee('Block GEO cũ không được hiện trong CMS khi flag tắt.');

        $initialBlockCount = count($component->get('form.blocks'));

        $component
            ->call('addBlock', LandingPageBlocks::TYPE_GEO_ANSWER)
            ->set('form.title', 'Về Hải Đăng Travel khi GEO tắt')
            ->set('form.geo_config.answer_summary', 'Không được lưu khi flag tắt.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertCount($initialBlockCount, $component->get('form.blocks'));
        $this->assertSame('GEO copy đang được giữ nguyên.', $page->fresh()->geo_config['answer_summary']);
    }

    public function test_home_geo_hardcode_section_is_hidden_from_cms_when_geo_is_disabled(): void
    {
        config(['frontsite_geo.enabled' => false]);

        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->assertSeeText('Thanh tìm kiếm')
            ->assertDontSee('GEO / AI Search')
            ->assertDontSee('Trước GEO / AI Search')
            ->assertDontSee('GEO answer');
    }

    public function test_landing_page_can_store_media_blocks_from_media_library(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $siteSetting = SiteSetting::query()->findOrFail(1);
        $heroMedia = $siteSetting
            ->addMedia(UploadedFile::fake()->image('hero-library.jpg', 1600, 900))
            ->usingName('Hero library image')
            ->usingFileName('hero-library.jpg')
            ->withCustomProperties(['alt' => 'Hero library alt'])
            ->toMediaCollection('library', 'public');
        $galleryMedia = $siteSetting
            ->addMedia(UploadedFile::fake()->image('gallery-library.jpg', 1600, 900))
            ->usingName('Gallery library image')
            ->usingFileName('gallery-library.jpg')
            ->withCustomProperties(['alt' => 'Gallery library alt'])
            ->toMediaCollection('library', 'public');

        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $heroBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HERO_MEDIA);
        $galleryBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_GALLERY_MEDIA);
        $heroBlock['title'] = 'Hero landing';
        $galleryBlock['title'] = 'Gallery demo';
        $galleryBlock['items'][0]['title'] = 'Ảnh gallery';

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$heroBlock, $galleryBlock])
            ->call('selectLibraryMediaForUpload', 'blockUploads.'.$heroBlock['uuid'].'.media', $heroMedia->id, null, 'form.blocks.0.media_alt')
            ->call('selectLibraryMediaForUpload', 'blockUploads.'.$galleryBlock['uuid'].'.gallery.'.$galleryBlock['items'][0]['uuid'], $galleryMedia->id, null, 'form.blocks.1.items.0.image_alt')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $heroStored = $page->getFirstMedia(LandingPageBlocks::mediaCollection($heroBlock['uuid']));
        $galleryStored = $page->getFirstMedia(LandingPageBlocks::galleryItemCollection($galleryBlock['uuid'], $galleryBlock['items'][0]['uuid']));

        $this->assertNotNull($heroStored);
        $this->assertSame($heroMedia->id, (int) data_get($heroStored?->custom_properties, 'source_library_media_id'));
        $this->assertNotNull($galleryStored);
        $this->assertSame($galleryMedia->id, (int) data_get($galleryStored?->custom_properties, 'source_library_media_id'));
    }

    public function test_homepage_can_add_and_render_a_gallery_with_three_library_images(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $siteSetting = SiteSetting::query()->findOrFail(1);
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $libraryMedia = collect(range(1, 3))->map(fn (int $number) => $siteSetting
            ->addMedia(UploadedFile::fake()->image("voucher-{$number}.jpg", 1200, 800))
            ->usingName("Voucher {$number}")
            ->toMediaCollection('library', 'public'));

        $this->actingAs($user);

        $component = Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->call('addBlock', LandingPageBlocks::TYPE_GALLERY_MEDIA);

        $blockIndex = count($component->get('form.blocks')) - 1;
        $component
            ->call('addGalleryItem', $blockIndex)
            ->call('addGalleryItem', $blockIndex)
            ->set("form.blocks.{$blockIndex}.title", 'Ba ưu đãi voucher')
            ->set("form.blocks.{$blockIndex}.is_slider", true)
            ->set("form.blocks.{$blockIndex}.desktop_slides_per_view", 1.8);

        $galleryBlock = $component->get("form.blocks.{$blockIndex}");
        $this->assertCount(3, $galleryBlock['items']);

        foreach ($libraryMedia as $itemIndex => $media) {
            $itemUuid = $galleryBlock['items'][$itemIndex]['uuid'];
            $component->call(
                'selectLibraryMediaForUpload',
                "blockUploads.{$galleryBlock['uuid']}.gallery.{$itemUuid}",
                $media->id,
                null,
                "form.blocks.{$blockIndex}.items.{$itemIndex}.image_alt",
            );
        }

        $component
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();
        $storedBlock = collect($page->blocks)->firstWhere('uuid', $galleryBlock['uuid']);
        $this->assertCount(3, $storedBlock['items']);
        $this->assertTrue($storedBlock['is_slider']);
        $this->assertSame(1.8, $storedBlock['desktop_slides_per_view']);
        $this->assertContains(
            TravelHomePageConfig::homeLayoutTokenForBlock($galleryBlock['uuid']),
            data_get($page->home_config, 'layout_order', []),
        );

        foreach ($libraryMedia as $itemIndex => $media) {
            $itemUuid = $storedBlock['items'][$itemIndex]['uuid'];
            $storedMedia = $page->getFirstMedia(LandingPageBlocks::galleryItemCollection($galleryBlock['uuid'], $itemUuid));
            $this->assertNotNull($storedMedia);
            $this->assertSame($media->id, (int) data_get($storedMedia->custom_properties, 'source_library_media_id'));
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Ba ưu đãi voucher')
            ->assertSee('data-desktop-slider="true"', false)
            ->assertSee('--desktop-card-width: calc((100% - 0.8rem) / 1.8)', false)
            ->assertSee('--mobile-card-width: calc((100% - 1rem) / 1.2)', false)
            ->assertSee('alt="Voucher 1"', false)
            ->assertSee('alt="Voucher 2"', false)
            ->assertSee('alt="Voucher 3"', false);
    }

    public function test_landing_page_can_store_hero_demo_background_from_media_library(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $siteSetting = SiteSetting::query()->findOrFail(1);
        $heroDemoMedia = $siteSetting
            ->addMedia(UploadedFile::fake()->image('hero-demo-library.jpg', 1600, 900))
            ->usingName('Hero demo library image')
            ->usingFileName('hero-demo-library.jpg')
            ->withCustomProperties(['alt' => 'Hero demo library alt'])
            ->toMediaCollection('library', 'public');

        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $heroDemoBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE);
        $heroDemoBlock['title'] = 'Hero demo landingpage';

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$heroDemoBlock])
            ->call('selectLibraryMediaForUpload', 'blockUploads.'.$heroDemoBlock['uuid'].'.media', $heroDemoMedia->id, null, 'form.blocks.0.media_alt')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $heroDemoStored = $page->getFirstMedia(LandingPageBlocks::mediaCollection($heroDemoBlock['uuid']));

        $this->assertNotNull($heroDemoStored);
        $this->assertSame($heroDemoMedia->id, (int) data_get($heroDemoStored?->custom_properties, 'source_library_media_id'));
        $this->assertSame('Hero demo library alt', data_get($page->blocks, '0.media_alt'));
    }

    public function test_landing_page_can_switch_to_manual_html_mode(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $html = <<<'HTML'
<section class="landing-html-mode">
    <style>.landing-html-mode{padding:24px}</style>
    <script>window.__landingHtmlMode = true;</script>
    <div>Dán HTML thủ công</div>
</section>
HTML;

        $this->actingAs($user);

        Livewire::test(LandingPagesManager::class)
            ->call('createPage')
            ->set('form.title', 'Landing HTML thủ công')
            ->set('form.slug', 'landing-html-thu-cong')
            ->call('useBlankHtmlMode')
            ->set('form.html_content', $html)
            ->call('save')
            ->assertHasNoErrors();

        $page = LandingPage::query()->where('slug', 'landing-html-thu-cong')->firstOrFail();

        $this->assertSame(LandingPage::EDITOR_MODE_HTML, $page->editor_mode);
        $this->assertSame('blank', $page->template_key);
        $this->assertSame($html, $page->body);
        $this->assertSame([], $page->blocks ?? []);
    }

    public function test_landing_page_can_store_empty_html_widget_block(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$htmlWidget])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame(LandingPageBlocks::TYPE_HTML_WIDGET, data_get($page->blocks, '0.type'));
        $this->assertSame('', data_get($page->blocks, '0.html'));
    }

    public function test_moving_html_widget_block_preserves_html_content(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $richText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $htmlWidget['html'] = '<section data-preserved-widget="yes"><strong>HTML đang có</strong></section>';
        $htmlWidget['home_position'] = LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS;

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$richText, $htmlWidget])
            ->call('moveBlockUp', 1)
            ->assertSet('form.blocks.0.type', LandingPageBlocks::TYPE_HTML_WIDGET)
            ->assertSet('form.blocks.0.html', $htmlWidget['html'])
            ->assertSet('form.blocks.0.home_position', LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS)
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();

        $this->assertSame(LandingPageBlocks::TYPE_HTML_WIDGET, data_get($page->blocks, '0.type'));
        $this->assertSame($htmlWidget['html'], data_get($page->blocks, '0.html'));
        $this->assertSame(LandingPageBlocks::HOME_POSITION_BEFORE_FEATURED_TOURS, data_get($page->blocks, '0.home_position'));
    }

    public function test_drag_sort_reorders_landing_blocks_and_preserves_html_drafts(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $first = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $second = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $second['html'] = '<div>Nội dung cũ</div>';
        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$first, $second])
            ->set('blockHtmlDrafts.'.$second['uuid'], '<div>Nội dung mới</div>')
            ->assertSeeHtml('wire:sort="sortContentBlock"')
            ->assertSeeHtml('wire:sort:item="block:'.$second['uuid'].'"')
            ->assertSeeHtml('x-data="{ expanded: false }"')
            ->call('sortContentBlock', 'block:'.$second['uuid'], 0)
            ->assertSet('form.blocks.0.uuid', $second['uuid'])
            ->assertSet('form.blocks.0.html', '<div>Nội dung mới</div>')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($second['uuid'], data_get($page->fresh()->blocks, '0.uuid'));
    }

    public function test_drag_sort_home_updates_mixed_layout_order(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $widget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $widget['html'] = '<div>Widget tour</div>';
        $widgetToken = TravelHomePageConfig::homeLayoutTokenForBlock($widget['uuid']);
        $searchToken = TravelHomePageConfig::homeLayoutTokenForSection('search');
        $featuredToken = TravelHomePageConfig::homeLayoutTokenForSection('featured_tours');
        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$widget])
            ->set('form.home_config.layout_order', [$searchToken, $featuredToken, $widgetToken])
            ->call('sortContentBlock', $widgetToken, 1)
            ->assertSet('form.home_config.layout_order.0', $searchToken)
            ->assertSet('form.home_config.layout_order.1', $widgetToken)
            ->assertSet('form.home_config.layout_order.2', $featuredToken)
            ->call('sortContentBlock', $searchToken, 2)
            ->assertSet('form.home_config.layout_order.0', $widgetToken)
            ->assertSet('form.home_config.layout_order.1', $featuredToken)
            ->assertSet('form.home_config.layout_order.2', $searchToken)
            ->call('save')
            ->assertHasNoErrors();

        $order = data_get($page->fresh()->home_config, 'layout_order', []);
        $this->assertLessThan(array_search($featuredToken, $order, true), array_search($widgetToken, $order, true));
    }

    public function test_changing_home_block_position_updates_mixed_layout_order(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $htmlWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $htmlWidget['html'] = '<section data-position-sync="yes">HTML cần đổi vị trí</section>';
        $token = TravelHomePageConfig::homeLayoutTokenForBlock((string) $htmlWidget['uuid']);
        $topicToken = TravelHomePageConfig::homeLayoutTokenForSection('topic_rail');

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$htmlWidget])
            ->set('form.home_config.layout_order', [])
            ->set('form.blocks.0.home_position', LandingPageBlocks::HOME_POSITION_BEFORE_TOPIC_RAIL)
            ->assertSet('form.home_config.layout_order.2', $token)
            ->assertSet('form.home_config.layout_order.3', $topicToken)
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $layoutOrder = data_get($page->home_config, 'layout_order', []);

        $this->assertLessThan(
            array_search($topicToken, $layoutOrder, true),
            array_search($token, $layoutOrder, true),
        );
    }

    public function test_home_layout_order_displays_tour_title_as_block_name(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $tourBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS);
        $tourBlock['title'] = 'Tour đoàn nổi bật';
        $token = TravelHomePageConfig::homeLayoutTokenForBlock((string) $tourBlock['uuid']);

        $this->actingAs($user);

        $component = Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$tourBlock])
            ->set('form.home_config.layout_order', [$token])
            ->assertSeeHtml('home-layout-order-block-'.$tourBlock['uuid'])
            ->assertSeeText('Tour đoàn nổi bật')
            ->assertDontSeeText('Tiêu đề block:');

        $this->assertMatchesRegularExpression(
            '/<h4[^>]*>Tour đoàn nổi bật<\/h4>.*?<p[^>]*>Tour: danh sách hoặc tab<\/p>/su',
            $component->html(),
        );
    }

    public function test_regular_landing_block_uses_type_as_name_when_title_is_empty(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $untitledBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $titledBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $titledBlock['title'] = 'Giới thiệu về hành trình';

        $this->actingAs($user);

        $component = Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$untitledBlock, $titledBlock]);

        $this->assertMatchesRegularExpression(
            '/<h4[^>]*>Rich text<\/h4>.*?<h4[^>]*>Giới thiệu về hành trình<\/h4>.*?<p[^>]*>Rich text<\/p>/su',
            $component->html(),
        );
    }

    public function test_home_blocks_stack_displays_hardcode_sections_as_rows(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->assertSeeText('Danh sách này trộn section cố định và widget')
            ->assertSeeText('Section cố định')
            ->assertSeeHtml('wire:model.defer="form.home_config.topic_rail.show_card_titles"')
            ->assertSeeText('Hiển thị tên chủ đề dưới ảnh')
            ->assertSeeText('Danh sách filter phân loại tour')
            ->assertSeeText('Vùng miền / Châu')
            ->assertSeeText('Đối tượng')
            ->assertDontSeeText('Nhãn filter')
            ->assertDontSeeText('Tiêu đề khi active')
            ->assertDontSeeText('Mô tả khi active')
            ->assertSeeHtml('wire:click="addHomeFeaturedTourFilter"')
            ->assertSeeText('Tìm kiếm nổi bật')
            ->assertSeeHtml('wire:click="addHomeFeaturedTourPopularSearch"')
            ->assertSeeText('Thanh tìm kiếm')
            ->assertSeeText('Chuyển xuống')
            ->assertSeeHtml("moveHomeLayoutItemDown('section:search')");
    }

    public function test_moving_multiple_html_widgets_keeps_content_attached_to_uuid(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $firstWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $secondWidget = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HTML_WIDGET);
        $richText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
        $firstHtml = '<section data-widget="first">HTML widget đầu tiên</section>';
        $secondHtml = '<section data-widget="second">HTML widget thứ hai</section>';
        $firstWidget['html'] = '<section data-widget="first-old">Nội dung cũ 1</section>';
        $secondWidget['html'] = '<section data-widget="second-old">Nội dung cũ 2</section>';
        $firstUuid = (string) $firstWidget['uuid'];
        $secondUuid = (string) $secondWidget['uuid'];

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$firstWidget, $richText, $secondWidget])
            ->set('blockHtmlDrafts.'.$firstUuid, $firstHtml)
            ->set('blockHtmlDrafts.'.$secondUuid, $secondHtml)
            ->call('moveBlockUp', 2)
            ->assertSet('form.blocks.0.uuid', $firstUuid)
            ->assertSet('form.blocks.0.html', $firstHtml)
            ->assertSet('form.blocks.1.uuid', $secondUuid)
            ->assertSet('form.blocks.1.html', $secondHtml)
            ->call('moveBlockDown', 0)
            ->assertSet('form.blocks.0.uuid', $secondUuid)
            ->assertSet('form.blocks.0.html', $secondHtml)
            ->assertSet('form.blocks.1.uuid', $firstUuid)
            ->assertSet('form.blocks.1.html', $firstHtml)
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();

        $this->assertSame($secondUuid, data_get($page->blocks, '0.uuid'));
        $this->assertSame($secondHtml, data_get($page->blocks, '0.html'));
        $this->assertSame($firstUuid, data_get($page->blocks, '1.uuid'));
        $this->assertSame($firstHtml, data_get($page->blocks, '1.html'));
    }

    public function test_custom_landing_page_can_auto_generate_slug_from_title_when_left_blank(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(LandingPagesManager::class)
            ->call('createPage')
            ->set('form.title', 'Landing tour mùa hè')
            ->set('form.slug', '')
            ->call('save')
            ->assertHasNoErrors();

        $page = LandingPage::query()->where('title', 'Landing tour mùa hè')->firstOrFail();

        $this->assertSame('landing-tour-mua-he', $page->slug);
    }

    public function test_landing_pages_index_exposes_delete_action_only_for_custom_pages(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $systemPage = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $customPage = LandingPage::query()->create([
            'title' => 'Landing khuyến mãi hè',
            'slug' => 'landing-khuyen-mai-he',
            'template_key' => 'generic',
            'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
            'is_active' => true,
            'blocks' => LandingPageBlocks::presetBlocks('generic'),
        ]);

        $this->actingAs($user)
            ->get(route('admin.landing-pages'))
            ->assertOk()
            ->assertSee('wire:click="deletePage('.$customPage->id.')"', false)
            ->assertSee('Bạn có chắc chắn muốn xóa landing page custom này? Hành động này không thể hoàn tác.', false)
            ->assertDontSee('wire:click="deletePage('.$systemPage->id.')"', false);
    }

    public function test_custom_landing_page_can_be_deleted_from_manager(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->create([
            'title' => 'Landing cần xóa',
            'slug' => 'landing-can-xoa',
            'template_key' => 'generic',
            'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
            'is_active' => true,
            'blocks' => LandingPageBlocks::presetBlocks('generic'),
        ]);
        $page
            ->addMedia(UploadedFile::fake()->image('landing-delete-hero.jpg', 1200, 800))
            ->usingFileName('landing-delete-hero.jpg')
            ->toMediaCollection('delete-test-media', 'public');

        $this->actingAs($user);

        Livewire::test(LandingPagesManager::class)
            ->call('deletePage', $page->id)
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages'));

        $this->assertDatabaseMissing('landing_pages', [
            'id' => $page->id,
        ]);
        $this->assertDatabaseMissing('media', [
            'model_type' => LandingPage::class,
            'model_id' => $page->id,
        ]);
    }

    public function test_system_landing_page_cannot_be_deleted(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();

        $this->actingAs($user);

        Livewire::test(LandingPagesManager::class)
            ->call('deletePage', $page->id)
            ->assertHasErrors(['form.page_key']);

        $this->assertDatabaseHas('landing_pages', [
            'id' => $page->id,
            'page_key' => 'home',
        ]);
    }

    public function test_landing_page_can_store_region_rail_block_with_toggle_state(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $regionRail = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_REGION_RAIL);
        $regionRail['title'] = 'Khám phá theo vùng miền';
        $regionRail['description'] = 'Block vùng miền dùng layout carousel homepage.';
        $regionRail['card_cta_label'] = 'Xem hub vùng miền';
        $regionRail['scope'] = 'domestic';
        $regionRail['limit'] = 6;
        $regionRail['featured'] = true;
        $regionRail['is_enabled'] = false;

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$regionRail])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame(LandingPageBlocks::TYPE_REGION_RAIL, data_get($page->blocks, '0.type'));
        $this->assertSame('Khám phá theo vùng miền', data_get($page->blocks, '0.title'));
        $this->assertSame('Xem hub vùng miền', data_get($page->blocks, '0.card_cta_label'));
        $this->assertFalse((bool) data_get($page->blocks, '0.is_enabled'));
    }

    public function test_landing_page_can_store_topic_rail_block_with_heading_and_navigation_state(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $topicRail = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOPIC_RAIL);
        $topicRail['eyebrow'] = 'Khởi đầu từ nhu cầu';
        $topicRail['title'] = 'Chọn nhanh chủ đề tour';
        $topicRail['description'] = 'Card icon gọn để khách quét nhanh các nhóm hành trình trước khi đi sâu vào điểm đến.';
        $topicRail['limit'] = 6;
        $topicRail['show_navigation'] = false;
        $topicRail['show_card_titles'] = false;

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$topicRail])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame(LandingPageBlocks::TYPE_TOPIC_RAIL, data_get($page->blocks, '0.type'));
        $this->assertSame('Khởi đầu từ nhu cầu', data_get($page->blocks, '0.eyebrow'));
        $this->assertSame('Chọn nhanh chủ đề tour', data_get($page->blocks, '0.title'));
        $this->assertFalse((bool) data_get($page->blocks, '0.show_navigation'));
        $this->assertFalse((bool) data_get($page->blocks, '0.show_card_titles'));
    }

    public function test_landing_page_can_store_voucher_rail_campaign_selection(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $campaign = VoucherCampaign::query()->create([
            'title' => 'Voucher rail campaign',
            'slug' => 'voucher-rail-campaign',
            'code_prefix' => 'RAIL',
            'code_quantity' => 10,
            'code_set_version' => (string) Str::uuid(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'is_active' => true,
            'meta' => [
                'public_widget_enabled' => true,
                'public_code' => 'TRIP300',
            ],
        ]);
        $voucherRail = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_VOUCHER_RAIL);
        $voucherRail['title'] = 'Ưu đãi dành cho bạn';
        $voucherRail['campaign_slugs'] = [$campaign->slug];
        $voucherRail['limit'] = 4;
        $voucherRail['show_expiry'] = false;

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$voucherRail])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame(LandingPageBlocks::TYPE_VOUCHER_RAIL, data_get($page->blocks, '0.type'));
        $this->assertSame([$campaign->slug], data_get($page->blocks, '0.campaign_slugs'));
        $this->assertSame(4, data_get($page->blocks, '0.limit'));
        $this->assertFalse((bool) data_get($page->blocks, '0.show_expiry'));
    }

    public function test_landing_page_can_store_region_taxonomy_tabs_block(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $regionTaxonomyTabs = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS);
        $regionTaxonomyTabs['title'] = 'Khám phá vùng miền theo điểm đến';
        $regionTaxonomyTabs['description'] = 'Chọn tab vùng miền để xem nhanh các điểm đến đang có tour hoạt động.';
        $regionTaxonomyTabs['cta_label'] = 'Xem hub vùng miền';
        $regionTaxonomyTabs['card_cta_label'] = 'Xem hub điểm đến';
        $regionTaxonomyTabs['card_source_type'] = 'tour_category';
        $regionTaxonomyTabs['scope'] = TourScope::Domestic->value;
        $regionTaxonomyTabs['limit'] = 6;
        $regionTaxonomyTabs['tab_limit'] = 5;
        $regionTaxonomyTabs['featured'] = true;

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$regionTaxonomyTabs])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame(LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS, data_get($page->blocks, '0.type'));
        $this->assertSame('tour_category', data_get($page->blocks, '0.card_source_type'));
        $this->assertSame('Xem hub vùng miền', data_get($page->blocks, '0.cta_label'));
        $this->assertSame(5, data_get($page->blocks, '0.tab_limit'));
        $this->assertTrue((bool) data_get($page->blocks, '0.featured'));
    }

    public function test_landing_page_can_store_tour_taxonomy_tabs_block(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $region = Region::query()->create([
            'name' => 'Miền Bắc',
            'slug' => 'mien-bac',
            'scope' => TourScope::Domestic->value,
            'status' => 'published',
        ]);
        $destination = Destination::query()->create([
            'name' => 'Hà Nội',
            'slug' => 'ha-noi',
            'status' => 'published',
            'region_id' => $region->id,
        ]);
        $category = TourCategory::query()->create([
            'name' => 'Tour gia đình',
            'slug' => 'tour-gia-dinh',
            'status' => 'published',
        ]);
        Tour::query()->create([
            'title' => 'Tour Hà Nội mùa thu',
            'slug' => 'tour-ha-noi-mua-thu',
            'status' => 'published',
            'scope' => TourScope::Domestic->value,
            'tour_category_id' => $category->id,
            'destination_id' => $destination->id,
            'region_id' => $region->id,
        ]);

        $tourTaxonomyTabs = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS);
        $tourTaxonomyTabs['title'] = 'Khám phá tour theo taxonomy';
        $tourTaxonomyTabs['description'] = 'Đổi tab để xem tour live theo vùng miền, điểm đến hoặc chủ đề.';
        $tourTaxonomyTabs['cta_label'] = 'Xem trang nhóm tour';
        $tourTaxonomyTabs['card_cta_variant'] = 'blue';
        $tourTaxonomyTabs['is_slider'] = true;
        $tourTaxonomyTabs['show_all_tab'] = true;
        $tourTaxonomyTabs['show_filters'] = false;
        $tourTaxonomyTabs['scope'] = TourScope::Domestic->value;
        $tourTaxonomyTabs['limit'] = 4;
        $tourTaxonomyTabs['tabs'] = [
            LandingPageBlocks::defaultTourTaxonomyTab('region', $region->slug, 'Miền Bắc', 'Tour theo Miền Bắc'),
            LandingPageBlocks::defaultTourTaxonomyTab('destination', $destination->slug, 'Hà Nội', 'Tour theo Hà Nội'),
            LandingPageBlocks::defaultTourTaxonomyTab('tour_category', $category->slug, 'Gia đình', 'Tour cho gia đình'),
        ];
        $tourTaxonomyTabs['popular_searches'] = [
            TravelHomePageConfig::featuredTourPopularSearch('Hà Nội', '/tim-tour?scope=domestic&destination=ha-noi', null, $tourTaxonomyTabs['tabs'][1]['uuid']),
        ];

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$tourTaxonomyTabs])
            ->set('form.blocks.0.popular_searches.0.url', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors(['form.blocks.0.popular_searches.0.url']);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$tourTaxonomyTabs])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS, data_get($page->blocks, '0.type'));
        $this->assertSame('Xem trang nhóm tour', data_get($page->blocks, '0.cta_label'));
        $this->assertSame('blue', data_get($page->blocks, '0.card_cta_variant'));
        $this->assertTrue(data_get($page->blocks, '0.is_slider'));
        $this->assertTrue(data_get($page->blocks, '0.show_all_tab'));
        $this->assertFalse(data_get($page->blocks, '0.show_filters'));
        $this->assertSame(TourScope::Domestic->value, data_get($page->blocks, '0.scope'));
        $this->assertSame('region', data_get($page->blocks, '0.tabs.0.source_type'));
        $this->assertSame('mien-bac', data_get($page->blocks, '0.tabs.0.source_slug'));
        $this->assertSame('Tour theo Hà Nội', data_get($page->blocks, '0.tabs.1.title'));
        $this->assertSame('tour-gia-dinh', data_get($page->blocks, '0.tabs.2.source_slug'));
        $this->assertSame('Hà Nội', data_get($page->blocks, '0.popular_searches.0.label'));
        $this->assertSame(data_get($page->blocks, '0.tabs.1.uuid'), data_get($page->blocks, '0.popular_searches.0.filter_uuid'));
    }

    public function test_landing_page_can_store_slider_choice_for_tour_list_and_flash_sale(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $tourList = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_LIST);
        $flashSale = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FLASH_SALE);

        unset($tourList['is_slider'], $flashSale['is_slider']);
        $this->assertFalse(data_get(LandingPageBlocks::normalize([$tourList]), '0.is_slider'));
        $this->assertFalse(data_get(LandingPageBlocks::normalize([$flashSale]), '0.is_slider'));

        $tourList['is_slider'] = true;
        $flashSale['is_slider'] = true;

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$tourList, $flashSale])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS, data_get($page->blocks, '0.type'));
        $this->assertSame('list', data_get($page->blocks, '0.display_mode'));
        $this->assertTrue(data_get($page->blocks, '0.is_slider'));
        $this->assertTrue(data_get($page->blocks, '1.is_slider'));
    }

    public function test_flash_sale_widget_can_be_hidden_and_override_its_view_more_button(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $flashSale = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FLASH_SALE);
        $flashSale['is_enabled'] = false;
        $flashSale['show_view_more'] = false;
        $flashSale['view_more_label'] = 'Xem toàn bộ ưu đãi';
        $flashSale['view_more_url'] = '/tim-tour?flash_sale=1';

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks', [$flashSale])
            ->assertSeeHtml('wire:model.defer="form.blocks.0.show_view_more"')
            ->assertSeeHtml('wire:model.defer="form.blocks.0.view_more_url"')
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $this->assertFalse(data_get($page->blocks, '0.is_enabled'));
        $this->assertFalse(data_get($page->blocks, '0.show_view_more'));
        $this->assertSame('Xem toàn bộ ưu đãi', data_get($page->blocks, '0.view_more_label'));
        $this->assertSame('/tim-tour?flash_sale=1', data_get($page->blocks, '0.view_more_url'));

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.blocks.0.view_more_url', 'javascript:alert(1)')
            ->call('save')
            ->assertHasErrors(['form.blocks.0.view_more_url']);
    }

    public function test_legacy_tour_list_and_tab_widget_use_one_cms_type_without_losing_configuration(): void
    {
        $homeTourWidgets = collect(LandingPageBlocks::presetBlocks('home'))
            ->filter(fn (array $block) => ($block['type'] ?? null) === LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS)
            ->values();
        $this->assertSame(['list', 'tabs'], $homeTourWidgets->pluck('display_mode')->all());

        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $list = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_LIST);
        $list['title'] = 'Tour thường';
        $list['scope'] = TourScope::Group->value;
        $list['category_slug'] = 'team-building';
        $tabs = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS);
        $tabs['title'] = 'Tour đoàn theo vùng';
        $page->update(['blocks' => [$list, $tabs]]);

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->assertSet('form.blocks.0.type', LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS)
            ->assertSet('form.blocks.0.display_mode', 'list')
            ->assertSet('form.blocks.0.category_slug', 'team-building')
            ->assertSet('form.blocks.1.display_mode', 'tabs')
            ->call('save')
            ->assertHasNoErrors();

        $page->refresh();
        $this->assertSame($list['uuid'], data_get($page->blocks, '0.uuid'));
        $this->assertSame(LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS, data_get($page->blocks, '0.type'));
        $this->assertSame('list', data_get($page->blocks, '0.display_mode'));
        $this->assertSame(TourScope::Group->value, data_get($page->blocks, '0.scope'));
        $this->assertSame('tabs', data_get($page->blocks, '1.display_mode'));
    }

    public function test_landing_page_can_clone_blocks_and_media_from_another_page_before_save(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $source = LandingPage::query()->where('page_key', 'services')->firstOrFail();
        $target = LandingPage::query()->where('page_key', 'about')->firstOrFail();
        $heroBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_HERO_MEDIA);
        $galleryBlock = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_GALLERY_MEDIA);
        $richText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);

        $heroBlock['title'] = 'Hero clone source';
        $heroBlock['description'] = 'Hero description clone source';
        $galleryBlock['title'] = 'Gallery clone source';
        $galleryBlock['items'][0]['title'] = 'Gallery clone item';
        $richText['title'] = 'Intro clone source';
        $source->update([
            'blocks' => [$heroBlock, $galleryBlock, $richText],
        ]);

        $source
            ->addMedia(UploadedFile::fake()->image('clone-hero.jpg', 1600, 900))
            ->usingFileName('clone-hero.jpg')
            ->toMediaCollection(LandingPageBlocks::mediaCollection($heroBlock['uuid']), 'public');
        $source
            ->addMedia(UploadedFile::fake()->image('clone-gallery.jpg', 1600, 900))
            ->usingFileName('clone-gallery.jpg')
            ->toMediaCollection(LandingPageBlocks::galleryItemCollection($galleryBlock['uuid'], $galleryBlock['items'][0]['uuid']), 'public');

        $this->actingAs($user);

        Livewire::withQueryParams(['page' => $target->id])
            ->test(LandingPagesManager::class)
            ->set('cloneSourceId', $source->id)
            ->call('cloneFromPage')
            ->assertSet('form.title', 'Landing Về chúng tôi')
            ->assertSet('form.blocks.0.title', 'Hero clone source')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $target));

        $target->refresh();

        $this->assertSame('Hero clone source', $target->hero_title);
        $this->assertSame('Intro clone source', $target->intro_title);
        $this->assertSame('Landing Về chúng tôi', $target->title);
        $this->assertNotNull($target->getFirstMedia(LandingPageBlocks::mediaCollection($heroBlock['uuid'])));
        $this->assertNotNull($target->getFirstMedia(LandingPageBlocks::galleryItemCollection($galleryBlock['uuid'], $galleryBlock['items'][0]['uuid'])));
    }

    public function test_home_landing_page_can_store_trust_section_config(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $awardMedia = $page
            ->addMedia(UploadedFile::fake()->image('travel-award.jpg', 1200, 1200))
            ->usingFileName('travel-award.jpg')
            ->withCustomProperties(['alt' => 'Giải thưởng du lịch tiêu biểu'])
            ->toMediaCollection('award-library', 'public');

        $this->actingAs($user);

        $component = Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.home_config.trust.title', 'Về Hải Đăng Travel')
            ->set('form.home_config.trust.subtitle', 'Tận tâm trên từng hành trình')
            ->set('form.home_config.trust.description', 'Giới thiệu ngắn về năng lực, kinh nghiệm và giá trị phục vụ của doanh nghiệp.')
            ->set('form.home_config.trust.stats.0.value', '19+')
            ->set('form.home_config.trust.stats.0.label', 'Năm kinh nghiệm')
            ->set('form.home_config.trust.awards.0.title', 'Giải thưởng du lịch tiêu biểu');

        $awardUuid = data_get($component->get('form'), 'home_config.trust.awards.0.uuid');

        $component
            ->call('selectHomeTrustAwardLibraryMedia', $awardUuid, $awardMedia->id, null)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame('Về Hải Đăng Travel', data_get($page->home_config, 'trust.title'));
        $this->assertSame('Tận tâm trên từng hành trình', data_get($page->home_config, 'trust.subtitle'));
        $this->assertSame('Giới thiệu ngắn về năng lực, kinh nghiệm và giá trị phục vụ của doanh nghiệp.', data_get($page->home_config, 'trust.description'));
        $this->assertSame('19+', data_get($page->home_config, 'trust.stats.0.value'));
        $this->assertSame('Năm kinh nghiệm', data_get($page->home_config, 'trust.stats.0.label'));
        $this->assertSame('Giải thưởng du lịch tiêu biểu', data_get($page->home_config, 'trust.awards.0.title'));
        $this->assertSame((string) $awardMedia->getUrl(), data_get($page->home_config, 'trust.awards.0.image_url'));
        $this->assertSame('Giải thưởng du lịch tiêu biểu', data_get($page->home_config, 'trust.awards.0.image_alt'));
    }

    public function test_home_landing_page_can_store_extended_home_block_config(): void
    {
        Storage::fake('public');
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $service = Service::query()->published()->firstOrFail();
        $post = BlogPost::query()->published()->firstOrFail();
        $processMedia = $page
            ->addMedia(UploadedFile::fake()->image('process-step.jpg', 1200, 900))
            ->usingFileName('process-step.jpg')
            ->withCustomProperties(['alt' => 'Ảnh bước tư vấn'])
            ->toMediaCollection('process-step-library', 'public');

        $this->actingAs($user);

        $component = Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->set('form.home_config.layout_order', [
                TravelHomePageConfig::homeLayoutTokenForSection('services'),
                TravelHomePageConfig::homeLayoutTokenForSection('featured_tours'),
                TravelHomePageConfig::homeLayoutTokenForSection('search'),
                TravelHomePageConfig::homeLayoutTokenForSection('topic_rail'),
                TravelHomePageConfig::homeLayoutTokenForSection('destination_slider'),
                TravelHomePageConfig::homeLayoutTokenForSection('trust'),
                TravelHomePageConfig::homeLayoutTokenForSection('process'),
                TravelHomePageConfig::homeLayoutTokenForSection('blog_preview'),
                TravelHomePageConfig::homeLayoutTokenForSection('faq'),
                TravelHomePageConfig::homeLayoutTokenForSection('cta'),
            ])
            ->set('form.home_config.section_order', [
                'services',
                'featured_tours',
                'search',
                'topic_rail',
                'destination_slider',
                'trust',
                'process',
                'blog_preview',
                'faq',
                'cta',
            ])
            ->set('form.home_config.search.placeholder', 'Tìm tour theo điểm đến hoặc chủ đề')
            ->set('form.home_config.search.button_label', 'Khám phá')
            ->set('form.home_config.topic_rail.eyebrow', 'Duyệt theo gu chuyến đi')
            ->set('form.home_config.topic_rail.title', 'Chủ đề tour dễ chọn')
            ->set('form.home_config.topic_rail.description', 'Nhóm các hành trình theo nhu cầu để khách mở đúng hub chủ đề trước khi so sánh tour.')
            ->set('form.home_config.topic_rail.show_card_titles', false)
            ->set('form.home_config.featured_tours.cta_label', 'Xem tab tour')
            ->set('form.home_config.featured_tours.card_cta_variant', 'green')
            ->set('form.home_config.featured_tours.is_slider', true)
            ->set('form.home_config.featured_tours.show_filters', false)
            ->set('form.home_config.featured_tours.filters.1.title', 'Tour trong nước đang được quan tâm')
            ->set('form.home_config.destination_slider.title', 'Điểm đến nên xem ngay')
            ->set('form.home_config.services.title', 'Dịch vụ đồng hành')
            ->set('form.home_config.services.is_enabled', false)
            ->set('form.home_config.trust.is_enabled', false)
            ->set('form.home_config.process.title', 'Quy trình làm việc cùng Hải Đăng');

        $processCardUuid = data_get($component->get('form'), 'home_config.process.cards.0.uuid');

        $component
            ->set('form.home_config.process.cards.0.title', 'Tiếp nhận nhu cầu và ngân sách')
            ->call('selectHomeProcessLibraryMedia', $processCardUuid, $processMedia->id, null)
            ->set('form.home_config.blog_preview.title', 'Bài viết nên đọc trước chuyến đi')
            ->set('form.home_config.featured_service_slugs', [$service->slug])
            ->set('form.home_config.featured_blog_slugs', [$post->slug])
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame('services', data_get($page->home_config, 'section_order.0'));
        $this->assertSame('featured_tours', data_get($page->home_config, 'section_order.1'));
        $this->assertSame('search', data_get($page->home_config, 'section_order.2'));
        $this->assertSame(TravelHomePageConfig::homeLayoutTokenForSection('services'), data_get($page->home_config, 'layout_order.0'));
        $this->assertSame(TravelHomePageConfig::homeLayoutTokenForSection('featured_tours'), data_get($page->home_config, 'layout_order.1'));
        $this->assertSame('Tìm tour theo điểm đến hoặc chủ đề', data_get($page->home_config, 'search.placeholder'));
        $this->assertSame('Khám phá', data_get($page->home_config, 'search.button_label'));
        $this->assertSame('Duyệt theo gu chuyến đi', data_get($page->home_config, 'topic_rail.eyebrow'));
        $this->assertSame('Chủ đề tour dễ chọn', data_get($page->home_config, 'topic_rail.title'));
        $this->assertSame('Nhóm các hành trình theo nhu cầu để khách mở đúng hub chủ đề trước khi so sánh tour.', data_get($page->home_config, 'topic_rail.description'));
        $this->assertFalse(data_get($page->home_config, 'topic_rail.show_card_titles'));
        $this->assertSame('Xem tab tour', data_get($page->home_config, 'featured_tours.cta_label'));
        $this->assertSame('green', data_get($page->home_config, 'featured_tours.card_cta_variant'));
        $this->assertTrue(data_get($page->home_config, 'featured_tours.is_slider'));
        $this->assertFalse(data_get($page->home_config, 'featured_tours.show_filters'));
        $this->assertSame('Tour trong nước đang được quan tâm', data_get($page->home_config, 'featured_tours.filters.1.title'));
        $this->assertSame('Điểm đến nên xem ngay', data_get($page->home_config, 'destination_slider.title'));
        $this->assertSame('Dịch vụ đồng hành', data_get($page->home_config, 'services.title'));
        $this->assertFalse((bool) data_get($page->home_config, 'services.is_enabled'));
        $this->assertFalse((bool) data_get($page->home_config, 'trust.is_enabled'));
        $this->assertSame('Quy trình làm việc cùng Hải Đăng', data_get($page->home_config, 'process.title'));
        $this->assertSame('Tiếp nhận nhu cầu và ngân sách', data_get($page->home_config, 'process.cards.0.title'));
        $this->assertSame((string) $processMedia->getUrl(), data_get($page->home_config, 'process.cards.0.image_url'));
        $this->assertSame('Ảnh bước tư vấn', data_get($page->home_config, 'process.cards.0.image_alt'));
        $this->assertSame('Bài viết nên đọc trước chuyến đi', data_get($page->home_config, 'blog_preview.title'));
        $this->assertSame([$service->slug], data_get($page->home_config, 'featured_service_slugs'));
        $this->assertSame([$post->slug], data_get($page->home_config, 'featured_blog_slugs'));
    }

    public function test_home_featured_tour_filters_and_popular_searches_are_configurable_and_validated(): void
    {
        $this->seed(CmsBootstrapSeeder::class);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();
        $page = LandingPage::query()->where('page_key', 'home')->firstOrFail();
        $destination = Destination::query()->create([
            'name' => 'Phú Quốc',
            'slug' => 'phu-quoc-cms-filter',
            'status' => 'published',
        ]);
        $region = Region::query()->create([
            'name' => 'Châu Á',
            'slug' => 'chau-a-cms-filter',
            'scope' => TourScope::International->value,
            'status' => 'published',
        ]);
        Region::query()->create([
            'name' => 'Châu Phi bản nháp',
            'slug' => 'chau-phi-draft-cms-filter',
            'scope' => TourScope::International->value,
            'status' => 'draft',
        ]);

        $this->actingAs($user);

        $component = Livewire::withQueryParams(['page' => $page->id])
            ->test(LandingPagesManager::class)
            ->call('addHomeFeaturedTourFilter')
            ->call('addHomeFeaturedTourPopularSearch');

        $filterIndex = count(data_get($component->get('form'), 'home_config.featured_tours.filters', [])) - 1;
        $popularIndex = count(data_get($component->get('form'), 'home_config.featured_tours.popular_searches', [])) - 1;
        $filterUuid = (string) data_get($component->get('form'), 'home_config.featured_tours.filters.'.$filterIndex.'.uuid');

        $component
            ->set('form.home_config.featured_tours.filters.'.$filterIndex.'.source_type', TravelHomePageConfig::FEATURED_TOUR_FILTER_REGION)
            ->set('form.home_config.featured_tours.filters.'.$filterIndex.'.source_value', 'chau-phi-draft-cms-filter')
            ->set('form.home_config.featured_tours.popular_searches.'.$popularIndex.'.label', 'Tìm tour nhanh')
            ->set('form.home_config.featured_tours.popular_searches.'.$popularIndex.'.url', 'javascript:alert(1)')
            ->set('form.home_config.featured_tours.popular_searches.'.$popularIndex.'.filter_uuid', 'filter-khong-ton-tai')
            ->call('save')
            ->assertHasErrors([
                'form.home_config.featured_tours.filters.'.$filterIndex.'.source_value',
                'form.home_config.featured_tours.popular_searches.'.$popularIndex.'.url',
                'form.home_config.featured_tours.popular_searches.'.$popularIndex.'.filter_uuid',
            ]);

        $component
            ->set('form.home_config.featured_tours.filters.'.$filterIndex.'.source_value', $region->slug)
            ->set('form.home_config.featured_tours.popular_searches.'.$popularIndex.'.url', '/tim-tour?destination='.$destination->slug)
            ->set('form.home_config.featured_tours.popular_searches.'.$popularIndex.'.filter_uuid', $filterUuid)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.landing-pages.edit', $page));

        $page->refresh();

        $this->assertSame(
            TravelHomePageConfig::FEATURED_TOUR_FILTER_REGION,
            data_get($page->home_config, 'featured_tours.filters.'.$filterIndex.'.source_type'),
        );
        $this->assertSame($region->slug, data_get($page->home_config, 'featured_tours.filters.'.$filterIndex.'.source_value'));
        $this->assertSame('Tìm tour nhanh', data_get($page->home_config, 'featured_tours.popular_searches.'.$popularIndex.'.label'));
        $this->assertSame('/tim-tour?destination='.$destination->slug, data_get($page->home_config, 'featured_tours.popular_searches.'.$popularIndex.'.url'));
        $this->assertSame($filterUuid, data_get($page->home_config, 'featured_tours.popular_searches.'.$popularIndex.'.filter_uuid'));
    }
}
