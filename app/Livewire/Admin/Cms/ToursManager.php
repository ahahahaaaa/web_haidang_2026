<?php

namespace App\Livewire\Admin\Cms;

use App\Jobs\Travel\PushTourToAgencyJob;
use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Cms\Concerns\HandlesGeoConfig;
use App\Livewire\Admin\Cms\Concerns\HandlesMediaUploads;
use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use App\Services\Travel\HaidangAgencyApiException;
use App\Services\Travel\TourAgencyPushSyncRunService;
use App\Services\Travel\TourDepartureManualSyncService;
use App\Services\Travel\TourSyncSourceTourCatalog;
use App\Support\ContentGallery;
use App\Support\FaqContent;
use App\Support\QrCodeSvg;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
use Src\Domains\Cms\Models\TourDepartureSyncState;
use Src\Domains\Cms\Models\TourReviewBatch;

#[Layout('layouts.app')]
#[Title('Quản lý tour')]
class ToursManager extends Component
{
    use AuthorizesAdminPermissions;
    use HandlesGeoConfig;
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

    public ?string $syncSourceError = null;

    public string $syncTourDirection = 'cms_to_agency';

    public int $syncSourcePage = 1;

    public int $syncSourcePerPage = 20;

    public string $syncSourceSearch = '';

    public string $syncSourceStatusFilter = '';

    public string $syncSourceTourId = '';

    public array $syncSourceTours = [];

    public bool $syncTourPickerOpen = false;

    public ?int $syncTourTargetId = null;

    public string $syncTourTargetTitle = '';

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
        $saleUsers = $this->saleUsersQuery()->orderBy('name')->get();
        $editingTour = $this->isTourEditorRoute() && $this->selectedId
            ? Tour::query()->with([
                'categories',
                'destinations',
                'departures',
                'destination',
                'manager',
                'primaryCategory',
                'region',
                'regions',
                'reviewBatches.departure',
            ])->when($this->currentUserIsSale(), fn (Builder $query) => $query->where('managed_by_user_id', auth()->id()))->find($this->selectedId)
            : null;
        $taxonomyOptionScope = $this->currentTourTaxonomyOptionScope();

        return view($this->resolveView(), [
            'categories' => $this->tourCategoryOptions($taxonomyOptionScope),
            'countryRootOptions' => Destination::query()->countryRoots()->orderBy('sort_order')->orderBy('name')->get(),
            'destinations' => $this->destinationOptions($taxonomyOptionScope),
            'editingTaxonomyModel' => $taxonomyType ? $this->resolveEditingTaxonomyModel($taxonomyType) : null,
            'editingTour' => $editingTour,
            'regions' => Region::query()->orderBy('sort_order')->orderBy('name')->get(),
            'reviewSubmission' => $this->reviewSubmissionPayload($editingTour),
            'saleUsers' => $saleUsers,
            'scopeOptions' => TourScope::cases(),
            'selectedGalleryLibraryMedia' => $this->resolveSelectedMediaPayload($this->selectedGalleryLibraryMediaIds),
            'selectedLibraryAvatarMedia' => $this->selectedLibraryAvatarMediaId
                ? Media::query()->whereKey($this->selectedLibraryAvatarMediaId)->where('mime_type', 'like', 'image/%')->first()
                : null,
            'syncSourcePagination' => $this->syncSourcePaginationMeta(),
            'syncSourceTourOptions' => $this->paginatedSyncSourceTourOptions(),
            'syncSourceTourStatusOptions' => $this->syncSourceStatusOptions(),
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

    public function addReviewBatch(): void
    {
        $this->form['review_batches'][] = $this->blankReviewBatch();
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

    public function openTourSyncPicker(int $id): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        $tour = $this->scopedTourQuery()->findOrFail($id);
        $this->syncTourTargetId = (int) $tour->getKey();
        $this->syncTourTargetTitle = $tour->title;
        $this->syncSourceTourId = '';
        $this->syncSourceSearch = '';
        $this->syncSourceStatusFilter = '';
        $this->syncSourcePage = 1;
        $this->syncSourceError = null;
        $this->syncTourDirection = 'cms_to_agency';

        try {
            $this->syncSourceTours = app(TourSyncSourceTourCatalog::class)->all();
        } catch (HaidangAgencyApiException $exception) {
            $this->syncSourceError = $exception->getMessage();
            $this->syncSourceTours = [];
        } catch (\Throwable $exception) {
            report($exception);
            $this->syncSourceError = 'Không tải được danh sách tour từ API Master Data DashBoard. Vui lòng thử lại sau.';
            $this->syncSourceTours = [];
        }

        $this->syncTourPickerOpen = true;
    }

    public function closeTourSyncPicker(): void
    {
        $this->syncTourPickerOpen = false;
        $this->syncTourTargetId = null;
        $this->syncTourTargetTitle = '';
        $this->syncSourceTourId = '';
        $this->syncSourceSearch = '';
        $this->syncSourceStatusFilter = '';
        $this->syncSourcePage = 1;
        $this->syncSourceError = null;
        $this->syncTourDirection = 'cms_to_agency';
        $this->syncSourceTours = [];
    }

    public function refreshTourSyncSourceCatalog(): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        if (! $this->syncTourTargetId) {
            return;
        }

        try {
            $this->syncSourceTours = app(TourSyncSourceTourCatalog::class)->all(refresh: true);
            $this->syncSourceTourId = '';
            $this->syncSourcePage = 1;
            $this->syncSourceError = null;
        } catch (HaidangAgencyApiException $exception) {
            $this->syncSourceError = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $this->syncSourceError = 'Không tải lại được danh sách tour từ API Master Data DashBoard. Vui lòng thử lại sau.';
        }
    }

    public function goToSyncSourcePage(int $page): void
    {
        $meta = $this->syncSourcePaginationMeta();
        $this->syncSourcePage = min(max(1, $page), (int) $meta['last_page']);
    }

    public function nextSyncSourcePage(): void
    {
        $this->goToSyncSourcePage($this->syncSourcePage + 1);
    }

    public function previousSyncSourcePage(): void
    {
        $this->goToSyncSourcePage($this->syncSourcePage - 1);
    }

    public function syncSelectedTourFromWeb(): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        if (! $this->syncTourTargetId) {
            $this->syncSourceError = 'Chưa chọn tour CMS cần đồng bộ.';

            return;
        }

        if ($this->syncSourceTourId === '') {
            $this->syncSourceError = 'Vui lòng chọn tour nguồn từ API trước khi đồng bộ.';

            return;
        }

        $tour = $this->scopedTourQuery()->findOrFail($this->syncTourTargetId);
        $catalog = app(TourSyncSourceTourCatalog::class);
        $sourceCatalogRefreshed = true;
        $sourceTour = $catalog->find($this->syncSourceTourId, refresh: true);

        if ($sourceTour === null) {
            Log::warning('tour_sync.master_data_dashboard.pull_source_missing', [
                'source' => 'api_master_data_dashboard',
                'target' => 'haidangtravel_cms',
                'event' => 'pull_source_missing',
                'cms_tour_id' => $tour->getKey(),
                'selected_source_tour_id' => $this->syncSourceTourId,
                'source_catalog_refreshed' => $sourceCatalogRefreshed,
                'direction' => 'agency_to_cms',
            ]);

            $this->syncSourceError = 'Không tìm thấy tour nguồn đã chọn trong danh sách API.';

            return;
        }

        $sourceTransport = data_get($sourceTour, 'transport')
            ?? data_get($sourceTour, 'traffic')
            ?? data_get($sourceTour, 'transport_label');
        $sourceTransportField = data_get($sourceTour, 'transport_source_field')
            ?: ($sourceTransport !== null ? 'transport' : null);
        $sourceTransportCandidates = data_get($sourceTour, 'transport_candidates', []);

        Log::info('tour_sync.master_data_dashboard.pull_started', [
            'source' => 'api_master_data_dashboard',
            'target' => 'haidangtravel_cms',
            'event' => 'pull_started',
            'cms_tour_id' => $tour->getKey(),
            'source_tour_id' => data_get($sourceTour, 'tour_id'),
            'tour_code' => data_get($sourceTour, 'tour_code'),
            'source_transport' => $sourceTransport,
            'source_transport_field' => $sourceTransportField,
            'source_transport_candidates' => $sourceTransportCandidates,
            'source_catalog_refreshed' => $sourceCatalogRefreshed,
            'direction' => 'agency_to_cms',
        ]);

        try {
            $summary = app(TourDepartureManualSyncService::class)->syncFromSourceTour($tour, $sourceTour);
        } catch (HaidangAgencyApiException $exception) {
            $this->logAgencyPullSyncFailed($tour, $sourceTour, $sourceTransport, $sourceTransportField, $sourceTransportCandidates, $sourceCatalogRefreshed, $exception, 'api_exception');
            $this->syncSourceError = $exception->getMessage();

            return;
        } catch (QueryException $exception) {
            $missingSyncStateTable = str_contains($exception->getMessage(), 'tour_departure_sync_states');

            $this->logAgencyPullSyncFailed($tour, $sourceTour, $sourceTransport, $sourceTransportField, $sourceTransportCandidates, $sourceCatalogRefreshed, $exception, 'query_exception', [
                'missing_sync_state_table' => $missingSyncStateTable,
            ]);

            if ($missingSyncStateTable) {
                $this->syncSourceError = 'Thiếu bảng lưu trạng thái đồng bộ. Cần chạy migration trước khi đồng bộ tour.';

                return;
            }

            report($exception);
            $this->syncSourceError = 'Không đồng bộ được lịch khởi hành. Vui lòng thử lại sau.';

            return;
        } catch (\Throwable $exception) {
            $this->logAgencyPullSyncFailed($tour, $sourceTour, $sourceTransport, $sourceTransportField, $sourceTransportCandidates, $sourceCatalogRefreshed, $exception, 'unexpected_exception');
            report($exception);
            $this->syncSourceError = 'Không đồng bộ được lịch khởi hành. Vui lòng thử lại sau.';

            return;
        }

        $syncedTour = $tour->fresh();

        Log::info('tour_sync.master_data_dashboard.pull_finished', [
            'source' => 'api_master_data_dashboard',
            'target' => 'haidangtravel_cms',
            'event' => 'pull_finished',
            'cms_tour_id' => $tour->getKey(),
            'source_tour_id' => $summary['source_tour_id'] ?? null,
            'tour_code' => $summary['tour_code'] ?? null,
            'source_transport' => $sourceTransport,
            'source_transport_field' => $sourceTransportField,
            'source_transport_candidates' => $sourceTransportCandidates,
            'source_catalog_refreshed' => $sourceCatalogRefreshed,
            'cms_transport' => $syncedTour?->transport,
            'created' => (int) ($summary['created'] ?? 0),
            'updated' => (int) ($summary['updated'] ?? 0),
            'skipped_invalid' => (int) ($summary['skipped_invalid'] ?? 0),
            'tour_title_updated' => (bool) ($summary['tour_title_updated'] ?? false),
            'tour_slug_updated' => (bool) ($summary['tour_slug_updated'] ?? false),
            'tour_details_updated' => (bool) ($summary['tour_details_updated'] ?? false),
            'direction' => 'agency_to_cms',
        ]);

        $this->closeTourSyncPicker();
        $syncNotes = collect([
            ((bool) ($summary['tour_title_updated'] ?? false)) ? 'đã cập nhật tên tour' : null,
            ((bool) ($summary['tour_slug_updated'] ?? false)) ? 'đã cập nhật slug' : null,
            ((bool) ($summary['tour_slug_skipped_duplicate'] ?? false)) ? 'bỏ qua slug do trùng với tour khác' : null,
        ])->filter()->values();

        session()->flash(
            'status',
            sprintf(
                'Đã đồng bộ tên tour và khởi hành cho "%s": thêm %d, cập nhật %d, bỏ qua %d dòng không hợp lệ%s',
                $syncedTour?->title ?? $tour->refresh()->title,
                (int) ($summary['created'] ?? 0),
                (int) ($summary['updated'] ?? 0),
                (int) ($summary['skipped_invalid'] ?? 0),
                $syncNotes->isNotEmpty() ? ', '.$syncNotes->implode(', ').'.' : '.',
            ),
        );
    }

    public function pushSelectedTourToAgency(): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        if (! $this->syncTourTargetId) {
            $this->syncSourceError = 'Chưa chọn tour CMS cần gửi sang API Master Data DashBoard.';

            return;
        }

        if ($this->syncSourceTourId === '') {
            $this->syncSourceError = 'Vui lòng chọn tour đích API Master Data DashBoard trước khi gửi dữ liệu CMS.';

            return;
        }

        if (! (bool) config('tour_sync.push_enabled', true)) {
            $this->syncSourceError = 'Cơ chế đẩy dữ liệu sang API Master Data DashBoard đang tắt trong cấu hình.';

            return;
        }

        $tour = $this->scopedTourQuery()->findOrFail($this->syncTourTargetId);
        $catalog = app(TourSyncSourceTourCatalog::class);
        $targetTour = $catalog->find($this->syncSourceTourId) ?: $catalog->find($this->syncSourceTourId, refresh: true);

        if ($targetTour === null) {
            $this->syncSourceError = 'Không tìm thấy tour đích API Master Data DashBoard đã chọn trong danh sách API.';

            return;
        }

        try {
            $agencyTour = $this->rememberAgencyTargetTour($tour, $targetTour);
        } catch (HaidangAgencyApiException $exception) {
            $this->syncSourceError = $exception->getMessage();

            return;
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'tour_departure_sync_states')) {
                $this->syncSourceError = 'Thiếu bảng lưu trạng thái đồng bộ. Cần chạy migration trước khi gửi tour sang API Master Data DashBoard.';

                return;
            }

            report($exception);
            $this->syncSourceError = 'Không lưu được mapping tour API Master Data DashBoard. Vui lòng thử lại sau.';

            return;
        } catch (\Throwable $exception) {
            report($exception);
            $this->syncSourceError = 'Không chuẩn bị được dữ liệu gửi sang API Master Data DashBoard. Vui lòng thử lại sau.';

            return;
        }

        $run = app(TourAgencyPushSyncRunService::class)->enqueue(
            $tour,
            [],
            'manual_picker',
            auth()->id(),
        );

        if (! $run) {
            $this->syncSourceError = 'Không tạo được hàng chờ đồng bộ. Tour có thể chưa có mapping API Master Data DashBoard hoặc cơ chế push đang tắt.';

            return;
        }

        PushTourToAgencyJob::dispatch($tour->getKey(), [], $run->getKey())
            ->onQueue((string) config('tour_sync.push_queue', 'default'));
        $this->logAgencyPushJobDispatched($tour, $run->getKey(), 'manual_picker');

        $this->closeTourSyncPicker();

        session()->flash(
            'status',
            sprintf(
                'Đã đưa tour "%s" vào hàng đợi gửi sang API Master Data DashBoard tour ID %d%s.',
                $tour->title,
                $agencyTour['tour_id'],
                $agencyTour['tour_code'] ? ' - mã '.$agencyTour['tour_code'] : '',
            ),
        );
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
                'reviewBatches.departure',
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

    public function removeReviewBatch(int $index): void
    {
        $this->removeIndexedValue('form.review_batches', $index);
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

    public function updatedDestinationFormIsCountryRoot(mixed $value): void
    {
        if ((bool) $value) {
            $this->normalizeDestinationRootFormState();
        }
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
        $this->categoryFilter = '';
        $this->destinationFilter = '';
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

    public function updatingSyncSourcePerPage(): void
    {
        $this->syncSourcePage = 1;
    }

    public function updatingSyncSourceSearch(): void
    {
        $this->syncSourcePage = 1;
    }

    public function updatingSyncSourceStatusFilter(): void
    {
        $this->syncSourcePage = 1;
    }

    public function updatedSyncTourDirection(): void
    {
        if (! in_array($this->syncTourDirection, ['agency_to_cms', 'cms_to_agency'], true)) {
            $this->syncTourDirection = 'cms_to_agency';
        }

        $this->syncSourceTourId = '';
        $this->syncSourceError = null;
        $this->syncSourcePage = 1;
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

    protected function blankReviewBatch(): array
    {
        return [
            'id' => null,
            'tour_departure_id' => '',
            'departure_date' => '',
            'label' => '',
            'enabled' => true,
            'password' => '',
            'token' => '',
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

    /**
     * @return array<int, array<string, mixed>>
     */
    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function filteredSyncSourceTourRows(): Collection
    {
        $query = Str::of($this->syncSourceSearch)->ascii()->lower()->squish()->value();
        $statusFilter = Str::of($this->syncSourceStatusFilter)->ascii()->lower()->squish()->value();

        return collect($this->syncSourceTours)
            ->when($query !== '', function (Collection $items) use ($query): Collection {
                return $items->filter(function (array $row) use ($query): bool {
                    $haystack = Str::of(implode(' ', [
                        $row['tour_id'] ?? '',
                        $row['title'] ?? '',
                        $row['tour_code'] ?? '',
                        $row['slug'] ?? '',
                        $row['status_label'] ?? '',
                    ]))->ascii()->lower()->value();

                    return str_contains($haystack, $query);
                });
            })
            ->when($statusFilter !== '', function (Collection $items) use ($statusFilter): Collection {
                return $items->filter(function (array $row) use ($statusFilter): bool {
                    $status = Str::of((string) ($row['status_label'] ?: $row['status'] ?: ''))->ascii()->lower()->squish()->value();

                    return $status === $statusFilter;
                });
            })
            ->values();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function paginatedSyncSourceTourOptions(): array
    {
        $meta = $this->syncSourcePaginationMeta();

        return $this->filteredSyncSourceTourRows()
            ->forPage((int) $meta['current_page'], (int) $meta['per_page'])
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    protected function syncSourcePaginationMeta(): array
    {
        $total = $this->filteredSyncSourceTourRows()->count();
        $perPage = $this->syncSourcePerPageValue();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min(max(1, $this->syncSourcePage), $lastPage);

        if ($currentPage !== $this->syncSourcePage) {
            $this->syncSourcePage = $currentPage;
        }

        return [
            'current_page' => $currentPage,
            'from' => $total === 0 ? 0 : (($currentPage - 1) * $perPage) + 1,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'to' => min($total, $currentPage * $perPage),
            'total' => $total,
        ];
    }

    protected function syncSourcePerPageValue(): int
    {
        $perPage = (int) $this->syncSourcePerPage;

        return in_array($perPage, [10, 20, 50], true) ? $perPage : 20;
    }

    /**
     * @return array<int, string>
     */
    protected function syncSourceStatusOptions(): array
    {
        return collect($this->syncSourceTours)
            ->map(fn (array $row): string => trim((string) ($row['status_label'] ?: $row['status'] ?: '')))
            ->filter()
            ->unique()
            ->sortBy(fn (string $status): string => Str::ascii($status))
            ->values()
            ->all();
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

    protected function currentTourTaxonomyOptionScope(): ?TourScope
    {
        if ($this->isTourEditorRoute()) {
            return TourScope::tryFrom((string) data_get($this->form, 'scope'));
        }

        if ($this->currentRouteName === 'admin.tours') {
            return TourScope::tryFrom($this->scopeFilter);
        }

        return null;
    }

    protected function destinationOptions(?TourScope $scope): Collection
    {
        return Destination::query()
            ->with(['country', 'region'])
            ->when($scope, fn (Builder $query, TourScope $scope) => $this->applyTaxonomyOptionScope(
                $query,
                $scope,
                $this->selectedDestinationOptionIds(),
            ))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function tourCategoryOptions(?TourScope $scope): Collection
    {
        return TourCategory::query()
            ->when($scope, fn (Builder $query, TourScope $scope) => $this->applyTaxonomyOptionScope(
                $query,
                $scope,
                $this->selectedTourCategoryOptionIds(),
            ))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function applyTaxonomyOptionScope(Builder $query, TourScope $scope, array $selectedIds = []): Builder
    {
        return $query->where(function (Builder $scopeQuery) use ($scope, $selectedIds): void {
            $scopeQuery
                ->whereNull('scope')
                ->orWhere('scope', '')
                ->orWhere('scope', $scope->value);

            if ($selectedIds !== []) {
                $scopeQuery->orWhereIn($scopeQuery->getModel()->getKeyName(), $selectedIds);
            }
        });
    }

    protected function selectedDestinationOptionIds(): array
    {
        return $this->positiveOptionIds(
            collect([$this->destinationFilter, data_get($this->form, 'destination_id')])
                ->merge((array) data_get($this->form, 'destination_ids', [])),
        );
    }

    protected function selectedTourCategoryOptionIds(): array
    {
        return $this->positiveOptionIds(
            collect([$this->categoryFilter, data_get($this->form, 'tour_category_id')])
                ->merge((array) data_get($this->form, 'tour_category_ids', [])),
        );
    }

    protected function positiveOptionIds(Collection $ids): array
    {
        return $ids
            ->flatten()
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function saleUsersQuery(): Builder
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query) => $query->where('name', 'sale'));
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
        $form['country_id'] = data_get($model, 'country_id');
        $form['is_country_root'] = (bool) data_get($model, 'is_country_root', false);
        $form['show_tours_on_page'] = (bool) data_get($model, 'show_tours_on_page', true);
        $form['show_blogs_on_page'] = (bool) data_get($model, 'show_blogs_on_page', false);
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
        $form['geo_config'] = $this->defaultGeoConfigForm(data_get($model, 'geo_config'));

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
            'destination' => Destination::query()->with(['country', 'region'])->withCount('tours'),
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
            'review_submission_enabled' => false,
            'review_submission_password' => '',
            'review_submission_token' => '',
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
            'geo_config' => $this->defaultGeoConfigForm(),
            'itinerary_items' => [$this->blankItineraryItem()],
            'pricing_items' => [$this->blankPricingItem()],
            'departure_schedules_text' => '',
            'inclusions_text' => '',
            'tour_terms_items' => [FaqContent::blankItem()],
            'faq_items' => [FaqContent::blankItem()],
            'gallery' => [],
            'related_questions' => [''],
            'departures' => [$this->blankDeparture()],
            'review_batches' => [],
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
                || (method_exists($model, 'tours') && $model->tours()->exists())
                || (method_exists($model, 'childDestinations') && $model->childDestinations()->exists()),
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
        $reviewBatches = $tour->reviewBatches
            ->map(fn (TourReviewBatch $batch) => [
                'id' => $batch->id,
                'tour_departure_id' => $batch->tour_departure_id ?: '',
                'departure_date' => optional($batch->departure_date)->format('Y-m-d'),
                'label' => (string) $batch->label,
                'enabled' => (bool) $batch->enabled,
                'password' => (string) $batch->password,
                'token' => (string) $batch->token,
                'sort_order' => (int) $batch->sort_order,
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
            'review_submission_enabled' => (bool) $tour->review_submission_enabled,
            'review_submission_password' => (string) $tour->review_submission_password,
            'review_submission_token' => (string) $tour->review_submission_token,
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
            'geo_config' => $this->defaultGeoConfigForm($tour->geo_config),
            'itinerary_items' => $this->prepareItineraryItems($tour->itinerary),
            'pricing_items' => $this->preparePricingItems($tour->pricing_table),
            'departure_schedules_text' => implode(PHP_EOL, array_values(array_filter((array) $tour->departure_schedules))),
            'inclusions_text' => implode(PHP_EOL, array_values(array_filter((array) $tour->inclusions))),
            'tour_terms_items' => FaqContent::prepareItems($tour->tour_terms_items, [FaqContent::blankItem()]),
            'faq_items' => FaqContent::prepareItems($tour->faq_items),
            'gallery' => ContentGallery::prepare($tour->gallery),
            'related_questions' => FaqContent::prepareQuestions($tour->related_questions),
            'departures' => $departures !== [] ? $departures : [$this->blankDeparture()],
            'review_batches' => $reviewBatches,
        ];
    }

    protected function reviewSubmissionPayload(?Tour $tour): array
    {
        if (! $tour || ! config('travel_reviews.enabled', true)) {
            return [
                'batches' => [],
                'enabled' => false,
                'password' => '',
                'qr_svg' => '',
                'url' => '',
            ];
        }

        $batches = ($tour->relationLoaded('reviewBatches') ? $tour->reviewBatches : $tour->reviewBatches()->with('departure')->ordered()->get())
            ->map(function (TourReviewBatch $batch): array {
                $url = $batch->publicReviewUrl();
                $departureDate = $batch->departure_date?->format('d/m/Y');

                return [
                    'departure_date' => $departureDate,
                    'enabled' => (bool) $batch->enabled,
                    'id' => (int) $batch->getKey(),
                    'label' => (string) $batch->label,
                    'password' => (string) $batch->password,
                    'qr_svg' => filled($url) ? QrCodeSvg::render($url, 190) : '',
                    'requires_password' => $batch->requiresPassword(),
                    'token' => (string) $batch->token,
                    'url' => $url ?: '',
                ];
            })
            ->values();
        $firstEnabledBatch = $batches->first(fn (array $batch): bool => (bool) $batch['enabled'] && filled($batch['url']));

        return [
            'batches' => $batches->all(),
            'enabled' => $firstEnabledBatch !== null,
            'password' => (string) data_get($firstEnabledBatch, 'password', ''),
            'qr_svg' => (string) data_get($firstEnabledBatch, 'qr_svg', ''),
            'url' => (string) data_get($firstEnabledBatch, 'url', ''),
        ];
    }

    protected function reviewSubmissionToken(?Tour $existingTour = null): string
    {
        if (filled($existingTour?->review_submission_token)) {
            return (string) $existingTour->review_submission_token;
        }

        do {
            $token = Str::random(48);
        } while (Tour::query()->where('review_submission_token', $token)->exists());

        return $token;
    }

    protected function reviewBatchToken(?TourReviewBatch $existingBatch = null, ?string $incomingToken = null): string
    {
        if (filled($incomingToken)) {
            $tokenBelongsToAnotherBatch = TourReviewBatch::query()
                ->where('token', $incomingToken)
                ->when($existingBatch, fn ($query) => $query->where('id', '<>', $existingBatch->getKey()))
                ->exists();
            $tokenBelongsToAnotherTour = Tour::query()
                ->where('review_submission_token', $incomingToken)
                ->when($existingBatch, fn ($query) => $query->where('id', '<>', $existingBatch->tour_id))
                ->exists();

            if (! $tokenBelongsToAnotherBatch && ! $tokenBelongsToAnotherTour) {
                return (string) $incomingToken;
            }
        }

        if (filled($existingBatch?->token)) {
            return (string) $existingBatch->token;
        }

        do {
            $token = Str::random(48);
        } while (TourReviewBatch::query()->where('token', $token)->exists()
            || Tour::query()->where('review_submission_token', $token)->exists());

        return $token;
    }

    protected function defaultTaxonomyForm(): array
    {
        return [
            'name' => '',
            'slug' => '',
            'scope' => '',
            'country_id' => null,
            'is_country_root' => false,
            'show_tours_on_page' => true,
            'show_blogs_on_page' => false,
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
            'geo_config' => $this->defaultGeoConfigForm(),
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

    protected function normalizeReviewBatches(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row, int $index): array {
                return [
                    'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                    'tour_departure_id' => filled($row['tour_departure_id'] ?? null) ? (int) $row['tour_departure_id'] : null,
                    'departure_date' => filled($row['departure_date'] ?? null) ? $row['departure_date'] : null,
                    'label' => trim((string) ($row['label'] ?? '')),
                    'enabled' => (bool) ($row['enabled'] ?? false),
                    'password' => trim((string) ($row['password'] ?? '')),
                    'token' => trim((string) ($row['token'] ?? '')),
                    'sort_order' => filled($row['sort_order'] ?? null) ? (int) $row['sort_order'] : $index,
                ];
            })
            ->filter(function (array $row): bool {
                return filled($row['id'])
                    || filled($row['tour_departure_id'])
                    || filled($row['departure_date'])
                    || filled($row['label'])
                    || (bool) $row['enabled']
                    || filled($row['password'])
                    || filled($row['token']);
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
        $reviewBatches = $this->normalizeReviewBatches($form['review_batches'] ?? []);
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
                'review_submission_enabled' => collect($reviewBatches)->contains(fn (array $row): bool => (bool) $row['enabled']),
                'review_submission_password' => null,
                'review_submission_token' => $existingTour?->review_submission_token,
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
                ...$this->geoConfigPayload($form['geo_config'] ?? []),
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
        $deletedAgencyDepartures = $this->syncTourDepartures($tour, $departures);
        $firstReviewBatch = $this->syncReviewBatches($tour, $reviewBatches);

        $tour->forceFill([
            'review_submission_enabled' => $firstReviewBatch !== null,
            'review_submission_password' => $firstReviewBatch?->password,
            'review_submission_token' => $firstReviewBatch?->token,
        ])->saveQuietly();

        $this->dispatchAgencyPushSync($tour, $deletedAgencyDepartures);
        $this->avatarUpload = null;
        $this->galleryUploads = [];
        $this->editTour($tour->id);

        session()->flash('status', 'Đã lưu tour.');
        $this->redirectRoute('admin.tours.edit', ['tour' => $tour->getRouteKey()], navigate: true);
    }

    protected function saveTaxonomy(string $type): void
    {
        if ($type === 'destination') {
            $this->normalizeDestinationRootFormState();
        }

        $rules = $this->taxonomyValidationRules($type);
        $validated = $this->validate(
            $rules,
            $this->taxonomyValidationMessages($type),
            $this->taxonomyValidationAttributes($type),
        );
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
            ...$this->geoConfigPayload($form['geo_config'] ?? []),
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
            $isCountryRoot = (bool) ($form['is_country_root'] ?? false);

            if (! $isCountryRoot && blank($form['country_id'] ?? null)) {
                throw ValidationException::withMessages([
                    'destinationForm.country_id' => 'Mọi điểm đến phải thuộc một quốc gia root.',
                ]);
            }

            if ($isCountryRoot) {
                $payload['slug'] = Destination::countryRootSlug($payload['slug'] ?: $payload['name']);
            }

            $payload['country_id'] = $isCountryRoot ? null : ($form['country_id'] ?: null);
            $payload['is_country_root'] = $isCountryRoot;
            $payload['show_tours_on_page'] = (bool) ($form['show_tours_on_page'] ?? true);
            $payload['show_blogs_on_page'] = (bool) ($form['show_blogs_on_page'] ?? false);
            $payload['region_id'] = $isCountryRoot ? null : ($form['region_id'] ?: null);
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

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function syncTourDepartures(Tour $tour, array $departures): array
    {
        $existingIds = $tour->departures()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $incomingIds = collect($departures)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $deletedIds = array_diff($existingIds, $incomingIds);
        $deletedAgencyDepartures = TourDepartureSyncState::query()
            ->where('tour_id', $tour->getKey())
            ->whereIn('tour_departure_id', $deletedIds)
            ->get()
            ->map(fn (TourDepartureSyncState $state): array => [
                'cms_tour_id' => $tour->getKey(),
                'cms_departure_id' => $state->tour_departure_id,
                'tour_id' => $state->source_tour_id,
                'tour_code' => $state->tour_code,
                'startdate_id' => $state->source_startdate_id,
            ])
            ->values()
            ->all();

        foreach ($deletedIds as $departureId) {
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

        return $deletedAgencyDepartures;
    }

    protected function syncReviewBatches(Tour $tour, array $reviewBatches): ?TourReviewBatch
    {
        $existingIds = $tour->reviewBatches()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $incomingIds = collect($reviewBatches)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        foreach (array_diff($existingIds, $incomingIds) as $batchId) {
            TourReviewBatch::query()->whereKey($batchId)->delete();
        }

        foreach ($reviewBatches as $index => $reviewBatch) {
            $existing = $reviewBatch['id']
                ? $tour->reviewBatches()->whereKey($reviewBatch['id'])->first()
                : null;
            $departure = $reviewBatch['tour_departure_id']
                ? $tour->departures()->whereKey($reviewBatch['tour_departure_id'])->first()
                : null;

            $payload = [
                'departure_date' => $reviewBatch['departure_date'] ?: $departure?->departure_date?->toDateString(),
                'enabled' => (bool) $reviewBatch['enabled'],
                'label' => $reviewBatch['label'] ?: null,
                'password' => $reviewBatch['password'] !== '' ? $reviewBatch['password'] : null,
                'sort_order' => filled($reviewBatch['sort_order'] ?? null) ? (int) $reviewBatch['sort_order'] : $index,
                'token' => $this->reviewBatchToken($existing, $reviewBatch['token'] ?: null),
                'tour_departure_id' => $departure?->getKey(),
            ];

            if ($existing) {
                $existing->fill($payload)->save();

                continue;
            }

            $tour->reviewBatches()->create($payload);
        }

        return $tour->reviewBatches()->enabled()->ordered()->first();
    }

    /**
     * @param  array<string, mixed>  $sourceTour
     * @return array{tour_id: int, tour_code: string|null}
     */
    protected function rememberAgencyTargetTour(Tour $tour, array $sourceTour): array
    {
        $sourceTourId = is_numeric($sourceTour['tour_id'] ?? null) ? (int) $sourceTour['tour_id'] : null;

        if (! $sourceTourId) {
            throw new HaidangAgencyApiException('Tour đích API Master Data DashBoard chưa có ID để lưu mapping.');
        }

        $tourCode = trim((string) ($sourceTour['tour_code'] ?? ''));
        $tourCode = $tourCode !== '' ? Str::limit($tourCode, 255, '') : null;

        TourDepartureSyncState::query()->updateOrCreate(
            [
                'tour_id' => $tour->getKey(),
                'tour_departure_id' => null,
                'source_startdate_id' => null,
            ],
            [
                'source_tour_id' => $sourceTourId,
                'tour_code' => $tourCode,
                'source_checksum' => null,
                'last_synced_at' => now(),
            ],
        );

        return [
            'tour_id' => $sourceTourId,
            'tour_code' => $tourCode,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $deletedAgencyDepartures
     */
    protected function dispatchAgencyPushSync(Tour $tour, array $deletedAgencyDepartures): void
    {
        if (! (bool) config('tour_sync.push_enabled', true)) {
            return;
        }

        $run = app(TourAgencyPushSyncRunService::class)->enqueue(
            $tour,
            $deletedAgencyDepartures,
            'tour_saved',
            auth()->id(),
        );

        if (! $run) {
            return;
        }

        PushTourToAgencyJob::dispatch($tour->getKey(), $deletedAgencyDepartures, $run->getKey())
            ->onQueue((string) config('tour_sync.push_queue', 'default'));
        $this->logAgencyPushJobDispatched($tour, $run->getKey(), 'tour_saved');
    }

    protected function logAgencyPushJobDispatched(Tour $tour, int $runId, string $trigger): void
    {
        Log::info('tour_sync.master_data_dashboard.job_dispatched', [
            'source' => 'haidangtravel_cms',
            'target' => 'api_master_data_dashboard',
            'event' => 'job_dispatched',
            'run_id' => $runId,
            'cms_tour_id' => $tour->getKey(),
            'tour_title' => $tour->title,
            'trigger' => $trigger,
            'queue_name' => (string) config('tour_sync.push_queue', 'default'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $sourceTour
     * @param  array<string, mixed>  $extra
     */
    protected function logAgencyPullSyncFailed(
        Tour $tour,
        array $sourceTour,
        mixed $sourceTransport,
        mixed $sourceTransportField,
        mixed $sourceTransportCandidates,
        bool $sourceCatalogRefreshed,
        \Throwable $exception,
        string $reason,
        array $extra = [],
    ): void {
        Log::error('tour_sync.master_data_dashboard.pull_failed', [
            'source' => 'api_master_data_dashboard',
            'target' => 'haidangtravel_cms',
            'event' => 'pull_failed',
            'reason' => $reason,
            'cms_tour_id' => $tour->getKey(),
            'source_tour_id' => data_get($sourceTour, 'tour_id'),
            'tour_code' => data_get($sourceTour, 'tour_code'),
            'source_transport' => $sourceTransport,
            'source_transport_field' => $sourceTransportField,
            'source_transport_candidates' => $sourceTransportCandidates,
            'source_catalog_refreshed' => $sourceCatalogRefreshed,
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'direction' => 'agency_to_cms',
        ] + $extra);
    }

    protected function taxonomyValidationRules(string $type): array
    {
        $prefix = match ($type) {
            'category' => 'categoryForm',
            'destination' => 'destinationForm',
            'region' => 'regionForm',
            default => 'categoryForm',
        };

        return array_merge([
            $prefix.'.name' => ['required', 'string', 'max:255'],
            $prefix.'.slug' => ['nullable', 'string', 'max:255'],
            $prefix.'.scope' => ['nullable', Rule::in(array_map(fn (TourScope $scope) => $scope->value, TourScope::cases()))],
            $prefix.'.country_id' => array_values(array_filter([
                $type === 'destination' ? Rule::requiredIf(fn (): bool => ! (bool) data_get($this, $prefix.'.is_country_root')) : 'nullable',
                'nullable',
                'integer',
                Rule::exists('destinations', 'id')->where('is_country_root', true),
                $type === 'destination' && $this->editingDestinationId ? Rule::notIn([$this->editingDestinationId]) : null,
            ])),
            $prefix.'.is_country_root' => ['boolean'],
            $prefix.'.show_tours_on_page' => ['boolean'],
            $prefix.'.show_blogs_on_page' => ['boolean'],
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
        ], $this->geoValidationRules($prefix.'.geo_config'));
    }

    protected function taxonomyValidationAttributes(string $type): array
    {
        if ($type !== 'destination') {
            return [];
        }

        return [
            'destinationForm.country_id' => 'quốc gia root',
        ];
    }

    protected function taxonomyValidationMessages(string $type): array
    {
        if ($type !== 'destination') {
            return [];
        }

        return [
            'destinationForm.country_id.required' => 'Mọi điểm đến phải thuộc một quốc gia root.',
            'destinationForm.country_id.exists' => 'Quốc gia root được chọn không còn hợp lệ. Vui lòng chọn lại.',
            'destinationForm.country_id.not_in' => 'Điểm đến không thể tự chọn chính nó làm quốc gia root.',
        ];
    }

    protected function normalizeDestinationRootFormState(): void
    {
        if (! (bool) data_get($this->destinationForm, 'is_country_root')) {
            return;
        }

        $this->destinationForm['country_id'] = null;
        $this->destinationForm['region_id'] = null;
    }

    protected function tourValidationRules(): array
    {
        return array_merge([
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
            'form.review_submission_enabled' => ['boolean'],
            'form.review_submission_password' => ['nullable', 'string', 'max:120'],
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
            'form.review_batches' => ['nullable', 'array'],
            'form.review_batches.*.id' => ['nullable', 'integer'],
            'form.review_batches.*.tour_departure_id' => ['nullable', 'integer', 'exists:tour_departures,id'],
            'form.review_batches.*.departure_date' => ['nullable', 'date'],
            'form.review_batches.*.label' => ['nullable', 'string', 'max:255'],
            'form.review_batches.*.enabled' => ['boolean'],
            'form.review_batches.*.password' => ['nullable', 'string', 'max:120'],
            'form.review_batches.*.token' => ['nullable', 'string', 'max:64'],
            'form.review_batches.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'galleryUploads.*' => ['nullable', 'image', 'max:4096'],
        ], $this->geoValidationRules('form.geo_config'));
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
