<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Cms\Concerns\HandlesMediaUploads;
use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use App\Support\FaqContent;
use App\Support\LandingPageBlocks;
use App\Support\LandingPageVisuals;
use App\Support\TravelHomePageConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\LandingPage;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\Service;
use Src\Domains\Cms\Models\Slider;
use Src\Domains\Cms\Models\TourCategory;

#[Layout('layouts.app')]
#[Title('Landing pages')]
class LandingPagesManager extends Component
{
    use AuthorizesAdminPermissions;
    use HandlesMediaUploads;
    use InteractsWithEditorContent;
    use WithPagination;

    public array $blockUploads = [];

    public ?int $cloneSourceId = null;

    public ?string $currentRouteName = null;

    public array $form = [];

    public ?int $pendingCloneMediaSourceId = null;

    public ?int $selectedId = null;

    public string $search = '';

    public string $statusFilter = '';

    public string $templateFilter = '';

    public string $typeFilter = '';

    public function mount(?LandingPage $landingPage = null): void
    {
        $this->currentRouteName = request()->route()?->getName();
        $this->ensureDefaults();
        $this->resetEditorState();
        $legacySelectedId = request()->integer('page');

        if ($this->isCreateRoute()) {
            return;
        }

        if ($this->isEditRoute() && $landingPage) {
            $this->edit((int) $landingPage->getKey());

            return;
        }

        if ($legacySelectedId > 0 && LandingPage::query()->whereKey($legacySelectedId)->exists()) {
            $this->edit($legacySelectedId);
        }
    }

    public function addBlock(string $type): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        if (! array_key_exists($type, LandingPageBlocks::blockTypes())) {
            return;
        }

        $blocks = $this->form['blocks'] ?? [];
        $blocks[] = LandingPageBlocks::defaultBlock($type);
        $this->form['blocks'] = $blocks;
    }

    public function addFaqItem(int $blockIndex): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $items = data_get($this->form, 'blocks.'.$blockIndex.'.items', []);
        $items[] = FaqContent::blankItem();
        data_set($this->form, 'blocks.'.$blockIndex.'.items', $items);
    }

    public function addGalleryItem(int $blockIndex): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $items = data_get($this->form, 'blocks.'.$blockIndex.'.items', []);
        $items[] = LandingPageBlocks::defaultGalleryItem();
        data_set($this->form, 'blocks.'.$blockIndex.'.items', $items);
    }

    public function addTourTaxonomyTab(int $blockIndex): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $tabs = data_get($this->form, 'blocks.'.$blockIndex.'.tabs', []);

        if (! is_array($tabs)) {
            $tabs = [];
        }

        $tabs[] = LandingPageBlocks::defaultTourTaxonomyTab();
        data_set($this->form, 'blocks.'.$blockIndex.'.tabs', array_values($tabs));
    }

    public function addTrustProofCard(int $blockIndex): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $cards = data_get($this->form, 'blocks.'.$blockIndex.'.cards', []);

        if (! is_array($cards)) {
            $cards = [];
        }

        $cards[] = $this->blankTrustProofCard();
        data_set($this->form, 'blocks.'.$blockIndex.'.cards', array_values($cards));
    }

    public function addTrustProofStat(int $blockIndex): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $stats = data_get($this->form, 'blocks.'.$blockIndex.'.stats', []);

        if (! is_array($stats)) {
            $stats = [];
        }

        $stats[] = $this->blankTrustProofStat();
        data_set($this->form, 'blocks.'.$blockIndex.'.stats', array_values($stats));
    }

    public function addHomeTrustCard(): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $cards = data_get($this->form, 'home_config.trust.cards', []);

        if (! is_array($cards)) {
            $cards = [];
        }

        $cards[] = $this->blankHomeTrustCard();
        data_set($this->form, 'home_config.trust.cards', array_values($cards));
    }

    public function addHomeProcessCard(): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $cards = data_get($this->form, 'home_config.process.cards', []);

        if (! is_array($cards)) {
            $cards = [];
        }

        $cards[] = $this->blankHomeProcessCard();
        data_set($this->form, 'home_config.process.cards', array_values($cards));
    }

    public function clearHomeProcessCardImage(string $uuid): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $index = $this->findHomeProcessCardIndex($uuid);

        if ($index === null) {
            return;
        }

        data_set($this->form, "home_config.process.cards.{$index}.image_url", '');
        data_set($this->form, "home_config.process.cards.{$index}.image_alt", '');
        data_set($this->form, "home_config.process.cards.{$index}.source_library_media_id", null);
    }

    public function applyTemplatePreset(): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $templateKey = (string) ($this->form['template_key'] ?? 'generic');

        if (! array_key_exists($templateKey, LandingPageBlocks::templates())) {
            $templateKey = 'generic';
        }

        $previousBlocks = LandingPageBlocks::normalize($this->form['blocks'] ?? []);
        $this->form['blocks'] = LandingPageBlocks::presetBlocks($templateKey);
        $this->purgeSelectionsForMissingBlocks($previousBlocks, $this->form['blocks']);
    }

    public function cloneFromPage(): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $validated = $this->validate([
            'cloneSourceId' => ['required', 'exists:landing_pages,id'],
        ]);

        $source = LandingPage::query()->findOrFail($validated['cloneSourceId']);
        $sourceForm = $this->formFromModel($source);
        $preserveIdentity = $this->selectedId !== null;

        if ($preserveIdentity) {
            $sourceForm['page_key'] = $this->form['page_key'] ?? $sourceForm['page_key'];
            $sourceForm['slug'] = $this->form['slug'] ?? $sourceForm['slug'];
            $sourceForm['title'] = $this->form['title'] ?? $sourceForm['title'];
            $sourceForm['is_active'] = $this->form['is_active'] ?? $sourceForm['is_active'];
        }

        $this->blockUploads = [];
        $this->selectedLibraryMediaSelections = [];
        $this->pendingCloneMediaSourceId = (int) $source->getKey();
        $this->form = $sourceForm;

        session()->flash('status', 'Đã nạp block từ landing page nguồn. Bạn có thể chỉnh sửa trước khi lưu.');
    }

    public function createPage(): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $this->selectedId = null;
        $this->cloneSourceId = null;
        $this->pendingCloneMediaSourceId = null;
        $this->resetEditorState();
    }

    public function useBlankHtmlMode(): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $previousBlocks = LandingPageBlocks::normalize($this->form['blocks'] ?? []);
        $this->form['template_key'] = 'blank';
        $this->form['editor_mode'] = LandingPage::EDITOR_MODE_HTML;
        $this->form['blocks'] = [];
        $this->purgeSelectionsForMissingBlocks($previousBlocks, []);
    }

    public function deletePage(int $id): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $page = LandingPage::query()->findOrFail($id);

        if ($page->isSystemPage()) {
            throw ValidationException::withMessages([
                'form.page_key' => 'Landing page hệ thống không thể xóa.',
            ]);
        }

        $page->delete();

        if ($this->selectedId === $id) {
            $this->selectedId = null;
            $this->resetEditorState();
        }

        session()->flash('status', 'Đã xóa landing page custom.');
        $this->redirectRoute('admin.landing-pages', navigate: true);
    }

    public function duplicateBlock(int $index): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $blocks = LandingPageBlocks::normalize($this->form['blocks'] ?? []);
        $block = $blocks[$index] ?? null;

        if (! is_array($block)) {
            return;
        }

        $duplicated = $this->duplicateBlockData($block);
        array_splice($blocks, $index + 1, 0, [$duplicated]);
        $this->form['blocks'] = $blocks;
    }

    public function edit(int $id): void
    {
        $page = LandingPage::query()->findOrFail($id);

        $this->selectedId = (int) $page->getKey();
        $this->cloneSourceId = null;
        $this->pendingCloneMediaSourceId = null;
        $this->blockUploads = [];
        $this->selectedLibraryMediaSelections = [];
        $this->form = $this->formFromModel($page);
    }

    public function selectHomeProcessLibraryMedia(string $uuid, int $mediaId, ?string $alt = null): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $media = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->firstOrFail();

        $index = $this->findHomeProcessCardIndex($uuid);

        if ($index === null) {
            return;
        }

        data_set($this->form, "home_config.process.cards.{$index}.image_url", (string) $media->getUrl());
        data_set(
            $this->form,
            "home_config.process.cards.{$index}.image_alt",
            trim((string) $alt) !== ''
                ? trim((string) $alt)
                : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name),
        );
        data_set($this->form, "home_config.process.cards.{$index}.source_library_media_id", (int) $media->getKey());
    }

    public function moveBlockDown(int $index): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $blocks = LandingPageBlocks::normalize($this->form['blocks'] ?? []);

        if (! isset($blocks[$index + 1])) {
            return;
        }

        [$blocks[$index], $blocks[$index + 1]] = [$blocks[$index + 1], $blocks[$index]];
        $this->form['blocks'] = array_values($blocks);
    }

    public function moveBlockUp(int $index): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $blocks = LandingPageBlocks::normalize($this->form['blocks'] ?? []);

        if ($index <= 0 || ! isset($blocks[$index])) {
            return;
        }

        [$blocks[$index - 1], $blocks[$index]] = [$blocks[$index], $blocks[$index - 1]];
        $this->form['blocks'] = array_values($blocks);
    }

    public function removeBlock(int $index): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $blocks = LandingPageBlocks::normalize($this->form['blocks'] ?? []);
        $block = $blocks[$index] ?? null;

        if (! is_array($block)) {
            return;
        }

        $this->clearSelectionsForBlock($block);
        array_splice($blocks, $index, 1);
        $this->form['blocks'] = array_values($blocks);
    }

    public function removeFaqItem(int $blockIndex, int $itemIndex): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $items = data_get($this->form, 'blocks.'.$blockIndex.'.items', []);

        if (! is_array($items) || ! array_key_exists($itemIndex, $items)) {
            return;
        }

        array_splice($items, $itemIndex, 1);
        data_set(
            $this->form,
            'blocks.'.$blockIndex.'.items',
            $items === [] ? [FaqContent::blankItem()] : array_values($items),
        );
    }

    public function removeGalleryItem(int $blockIndex, int $itemIndex): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $items = data_get($this->form, 'blocks.'.$blockIndex.'.items', []);
        $item = $items[$itemIndex] ?? null;

        if (! is_array($item)) {
            return;
        }

        unset($this->selectedLibraryMediaSelections['blockUploads.'.data_get($this->form, 'blocks.'.$blockIndex.'.uuid').'.gallery.'.($item['uuid'] ?? '')]);
        array_splice($items, $itemIndex, 1);
        data_set(
            $this->form,
            'blocks.'.$blockIndex.'.items',
            $items === [] ? [[
                'uuid' => (string) Str::uuid(),
                'title' => '',
                'subtitle' => '',
                'description' => '',
                'url' => '',
                'image_alt' => '',
            ]] : array_values($items),
        );
    }

    public function removeTourTaxonomyTab(int $blockIndex, int $tabIndex): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $tabs = data_get($this->form, 'blocks.'.$blockIndex.'.tabs', []);

        if (! is_array($tabs) || ! array_key_exists($tabIndex, $tabs)) {
            return;
        }

        array_splice($tabs, $tabIndex, 1);
        data_set(
            $this->form,
            'blocks.'.$blockIndex.'.tabs',
            $tabs === [] ? [LandingPageBlocks::defaultTourTaxonomyTab()] : array_values($tabs),
        );
    }

    public function removeTrustProofCard(int $blockIndex, int $cardIndex): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $cards = data_get($this->form, 'blocks.'.$blockIndex.'.cards', []);

        if (! is_array($cards) || ! array_key_exists($cardIndex, $cards)) {
            return;
        }

        array_splice($cards, $cardIndex, 1);
        data_set(
            $this->form,
            'blocks.'.$blockIndex.'.cards',
            $cards === [] ? [$this->blankTrustProofCard()] : array_values($cards),
        );
    }

    public function removeTrustProofStat(int $blockIndex, int $statIndex): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $stats = data_get($this->form, 'blocks.'.$blockIndex.'.stats', []);

        if (! is_array($stats) || ! array_key_exists($statIndex, $stats)) {
            return;
        }

        array_splice($stats, $statIndex, 1);
        data_set($this->form, 'blocks.'.$blockIndex.'.stats', array_values($stats));
    }

    public function removeHomeTrustCard(int $index): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $cards = data_get($this->form, 'home_config.trust.cards', []);

        if (! is_array($cards) || ! array_key_exists($index, $cards)) {
            return;
        }

        array_splice($cards, $index, 1);
        data_set(
            $this->form,
            'home_config.trust.cards',
            $cards === [] ? [$this->blankHomeTrustCard()] : array_values($cards),
        );
    }

    public function removeHomeProcessCard(int $index): void
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $cards = data_get($this->form, 'home_config.process.cards', []);

        if (! is_array($cards) || ! array_key_exists($index, $cards)) {
            return;
        }

        array_splice($cards, $index, 1);
        data_set(
            $this->form,
            'home_config.process.cards',
            $cards === [] ? [$this->blankHomeProcessCard()] : array_values($cards),
        );
    }

    public function render()
    {
        $pageOptions = LandingPage::query()
            ->orderByRaw('case when page_key is null then 1 else 0 end')
            ->orderBy('page_key')
            ->orderBy('title')
            ->get();

        $pages = LandingPage::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($nested) {
                    $nested
                        ->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%')
                        ->orWhere('page_key', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($this->templateFilter !== '', fn ($query) => $query->where('template_key', $this->templateFilter))
            ->when($this->typeFilter === 'system', fn ($query) => $query->whereNotNull('page_key'))
            ->when($this->typeFilter === 'custom', fn ($query) => $query->whereNull('page_key'))
            ->orderByRaw('case when page_key is null then 1 else 0 end')
            ->orderBy('page_key')
            ->orderBy('title')
            ->paginate(10);

        return view($this->resolveView(), [
            'blockTypes' => LandingPageBlocks::blockTypes(),
            'blogCategories' => ContentCategory::query()->forTaxonomy('blog')->orderBy('sort_order')->orderBy('name')->get(),
            'canEdit' => $this->canAdmin('admin.landing-pages.edit'),
            'destinations' => Destination::query()->published()->orderBy('sort_order')->orderBy('name')->get(),
            'editorModes' => $this->editorModes(),
            'blogPostOptions' => BlogPost::query()->published()->latest('published_at')->orderBy('title')->get(['id', 'title', 'slug']),
            'pages' => $pages,
            'pageOptions' => $pageOptions,
            'regions' => Region::query()->published()->orderBy('sort_order')->orderBy('name')->get(),
            'regionTaxonomyCardTypes' => LandingPageBlocks::regionTaxonomyCardTypes(),
            'reservedSlugs' => LandingPageBlocks::reservedSlugs(),
            'scopeOptions' => collect(TourScope::cases())->mapWithKeys(fn (TourScope $scope) => [$scope->value => $scope->label()])->all(),
            'selectedBlockMedia' => $this->selectedBlockMediaPayload(),
            'selectedPage' => $this->selectedId ? LandingPage::query()->find($this->selectedId) : null,
            'serviceOptions' => Service::query()->published()->orderBy('title')->get(['id', 'title', 'slug']),
            'sliders' => Slider::query()
                ->withCount(['items as active_items_count' => fn ($query) => $query->where('is_active', true)])
                ->orderBy('location')
                ->orderBy('name')
                ->get(),
            'systemPages' => LandingPageBlocks::systemPages(),
            'templates' => LandingPageBlocks::templates(),
            'tourCategories' => TourCategory::query()->published()->orderBy('sort_order')->orderBy('name')->get(),
            'tourSortOptions' => LandingPageBlocks::tourSortOptions(),
            'blogSortOptions' => LandingPageBlocks::blogSortOptions(),
            'galleryTileSizes' => LandingPageBlocks::galleryTileSizes(),
            'galleryVariants' => LandingPageBlocks::galleryVariants(),
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTemplateFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function save()
    {
        $this->authorizeAdminPermission('admin.landing-pages.edit');

        $validated = $this->validate($this->rules());
        $editorMode = (string) ($validated['form']['editor_mode'] ?? LandingPage::EDITOR_MODE_BLOCKS);
        $pageKey = filled($validated['form']['page_key'] ?? null) ? (string) $validated['form']['page_key'] : null;
        $slug = trim((string) ($validated['form']['slug'] ?? ''));
        $title = trim((string) $validated['form']['title']);
        $resolvedSlug = $pageKey ? ($slug !== '' ? Str::slug($slug) : null) : Str::slug($slug !== '' ? $slug : $title);

        if (! $pageKey && in_array($resolvedSlug, LandingPageBlocks::reservedSlugs(), true)) {
            throw ValidationException::withMessages([
                'form.slug' => 'Slug này đang được hệ thống sử dụng, vui lòng chọn slug khác.',
            ]);
        }

        $blocks = LandingPageBlocks::normalize($this->form['blocks'] ?? []);
        $schema = $this->decodeJson($validated['form']['schema_json'] ?? null);
        $page = $this->selectedId ? LandingPage::query()->find($this->selectedId) : null;
        $oldBlocks = $page ? LandingPageBlocks::normalize($this->blocksFromModel($page)) : [];
        $legacyHero = LandingPageBlocks::legacyHero($blocks);
        $legacyContent = LandingPageBlocks::legacyContent($blocks);
        $legacyFaqItems = LandingPageBlocks::legacyFaqItems($blocks);

        $page = LandingPage::query()->updateOrCreate(
            ['id' => $this->selectedId],
            [
                'page_key' => $pageKey,
                'template_key' => (string) ($validated['form']['template_key'] ?? 'generic'),
                'editor_mode' => $editorMode,
                'title' => $title,
                'slug' => $resolvedSlug ?: null,
                'is_active' => (bool) ($validated['form']['is_active'] ?? true),
                'hero_badge' => $legacyHero['hero_badge'],
                'hero_title' => $legacyHero['hero_title'],
                'hero_excerpt' => $this->plainEditorContent($legacyHero['hero_excerpt']),
                'intro_title' => $legacyContent['intro_title'],
                'intro_excerpt' => $this->plainEditorContent($legacyContent['intro_excerpt']),
                'body' => $editorMode === LandingPage::EDITOR_MODE_HTML
                    ? (string) ($validated['form']['html_content'] ?? '')
                    : $this->richEditorContent($legacyContent['body']),
                'cta_title' => $legacyContent['cta_title'],
                'cta_excerpt' => $this->plainEditorContent($legacyContent['cta_excerpt']),
                'cta_primary_label' => $legacyContent['cta_primary_label'],
                'cta_primary_url' => $legacyContent['cta_primary_url'],
                'cta_secondary_label' => $legacyContent['cta_secondary_label'],
                'cta_secondary_url' => $legacyContent['cta_secondary_url'],
                'meta_title' => $validated['form']['meta_title'],
                'meta_description' => $this->plainEditorContent($validated['form']['meta_description'] ?? null),
                'og_title' => $validated['form']['og_title'],
                'og_description' => $this->plainEditorContent($validated['form']['og_description'] ?? null),
                'canonical_url' => $validated['form']['canonical_url'],
                'robots_directive' => $validated['form']['robots_directive'] ?: 'index,follow',
                'schema' => $schema,
                'faq_items' => $legacyFaqItems,
                'blocks' => $blocks,
                'visual_config' => $this->legacyVisualConfig($blocks),
                'home_config' => $this->normalizedHomeConfig($page?->home_config, $validated['form']['home_config'] ?? []),
                'service_detail_config' => $page?->service_detail_config,
                'estimate_config' => $page?->estimate_config,
            ],
        );

        $this->selectedId = (int) $page->getKey();
        $this->syncBlockMedia($page, $oldBlocks, $blocks);
        $this->pendingCloneMediaSourceId = null;
        $this->edit((int) $page->getKey());

        session()->flash(
            'status',
            $editorMode === LandingPage::EDITOR_MODE_HTML
                ? 'Đã lưu landing page ở chế độ HTML thủ công.'
                : 'Đã lưu landing page theo block template.',
        );

        return $this->redirectRoute('admin.landing-pages.edit', ['landingPage' => $page], navigate: true);
    }

    protected function blocksFromModel(LandingPage $page): array
    {
        $blocks = LandingPageBlocks::normalize($page->blocks ?? []);

        if ($blocks !== [] || $page->isHtmlMode()) {
            return $blocks;
        }

        $derivedBlocks = [];
        $visualConfig = LandingPageVisuals::prepare($page->visual_config);
        $heroSource = (string) data_get($visualConfig, 'hero.source', LandingPageVisuals::SOURCE_NONE);
        $gallerySource = (string) data_get($visualConfig, 'gallery.source', LandingPageVisuals::SOURCE_NONE);

        if ((bool) data_get($visualConfig, 'hero.enabled')) {
            $heroType = $heroSource === LandingPageVisuals::SOURCE_SLIDER
                ? LandingPageBlocks::TYPE_HERO_SLIDER
                : LandingPageBlocks::TYPE_HERO_MEDIA;
            $heroBlock = LandingPageBlocks::defaultBlock($heroType);
            $heroBlock['eyebrow'] = (string) ($page->hero_badge ?? '');
            $heroBlock['title'] = (string) ($page->hero_title ?? $page->title);
            $heroBlock['description'] = (string) ($page->hero_excerpt ?? '');
            $heroBlock['primary_label'] = (string) ($page->cta_primary_label ?? '');
            $heroBlock['primary_url'] = (string) ($page->cta_primary_url ?? '');
            $heroBlock['secondary_label'] = (string) ($page->cta_secondary_label ?? '');
            $heroBlock['secondary_url'] = (string) ($page->cta_secondary_url ?? '');

            if ($heroType === LandingPageBlocks::TYPE_HERO_SLIDER) {
                $heroBlock['slider_id'] = data_get($visualConfig, 'hero.slider_id');
            } else {
                $heroBlock['media_alt'] = (string) data_get($visualConfig, 'hero.media_alt');
            }

            $derivedBlocks[] = $heroBlock;
        }

        if ((bool) data_get($visualConfig, 'gallery.enabled')) {
            $galleryType = $gallerySource === LandingPageVisuals::SOURCE_SLIDER
                ? LandingPageBlocks::TYPE_GALLERY_SLIDER
                : LandingPageBlocks::TYPE_GALLERY_MEDIA;
            $galleryBlock = LandingPageBlocks::defaultBlock($galleryType);
            $galleryBlock['eyebrow'] = (string) data_get($visualConfig, 'gallery.eyebrow');
            $galleryBlock['title'] = (string) data_get($visualConfig, 'gallery.title');
            $galleryBlock['description'] = (string) data_get($visualConfig, 'gallery.description');

            if ($galleryType === LandingPageBlocks::TYPE_GALLERY_SLIDER) {
                $galleryBlock['slider_id'] = data_get($visualConfig, 'gallery.slider_id');
            } else {
                $galleryBlock['items'] = collect(data_get($visualConfig, 'gallery.items', []))
                    ->map(fn (array $item) => [
                        'uuid' => (string) ($item['uuid'] ?? Str::uuid()),
                        'title' => (string) ($item['title'] ?? ''),
                        'subtitle' => (string) ($item['subtitle'] ?? ''),
                        'description' => (string) ($item['description'] ?? ''),
                        'image_url' => (string) ($item['image_url'] ?? ''),
                        'tab_label' => (string) ($item['tab_label'] ?? ''),
                        'tile_size' => (string) ($item['tile_size'] ?? LandingPageBlocks::GALLERY_TILE_STANDARD),
                        'url' => (string) ($item['url'] ?? ''),
                        'image_alt' => (string) ($item['image_alt'] ?? ''),
                    ])
                    ->whenEmpty(fn (Collection $collection) => $collection->push(LandingPageBlocks::defaultGalleryItem()))
                    ->values()
                    ->all();
            }

            $derivedBlocks[] = $galleryBlock;
        }

        if (filled($page->intro_title) || filled($page->intro_excerpt) || filled($page->body)) {
            $richText = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_RICH_TEXT);
            $richText['title'] = (string) ($page->intro_title ?? '');
            $richText['excerpt'] = (string) ($page->intro_excerpt ?? '');
            $richText['body'] = (string) ($page->body ?? '');
            $derivedBlocks[] = $richText;
        }

        if (filled($page->cta_title) || filled($page->cta_excerpt) || filled($page->cta_primary_label) || filled($page->cta_secondary_label)) {
            $cta = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_CTA);
            $cta['title'] = (string) ($page->cta_title ?? '');
            $cta['description'] = (string) ($page->cta_excerpt ?? '');
            $cta['primary_label'] = (string) ($page->cta_primary_label ?? '');
            $cta['primary_url'] = (string) ($page->cta_primary_url ?? '');
            $cta['secondary_label'] = (string) ($page->cta_secondary_label ?? '');
            $cta['secondary_url'] = (string) ($page->cta_secondary_url ?? '');
            $derivedBlocks[] = $cta;
        }

        if (($page->faq_items ?? []) !== []) {
            $faq = LandingPageBlocks::defaultBlock(LandingPageBlocks::TYPE_FAQ);
            $faq['title'] = 'Câu hỏi thường gặp';
            $faq['items'] = FaqContent::prepareItems($page->faq_items, [FaqContent::blankItem()]);
            $derivedBlocks[] = $faq;
        }

        return $derivedBlocks === []
            ? LandingPageBlocks::presetBlocks($this->resolveTemplateKey($page))
            : LandingPageBlocks::normalize($derivedBlocks);
    }

    protected function clearSelectionsForBlock(array $block): void
    {
        $blockUuid = (string) ($block['uuid'] ?? '');

        if ($blockUuid === '') {
            return;
        }

        unset($this->selectedLibraryMediaSelections['blockUploads.'.$blockUuid.'.media']);

        foreach (data_get($block, 'items', []) as $item) {
            $itemUuid = (string) ($item['uuid'] ?? '');

            if ($itemUuid === '') {
                continue;
            }

            unset($this->selectedLibraryMediaSelections['blockUploads.'.$blockUuid.'.gallery.'.$itemUuid]);
        }
    }

    protected function copyCollectionMedia(LandingPage $source, LandingPage $target, string $collection, array $customProperties = []): void
    {
        $media = $source->getFirstMedia($collection);

        if (! $media) {
            return;
        }

        $target->clearMediaCollection($collection);

        $media->copy(
            model: $target,
            collectionName: $collection,
            diskName: config('media-library.disk_name', 'public'),
            fileAdderCallback: fn ($fileAdder) => $fileAdder->withCustomProperties(array_merge(
                $media->custom_properties ?? [],
                $customProperties,
            )),
        );
    }

    protected function decodeJson(?string $json): ?array
    {
        if (! filled($json)) {
            return null;
        }

        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : null;
    }

    protected function blankHomeTrustCard(?string $highlight = '', ?string $title = '', ?string $text = '', ?string $icon = ''): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'icon' => trim((string) $icon),
            'highlight' => trim((string) $highlight),
            'title' => trim((string) $title),
            'text' => trim((string) $text),
        ];
    }

    protected function blankTrustProofCard(?string $highlight = '', ?string $title = '', ?string $text = '', ?string $icon = ''): array
    {
        return LandingPageBlocks::defaultTrustProofCard(
            trim((string) $highlight),
            trim((string) $title),
            trim((string) $text),
            trim((string) $icon),
        );
    }

    protected function blankTrustProofStat(?string $value = '', ?string $label = '', ?string $icon = ''): array
    {
        return LandingPageBlocks::defaultTrustProofStat(
            trim((string) $value),
            trim((string) $label),
            trim((string) $icon),
        );
    }

    protected function blankHomeProcessCard(?string $title = '', ?string $description = ''): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => trim((string) $title),
            'description' => trim((string) $description),
            'image_url' => '',
            'image_alt' => trim((string) $title),
            'source_library_media_id' => null,
        ];
    }

    protected function findHomeProcessCardIndex(string $uuid): ?int
    {
        foreach (data_get($this->form, 'home_config.process.cards', []) as $index => $card) {
            if ((string) data_get($card, 'uuid') === $uuid) {
                return (int) $index;
            }
        }

        return null;
    }

    protected function normalizedHomeConfig(?array $existingConfig = null, array $submittedConfig = []): array
    {
        return TravelHomePageConfig::prepare(array_merge($existingConfig ?? [], $submittedConfig));
    }

    protected function duplicateBlockData(array $block): array
    {
        $duplicate = $block;
        $oldBlockUuid = (string) ($block['uuid'] ?? '');
        $duplicate['uuid'] = (string) Str::uuid();

        if (($duplicate['type'] ?? null) === LandingPageBlocks::TYPE_GALLERY_MEDIA) {
            $duplicate['items'] = collect($duplicate['items'] ?? [])
                ->map(function (array $item) use ($oldBlockUuid, &$duplicate): array {
                    $oldItemUuid = (string) ($item['uuid'] ?? '');
                    $item['uuid'] = (string) Str::uuid();

                    $oldKey = 'blockUploads.'.$oldBlockUuid.'.gallery.'.$oldItemUuid;
                    $newKey = 'blockUploads.'.$duplicate['uuid'].'.gallery.'.$item['uuid'];

                    if (isset($this->selectedLibraryMediaSelections[$oldKey])) {
                        $this->selectedLibraryMediaSelections[$newKey] = $this->selectedLibraryMediaSelections[$oldKey];
                    }

                    return $item;
                })
                ->values()
                ->all();
        }

        if (($duplicate['type'] ?? null) === LandingPageBlocks::TYPE_TRUST_PROOF) {
            $duplicate['cards'] = collect($duplicate['cards'] ?? [])
                ->map(function (array $card): array {
                    $card['uuid'] = (string) Str::uuid();

                    return $card;
                })
                ->values()
                ->all();
        }

        if (($duplicate['type'] ?? null) === LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS) {
            $duplicate['tabs'] = collect($duplicate['tabs'] ?? [])
                ->map(function (array $tab): array {
                    $tab['uuid'] = (string) Str::uuid();

                    return $tab;
                })
                ->values()
                ->all();
        }

        $oldMediaKey = 'blockUploads.'.$oldBlockUuid.'.media';
        $newMediaKey = 'blockUploads.'.$duplicate['uuid'].'.media';

        if (isset($this->selectedLibraryMediaSelections[$oldMediaKey])) {
            $this->selectedLibraryMediaSelections[$newMediaKey] = $this->selectedLibraryMediaSelections[$oldMediaKey];
        }

        return $duplicate;
    }

    protected function editorModes(): array
    {
        return [
            LandingPage::EDITOR_MODE_BLOCKS => 'Block builder',
            LandingPage::EDITOR_MODE_HTML => 'HTML thủ công',
        ];
    }

    protected function ensureDefaults(): void
    {
        collect(LandingPageBlocks::systemPages())
            ->each(function (string $title, string $pageKey): void {
                LandingPage::query()->firstOrCreate(
                    ['page_key' => $pageKey],
                    [
                        'template_key' => $pageKey,
                        'title' => $title,
                        'is_active' => true,
                        'robots_directive' => 'index,follow',
                    ],
                );
            });
    }

    protected function formatJson(mixed $value): string
    {
        if (! is_array($value) || $value === []) {
            return '';
        }

        return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function formFromModel(LandingPage $page): array
    {
        return [
            'page_key' => $page->page_key,
            'template_key' => $this->resolveTemplateKey($page),
            'editor_mode' => $page->editor_mode ?: LandingPage::EDITOR_MODE_BLOCKS,
            'title' => $page->title,
            'slug' => $page->slug,
            'is_active' => (bool) $page->is_active,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'og_title' => $page->og_title,
            'og_description' => $page->og_description,
            'canonical_url' => $page->canonical_url,
            'robots_directive' => $page->robots_directive ?: 'index,follow',
            'schema_json' => $this->formatJson($page->schema),
            'html_content' => $page->isHtmlMode() ? (string) ($page->body ?? '') : '',
            'home_config' => $this->normalizedHomeConfig($page->home_config),
            'blocks' => $this->blocksFromModel($page),
        ];
    }

    protected function isCreateRoute(): bool
    {
        return $this->currentRouteName === 'admin.landing-pages.create';
    }

    protected function isEditRoute(): bool
    {
        return $this->currentRouteName === 'admin.landing-pages.edit';
    }

    protected function resolveView(): string
    {
        return $this->currentRouteName === 'admin.landing-pages'
            ? 'livewire.admin.cms.landing-pages.index'
            : 'livewire.admin.cms.landing-pages-manager';
    }

    protected function legacyVisualConfig(array $blocks): array
    {
        $config = LandingPageVisuals::defaultConfig();
        $enabledBlocks = collect($blocks)->filter(fn ($block) => is_array($block) && (bool) ($block['is_enabled'] ?? true));
        $hero = $enabledBlocks->first(fn (array $block) => in_array($block['type'] ?? null, [
            LandingPageBlocks::TYPE_HERO_SLIDER,
            LandingPageBlocks::TYPE_HERO_MEDIA,
        ], true));
        $gallery = $enabledBlocks->first(fn (array $block) => in_array($block['type'] ?? null, [
            LandingPageBlocks::TYPE_GALLERY_SLIDER,
            LandingPageBlocks::TYPE_GALLERY_MEDIA,
        ], true));

        if (is_array($hero)) {
            $config['hero']['enabled'] = true;
            $config['hero']['source'] = ($hero['type'] ?? null) === LandingPageBlocks::TYPE_HERO_SLIDER
                ? LandingPageVisuals::SOURCE_SLIDER
                : LandingPageVisuals::SOURCE_MEDIA;
            $config['hero']['slider_id'] = $hero['slider_id'] ?? null;
            $config['hero']['media_alt'] = (string) ($hero['media_alt'] ?? '');
        }

        if (is_array($gallery)) {
            $config['gallery']['enabled'] = true;
            $config['gallery']['source'] = ($gallery['type'] ?? null) === LandingPageBlocks::TYPE_GALLERY_SLIDER
                ? LandingPageVisuals::SOURCE_SLIDER
                : LandingPageVisuals::SOURCE_MEDIA;
            $config['gallery']['slider_id'] = $gallery['slider_id'] ?? null;
            $config['gallery']['eyebrow'] = (string) ($gallery['eyebrow'] ?? '');
            $config['gallery']['title'] = (string) ($gallery['title'] ?? '');
            $config['gallery']['description'] = (string) ($gallery['description'] ?? '');
            $config['gallery']['items'] = collect($gallery['items'] ?? [])
                ->map(fn (array $item) => [
                    'uuid' => (string) ($item['uuid'] ?? Str::uuid()),
                    'title' => (string) ($item['title'] ?? ''),
                    'subtitle' => (string) ($item['subtitle'] ?? ''),
                    'description' => (string) ($item['description'] ?? ''),
                    'url' => (string) ($item['url'] ?? ''),
                    'image_alt' => (string) ($item['image_alt'] ?? ''),
                ])
                ->values()
                ->all();
        }

        return $config;
    }

    protected function purgeSelectionsForMissingBlocks(array $oldBlocks, array $newBlocks): void
    {
        $newUuids = collect($newBlocks)->pluck('uuid')->filter()->all();

        foreach ($oldBlocks as $block) {
            if (in_array($block['uuid'] ?? null, $newUuids, true)) {
                continue;
            }

            $this->clearSelectionsForBlock($block);
        }
    }

    protected function resetEditorState(): void
    {
        $this->blockUploads = [];
        $this->selectedLibraryMediaSelections = [];
        $this->form = [
            'page_key' => null,
            'template_key' => 'generic',
            'editor_mode' => LandingPage::EDITOR_MODE_BLOCKS,
            'title' => '',
            'slug' => '',
            'is_active' => true,
            'meta_title' => '',
            'meta_description' => '',
            'og_title' => '',
            'og_description' => '',
            'canonical_url' => '',
            'robots_directive' => 'index,follow',
            'schema_json' => '',
            'html_content' => '',
            'home_config' => $this->normalizedHomeConfig(),
            'blocks' => LandingPageBlocks::presetBlocks('generic'),
        ];
    }

    protected function resolveTemplateKey(LandingPage $page): string
    {
        $templateKey = $page->template_key ?: ($page->page_key ?: 'generic');

        return array_key_exists($templateKey, LandingPageBlocks::templates())
            ? $templateKey
            : 'generic';
    }

    protected function rules(): array
    {
        return [
            'form.page_key' => [
                'nullable',
                'string',
                Rule::in(array_merge([''], array_keys(LandingPageBlocks::systemPages()))),
                Rule::unique('landing_pages', 'page_key')->ignore($this->selectedId),
            ],
            'form.template_key' => ['required', 'string', Rule::in(array_keys(LandingPageBlocks::templates()))],
            'form.editor_mode' => ['required', 'string', Rule::in(array_keys($this->editorModes()))],
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('landing_pages', 'slug')->ignore($this->selectedId),
            ],
            'form.is_active' => ['boolean'],
            'form.meta_title' => ['nullable', 'string', 'max:255'],
            'form.meta_description' => ['nullable', 'string', 'max:500'],
            'form.og_title' => ['nullable', 'string', 'max:255'],
            'form.og_description' => ['nullable', 'string', 'max:500'],
            'form.canonical_url' => ['nullable', 'url'],
            'form.robots_directive' => ['nullable', 'string', 'max:255'],
            'form.schema_json' => ['nullable', 'string'],
            'form.html_content' => ['nullable', 'string'],
            'form.home_config' => ['array'],
            'form.home_config.featured_tour_category_slug' => ['nullable', 'string', 'max:255'],
            'form.home_config.featured_tour_limit' => ['nullable', 'integer', 'min:1', 'max:12'],
            'form.home_config.featured_blog_limit' => ['nullable', 'integer', 'min:1', 'max:12'],
            'form.home_config.featured_service_slugs' => ['nullable', 'array', 'max:4'],
            'form.home_config.featured_service_slugs.*' => ['nullable', 'string', 'max:255'],
            'form.home_config.featured_blog_slugs' => ['nullable', 'array', 'max:12'],
            'form.home_config.featured_blog_slugs.*' => ['nullable', 'string', 'max:255'],
            'form.home_config.featured_destination_slugs' => ['nullable', 'array', 'max:8'],
            'form.home_config.featured_destination_slugs.*' => ['nullable', 'string', 'max:255'],
            'form.home_config.search' => ['nullable', 'array'],
            'form.home_config.search.is_enabled' => ['boolean'],
            'form.home_config.search.placeholder' => ['nullable', 'string', 'max:255'],
            'form.home_config.search.button_label' => ['nullable', 'string', 'max:100'],
            'form.home_config.topic_rail' => ['nullable', 'array'],
            'form.home_config.topic_rail.is_enabled' => ['boolean'],
            'form.home_config.featured_tours' => ['nullable', 'array'],
            'form.home_config.featured_tours.is_enabled' => ['boolean'],
            'form.home_config.featured_tours.cta_label' => ['nullable', 'string', 'max:100'],
            'form.home_config.featured_tours.tabs' => ['nullable', 'array'],
            'form.home_config.featured_tours.tabs.domestic.label' => ['nullable', 'string', 'max:255'],
            'form.home_config.featured_tours.tabs.domestic.title' => ['nullable', 'string', 'max:255'],
            'form.home_config.featured_tours.tabs.domestic.description' => ['nullable', 'string', 'max:500'],
            'form.home_config.featured_tours.tabs.international.label' => ['nullable', 'string', 'max:255'],
            'form.home_config.featured_tours.tabs.international.title' => ['nullable', 'string', 'max:255'],
            'form.home_config.featured_tours.tabs.international.description' => ['nullable', 'string', 'max:500'],
            'form.home_config.featured_tours.tabs.group.label' => ['nullable', 'string', 'max:255'],
            'form.home_config.featured_tours.tabs.group.title' => ['nullable', 'string', 'max:255'],
            'form.home_config.featured_tours.tabs.group.description' => ['nullable', 'string', 'max:500'],
            'form.home_config.destination_slider' => ['nullable', 'array'],
            'form.home_config.destination_slider.is_enabled' => ['boolean'],
            'form.home_config.destination_slider.title' => ['nullable', 'string', 'max:255'],
            'form.home_config.destination_slider.description' => ['nullable', 'string', 'max:500'],
            'form.home_config.destination_slider.card_cta_label' => ['nullable', 'string', 'max:100'],
            'form.home_config.services' => ['nullable', 'array'],
            'form.home_config.services.is_enabled' => ['boolean'],
            'form.home_config.services.title' => ['nullable', 'string', 'max:255'],
            'form.home_config.services.description' => ['nullable', 'string', 'max:500'],
            'form.home_config.services.cta_label' => ['nullable', 'string', 'max:100'],
            'form.home_config.services.cta_url' => ['nullable', 'string', 'max:255'],
            'form.home_config.trust' => ['nullable', 'array'],
            'form.home_config.trust.is_enabled' => ['boolean'],
            'form.home_config.trust.title' => [
                Rule::requiredIf(fn (): bool => ($this->form['page_key'] ?? null) === 'home'),
                'nullable',
                'string',
                'max:255',
            ],
            'form.home_config.trust.description' => ['nullable', 'string', 'max:500'],
            'form.home_config.trust.cards' => [
                Rule::requiredIf(fn (): bool => ($this->form['page_key'] ?? null) === 'home'),
                'array',
                'min:1',
            ],
            'form.home_config.trust.cards.*.uuid' => ['nullable', 'string', 'max:100'],
            'form.home_config.trust.cards.*.icon' => ['nullable', 'string', 'max:255'],
            'form.home_config.trust.cards.*.highlight' => ['nullable', 'string', 'max:120'],
            'form.home_config.trust.cards.*.title' => [
                Rule::requiredIf(fn (): bool => ($this->form['page_key'] ?? null) === 'home'),
                'nullable',
                'string',
                'max:255',
            ],
            'form.home_config.trust.cards.*.text' => [
                Rule::requiredIf(fn (): bool => ($this->form['page_key'] ?? null) === 'home'),
                'nullable',
                'string',
                'max:1000',
            ],
            'form.home_config.process' => ['nullable', 'array'],
            'form.home_config.process.is_enabled' => ['boolean'],
            'form.home_config.process.title' => ['nullable', 'string', 'max:255'],
            'form.home_config.process.description' => ['nullable', 'string', 'max:500'],
            'form.home_config.process.cards' => ['nullable', 'array', 'min:1', 'max:6'],
            'form.home_config.process.cards.*.uuid' => ['nullable', 'string', 'max:100'],
            'form.home_config.process.cards.*.title' => ['nullable', 'string', 'max:255'],
            'form.home_config.process.cards.*.description' => ['nullable', 'string', 'max:500'],
            'form.home_config.process.cards.*.image_url' => ['nullable', 'string', 'max:2048'],
            'form.home_config.process.cards.*.image_alt' => ['nullable', 'string', 'max:255'],
            'form.home_config.process.cards.*.source_library_media_id' => ['nullable', 'integer'],
            'form.home_config.blog_preview' => ['nullable', 'array'],
            'form.home_config.blog_preview.is_enabled' => ['boolean'],
            'form.home_config.blog_preview.title' => ['nullable', 'string', 'max:255'],
            'form.home_config.blog_preview.description' => ['nullable', 'string', 'max:500'],
            'form.home_config.blog_preview.cta_label' => ['nullable', 'string', 'max:100'],
            'form.home_config.blog_preview.cta_url' => ['nullable', 'string', 'max:255'],
            'form.blocks' => ['array'],
            'form.blocks.*.uuid' => ['required', 'string', 'max:100'],
            'form.blocks.*.type' => ['required', 'string', Rule::in(array_keys(LandingPageBlocks::blockTypes()))],
            'form.blocks.*.html' => ['nullable', 'string'],
            'form.blocks.*.variant' => ['nullable', 'string'],
            'form.blocks.*.cta_label' => ['nullable', 'string', 'max:100'],
            'form.blocks.*.card_source_type' => ['nullable', 'string', Rule::in(array_keys(LandingPageBlocks::regionTaxonomyCardTypes()))],
            'form.blocks.*.cards' => ['nullable', 'array', 'min:1', 'max:6'],
            'form.blocks.*.cards.*.uuid' => ['nullable', 'string', 'max:100'],
            'form.blocks.*.cards.*.icon' => ['nullable', 'string', 'max:255'],
            'form.blocks.*.cards.*.highlight' => ['nullable', 'string', 'max:120'],
            'form.blocks.*.cards.*.title' => ['nullable', 'string', 'max:255'],
            'form.blocks.*.cards.*.text' => ['nullable', 'string', 'max:500'],
            'form.blocks.*.stats' => ['nullable', 'array', 'max:6'],
            'form.blocks.*.stats.*.uuid' => ['nullable', 'string', 'max:100'],
            'form.blocks.*.stats.*.icon' => ['nullable', 'string', 'max:255'],
            'form.blocks.*.stats.*.label' => ['nullable', 'string', 'max:120'],
            'form.blocks.*.stats.*.value' => ['nullable', 'string', 'max:120'],
            'form.blocks.*.tabs' => ['nullable', 'array', 'min:1', 'max:8'],
            'form.blocks.*.tabs.*.uuid' => ['nullable', 'string', 'max:100'],
            'form.blocks.*.tabs.*.source_type' => ['nullable', 'string', Rule::in(array_keys(LandingPageBlocks::tourTaxonomyTabTypes()))],
            'form.blocks.*.tabs.*.source_slug' => ['nullable', 'string', 'max:255'],
            'form.blocks.*.tabs.*.label' => ['nullable', 'string', 'max:255'],
            'form.blocks.*.tabs.*.title' => ['nullable', 'string', 'max:255'],
            'form.blocks.*.tabs.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function selectedBlockMediaPayload(): array
    {
        $payload = $this->resolveSelectedUploadMediaPayloadByPrefix('blockUploads');
        $resolved = [];

        foreach ($payload as $key => $media) {
            $segments = explode('.', $key);

            if (($segments[0] ?? null) !== 'blockUploads' || ! isset($segments[1], $segments[2])) {
                continue;
            }

            $blockUuid = $segments[1];

            if ($segments[2] === 'media') {
                $resolved[$blockUuid]['media'] = $media;

                continue;
            }

            if ($segments[2] === 'gallery' && isset($segments[3])) {
                $resolved[$blockUuid]['gallery'][$segments[3]] = $media;
            }
        }

        return $resolved;
    }

    protected function syncBlockMedia(LandingPage $page, array $oldBlocks, array $blocks): void
    {
        $oldCollections = $this->mediaCollectionsForBlocks($oldBlocks);
        $newCollections = $this->mediaCollectionsForBlocks($blocks);

        foreach (array_diff($oldCollections, $newCollections) as $collection) {
            $page->clearMediaCollection($collection);
        }

        $cloneSource = $this->pendingCloneMediaSourceId
            ? LandingPage::query()->find($this->pendingCloneMediaSourceId)
            : null;

        foreach ($blocks as $block) {
            $blockUuid = (string) ($block['uuid'] ?? '');

            if ($blockUuid === '') {
                continue;
            }

            if (in_array($block['type'] ?? null, [
                LandingPageBlocks::TYPE_HERO_MEDIA,
                LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE,
            ], true)) {
                $collection = LandingPageBlocks::mediaCollection($blockUuid);
                $selectionKey = 'blockUploads.'.$blockUuid.'.media';
                $mediaId = $this->selectedLibraryMediaIdForUpload($selectionKey);
                $alt = (string) ($block['media_alt'] ?? '');

                if ($mediaId) {
                    $this->syncSingleImageFromLibrary($page, $mediaId, $collection, ['alt' => $alt]);
                } elseif ($cloneSource) {
                    $this->copyCollectionMedia($cloneSource, $page, $collection, ['alt' => $alt]);
                } else {
                    $this->touchCollectionAlt($page, $collection, $alt);
                }
            }

            if (($block['type'] ?? null) !== LandingPageBlocks::TYPE_GALLERY_MEDIA) {
                continue;
            }

            foreach ($block['items'] ?? [] as $item) {
                $itemUuid = (string) ($item['uuid'] ?? '');

                if ($itemUuid === '') {
                    continue;
                }

                $collection = LandingPageBlocks::galleryItemCollection($blockUuid, $itemUuid);
                $selectionKey = 'blockUploads.'.$blockUuid.'.gallery.'.$itemUuid;
                $mediaId = $this->selectedLibraryMediaIdForUpload($selectionKey);
                $alt = (string) ($item['image_alt'] ?? '');

                if ($mediaId) {
                    $this->syncSingleImageFromLibrary($page, $mediaId, $collection, ['alt' => $alt]);
                } elseif ($cloneSource) {
                    $this->copyCollectionMedia($cloneSource, $page, $collection, ['alt' => $alt]);
                } else {
                    $this->touchCollectionAlt($page, $collection, $alt);
                }
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, string>
     */
    protected function mediaCollectionsForBlocks(array $blocks): array
    {
        return collect($blocks)
            ->flatMap(function (array $block): array {
                $uuid = (string) ($block['uuid'] ?? '');

                if ($uuid === '') {
                    return [];
                }

                if (in_array($block['type'] ?? null, [
                    LandingPageBlocks::TYPE_HERO_MEDIA,
                    LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE,
                ], true)) {
                    return [LandingPageBlocks::mediaCollection($uuid)];
                }

                if (($block['type'] ?? null) !== LandingPageBlocks::TYPE_GALLERY_MEDIA) {
                    return [];
                }

                return collect($block['items'] ?? [])
                    ->map(fn (array $item) => LandingPageBlocks::galleryItemCollection(
                        $uuid,
                        (string) ($item['uuid'] ?? ''),
                    ))
                    ->filter()
                    ->values()
                    ->all();
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function touchCollectionAlt(LandingPage $page, string $collection, string $alt): void
    {
        $media = $page->getFirstMedia($collection);

        if (! $media) {
            return;
        }

        $media->setCustomProperty('alt', $alt);
        $media->save();
    }
}
