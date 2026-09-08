<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Cms\Concerns\HandlesMediaUploads;
use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use App\Support\ContentGallery;
use App\Support\FaqContent;
use App\Support\ServiceDetailContent;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Service;

#[Layout('layouts.app')]
#[Title('Quản lý dịch vụ')]
class ServicesManager extends Component
{
    use AuthorizesAdminPermissions;
    use HandlesMediaUploads;
    use InteractsWithEditorContent;
    use WithFileUploads;
    use WithPagination;

    public array $categoryForm = [];

    public string $categoryFilter = '';

    public string $categorySearch = '';

    public mixed $avatarUpload = null;

    public mixed $coverUpload = null;

    public ?string $coverUploadOriginalName = null;

    public ?string $currentRouteName = null;

    public ?int $editingCategoryId = null;

    public array $featureBlockUploads = [];

    public array $form = [];

    public array $galleryUploads = [];

    public array $heroSlideUploads = [];

    public string $search = '';

    public string $statusFilter = '';

    public ?int $selectedLibraryAvatarMediaId = null;

    public array $selectedFeatureBlockLibraryMediaIds = [];

    public array $selectedGalleryLibraryMediaIds = [];

    public array $selectedHeroSlideLibraryMediaIds = [];

    public ?int $selectedId = null;

    public ?int $selectedLibraryCoverMediaId = null;

    public function mount(?Service $service = null, ?ContentCategory $category = null): void
    {
        $this->currentRouteName = request()->route()?->getName();
        $this->resetCategoryForm();
        $this->resetServiceForm();

        if ($this->isServiceEditorRoute() && $service) {
            $this->editService((int) $service->getKey());
        }

        if ($this->isCategoryEditorRoute() && $category) {
            $this->editCategory((int) $category->getKey());
        }
    }

    public function addFaqItem(): void
    {
        $this->form['faq_items'][] = FaqContent::blankItem();
    }

    public function addFeatureBlock(): void
    {
        $this->form['detail_config']['feature_blocks'][] = $this->blankFeatureBlock();
    }

    public function addFeatureHighlight(int $blockIndex): void
    {
        $highlights = data_get($this->form, "detail_config.feature_blocks.{$blockIndex}.highlights", []);
        $highlights[] = '';
        data_set($this->form, "detail_config.feature_blocks.{$blockIndex}.highlights", array_values($highlights));
    }

    public function addGalleryItem(string $type = ContentGallery::TYPE_IMAGE): void
    {
        $this->form['gallery'][] = ContentGallery::defaultItem($type);
    }

    public function addHeroSlide(): void
    {
        $this->form['detail_config']['hero_slides'][] = $this->blankHeroSlide();
    }

    public function addPricingRow(): void
    {
        $this->form['detail_config']['pricing']['rows'][] = $this->blankPricingRow();
    }

    public function addProcessCard(): void
    {
        $this->form['detail_config']['process']['cards'][] = $this->blankProcessCard();
    }

    public function addRelatedQuestion(): void
    {
        $this->form['related_questions'][] = '';
    }

    public function clearCoverLibraryMediaSelection(): void
    {
        $this->authorizeAdminPermission('admin.services.edit');

        $this->selectedLibraryCoverMediaId = null;
    }

    public function clearAvatarLibraryMediaSelection(): void
    {
        $this->authorizeAdminPermission('admin.services.categories.edit');

        $this->selectedLibraryAvatarMediaId = null;
    }

    public function clearFeatureBlockLibraryMediaSelection(string $uuid): void
    {
        unset($this->selectedFeatureBlockLibraryMediaIds[$uuid]);
    }

    public function clearGalleryLibraryMediaSelection(string $uuid): void
    {
        unset($this->selectedGalleryLibraryMediaIds[$uuid]);
    }

    public function clearHeroSlideLibraryMediaSelection(string $uuid): void
    {
        unset($this->selectedHeroSlideLibraryMediaIds[$uuid]);
    }

    public function createService(): void
    {
        $this->authorizeAdminPermission('admin.services.edit');

        $this->selectedId = null;
        $this->coverUpload = null;
        $this->coverUploadOriginalName = null;
        $this->featureBlockUploads = [];
        $this->galleryUploads = [];
        $this->heroSlideUploads = [];
        $this->selectedFeatureBlockLibraryMediaIds = [];
        $this->selectedGalleryLibraryMediaIds = [];
        $this->selectedHeroSlideLibraryMediaIds = [];
        $this->selectedLibraryCoverMediaId = null;
        $this->resetServiceForm();
    }

    public function deleteCategory(int $id): void
    {
        $this->authorizeAdminPermission('admin.services.categories.edit');

        $category = ContentCategory::query()->forTaxonomy('service')->findOrFail($id);

        if ($category->services()->exists()) {
            $this->addError('categoryForm.name', 'Không thể xóa danh mục đang có dịch vụ.');

            return;
        }

        $category->delete();
        $this->resetCategoryForm();
        session()->flash('status', 'Đã xóa danh mục dịch vụ.');

        if ($this->isCategoryEditorRoute()) {
            $this->redirectRoute('admin.services.categories', navigate: true);
        }
    }

    public function deleteService(int $id): void
    {
        $this->authorizeAdminPermission('admin.services.edit');

        Service::query()->findOrFail($id)->delete();

        if ($this->selectedId === $id) {
            $this->createService();
        }

        session()->flash('status', 'Đã xóa dịch vụ.');

        if ($this->isServiceEditorRoute()) {
            $this->redirectRoute('admin.services', navigate: true);
        }
    }

    public function editCategory(int $id): void
    {
        $this->authorizeAdminPermission('admin.services.categories.edit');

        $category = ContentCategory::query()->findOrFail($id);
        $avatarMedia = $category->getFirstMedia('avatar');

        $this->editingCategoryId = $category->id;
        $this->avatarUpload = null;
        $this->selectedLibraryAvatarMediaId = $avatarMedia
            ? (int) data_get($avatarMedia->custom_properties, 'source_library_media_id')
            : null;
        $this->categoryForm = [
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'content' => $category->content,
            'avatar_alt' => (string) data_get($avatarMedia?->custom_properties, 'alt', $category->name),
            'sort_order' => $category->sort_order,
        ];
    }

    public function editService(int $id): void
    {
        $this->authorizeAdminPermission('admin.services.edit');

        $service = Service::query()->findOrFail($id);
        $coverMedia = $service->getFirstMedia('cover');
        $detailConfig = $this->resolvedServiceConfig(
            $service->detail_config,
            $service->title,
            $service->excerpt,
            $service->content,
            $service->price_note,
        );
        $gallery = ContentGallery::prepare($service->gallery);

        $this->selectedId = $service->id;
        $this->coverUpload = null;
        $this->coverUploadOriginalName = null;
        $this->featureBlockUploads = [];
        $this->galleryUploads = [];
        $this->heroSlideUploads = [];
        $this->selectedLibraryCoverMediaId = $coverMedia
            ? (int) data_get($coverMedia->custom_properties, 'source_library_media_id')
            : null;
        $this->selectedHeroSlideLibraryMediaIds = $this->resolveSelectedLibraryMediaIds(
            $service,
            $detailConfig['hero_slides'] ?? [],
            fn (string $uuid): string => ServiceDetailContent::heroSlideCollection($uuid),
        );
        $this->selectedFeatureBlockLibraryMediaIds = $this->resolveSelectedLibraryMediaIds(
            $service,
            $detailConfig['feature_blocks'] ?? [],
            fn (string $uuid): string => ServiceDetailContent::featureBlockCollection($uuid),
        );
        $this->selectedGalleryLibraryMediaIds = $this->resolveSelectedLibraryMediaIds(
            $service,
            $gallery,
            fn (string $uuid): string => ContentGallery::serviceCollection($uuid),
        );
        $this->form = [
            'title' => $service->title,
            'slug' => $service->slug,
            'excerpt' => $service->excerpt,
            'content' => $service->content,
            'status' => $service->status,
            'content_category_id' => $service->content_category_id,
            'icon_class' => $service->icon_class,
            'price_note' => $service->price_note,
            'is_featured' => $service->is_featured,
            'cover_alt' => $service->cover_alt,
            'cover_image_url' => $service->cover_image_url,
            'meta_title' => $service->meta_title,
            'meta_description' => $service->meta_description,
            'og_title' => $service->og_title,
            'og_description' => $service->og_description,
            'canonical_url' => $service->canonical_url,
            'robots_directive' => $service->robots_directive ?: 'index,follow',
            'detail_config' => $detailConfig,
            'faq_items' => FaqContent::prepareItems($service->faq_items, [FaqContent::blankItem()]),
            'gallery' => $gallery,
            'related_questions' => FaqContent::prepareQuestions($service->related_questions, ['']),
        ];

        if (($this->form['faq_items'] ?? []) === []) {
            $this->form['faq_items'][] = FaqContent::blankItem();
        }

        if (($this->form['related_questions'] ?? []) === []) {
            $this->form['related_questions'][] = '';
        }
    }

    public function removeFaqItem(int $index): void
    {
        $this->removeIndexedValue('form.faq_items', $index);

        if (($this->form['faq_items'] ?? []) === []) {
            $this->form['faq_items'][] = FaqContent::blankItem();
        }
    }

    public function removeFeatureBlock(int $index): void
    {
        $uuid = (string) data_get($this->form, "detail_config.feature_blocks.{$index}.uuid", '');

        $this->removeIndexedValue('form.detail_config.feature_blocks', $index);
        $this->removeIndexedValue('featureBlockUploads', $index);

        if ($uuid !== '') {
            unset($this->selectedFeatureBlockLibraryMediaIds[$uuid]);
        }

        if ((data_get($this->form, 'detail_config.feature_blocks', [])) === []) {
            $this->form['detail_config']['feature_blocks'][] = $this->blankFeatureBlock();
        }
    }

    public function removeFeatureHighlight(int $blockIndex, int $highlightIndex): void
    {
        $highlights = data_get($this->form, "detail_config.feature_blocks.{$blockIndex}.highlights", []);

        if (! is_array($highlights) || ! array_key_exists($highlightIndex, $highlights)) {
            return;
        }

        unset($highlights[$highlightIndex]);
        $highlights = array_values($highlights);

        if ($highlights === []) {
            $highlights[] = '';
        }

        data_set($this->form, "detail_config.feature_blocks.{$blockIndex}.highlights", $highlights);
    }

    public function removeGalleryItem(int $index): void
    {
        $uuid = (string) data_get($this->form, "gallery.{$index}.uuid", '');

        $this->removeIndexedValue('form.gallery', $index);
        $this->removeIndexedValue('galleryUploads', $index);

        if ($uuid !== '') {
            unset($this->selectedGalleryLibraryMediaIds[$uuid]);
        }
    }

    public function removeHeroSlide(int $index): void
    {
        $uuid = (string) data_get($this->form, "detail_config.hero_slides.{$index}.uuid", '');

        $this->removeIndexedValue('form.detail_config.hero_slides', $index);
        $this->removeIndexedValue('heroSlideUploads', $index);

        if ($uuid !== '') {
            unset($this->selectedHeroSlideLibraryMediaIds[$uuid]);
        }

        if ((data_get($this->form, 'detail_config.hero_slides', [])) === []) {
            $this->form['detail_config']['hero_slides'][] = $this->blankHeroSlide();
        }
    }

    public function removePricingRow(int $index): void
    {
        $this->removeIndexedValue('form.detail_config.pricing.rows', $index);

        if ((data_get($this->form, 'detail_config.pricing.rows', [])) === []) {
            $this->form['detail_config']['pricing']['rows'][] = $this->blankPricingRow();
        }
    }

    public function removeProcessCard(int $index): void
    {
        $this->removeIndexedValue('form.detail_config.process.cards', $index);

        if ((data_get($this->form, 'detail_config.process.cards', [])) === []) {
            $this->form['detail_config']['process']['cards'][] = $this->blankProcessCard();
        }
    }

    public function removeRelatedQuestion(int $index): void
    {
        $this->removeIndexedValue('form.related_questions', $index);

        if (($this->form['related_questions'] ?? []) === []) {
            $this->form['related_questions'][] = '';
        }
    }

    public function render()
    {
        $categoryOptions = ContentCategory::query()
            ->forTaxonomy('service')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view($this->resolveView(), [
            'categories' => $categoryOptions,
            'categoryRows' => ContentCategory::query()
                ->forTaxonomy('service')
                ->withCount('services')
                ->when($this->categorySearch !== '', function ($query) {
                    $query->where(function ($nested) {
                        $nested
                            ->where('name', 'like', '%'.$this->categorySearch.'%')
                            ->orWhere('slug', 'like', '%'.$this->categorySearch.'%');
                    });
                })
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(10),
            'editingCategoryModel' => $this->isCategoryEditorRoute() && $this->editingCategoryId
                ? ContentCategory::query()->forTaxonomy('service')->find($this->editingCategoryId)
                : null,
            'editingService' => $this->isServiceEditorRoute() && $this->selectedId
                ? Service::query()->find($this->selectedId)
                : null,
            'selectedLibraryAvatarMedia' => $this->isCategoryEditorRoute() && $this->selectedLibraryAvatarMediaId
                ? Media::query()->whereKey($this->selectedLibraryAvatarMediaId)->where('mime_type', 'like', 'image/%')->first()
                : null,
            'selectedFeatureBlockLibraryMedia' => $this->isServiceEditorRoute()
                ? $this->resolveSelectedMediaPayload($this->selectedFeatureBlockLibraryMediaIds)
                : [],
            'selectedGalleryLibraryMedia' => $this->isServiceEditorRoute()
                ? $this->resolveSelectedMediaPayload($this->selectedGalleryLibraryMediaIds)
                : [],
            'selectedHeroSlideLibraryMedia' => $this->isServiceEditorRoute()
                ? $this->resolveSelectedMediaPayload($this->selectedHeroSlideLibraryMediaIds)
                : [],
            'selectedLibraryCoverMedia' => $this->isServiceEditorRoute() && $this->selectedLibraryCoverMediaId
                ? Media::query()->whereKey($this->selectedLibraryCoverMediaId)->where('mime_type', 'like', 'image/%')->first()
                : null,
            'services' => Service::query()
                ->with('category')
                ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
                ->when($this->categoryFilter !== '', fn ($query) => $query->where('content_category_id', $this->categoryFilter))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->orderByDesc('is_featured')
                ->orderByDesc('updated_at')
                ->paginate(10),
        ]);
    }

    public function saveCategory(): void
    {
        $this->authorizeAdminPermission('admin.services.categories.edit');

        $validated = $this->validate([
            'categoryForm.name' => ['required', 'string', 'max:255'],
            'categoryForm.slug' => ['nullable', 'string', 'max:255'],
            'categoryForm.description' => ['nullable', 'string'],
            'categoryForm.content' => ['nullable', 'string'],
            'categoryForm.avatar_alt' => ['nullable', 'string', 'max:255'],
            'categoryForm.sort_order' => ['nullable', 'integer', 'min:0'],
            'avatarUpload' => ['nullable', 'image', 'max:4096'],
        ]);

        $category = ContentCategory::query()->updateOrCreate(
            ['id' => $this->editingCategoryId],
            [
                'taxonomy' => 'service',
                'name' => $validated['categoryForm']['name'],
                'slug' => Str::slug($validated['categoryForm']['slug'] ?: $validated['categoryForm']['name']),
                'description' => $this->plainEditorContent($validated['categoryForm']['description'] ?? null),
                'content' => $this->richEditorContent($validated['categoryForm']['content'] ?? null),
                'sort_order' => $validated['categoryForm']['sort_order'] ?? 0,
            ],
        );

        $avatarAlt = trim((string) ($validated['categoryForm']['avatar_alt'] ?? '')) ?: $category->name;
        $uploadedNewAvatar = $this->syncSingleImage($category, 'avatarUpload', 'avatar', [
            'alt' => $avatarAlt,
        ]);

        if (! $uploadedNewAvatar) {
            $this->syncSingleImageFromLibrary($category, $this->selectedLibraryAvatarMediaId, 'avatar', [
                'alt' => $avatarAlt,
            ]);
        }

        $this->avatarUpload = null;

        session()->flash('status', 'Đã lưu danh mục dịch vụ.');
        $this->redirectRoute('admin.services.categories.edit', ['category' => $category->getKey()], navigate: true);
    }

    public function saveService(): void
    {
        $this->authorizeAdminPermission('admin.services.edit');

        $validated = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => ['nullable', 'string', 'max:255'],
            'form.excerpt' => ['nullable', 'string'],
            'form.content' => ['nullable', 'string'],
            'form.status' => ['required', 'string', 'max:50'],
            'form.content_category_id' => ['nullable', 'exists:content_categories,id'],
            'form.icon_class' => ['nullable', 'string', 'max:255'],
            'form.price_note' => ['nullable', 'string', 'max:255'],
            'form.is_featured' => ['boolean'],
            'form.cover_alt' => ['nullable', 'string', 'max:255'],
            'form.cover_image_url' => ['nullable', 'url'],
            'form.meta_title' => ['nullable', 'string', 'max:255'],
            'form.meta_description' => ['nullable', 'string', 'max:500'],
            'form.og_title' => ['nullable', 'string', 'max:255'],
            'form.og_description' => ['nullable', 'string', 'max:500'],
            'form.canonical_url' => ['nullable', 'url'],
            'form.robots_directive' => ['nullable', 'string', 'max:255'],
            'form.detail_config' => ['nullable', 'array'],
            'form.detail_config.hero_slides' => ['nullable', 'array'],
            'form.detail_config.hero_slides.*.uuid' => ['required', 'string', 'max:100'],
            'form.detail_config.hero_slides.*.eyebrow' => ['nullable', 'string', 'max:255'],
            'form.detail_config.hero_slides.*.title' => ['nullable', 'string'],
            'form.detail_config.hero_slides.*.description' => ['nullable', 'string', 'max:5000'],
            'form.detail_config.hero_slides.*.primary_label' => ['nullable', 'string', 'max:255'],
            'form.detail_config.hero_slides.*.secondary_label' => ['nullable', 'string', 'max:255'],
            'form.detail_config.hero_slides.*.image_alt' => ['nullable', 'string', 'max:255'],
            'form.detail_config.feature_blocks' => ['nullable', 'array'],
            'form.detail_config.feature_blocks.*.uuid' => ['required', 'string', 'max:100'],
            'form.detail_config.feature_blocks.*.eyebrow' => ['nullable', 'string', 'max:255'],
            'form.detail_config.feature_blocks.*.title' => ['nullable', 'string', 'max:255'],
            'form.detail_config.feature_blocks.*.description' => ['nullable', 'string', 'max:5000'],
            'form.detail_config.feature_blocks.*.image_alt' => ['nullable', 'string', 'max:255'],
            'form.detail_config.feature_blocks.*.highlights' => ['nullable', 'array'],
            'form.detail_config.feature_blocks.*.highlights.*' => ['nullable', 'string', 'max:500'],
            'form.detail_config.process.eyebrow' => ['nullable', 'string', 'max:255'],
            'form.detail_config.process.title' => ['nullable', 'string', 'max:255'],
            'form.detail_config.process.description' => ['nullable', 'string', 'max:5000'],
            'form.detail_config.process.cards' => ['nullable', 'array'],
            'form.detail_config.process.cards.*.icon_class' => ['nullable', 'string', 'max:255'],
            'form.detail_config.process.cards.*.title' => ['nullable', 'string', 'max:255'],
            'form.detail_config.process.cards.*.description' => ['nullable', 'string', 'max:5000'],
            'form.detail_config.pricing.eyebrow' => ['nullable', 'string', 'max:255'],
            'form.detail_config.pricing.title' => ['nullable', 'string', 'max:255'],
            'form.detail_config.pricing.description' => ['nullable', 'string', 'max:5000'],
            'form.detail_config.pricing.columns.label' => ['nullable', 'string', 'max:255'],
            'form.detail_config.pricing.columns.size' => ['nullable', 'string', 'max:255'],
            'form.detail_config.pricing.columns.price' => ['nullable', 'string', 'max:255'],
            'form.detail_config.pricing.columns.note' => ['nullable', 'string', 'max:255'],
            'form.detail_config.pricing.rows' => ['nullable', 'array'],
            'form.detail_config.pricing.rows.*.label' => ['nullable', 'string', 'max:255'],
            'form.detail_config.pricing.rows.*.size' => ['nullable', 'string', 'max:255'],
            'form.detail_config.pricing.rows.*.price' => ['nullable', 'string', 'max:255'],
            'form.detail_config.pricing.rows.*.note' => ['nullable', 'string', 'max:255'],
            'form.detail_config.pricing.footnote' => ['nullable', 'string', 'max:5000'],
            'form.faq_items' => ['nullable', 'array'],
            'form.faq_items.*.question' => ['nullable', 'string', 'max:500'],
            'form.faq_items.*.answer' => ['nullable', 'string', 'max:5000'],
            'form.gallery' => ['nullable', 'array'],
            'form.gallery.*.uuid' => ['required', 'string', 'max:100'],
            'form.gallery.*.type' => ['required', 'in:image,youtube,mp4'],
            'form.gallery.*.title' => ['nullable', 'string', 'max:255'],
            'form.gallery.*.description' => ['nullable', 'string', 'max:1000'],
            'form.gallery.*.image_alt' => ['nullable', 'string', 'max:255'],
            'form.gallery.*.video_url' => ['nullable', 'url', 'max:2048'],
            'form.related_questions' => ['nullable', 'array'],
            'form.related_questions.*' => ['nullable', 'string', 'max:500'],
            'heroSlideUploads.*' => ['nullable', 'image', 'max:4096'],
            'featureBlockUploads.*' => ['nullable', 'image', 'max:4096'],
            'galleryUploads.*' => ['nullable', 'image', 'max:4096'],
        ]);

        $detailConfig = $this->resolvedServiceConfig(
            $validated['form']['detail_config'] ?? [],
            $validated['form']['title'],
            $validated['form']['excerpt'] ?? null,
            $validated['form']['content'] ?? null,
            $validated['form']['price_note'] ?? null,
        );
        $detailConfig['highlights'] = collect(data_get($detailConfig, 'feature_blocks.0.highlights', []))
            ->map(fn ($highlight) => trim((string) $highlight))
            ->filter()
            ->values()
            ->all();
        $gallery = ContentGallery::normalize($validated['form']['gallery'] ?? []);
        $faqItems = FaqContent::normalizeItems($validated['form']['faq_items'] ?? []);
        $relatedQuestions = FaqContent::normalizeQuestions($validated['form']['related_questions'] ?? []);

        $this->validateGalleryVideoItems($gallery);

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $existingService = $this->selectedId ? Service::query()->find($this->selectedId) : null;
        $oldDetailConfig = $existingService?->detail_config ?? [];
        $oldGallery = $existingService?->gallery ?? [];

        $service = Service::query()->updateOrCreate(
            ['id' => $this->selectedId],
            [
                'title' => $validated['form']['title'],
                'slug' => Str::slug($validated['form']['slug'] ?: $validated['form']['title']),
                'excerpt' => $this->plainEditorContent($validated['form']['excerpt'] ?? null),
                'content' => $this->richEditorContent($validated['form']['content'] ?? null),
                'status' => $validated['form']['status'],
                'content_category_id' => $validated['form']['content_category_id'],
                'icon_class' => $validated['form']['icon_class'],
                'price_note' => $validated['form']['price_note'],
                'is_featured' => $validated['form']['is_featured'] ?? false,
                'cover_alt' => $validated['form']['cover_alt'],
                'cover_image_url' => $validated['form']['cover_image_url'],
                'meta_title' => $validated['form']['meta_title'],
                'meta_description' => $this->plainEditorContent($validated['form']['meta_description'] ?? null),
                'og_title' => $validated['form']['og_title'],
                'og_description' => $this->plainEditorContent($validated['form']['og_description'] ?? null),
                'canonical_url' => $validated['form']['canonical_url'],
                'robots_directive' => $validated['form']['robots_directive'] ?: 'index,follow',
                'detail_config' => $detailConfig,
                'faq_items' => $faqItems,
                'gallery' => $gallery,
                'related_questions' => $relatedQuestions,
            ],
        );

        $uploadedNewCover = $this->syncSingleImage($service, 'coverUpload', 'cover', [
            'alt' => $validated['form']['cover_alt'] ?? null,
        ], $this->coverUploadOriginalName);

        if (! $uploadedNewCover) {
            $this->syncSingleImageFromLibrary($service, $this->selectedLibraryCoverMediaId, 'cover', [
                'alt' => $validated['form']['cover_alt'] ?? null,
            ]);
        }

        $this->syncHeroSlideMedia($service, $oldDetailConfig, $detailConfig);
        $this->syncFeatureBlockMedia($service, $oldDetailConfig, $detailConfig);
        $this->syncGalleryMedia($service, $oldGallery, $gallery);

        $this->selectedId = $service->id;
        $this->coverUpload = null;
        $this->coverUploadOriginalName = null;
        $this->featureBlockUploads = [];
        $this->galleryUploads = [];
        $this->heroSlideUploads = [];
        $this->editService($service->id);

        session()->flash('status', 'Đã lưu dịch vụ.');
        $this->redirectRoute('admin.services.edit', ['service' => $service], navigate: true);
    }

    public function selectAvatarLibraryMedia(int $mediaId, ?string $alt = null): void
    {
        $this->authorizeAdminPermission('admin.services.categories.edit');

        $media = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->firstOrFail();

        $this->selectedLibraryAvatarMediaId = (int) $media->getKey();
        $this->avatarUpload = null;
        $this->categoryForm['avatar_alt'] = trim((string) $alt) !== ''
            ? trim((string) $alt)
            : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name);
    }

    public function selectCoverLibraryMedia(int $mediaId, ?string $alt = null): void
    {
        $this->authorizeAdminPermission('admin.services.edit');

        $media = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->firstOrFail();

        $this->selectedLibraryCoverMediaId = (int) $media->getKey();
        $this->coverUpload = null;
        $this->form['cover_alt'] = trim((string) $alt) !== ''
            ? trim((string) $alt)
            : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name);
    }

    public function selectFeatureBlockLibraryMedia(string $uuid, int $mediaId, ?string $alt = null): void
    {
        $media = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->firstOrFail();

        $index = $this->findFeatureBlockIndex($uuid);

        if ($index === null) {
            return;
        }

        $this->selectedFeatureBlockLibraryMediaIds[$uuid] = (int) $media->getKey();
        unset($this->featureBlockUploads[$index]);
        data_set($this->form, "detail_config.feature_blocks.{$index}.image_alt", trim((string) $alt) !== ''
            ? trim((string) $alt)
            : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name));
    }

    public function selectGalleryLibraryMedia(string $uuid, int $mediaId, ?string $alt = null): void
    {
        $media = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->firstOrFail();

        $index = $this->findGalleryIndex($uuid);

        if ($index === null) {
            return;
        }

        $this->selectedGalleryLibraryMediaIds[$uuid] = (int) $media->getKey();
        unset($this->galleryUploads[$index]);
        data_set($this->form, "gallery.{$index}.image_alt", trim((string) $alt) !== ''
            ? trim((string) $alt)
            : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name));
    }

    public function selectHeroSlideLibraryMedia(string $uuid, int $mediaId, ?string $alt = null): void
    {
        $media = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->firstOrFail();

        $index = $this->findHeroSlideIndex($uuid);

        if ($index === null) {
            return;
        }

        $this->selectedHeroSlideLibraryMediaIds[$uuid] = (int) $media->getKey();
        unset($this->heroSlideUploads[$index]);
        data_set($this->form, "detail_config.hero_slides.{$index}.image_alt", trim((string) $alt) !== ''
            ? trim((string) $alt)
            : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name));
    }

    public function updatedCoverUpload(mixed $value): void
    {
        $this->coverUploadOriginalName = $value instanceof \Illuminate\Http\UploadedFile
            ? $value->getClientOriginalName()
            : null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategorySearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function blankFeatureBlock(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'eyebrow' => '',
            'title' => '',
            'description' => '',
            'highlights' => [''],
            'image_alt' => '',
        ];
    }

    protected function blankHeroSlide(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'eyebrow' => '',
            'title' => '',
            'description' => '',
            'primary_label' => '',
            'secondary_label' => '',
            'image_alt' => '',
        ];
    }

    protected function blankPricingRow(): array
    {
        return ['label' => '', 'size' => '', 'price' => '', 'note' => ''];
    }

    protected function blankProcessCard(): array
    {
        return ['icon_class' => '', 'title' => '', 'description' => ''];
    }

    protected function findFeatureBlockIndex(string $uuid): ?int
    {
        foreach (data_get($this->form, 'detail_config.feature_blocks', []) as $index => $item) {
            if ((string) data_get($item, 'uuid') === $uuid) {
                return (int) $index;
            }
        }

        return null;
    }

    protected function findGalleryIndex(string $uuid): ?int
    {
        foreach (data_get($this->form, 'gallery', []) as $index => $item) {
            if ((string) data_get($item, 'uuid') === $uuid) {
                return (int) $index;
            }
        }

        return null;
    }

    protected function findHeroSlideIndex(string $uuid): ?int
    {
        foreach (data_get($this->form, 'detail_config.hero_slides', []) as $index => $item) {
            if ((string) data_get($item, 'uuid') === $uuid) {
                return (int) $index;
            }
        }

        return null;
    }

    protected function isCategoryEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.services.categories.create', 'admin.services.categories.edit'], true);
    }

    protected function isServiceEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.services.create', 'admin.services.edit'], true);
    }

    protected function removeIndexedValue(string $path, int $index): void
    {
        $values = data_get($this, $path, []);

        if (! is_array($values) || ! array_key_exists($index, $values)) {
            return;
        }

        unset($values[$index]);
        data_set($this, $path, array_values($values));
    }

    protected function resetCategoryForm(): void
    {
        $this->editingCategoryId = null;
        $this->avatarUpload = null;
        $this->selectedLibraryAvatarMediaId = null;
        $this->categoryForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'content' => '',
            'avatar_alt' => '',
            'sort_order' => 0,
        ];
    }

    protected function resetServiceForm(): void
    {
        $defaultConfig = $this->resolvedServiceConfig();

        $this->selectedLibraryCoverMediaId = null;
        $this->selectedHeroSlideLibraryMediaIds = [];
        $this->selectedFeatureBlockLibraryMediaIds = [];
        $this->selectedGalleryLibraryMediaIds = [];
        $this->form = [
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'content' => '',
            'status' => 'draft',
            'content_category_id' => null,
            'icon_class' => '',
            'price_note' => '',
            'is_featured' => false,
            'cover_alt' => '',
            'cover_image_url' => '',
            'meta_title' => '',
            'meta_description' => '',
            'og_title' => '',
            'og_description' => '',
            'canonical_url' => '',
            'robots_directive' => 'index,follow',
            'detail_config' => $defaultConfig,
            'faq_items' => [FaqContent::blankItem()],
            'gallery' => [],
            'related_questions' => [''],
        ];
    }

    protected function resolveSelectedLibraryMediaIds(Service $service, array $items, callable $collectionResolver): array
    {
        $selectedIds = [];

        foreach ($items as $item) {
            $uuid = (string) data_get($item, 'uuid', '');

            if ($uuid === '') {
                continue;
            }

            $media = $service->getFirstMedia($collectionResolver($uuid));
            $sourceLibraryMediaId = (int) data_get($media?->custom_properties, 'source_library_media_id', 0);

            if ($sourceLibraryMediaId > 0) {
                $selectedIds[$uuid] = $sourceLibraryMediaId;
            }
        }

        return $selectedIds;
    }

    protected function resolveSelectedMediaPayload(array $selectedIds): array
    {
        $ids = collect($selectedIds)->filter(fn ($id) => (int) $id > 0)->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $mediaById = Media::query()
            ->whereIn('id', $ids->all())
            ->where('mime_type', 'like', 'image/%')
            ->get()
            ->keyBy(fn (Media $media) => (int) $media->getKey());

        $resolved = [];

        foreach ($selectedIds as $uuid => $mediaId) {
            $media = $mediaById->get((int) $mediaId);

            if ($media) {
                $resolved[$uuid] = $media;
            }
        }

        return $resolved;
    }

    protected function resolvedServiceConfig(
        ?array $config = null,
        ?string $serviceTitle = null,
        ?string $excerpt = null,
        ?string $content = null,
        ?string $priceNote = null,
    ): array {
        $default = ServiceDetailContent::defaultServiceConfig($serviceTitle, $excerpt, $content, $priceNote);
        $normalized = ServiceDetailContent::prepareServiceConfig($config, $serviceTitle, $excerpt, $content, $priceNote);

        return [
            'hero_slides' => $normalized['hero_slides'] !== [] ? $normalized['hero_slides'] : $default['hero_slides'],
            'feature_blocks' => $normalized['feature_blocks'] !== [] ? $normalized['feature_blocks'] : $default['feature_blocks'],
            'process' => [
                'eyebrow' => $normalized['process']['eyebrow'] !== '' ? $normalized['process']['eyebrow'] : $default['process']['eyebrow'],
                'title' => $normalized['process']['title'] !== '' ? $normalized['process']['title'] : $default['process']['title'],
                'description' => $normalized['process']['description'] !== '' ? $normalized['process']['description'] : $default['process']['description'],
                'cards' => $normalized['process']['cards'] !== [] ? $normalized['process']['cards'] : $default['process']['cards'],
            ],
            'pricing' => [
                'eyebrow' => $normalized['pricing']['eyebrow'] !== '' ? $normalized['pricing']['eyebrow'] : $default['pricing']['eyebrow'],
                'title' => $normalized['pricing']['title'] !== '' ? $normalized['pricing']['title'] : $default['pricing']['title'],
                'description' => $normalized['pricing']['description'] !== '' ? $normalized['pricing']['description'] : $default['pricing']['description'],
                'columns' => [
                    'label' => $normalized['pricing']['columns']['label'] !== '' ? $normalized['pricing']['columns']['label'] : $default['pricing']['columns']['label'],
                    'size' => $normalized['pricing']['columns']['size'] !== '' ? $normalized['pricing']['columns']['size'] : $default['pricing']['columns']['size'],
                    'price' => $normalized['pricing']['columns']['price'] !== '' ? $normalized['pricing']['columns']['price'] : $default['pricing']['columns']['price'],
                    'note' => $normalized['pricing']['columns']['note'] !== '' ? $normalized['pricing']['columns']['note'] : $default['pricing']['columns']['note'],
                ],
                'rows' => $normalized['pricing']['rows'] !== [] ? $normalized['pricing']['rows'] : $default['pricing']['rows'],
                'footnote' => $normalized['pricing']['footnote'] !== '' ? $normalized['pricing']['footnote'] : $default['pricing']['footnote'],
            ],
        ];
    }

    protected function resolveView(): string
    {
        return match (true) {
            $this->currentRouteName === 'admin.services' => 'livewire.admin.cms.services.index',
            $this->isServiceEditorRoute() => 'livewire.admin.cms.services.editor',
            $this->currentRouteName === 'admin.services.categories' => 'livewire.admin.cms.services.categories-index',
            $this->isCategoryEditorRoute() => 'livewire.admin.cms.services.categories-editor',
            default => 'livewire.admin.cms.services.index',
        };
    }

    protected function syncFeatureBlockMedia(Service $service, array $oldConfig, array $newConfig): void
    {
        $oldUuids = collect(data_get($oldConfig, 'feature_blocks', []))->pluck('uuid')->filter()->values()->all();
        $newUuids = collect(data_get($newConfig, 'feature_blocks', []))->pluck('uuid')->filter()->values()->all();

        foreach (array_diff($oldUuids, $newUuids) as $uuid) {
            $service->clearMediaCollection(ServiceDetailContent::featureBlockCollection($uuid));
        }

        foreach (data_get($newConfig, 'feature_blocks', []) as $index => $block) {
            $collection = ServiceDetailContent::featureBlockCollection($block['uuid']);
            $uploadedImage = $this->featureBlockUploads[$index] ?? null;

            if ($uploadedImage) {
                $this->syncUploadedImage($service, $uploadedImage, $collection, [
                    'alt' => $block['image_alt'] ?: $block['title'] ?: $service->title,
                ]);

                continue;
            }

            $this->syncSingleImageFromLibrary($service, $this->selectedFeatureBlockLibraryMediaIds[$block['uuid']] ?? null, $collection, [
                'alt' => $block['image_alt'] ?: $block['title'] ?: $service->title,
            ]);
        }
    }

    protected function syncGalleryMedia(Service $service, array $oldGallery, array $newGallery): void
    {
        $oldUuids = collect($oldGallery)->pluck('uuid')->filter()->values()->all();
        $newUuids = collect($newGallery)->pluck('uuid')->filter()->values()->all();

        foreach (array_diff($oldUuids, $newUuids) as $uuid) {
            $service->clearMediaCollection(ContentGallery::serviceCollection($uuid));
        }

        foreach ($newGallery as $index => $item) {
            $collection = ContentGallery::serviceCollection($item['uuid']);

            if (($item['type'] ?? ContentGallery::TYPE_IMAGE) !== ContentGallery::TYPE_IMAGE) {
                $service->clearMediaCollection($collection);

                continue;
            }

            $uploadedImage = $this->galleryUploads[$index] ?? null;

            if ($uploadedImage) {
                $this->syncUploadedImage($service, $uploadedImage, $collection, [
                    'alt' => $item['image_alt'] ?: $item['title'] ?: $service->title,
                ]);

                continue;
            }

            $this->syncSingleImageFromLibrary($service, $this->selectedGalleryLibraryMediaIds[$item['uuid']] ?? null, $collection, [
                'alt' => $item['image_alt'] ?: $item['title'] ?: $service->title,
            ]);
        }
    }

    protected function syncHeroSlideMedia(Service $service, array $oldConfig, array $newConfig): void
    {
        $oldUuids = collect(data_get($oldConfig, 'hero_slides', []))->pluck('uuid')->filter()->values()->all();
        $newUuids = collect(data_get($newConfig, 'hero_slides', []))->pluck('uuid')->filter()->values()->all();

        foreach (array_diff($oldUuids, $newUuids) as $uuid) {
            $service->clearMediaCollection(ServiceDetailContent::heroSlideCollection($uuid));
        }

        foreach (data_get($newConfig, 'hero_slides', []) as $index => $slide) {
            $collection = ServiceDetailContent::heroSlideCollection($slide['uuid']);
            $uploadedImage = $this->heroSlideUploads[$index] ?? null;

            if ($uploadedImage) {
                $this->syncUploadedImage($service, $uploadedImage, $collection, [
                    'alt' => $slide['image_alt'] ?: strip_tags((string) ($slide['title'] ?? '')) ?: $service->title,
                ]);

                continue;
            }

            $this->syncSingleImageFromLibrary($service, $this->selectedHeroSlideLibraryMediaIds[$slide['uuid']] ?? null, $collection, [
                'alt' => $slide['image_alt'] ?: strip_tags((string) ($slide['title'] ?? '')) ?: $service->title,
            ]);
        }
    }

    protected function validateGalleryVideoItems(array $gallery): void
    {
        foreach ($gallery as $index => $item) {
            if (($item['type'] ?? ContentGallery::TYPE_IMAGE) === ContentGallery::TYPE_IMAGE) {
                continue;
            }

            if (blank($item['video_url'] ?? null)) {
                $this->addError("form.gallery.{$index}.video_url", 'Vui lòng nhập URL video hợp lệ.');

                continue;
            }

            if (($item['type'] ?? ContentGallery::TYPE_IMAGE) === ContentGallery::TYPE_YOUTUBE && ! ContentGallery::youtubeId($item['video_url'])) {
                $this->addError("form.gallery.{$index}.video_url", 'URL YouTube chưa đúng định dạng hỗ trợ.');
            }
        }
    }
}
