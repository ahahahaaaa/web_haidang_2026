<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Cms\Concerns\HandlesMediaUploads;
use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use App\Support\ContentGallery;
use App\Support\FaqContent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\Region;
use Src\Domains\Cms\Models\SiteSetting;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TourDeparture;

#[Layout('layouts.app')]
#[Title('Quản lý tour')]
class ToursManager extends Component
{
    use AuthorizesAdminPermissions;
    use HandlesMediaUploads;
    use InteractsWithEditorContent;
    use WithFileUploads;
    use WithPagination;

    public mixed $avatarUpload = null;

    public string $categoryFilter = '';

    public array $categoryForm = [];

    public ?string $currentRouteName = null;

    public string $destinationFilter = '';

    public array $destinationForm = [];

    public ?int $editingCategoryId = null;

    public ?int $editingDestinationId = null;

    public ?int $editingRegionId = null;

    public array $form = [];

    public array $galleryUploads = [];

    public string $managerFilter = '';

    public array $regionForm = [];

    public string $scopeFilter = '';

    public string $search = '';

    public array $selectedGalleryLibraryMediaIds = [];

    public ?int $selectedId = null;

    public ?int $selectedLibraryAvatarMediaId = null;

    public string $statusFilter = '';

    public string $taxonomySearch = '';

    public string $taxonomyStatusFilter = '';

    public string $transportFilter = '';

    public function mount(
        ?Tour $tour = null,
        ?TourCategory $category = null,
        ?Destination $destination = null,
        ?Region $region = null,
    ): void {
        $this->currentRouteName = request()->route()?->getName();
        $this->resetCategoryForm();
        $this->resetDestinationForm();
        $this->resetRegionForm();
        $this->resetTourForm();
        $this->initializeRouteState(
            $tour?->getKey(),
            $category?->getKey(),
            $destination?->getKey(),
            $region?->getKey(),
        );
    }

    public function render()
    {
        $taxonomyType = $this->currentTaxonomyType();

        return view($this->resolveView(), [
            'categories' => TourCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'destinations' => Destination::query()->with('region')->orderBy('sort_order')->orderBy('name')->get(),
            'editingTaxonomyModel' => $taxonomyType ? $this->resolveEditingTaxonomyModel($taxonomyType) : null,
            'editingTour' => $this->isTourEditorRoute() && $this->selectedId
                ? Tour::query()->with([
                    'categories',
                    'destinations',
                    'departures',
                    'destination',
                    'manager',
                    'primaryCategory',
                    'region',
                    'regions',
                ])->when($this->currentUserIsSale(), fn (Builder $query) => $query->where('managed_by_user_id', auth()->id()))->find($this->selectedId)
                : null,
            'regions' => Region::query()->orderBy('sort_order')->orderBy('name')->get(),
            'saleUsers' => User::query()->whereHas('roles', fn (Builder $query) => $query->where('name', 'sale'))->orderBy('name')->get(),
            'scopeOptions' => TourScope::cases(),
            'selectedGalleryLibraryMedia' => $this->resolveSelectedMediaPayload($this->selectedGalleryLibraryMediaIds),
            'selectedLibraryAvatarMedia' => $this->selectedLibraryAvatarMediaId
                ? Media::query()->whereKey($this->selectedLibraryAvatarMediaId)->where('mime_type', 'like', 'image/%')->first()
                : null,
            'taxonomyConfig' => $taxonomyType ? $this->taxonomyConfig($taxonomyType) : null,
            'taxonomyForm' => $taxonomyType ? $this->resolveTaxonomyForm($taxonomyType) : [],
            'taxonomyItems' => $taxonomyType ? $this->resolveTaxonomyItems($taxonomyType) : collect(),
            'tours' => $this->scopedTourQuery()
                ->with(['primaryCategory', 'destination', 'region', 'manager'])
                ->withCount('departures')
                ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
                ->when($this->scopeFilter !== '', fn ($query) => $query->where('scope', $this->scopeFilter))
                ->when($this->statusFilter !== '', fn ($query) => $query->where('status', $this->statusFilter))
                ->when($this->categoryFilter !== '', fn ($query) => $query->whereHas('categories', fn ($taxonomyQuery) => $taxonomyQuery->whereKey($this->categoryFilter)))
                ->when($this->destinationFilter !== '', fn ($query) => $query->whereHas('destinations', fn ($taxonomyQuery) => $taxonomyQuery->whereKey($this->destinationFilter)))
                ->when($this->managerFilter === '0', fn ($query) => $query->whereNull('managed_by_user_id'))
                ->when($this->managerFilter !== '' && $this->managerFilter !== '0', fn ($query) => $query->where('managed_by_user_id', $this->managerFilter))
                ->orderByDesc('updated_at')
                ->paginate(10),
        ]);
    }

    public function addDeparture(): void
    {
        $this->form['departures'][] = $this->blankDeparture();
    }

    public function addFaqItem(): void
    {
        $path = $this->activeFaqItemsPath();

        if ($path === null) {
            return;
        }

        $items = data_get($this, $path, []);
        $items[] = FaqContent::blankItem();
        data_set($this, $path, $items);
    }

    public function addItineraryItem(): void
    {
        $this->form['itinerary_items'][] = $this->blankItineraryItem();
    }

    public function addPricingItem(): void
    {
        $this->form['pricing_items'][] = $this->blankPricingItem();
    }

    public function addGalleryItem(string $type = ContentGallery::TYPE_IMAGE): void
    {
        $gallery = data_get($this, $this->activeGalleryPath(), []);
        $gallery[] = ContentGallery::defaultItem($type);
        data_set($this, $this->activeGalleryPath(), $gallery);
    }

    public function addRelatedQuestion(): void
    {
        $this->form['related_questions'][] = '';
    }

    public function addTourTermItem(): void
    {
        $this->form['tour_terms_items'][] = FaqContent::blankItem();
    }

    public function clearAvatarLibraryMediaSelection(): void
    {
        $this->selectedLibraryAvatarMediaId = null;
    }

    public function clearGalleryLibraryMediaSelection(string $uuid): void
    {
        unset($this->selectedGalleryLibraryMediaIds[$uuid]);
    }

    public function deleteCategory(int $id): void
    {
        $this->authorizeAdminPermission('admin.tours.categories.edit');

        $this->deleteTaxonomy('category', $id);
    }

    public function deleteDestination(int $id): void
    {
        $this->authorizeAdminPermission('admin.tours.destinations.edit');

        $this->deleteTaxonomy('destination', $id);
    }

    public function deleteRegion(int $id): void
    {
        $this->authorizeAdminPermission('admin.tours.regions.edit');

        $this->deleteTaxonomy('region', $id);
    }

    public function deleteTour(int $id): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        $this->scopedTourQuery()->findOrFail($id)->delete();

        if ($this->selectedId === $id) {
            $this->selectedId = null;
            $this->resetTourForm();
        }

        session()->flash('status', 'Đã xóa tour.');

        if ($this->isTourEditorRoute()) {
            $this->redirectRoute('admin.tours', navigate: true);
        }
    }

    public function editCategory(int $id): void
    {
        $this->fillTaxonomyForm('category', TourCategory::query()->findOrFail($id));
    }

    public function editDestination(int $id): void
    {
        $this->fillTaxonomyForm('destination', Destination::query()->findOrFail($id));
    }

    public function editRegion(int $id): void
    {
        $this->fillTaxonomyForm('region', Region::query()->findOrFail($id));
    }

    public function editTour(int $id): void
    {
        $tour = Tour::query()
            ->with([
                'categories',
                'departures',
                'destinations',
                'destination',
                'manager',
                'primaryCategory',
                'region',
                'regions',
            ])
            ->when($this->currentUserIsSale(), fn (Builder $query) => $query->where('managed_by_user_id', auth()->id()))
            ->findOrFail($id);

        $coverMedia = $tour->getFirstMedia('cover');

        $this->selectedId = $tour->id;
        $this->avatarUpload = null;
        $this->galleryUploads = [];
        $this->selectedLibraryAvatarMediaId = $coverMedia
            ? (int) data_get($coverMedia->custom_properties, 'source_library_media_id')
            : null;
        $this->selectedGalleryLibraryMediaIds = $this->resolveSelectedLibraryMediaIds(
            $tour,
            $tour->gallery ?? [],
            fn (string $uuid) => ContentGallery::tourCollection($uuid),
        );
        $this->form = $this->tourFormFromModel($tour);
    }

    public function removeDeparture(int $index): void
    {
        $this->removeIndexedValue('form.departures', $index);

        if (($this->form['departures'] ?? []) === []) {
            $this->form['departures'][] = $this->blankDeparture();
        }
    }

    public function removeFaqItem(int $index): void
    {
        $path = $this->activeFaqItemsPath();

        if ($path === null) {
            return;
        }

        $this->removeIndexedValue($path, $index);

        if (data_get($this, $path, []) === []) {
            data_set($this, $path, [FaqContent::blankItem()]);
        }
    }

    public function removeItineraryItem(int $index): void
    {
        $this->removeIndexedValue('form.itinerary_items', $index);

        if (($this->form['itinerary_items'] ?? []) === []) {
            $this->form['itinerary_items'][] = $this->blankItineraryItem();
        }
    }

    public function removePricingItem(int $index): void
    {
        $this->removeIndexedValue('form.pricing_items', $index);

        if (($this->form['pricing_items'] ?? []) === []) {
            $this->form['pricing_items'][] = $this->blankPricingItem();
        }
    }

    public function removeGalleryItem(int $index): void
    {
        $formPath = $this->activeGalleryPath();
        $gallery = data_get($this, $formPath, []);
        $uuid = (string) data_get($gallery, $index.'.uuid', '');

        $this->removeIndexedValue($formPath, $index);
        $this->removeIndexedValue('galleryUploads', $index);

        if ($uuid !== '') {
            unset($this->selectedGalleryLibraryMediaIds[$uuid]);
        }
    }

    public function removeRelatedQuestion(int $index): void
    {
        $this->removeIndexedValue('form.related_questions', $index);

        if (($this->form['related_questions'] ?? []) === []) {
            $this->form['related_questions'][] = '';
        }
    }

    public function removeTourTermItem(int $index): void
    {
        $this->removeIndexedValue('form.tour_terms_items', $index);

        if (($this->form['tour_terms_items'] ?? []) === []) {
            $this->form['tour_terms_items'][] = FaqContent::blankItem();
        }
    }

    public function saveCategory(): void
    {
        $this->authorizeAdminPermission('admin.tours.categories.edit');

        $this->saveTaxonomy('category');
    }

    public function saveDestination(): void
    {
        $this->authorizeAdminPermission('admin.tours.destinations.edit');

        $this->saveTaxonomy('destination');
    }

    public function saveRegion(): void
    {
        $this->authorizeAdminPermission('admin.tours.regions.edit');

        $this->saveTaxonomy('region');
    }

    public function saveTour(): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        $validated = $this->validate($this->tourValidationRules());
        $gallery = ContentGallery::normalize($validated['form']['gallery'] ?? []);

        $this->validateGalleryVideoItems($gallery, 'form.gallery');

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->persistTour($validated['form'], $gallery);
    }

    public function selectAvatarLibraryMedia(int $mediaId, ?string $alt = null): void
    {
        $media = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->firstOrFail();

        $this->selectedLibraryAvatarMediaId = (int) $media->getKey();
        $this->avatarUpload = null;

        if ($this->isTourEditorRoute()) {
            $this->form['cover_alt'] = trim((string) $alt) !== ''
                ? trim((string) $alt)
                : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name);

            return;
        }

        $form = $this->activeTaxonomyForm();
        $form['cover_alt'] = trim((string) $alt) !== ''
            ? trim((string) $alt)
            : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name);
        $this->setActiveTaxonomyForm($form);
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
        data_set(
            $this,
            $this->activeGalleryPath().'.'.$index.'.image_alt',
            trim((string) $alt) !== ''
                ? trim((string) $alt)
                : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name),
        );
    }

    public function updatingScopeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDestinationFilter(): void
    {
        $this->resetPage();
    }

    public function updatingManagerFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTransportFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTaxonomySearch(): void
    {
        $this->resetPage();
    }

    public function updatingTaxonomyStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function activeGalleryPath(): string
    {
        if ($this->isTourEditorRoute()) {
            return 'form.gallery';
        }

        return match ($this->currentTaxonomyType()) {
            'category' => 'categoryForm.gallery',
            'destination' => 'destinationForm.gallery',
            'region' => 'regionForm.gallery',
            default => 'form.gallery',
        };
    }

    protected function activeFaqItemsPath(): ?string
    {
        if ($this->isTourEditorRoute()) {
            return 'form.faq_items';
        }

        return match ($this->currentTaxonomyType()) {
            'category' => 'categoryForm.faq_items',
            'destination' => 'destinationForm.faq_items',
            default => null,
        };
    }

    protected function activeTaxonomyForm(): array
    {
        return $this->currentTaxonomyType()
            ? $this->resolveTaxonomyForm($this->currentTaxonomyType())
            : [];
    }

    protected function blankDeparture(): array
    {
        return [
            'id' => null,
            'departure_date' => '',
            'return_date' => '',
            'departure_location' => '',
            'transport_label' => '',
            'standard_label' => '',
            'base_price' => '',
            'sale_price' => '',
            'available_slots' => '',
            'pricing_note' => '',
            'status' => 'scheduled',
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }

    protected function blankItineraryItem(): array
    {
        return [
            'title' => '',
            'content' => '',
        ];
    }

    protected function blankPricingItem(): array
    {
        return [
            'label' => '',
            'price' => '',
        ];
    }

    protected function currentTaxonomyType(): ?string
    {
        return match (true) {
            str_starts_with((string) $this->currentRouteName, 'admin.tours.categories') => 'category',
            str_starts_with((string) $this->currentRouteName, 'admin.tours.destinations') => 'destination',
            str_starts_with((string) $this->currentRouteName, 'admin.tours.regions') => 'region',
            default => null,
        };
    }

    protected function currentUserIsSale(): bool
    {
        return (bool) auth()->user()?->hasRole('sale');
    }

    protected function defaultTourContactPhone(?int $managerId = null): string
    {
        $userPhone = $managerId
            ? trim((string) User::query()->whereKey($managerId)->value('phone'))
            : trim((string) auth()->user()?->phone);

        if ($userPhone !== '') {
            return $userPhone;
        }

        $settings = SiteSetting::query()->first();

        return trim((string) ($settings?->hotline ?: $settings?->phone));
    }

    protected function scopedTourQuery(): Builder
    {
        return Tour::query()
            ->when($this->currentUserIsSale(), fn (Builder $query) => $query->where('managed_by_user_id', auth()->id()));
    }

    protected function deleteTaxonomy(string $type, int $id): void
    {
        $model = $this->resolveTaxonomyModelClass($type)::query()->findOrFail($id);

        if ($this->taxonomyHasChildren($type, $model)) {
            $this->addError('taxonomy', $this->taxonomyConfig($type)['delete_block_message']);

            return;
        }

        $model->delete();
        session()->flash('status', 'Đã xóa '.$this->taxonomyConfig($type)['singular_label'].'.');

        if ($this->currentTaxonomyType() === $type) {
            $this->redirectRoute($this->taxonomyConfig($type)['index_route'], navigate: true);
        }
    }

    protected function fillTaxonomyForm(string $type, Model $model): void
    {
        $avatarMedia = $model instanceof HasMedia ? $model->getFirstMedia('avatar') : null;

        $this->avatarUpload = null;
        $this->galleryUploads = [];
        $this->selectedLibraryAvatarMediaId = $avatarMedia
            ? (int) data_get($avatarMedia->custom_properties, 'source_library_media_id')
            : null;
        $this->selectedGalleryLibraryMediaIds = $model instanceof HasMedia
            ? $this->resolveSelectedLibraryMediaIds(
                $model,
                (array) ($model->gallery ?? []),
                fn (string $uuid) => ContentGallery::taxonomyCollection($type, $uuid),
            )
            : [];

        $form = $this->defaultTaxonomyForm();
        $form['name'] = (string) data_get($model, 'name', '');
        $form['slug'] = (string) data_get($model, 'slug', '');
        $form['scope'] = (string) data_get($model, 'scope', '');
        $form['region_id'] = data_get($model, 'region_id');
        $form['excerpt'] = (string) data_get($model, 'excerpt', '');
        $form['content'] = (string) data_get($model, 'content', '');
        $form['status'] = (string) data_get($model, 'status', 'draft');
        $form['is_featured'] = (bool) data_get($model, 'is_featured', false);
        $form['sort_order'] = (int) data_get($model, 'sort_order', 0);
        $form['published_at'] = optional(data_get($model, 'published_at'))->format('Y-m-d\TH:i');
        $form['cover_alt'] = (string) data_get($model, 'cover_alt', '');
        $form['cover_image_url'] = (string) data_get($model, 'cover_image_url', '');
        $form['meta_title'] = (string) data_get($model, 'meta_title', '');
        $form['meta_description'] = (string) data_get($model, 'meta_description', '');
        $form['og_title'] = (string) data_get($model, 'og_title', '');
        $form['og_description'] = (string) data_get($model, 'og_description', '');
        $form['canonical_url'] = (string) data_get($model, 'canonical_url', '');
        $form['robots_directive'] = (string) data_get($model, 'robots_directive', 'index,follow');
        $form['rating_average'] = data_get($model, 'rating_average');
        $form['rating_count'] = data_get($model, 'rating_count');
        $form['gallery'] = ContentGallery::prepare(data_get($model, 'gallery'));
        $form['faq_items'] = FaqContent::prepareItems(data_get($model, 'faq_items'), [FaqContent::blankItem()]);

        $this->setEditingTaxonomyId($type, (int) $model->getKey());
        $this->setActiveTaxonomyForm($form);
    }

    protected function findGalleryIndex(string $uuid): ?int
    {
        foreach (data_get($this, $this->activeGalleryPath(), []) as $index => $item) {
            if ((string) data_get($item, 'uuid') === $uuid) {
                return (int) $index;
            }
        }

        return null;
    }

    protected function initializeRouteState(
        ?int $tourId = null,
        ?int $categoryId = null,
        ?int $destinationId = null,
        ?int $regionId = null,
    ): void {
        if ($this->isTourEditorRoute()) {
            $tourId ??= $this->resolveRouteKey('tour');

            if ($tourId) {
                $this->editTour($tourId);
            }

            return;
        }

        if ($this->isCategoryEditorRoute()) {
            $categoryId ??= $this->resolveRouteKey('category');

            if ($categoryId) {
                $this->editCategory($categoryId);
            }

            return;
        }

        if ($this->isDestinationEditorRoute()) {
            $destinationId ??= $this->resolveRouteKey('destination');

            if ($destinationId) {
                $this->editDestination($destinationId);
            }

            return;
        }

        if ($this->isRegionEditorRoute()) {
            $regionId ??= $this->resolveRouteKey('region');

            if ($regionId) {
                $this->editRegion($regionId);
            }
        }
    }

    protected function isCategoryEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.tours.categories.create', 'admin.tours.categories.edit'], true);
    }

    protected function isDestinationEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.tours.destinations.create', 'admin.tours.destinations.edit'], true);
    }

    protected function isRegionEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.tours.regions.create', 'admin.tours.regions.edit'], true);
    }

    protected function isTourEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.tours.create', 'admin.tours.edit'], true);
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

    protected function resolveEditingTaxonomyModel(string $type): ?Model
    {
        $editingId = $this->resolveEditingTaxonomyId($type);

        if (! $editingId) {
            return null;
        }

        return $this->resolveTaxonomyModelClass($type)::query()->find($editingId);
    }

    protected function resolveRouteKey(string $parameter): ?int
    {
        $value = request()->route($parameter);

        if (is_object($value) && method_exists($value, 'getKey')) {
            $value = $value->getKey();
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    protected function resolveSelectedLibraryMediaIds(HasMedia $model, array $items, callable $collectionResolver): array
    {
        $selectedIds = [];

        foreach ($items as $item) {
            $uuid = (string) data_get($item, 'uuid', '');

            if ($uuid === '') {
                continue;
            }

            $media = $model->getFirstMedia($collectionResolver($uuid));
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

    protected function resolveTaxonomyForm(string $type): array
    {
        return match ($type) {
            'category' => $this->categoryForm,
            'destination' => $this->destinationForm,
            'region' => $this->regionForm,
            default => [],
        };
    }

    protected function resolveTaxonomyItems(string $type): Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = match ($type) {
            'category' => TourCategory::query()->withCount('tours'),
            'destination' => Destination::query()->with('region')->withCount('tours'),
            'region' => Region::query()->withCount(['destinations', 'tours']),
            default => null,
        };

        if (! $query) {
            return collect();
        }

        return $query
            ->when($this->taxonomySearch !== '', function ($builder) {
                $builder->where(function ($nested) {
                    $nested
                        ->where('name', 'like', '%'.$this->taxonomySearch.'%')
                        ->orWhere('slug', 'like', '%'.$this->taxonomySearch.'%');
                });
            })
            ->when($this->taxonomyStatusFilter !== '', fn ($builder) => $builder->where('status', $this->taxonomyStatusFilter))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(10);
    }

    protected function resolveTaxonomyModelClass(string $type): string
    {
        return match ($type) {
            'category' => TourCategory::class,
            'destination' => Destination::class,
            'region' => Region::class,
            default => TourCategory::class,
        };
    }

    protected function resetCategoryForm(): void
    {
        $this->editingCategoryId = null;
        $this->categoryForm = $this->defaultTaxonomyForm();
    }

    protected function resetDestinationForm(): void
    {
        $this->editingDestinationId = null;
        $this->destinationForm = $this->defaultTaxonomyForm();
    }

    protected function resetRegionForm(): void
    {
        $this->editingRegionId = null;
        $this->regionForm = $this->defaultTaxonomyForm();
    }

    protected function resetTourForm(): void
    {
        $this->selectedLibraryAvatarMediaId = null;
        $this->selectedGalleryLibraryMediaIds = [];
        $this->avatarUpload = null;
        $this->galleryUploads = [];
        $this->form = [
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'content' => '',
            'status' => 'draft',
            'scope' => TourScope::Domestic->value,
            'managed_by_user_id' => $this->currentUserIsSale() ? auth()->id() : null,
            'tour_category_id' => null,
            'tour_category_ids' => [],
            'destination_id' => null,
            'destination_ids' => [],
            'region_id' => null,
            'region_ids' => [],
            'transport' => '',
            'departure_location' => '',
            'contact_phone' => $this->defaultTourContactPhone(),
            'duration_days' => '',
            'duration_nights' => '',
            'standard_label' => '',
            'base_price' => '',
            'sale_price' => '',
            'rating_average' => '',
            'rating_count' => '',
            'cta_mode' => 'book',
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => '',
            'cover_alt' => '',
            'cover_image_url' => '',
            'meta_title' => '',
            'meta_description' => '',
            'og_title' => '',
            'og_description' => '',
            'canonical_url' => '',
            'robots_directive' => 'index,follow',
            'itinerary_items' => [$this->blankItineraryItem()],
            'pricing_items' => [$this->blankPricingItem()],
            'departure_schedules_text' => '',
            'inclusions_text' => '',
            'tour_terms_items' => [FaqContent::blankItem()],
            'faq_items' => [FaqContent::blankItem()],
            'gallery' => [],
            'related_questions' => [''],
            'departures' => [$this->blankDeparture()],
        ];
    }

    protected function resolveEditingTaxonomyId(string $type): ?int
    {
        return match ($type) {
            'category' => $this->editingCategoryId,
            'destination' => $this->editingDestinationId,
            'region' => $this->editingRegionId,
            default => null,
        };
    }

    protected function resolveView(): string
    {
        return match (true) {
            $this->currentRouteName === 'admin.tours' => 'livewire.admin.cms.tours.index',
            $this->isTourEditorRoute() => 'livewire.admin.cms.tours.editor',
            in_array($this->currentRouteName, ['admin.tours.categories', 'admin.tours.destinations', 'admin.tours.regions'], true) => 'livewire.admin.cms.tours.taxonomies-index',
            $this->isCategoryEditorRoute() || $this->isDestinationEditorRoute() || $this->isRegionEditorRoute() => 'livewire.admin.cms.tours.taxonomies-editor',
            default => 'livewire.admin.cms.tours.index',
        };
    }

    protected function setActiveTaxonomyForm(array $form): void
    {
        match ($this->currentTaxonomyType()) {
            'category' => $this->categoryForm = $form,
            'destination' => $this->destinationForm = $form,
            'region' => $this->regionForm = $form,
            default => null,
        };
    }

    protected function setEditingTaxonomyId(string $type, ?int $value): void
    {
        match ($type) {
            'category' => $this->editingCategoryId = $value,
            'destination' => $this->editingDestinationId = $value,
            'region' => $this->editingRegionId = $value,
            default => null,
        };
    }

    protected function taxonomyConfig(string $type): array
    {
        return match ($type) {
            'category' => [
                'type' => 'category',
                'plural_label' => 'Danh mục tour',
                'singular_label' => 'danh mục tour',
                'index_route' => 'admin.tours.categories',
                'create_route' => 'admin.tours.categories.create',
                'edit_route' => 'admin.tours.categories.edit',
                'reviews_index_route' => 'admin.tours.categories.reviews.index',
                'save_action' => 'saveCategory',
                'delete_action' => 'deleteCategory',
                'delete_block_message' => 'Không thể xóa danh mục tour đang có tour.',
            ],
            'destination' => [
                'type' => 'destination',
                'plural_label' => 'Điểm đến',
                'singular_label' => 'điểm đến',
                'index_route' => 'admin.tours.destinations',
                'create_route' => 'admin.tours.destinations.create',
                'edit_route' => 'admin.tours.destinations.edit',
                'reviews_index_route' => 'admin.tours.destinations.reviews.index',
                'save_action' => 'saveDestination',
                'delete_action' => 'deleteDestination',
                'delete_block_message' => 'Không thể xóa điểm đến đang có tour.',
            ],
            'region' => [
                'type' => 'region',
                'plural_label' => 'Vùng miền',
                'singular_label' => 'vùng miền',
                'index_route' => 'admin.tours.regions',
                'create_route' => 'admin.tours.regions.create',
                'edit_route' => 'admin.tours.regions.edit',
                'reviews_index_route' => null,
                'save_action' => 'saveRegion',
                'delete_action' => 'deleteRegion',
                'delete_block_message' => 'Không thể xóa vùng miền đang có điểm đến hoặc tour.',
            ],
            default => [],
        };
    }

    protected function taxonomyHasChildren(string $type, Model $model): bool
    {
        return match ($type) {
            'category' => (method_exists($model, 'primaryTours') && $model->primaryTours()->exists())
                || (method_exists($model, 'tours') && $model->tours()->exists()),
            'destination' => (method_exists($model, 'primaryTours') && $model->primaryTours()->exists())
                || (method_exists($model, 'tours') && $model->tours()->exists()),
            'region' => (method_exists($model, 'destinations') && $model->destinations()->exists())
                || (method_exists($model, 'primaryTours') && $model->primaryTours()->exists())
                || (method_exists($model, 'tours') && $model->tours()->exists()),
            default => false,
        };
    }

    protected function tourFormFromModel(Tour $tour): array
    {
        $departures = $tour->departures
            ->map(fn (TourDeparture $departure) => [
                'id' => $departure->id,
                'departure_date' => optional($departure->departure_date)->format('Y-m-d'),
                'return_date' => optional($departure->return_date)->format('Y-m-d'),
                'departure_location' => (string) $departure->departure_location,
                'transport_label' => (string) $departure->transport_label,
                'standard_label' => (string) $departure->standard_label,
                'base_price' => $departure->base_price,
                'sale_price' => $departure->sale_price,
                'available_slots' => $departure->available_slots,
                'pricing_note' => (string) $departure->pricing_note,
                'status' => (string) $departure->status,
                'is_featured' => (bool) $departure->is_featured,
                'sort_order' => (int) $departure->sort_order,
            ])
            ->values()
            ->all();

        return [
            'title' => $tour->title,
            'slug' => $tour->slug,
            'excerpt' => $tour->excerpt,
            'content' => $tour->content,
            'status' => $tour->status,
            'scope' => $tour->scope?->value ?: TourScope::Domestic->value,
            'managed_by_user_id' => $tour->managed_by_user_id,
            'tour_category_id' => $tour->tour_category_id,
            'tour_category_ids' => $tour->categories->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'destination_id' => $tour->destination_id,
            'destination_ids' => $tour->destinations->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'region_id' => $tour->region_id,
            'region_ids' => $tour->regions->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'transport' => $tour->transport,
            'departure_location' => $tour->departure_location,
            'contact_phone' => $tour->contact_phone ?: $this->defaultTourContactPhone($tour->managed_by_user_id),
            'duration_days' => $tour->duration_days,
            'duration_nights' => $tour->duration_nights,
            'standard_label' => $tour->standard_label,
            'base_price' => $tour->base_price,
            'sale_price' => $tour->sale_price,
            'rating_average' => $tour->rating_average,
            'rating_count' => $tour->rating_count,
            'cta_mode' => $tour->cta_mode ?: 'book',
            'is_featured' => $tour->is_featured,
            'sort_order' => $tour->sort_order,
            'published_at' => optional($tour->published_at)->format('Y-m-d\TH:i'),
            'cover_alt' => $tour->cover_alt,
            'cover_image_url' => $tour->cover_image_url,
            'meta_title' => $tour->meta_title,
            'meta_description' => $tour->meta_description,
            'og_title' => $tour->og_title,
            'og_description' => $tour->og_description,
            'canonical_url' => $tour->canonical_url,
            'robots_directive' => $tour->robots_directive,
            'itinerary_items' => $this->prepareItineraryItems($tour->itinerary),
            'pricing_items' => $this->preparePricingItems($tour->pricing_table),
            'departure_schedules_text' => implode(PHP_EOL, array_values(array_filter((array) $tour->departure_schedules))),
            'inclusions_text' => implode(PHP_EOL, array_values(array_filter((array) $tour->inclusions))),
            'tour_terms_items' => FaqContent::prepareItems($tour->tour_terms_items, [FaqContent::blankItem()]),
            'faq_items' => FaqContent::prepareItems($tour->faq_items),
            'gallery' => ContentGallery::prepare($tour->gallery),
            'related_questions' => FaqContent::prepareQuestions($tour->related_questions),
            'departures' => $departures !== [] ? $departures : [$this->blankDeparture()],
        ];
    }

    protected function defaultTaxonomyForm(): array
    {
        return [
            'name' => '',
            'slug' => '',
            'scope' => '',
            'region_id' => null,
            'excerpt' => '',
            'content' => '',
            'status' => 'draft',
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => '',
            'cover_alt' => '',
            'cover_image_url' => '',
            'meta_title' => '',
            'meta_description' => '',
            'og_title' => '',
            'og_description' => '',
            'canonical_url' => '',
            'robots_directive' => 'index,follow',
            'rating_average' => '',
            'rating_count' => '',
            'gallery' => [],
            'faq_items' => [FaqContent::blankItem()],
        ];
    }

    protected function normalizeDepartures(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row, int $index): array {
                return [
                    'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                    'departure_date' => $row['departure_date'] ?: null,
                    'return_date' => $row['return_date'] ?: null,
                    'departure_location' => trim((string) ($row['departure_location'] ?? '')),
                    'transport_label' => trim((string) ($row['transport_label'] ?? '')),
                    'standard_label' => trim((string) ($row['standard_label'] ?? '')),
                    'base_price' => filled($row['base_price'] ?? null) ? (int) $row['base_price'] : null,
                    'sale_price' => filled($row['sale_price'] ?? null) ? (int) $row['sale_price'] : null,
                    'available_slots' => filled($row['available_slots'] ?? null) ? (int) $row['available_slots'] : null,
                    'pricing_note' => trim((string) ($row['pricing_note'] ?? '')),
                    'status' => trim((string) ($row['status'] ?? 'scheduled')) ?: 'scheduled',
                    'is_featured' => (bool) ($row['is_featured'] ?? false),
                    'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : $index,
                ];
            })
            ->filter(function (array $row): bool {
                return filled($row['departure_date'])
                    || filled($row['return_date'])
                    || filled($row['departure_location'])
                    || filled($row['transport_label'])
                    || filled($row['standard_label'])
                    || $row['base_price'] !== null
                    || $row['sale_price'] !== null
                    || $row['available_slots'] !== null
                    || filled($row['pricing_note']);
            })
            ->values()
            ->all();
    }

    protected function normalizeLineList(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeItineraryItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): ?array {
                $title = $this->plainEditorContent($item['title'] ?? null);
                $content = $this->richEditorContent($item['content'] ?? null);
                $plainContent = $this->plainEditorContent($item['content'] ?? null);

                if ($title === '' && $plainContent === '') {
                    return null;
                }

                return [
                    'title' => $title,
                    'content' => $content,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizePricingItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): ?array {
                $label = $this->plainEditorContent($item['label'] ?? null);
                $price = $this->plainEditorContent($item['price'] ?? null);

                if ($label === '' && $price === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'price' => $price,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function prepareItineraryItems(?array $items): array
    {
        $prepared = collect($items ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): ?array {
                $title = trim((string) data_get($item, 'title'));
                $content = trim((string) data_get($item, 'content'));

                if ($title === '' && $this->plainEditorContent($content) === '') {
                    return null;
                }

                return [
                    'title' => $title,
                    'content' => $content,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $prepared !== [] ? $prepared : [$this->blankItineraryItem()];
    }

    protected function preparePricingItems(?array $items): array
    {
        $prepared = collect($items ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): ?array {
                $label = trim((string) data_get($item, 'label'));
                $price = trim((string) data_get($item, 'price'));

                if ($label === '' && $price === '') {
                    return null;
                }

                return [
                    'label' => $label,
                    'price' => $price,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $prepared !== [] ? $prepared : [$this->blankPricingItem()];
    }

    protected function persistTour(array $form, array $gallery): void
    {
        $tourTermsItems = FaqContent::normalizeItems($form['tour_terms_items'] ?? []);
        $faqItems = FaqContent::normalizeItems($form['faq_items'] ?? []);
        $relatedQuestions = FaqContent::normalizeQuestions($form['related_questions'] ?? []);
        $itineraryItems = $this->normalizeItineraryItems($form['itinerary_items'] ?? []);
        $pricingItems = $this->normalizePricingItems($form['pricing_items'] ?? []);
        $departures = $this->normalizeDepartures($form['departures'] ?? []);
        $existingTour = $this->selectedId ? $this->scopedTourQuery()->findOrFail($this->selectedId) : null;
        $oldGallery = $existingTour?->gallery ?? [];
        $managerId = $this->currentUserIsSale()
            ? auth()->id()
            : (filled($form['managed_by_user_id'] ?? null) ? (int) $form['managed_by_user_id'] : null);

        $tour = Tour::query()->updateOrCreate(
            ['id' => $this->selectedId],
            [
                'title' => $form['title'],
                'slug' => Str::slug($form['slug'] ?: $form['title']),
                'excerpt' => $this->plainEditorContent($form['excerpt'] ?? null),
                'content' => $this->richEditorContent($form['content'] ?? null),
                'status' => $form['status'],
                'scope' => $form['scope'],
                'managed_by_user_id' => $managerId,
                'tour_category_id' => $form['tour_category_id'] ?: null,
                'destination_id' => $form['destination_id'] ?: null,
                'region_id' => $form['region_id'] ?: null,
                'transport' => $form['transport'] ?: null,
                'departure_location' => $form['departure_location'] ?: null,
                'contact_phone' => trim((string) ($form['contact_phone'] ?? '')) ?: $this->defaultTourContactPhone($managerId) ?: null,
                'duration_days' => filled($form['duration_days'] ?? null) ? (int) $form['duration_days'] : null,
                'duration_nights' => filled($form['duration_nights'] ?? null) ? (int) $form['duration_nights'] : null,
                'standard_label' => $form['standard_label'] ?: null,
                'base_price' => filled($form['base_price'] ?? null) ? (int) $form['base_price'] : null,
                'sale_price' => filled($form['sale_price'] ?? null) ? (int) $form['sale_price'] : null,
                'rating_average' => filled($form['rating_average'] ?? null) ? $form['rating_average'] : null,
                'rating_count' => filled($form['rating_count'] ?? null) ? (int) $form['rating_count'] : null,
                'cta_mode' => $form['cta_mode'] ?: 'book',
                'is_featured' => (bool) ($form['is_featured'] ?? false),
                'sort_order' => (int) ($form['sort_order'] ?? 0),
                'published_at' => $form['published_at'] ?: null,
                'cover_alt' => $form['cover_alt'] ?: null,
                'cover_image_url' => $form['cover_image_url'] ?: null,
                'meta_title' => $form['meta_title'] ?: null,
                'meta_description' => $this->plainEditorContent($form['meta_description'] ?? null),
                'og_title' => $form['og_title'] ?: null,
                'og_description' => $this->plainEditorContent($form['og_description'] ?? null),
                'canonical_url' => $form['canonical_url'] ?: null,
                'robots_directive' => $form['robots_directive'] ?: 'index,follow',
                'itinerary' => $itineraryItems,
                'pricing_table' => $pricingItems,
                'departure_schedules' => collect($this->normalizeLineList($form['departure_schedules_text'] ?? ''))
                    ->merge(
                        collect($departures)
                            ->pluck('departure_date')
                            ->filter()
                            ->map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->format('d/m/Y'))
                    )
                    ->unique()
                    ->values()
                    ->all(),
                'inclusions' => $this->normalizeLineList($form['inclusions_text'] ?? ''),
                'tour_terms_items' => $tourTermsItems,
                'faq_items' => $faqItems,
                'gallery' => $gallery,
                'related_questions' => $relatedQuestions,
            ],
        );

        $tour->syncTaxonomyLinks(
            $form['tour_category_ids'] ?? [],
            $form['destination_ids'] ?? [],
            $form['region_ids'] ?? [],
        );

        $this->selectedId = $tour->id;

        $uploadedNewCover = $this->syncSingleImage($tour, 'avatarUpload', 'cover', [
            'alt' => $form['cover_alt'] ?? null,
        ]);

        if (! $uploadedNewCover) {
            $this->syncSingleImageFromLibrary($tour, $this->selectedLibraryAvatarMediaId, 'cover', [
                'alt' => $form['cover_alt'] ?? null,
            ]);
        }

        $this->syncGalleryMedia($tour, $oldGallery, $gallery, fn (string $uuid) => ContentGallery::tourCollection($uuid));
        $this->syncTourDepartures($tour, $departures);
        $this->avatarUpload = null;
        $this->galleryUploads = [];
        $this->editTour($tour->id);

        session()->flash('status', 'Đã lưu tour.');
        $this->redirectRoute('admin.tours.edit', ['tour' => $tour->getRouteKey()], navigate: true);
    }

    protected function saveTaxonomy(string $type): void
    {
        $rules = $this->taxonomyValidationRules($type);
        $validated = $this->validate($rules);
        $formProperty = match ($type) {
            'category' => 'categoryForm',
            'destination' => 'destinationForm',
            'region' => 'regionForm',
            default => 'categoryForm',
        };
        $form = $validated[$formProperty];
        $gallery = ContentGallery::normalize($form['gallery'] ?? []);
        $faqItems = FaqContent::normalizeItems($form['faq_items'] ?? []);

        $this->validateGalleryVideoItems($gallery, $formProperty.'.gallery');

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $modelClass = $this->resolveTaxonomyModelClass($type);
        $existingModel = $this->resolveEditingTaxonomyId($type)
            ? $modelClass::query()->find($this->resolveEditingTaxonomyId($type))
            : null;
        $oldGallery = $existingModel?->gallery ?? [];
        $payload = [
            'name' => $form['name'],
            'slug' => Str::slug($form['slug'] ?: $form['name']),
            'excerpt' => $this->plainEditorContent($form['excerpt'] ?? null),
            'content' => $this->richEditorContent($form['content'] ?? null),
            'status' => $form['status'],
            'is_featured' => (bool) ($form['is_featured'] ?? false),
            'sort_order' => (int) ($form['sort_order'] ?? 0),
            'published_at' => $form['published_at'] ?: null,
            'cover_alt' => $form['cover_alt'] ?: null,
            'cover_image_url' => $form['cover_image_url'] ?: null,
            'meta_title' => $form['meta_title'] ?: null,
            'meta_description' => $this->plainEditorContent($form['meta_description'] ?? null),
            'og_title' => $form['og_title'] ?: null,
            'og_description' => $this->plainEditorContent($form['og_description'] ?? null),
            'canonical_url' => $form['canonical_url'] ?: null,
            'robots_directive' => $form['robots_directive'] ?: 'index,follow',
            'rating_average' => filled($form['rating_average'] ?? null) ? $form['rating_average'] : null,
            'rating_count' => filled($form['rating_count'] ?? null) ? (int) $form['rating_count'] : null,
            'gallery' => $gallery,
        ];

        if (in_array($type, ['category', 'destination'], true)) {
            $payload['faq_items'] = $faqItems;
        }

        if (in_array($type, ['category', 'destination', 'region'], true)) {
            $payload['scope'] = $form['scope'] ?: null;
        }

        if ($type === 'destination') {
            $payload['region_id'] = $form['region_id'] ?: null;
        }

        $model = $modelClass::query()->updateOrCreate(
            ['id' => $this->resolveEditingTaxonomyId($type)],
            $payload,
        );

        $uploadedNewAvatar = $this->syncSingleImage($model, 'avatarUpload', 'avatar', [
            'alt' => $form['cover_alt'] ?? null,
        ]);

        if (! $uploadedNewAvatar) {
            $this->syncSingleImageFromLibrary($model, $this->selectedLibraryAvatarMediaId, 'avatar', [
                'alt' => $form['cover_alt'] ?? null,
            ]);
        }

        $this->syncGalleryMedia(
            $model,
            $oldGallery,
            $gallery,
            fn (string $uuid) => ContentGallery::taxonomyCollection($type, $uuid),
        );

        $this->avatarUpload = null;
        $this->galleryUploads = [];
        $this->fillTaxonomyForm($type, $model);

        session()->flash('status', 'Đã lưu '.$this->taxonomyConfig($type)['singular_label'].'.');
        $this->redirectRoute(
            $this->taxonomyConfig($type)['edit_route'],
            [$type => $model->getRouteKey()],
            navigate: true,
        );
    }

    protected function syncGalleryMedia(HasMedia $model, array $oldGallery, array $newGallery, callable $collectionResolver): void
    {
        $oldUuids = collect($oldGallery)->pluck('uuid')->filter()->values()->all();
        $newUuids = collect($newGallery)->pluck('uuid')->filter()->values()->all();

        foreach (array_diff($oldUuids, $newUuids) as $uuid) {
            $model->clearMediaCollection($collectionResolver($uuid));
        }

        foreach ($newGallery as $index => $item) {
            if (($item['type'] ?? ContentGallery::TYPE_IMAGE) !== ContentGallery::TYPE_IMAGE) {
                continue;
            }

            $collection = $collectionResolver($item['uuid']);
            $uploadedImage = $this->galleryUploads[$index] ?? null;

            if ($uploadedImage) {
                $this->syncUploadedImage(
                    $model,
                    $uploadedImage,
                    $collection,
                    ['alt' => $item['image_alt'] ?: $item['title'] ?: 'Gallery image'],
                );

                continue;
            }

            if (filled($this->selectedGalleryLibraryMediaIds[$item['uuid']] ?? null)) {
                $this->syncSingleImageFromLibrary(
                    $model,
                    $this->selectedGalleryLibraryMediaIds[$item['uuid']] ?? null,
                    $collection,
                    ['alt' => $item['image_alt'] ?: $item['title'] ?: 'Gallery image'],
                );

                continue;
            }

            if (filled($item['image_url'] ?? null)) {
                $model->clearMediaCollection($collection);
            }
        }
    }

    protected function syncTourDepartures(Tour $tour, array $departures): void
    {
        $existingIds = $tour->departures()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $incomingIds = collect($departures)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        foreach (array_diff($existingIds, $incomingIds) as $departureId) {
            TourDeparture::query()->whereKey($departureId)->delete();
        }

        foreach ($departures as $index => $departure) {
            $payload = [
                'tour_id' => $tour->id,
                'departure_date' => $departure['departure_date'],
                'return_date' => $departure['return_date'],
                'departure_location' => $departure['departure_location'] ?: null,
                'transport_label' => $departure['transport_label'] ?: null,
                'standard_label' => $departure['standard_label'] ?: null,
                'base_price' => $departure['base_price'],
                'sale_price' => $departure['sale_price'],
                'available_slots' => $departure['available_slots'],
                'pricing_note' => $departure['pricing_note'] ?: null,
                'status' => $departure['status'] ?: 'scheduled',
                'is_featured' => (bool) ($departure['is_featured'] ?? false),
                'sort_order' => filled($departure['sort_order'] ?? null) ? (int) $departure['sort_order'] : $index,
            ];

            if ($departure['id']) {
                $existing = $tour->departures()->whereKey($departure['id'])->first();

                if ($existing) {
                    $existing->fill($payload)->save();

                    continue;
                }
            }

            $tour->departures()->create($payload);
        }
    }

    protected function taxonomyValidationRules(string $type): array
    {
        $prefix = match ($type) {
            'category' => 'categoryForm',
            'destination' => 'destinationForm',
            'region' => 'regionForm',
            default => 'categoryForm',
        };

        return [
            $prefix.'.name' => ['required', 'string', 'max:255'],
            $prefix.'.slug' => ['nullable', 'string', 'max:255'],
            $prefix.'.scope' => ['nullable', Rule::in(array_map(fn (TourScope $scope) => $scope->value, TourScope::cases()))],
            $prefix.'.region_id' => ['nullable', 'exists:regions,id'],
            $prefix.'.excerpt' => ['nullable', 'string'],
            $prefix.'.content' => ['nullable', 'string'],
            $prefix.'.status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            $prefix.'.is_featured' => ['boolean'],
            $prefix.'.sort_order' => ['nullable', 'integer', 'min:0'],
            $prefix.'.published_at' => ['nullable', 'date'],
            $prefix.'.cover_alt' => ['nullable', 'string', 'max:255'],
            $prefix.'.cover_image_url' => ['nullable', 'url', 'max:2048'],
            $prefix.'.meta_title' => ['nullable', 'string', 'max:255'],
            $prefix.'.meta_description' => ['nullable', 'string'],
            $prefix.'.og_title' => ['nullable', 'string', 'max:255'],
            $prefix.'.og_description' => ['nullable', 'string'],
            $prefix.'.canonical_url' => ['nullable', 'url', 'max:2048'],
            $prefix.'.robots_directive' => ['nullable', 'string', 'max:255'],
            $prefix.'.rating_average' => ['nullable', 'numeric', 'between:0,5'],
            $prefix.'.rating_count' => ['nullable', 'integer', 'min:0'],
            $prefix.'.faq_items' => ['nullable', 'array'],
            $prefix.'.faq_items.*.question' => ['nullable', 'string', 'max:500'],
            $prefix.'.faq_items.*.answer' => ['nullable', 'string', 'max:5000'],
            $prefix.'.gallery' => ['nullable', 'array'],
            $prefix.'.gallery.*.uuid' => ['required', 'string', 'max:100'],
            $prefix.'.gallery.*.type' => ['required', Rule::in([ContentGallery::TYPE_IMAGE, ContentGallery::TYPE_YOUTUBE, ContentGallery::TYPE_MP4])],
            $prefix.'.gallery.*.title' => ['nullable', 'string', 'max:255'],
            $prefix.'.gallery.*.description' => ['nullable', 'string', 'max:1000'],
            $prefix.'.gallery.*.image_alt' => ['nullable', 'string', 'max:255'],
            $prefix.'.gallery.*.image_url' => ['nullable', 'url', 'max:2048'],
            $prefix.'.gallery.*.video_url' => ['nullable', 'url', 'max:2048'],
            'galleryUploads.*' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function tourValidationRules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => ['nullable', 'string', 'max:255'],
            'form.excerpt' => ['nullable', 'string'],
            'form.content' => ['nullable', 'string'],
            'form.status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'form.scope' => ['required', Rule::in(array_map(fn (TourScope $scope) => $scope->value, TourScope::cases()))],
            'form.managed_by_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'form.tour_category_id' => ['nullable', 'exists:tour_categories,id'],
            'form.tour_category_ids' => ['nullable', 'array'],
            'form.tour_category_ids.*' => ['integer', 'exists:tour_categories,id'],
            'form.destination_id' => ['nullable', 'exists:destinations,id'],
            'form.destination_ids' => ['nullable', 'array'],
            'form.destination_ids.*' => ['integer', 'exists:destinations,id'],
            'form.region_id' => ['nullable', 'exists:regions,id'],
            'form.region_ids' => ['nullable', 'array'],
            'form.region_ids.*' => ['integer', 'exists:regions,id'],
            'form.transport' => ['nullable', 'string', 'max:255'],
            'form.departure_location' => ['nullable', 'string', 'max:255'],
            'form.contact_phone' => ['nullable', 'string', 'max:50'],
            'form.duration_days' => ['nullable', 'integer', 'min:0'],
            'form.duration_nights' => ['nullable', 'integer', 'min:0'],
            'form.standard_label' => ['nullable', 'string', 'max:255'],
            'form.base_price' => ['nullable', 'integer', 'min:0'],
            'form.sale_price' => ['nullable', 'integer', 'min:0'],
            'form.rating_average' => ['nullable', 'numeric', 'between:0,5'],
            'form.rating_count' => ['nullable', 'integer', 'min:0'],
            'form.cta_mode' => ['required', Rule::in(['book', 'contact'])],
            'form.is_featured' => ['boolean'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'form.published_at' => ['nullable', 'date'],
            'form.cover_alt' => ['nullable', 'string', 'max:255'],
            'form.cover_image_url' => ['nullable', 'url', 'max:2048'],
            'form.meta_title' => ['nullable', 'string', 'max:255'],
            'form.meta_description' => ['nullable', 'string'],
            'form.og_title' => ['nullable', 'string', 'max:255'],
            'form.og_description' => ['nullable', 'string'],
            'form.canonical_url' => ['nullable', 'url', 'max:2048'],
            'form.robots_directive' => ['nullable', 'string', 'max:255'],
            'form.itinerary_items' => ['nullable', 'array'],
            'form.itinerary_items.*.title' => ['nullable', 'string', 'max:255'],
            'form.itinerary_items.*.content' => ['nullable', 'string', 'max:20000'],
            'form.pricing_items' => ['nullable', 'array'],
            'form.pricing_items.*.label' => ['nullable', 'string', 'max:255'],
            'form.pricing_items.*.price' => ['nullable', 'string', 'max:255'],
            'form.departure_schedules_text' => ['nullable', 'string'],
            'form.inclusions_text' => ['nullable', 'string'],
            'form.tour_terms_items' => ['nullable', 'array'],
            'form.tour_terms_items.*.question' => ['nullable', 'string', 'max:500'],
            'form.tour_terms_items.*.answer' => ['nullable', 'string', 'max:5000'],
            'form.faq_items' => ['nullable', 'array'],
            'form.faq_items.*.question' => ['nullable', 'string', 'max:500'],
            'form.faq_items.*.answer' => ['nullable', 'string', 'max:5000'],
            'form.gallery' => ['nullable', 'array'],
            'form.gallery.*.uuid' => ['required', 'string', 'max:100'],
            'form.gallery.*.type' => ['required', Rule::in([ContentGallery::TYPE_IMAGE, ContentGallery::TYPE_YOUTUBE, ContentGallery::TYPE_MP4])],
            'form.gallery.*.title' => ['nullable', 'string', 'max:255'],
            'form.gallery.*.description' => ['nullable', 'string', 'max:1000'],
            'form.gallery.*.image_alt' => ['nullable', 'string', 'max:255'],
            'form.gallery.*.image_url' => ['nullable', 'url', 'max:2048'],
            'form.gallery.*.video_url' => ['nullable', 'url', 'max:2048'],
            'form.related_questions' => ['nullable', 'array'],
            'form.related_questions.*' => ['nullable', 'string', 'max:500'],
            'form.departures' => ['nullable', 'array'],
            'form.departures.*.id' => ['nullable', 'integer'],
            'form.departures.*.departure_date' => ['nullable', 'date'],
            'form.departures.*.return_date' => ['nullable', 'date'],
            'form.departures.*.departure_location' => ['nullable', 'string', 'max:255'],
            'form.departures.*.transport_label' => ['nullable', 'string', 'max:255'],
            'form.departures.*.standard_label' => ['nullable', 'string', 'max:255'],
            'form.departures.*.base_price' => ['nullable', 'integer', 'min:0'],
            'form.departures.*.sale_price' => ['nullable', 'integer', 'min:0'],
            'form.departures.*.available_slots' => ['nullable', 'integer', 'min:0'],
            'form.departures.*.pricing_note' => ['nullable', 'string', 'max:255'],
            'form.departures.*.status' => ['nullable', Rule::in(['scheduled', 'published', 'sold_out', 'finished', 'cancelled'])],
            'form.departures.*.is_featured' => ['boolean'],
            'form.departures.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'galleryUploads.*' => ['nullable', 'image', 'max:4096'],
        ];
    }

    protected function validateGalleryVideoItems(array $gallery, string $pathPrefix): void
    {
        foreach ($gallery as $index => $item) {
            if (($item['type'] ?? ContentGallery::TYPE_IMAGE) === ContentGallery::TYPE_IMAGE) {
                continue;
            }

            if (blank($item['video_url'] ?? null)) {
                $this->addError($pathPrefix.'.'.$index.'.video_url', 'Vui lòng nhập URL video hợp lệ.');

                continue;
            }

            if (
                ($item['type'] ?? ContentGallery::TYPE_IMAGE) === ContentGallery::TYPE_YOUTUBE
                && ! ContentGallery::youtubeId($item['video_url'])
            ) {
                $this->addError($pathPrefix.'.'.$index.'.video_url', 'URL YouTube chưa đúng định dạng hỗ trợ.');
            }
        }
    }
}
