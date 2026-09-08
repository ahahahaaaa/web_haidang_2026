<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Cms\Concerns\HandlesMediaUploads;
use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use App\Support\SliderAnimationEffects;
use App\Support\SliderLocations;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Src\Domains\Cms\Models\Slider;
use Src\Domains\Cms\Models\SliderItem;

#[Layout('layouts.app')]
#[Title('Quản lý slider')]
class SlidersManager extends Component
{
    use AuthorizesAdminPermissions;
    use HandlesMediaUploads;
    use InteractsWithEditorContent;
    use WithFileUploads;
    use WithPagination;

    public ?string $currentRouteName = null;

    public ?int $editingItemId = null;

    public array $itemForm = [];

    public mixed $itemImageUpload = null;

    public mixed $itemInnerImageUpload = null;

    public mixed $itemMobileImageUpload = null;

    public string $search = '';

    public ?int $selectedSliderId = null;

    public string $statusFilter = '';

    public array $sliderForm = [];

    public function mount(?Slider $slider = null): void
    {
        $this->currentRouteName = request()->route()?->getName();
        $this->ensureDefaultSlider();
        $this->resetItemForm();
        $this->resetSliderForm();

        if ($this->currentRouteName === 'admin.sliders.create') {
            return;
        }

        if ($this->currentRouteName === 'admin.sliders.edit' && $slider) {
            $this->selectedSliderId = (int) $slider->getKey();
            $this->selectSlider((int) $this->selectedSliderId);

            return;
        }

        if (blank($this->currentRouteName)) {
            $defaultSliderId = Slider::query()->orderBy('id')->value('id');

            if ($defaultSliderId) {
                $this->selectedSliderId = (int) $defaultSliderId;
                $this->selectSlider((int) $defaultSliderId);
            }
        }
    }

    public function createSlider(): void
    {
        $this->authorizeAdminPermission('admin.sliders.edit');

        $this->selectedSliderId = null;
        $this->resetSliderForm();
        $this->resetItemForm();
    }

    public function deleteItem(int $id): void
    {
        $this->authorizeAdminPermission('admin.sliders.edit');

        SliderItem::query()->findOrFail($id)->delete();
        session()->flash('status', 'Đã xóa slider item.');
    }

    public function deleteSlider(int $id): void
    {
        $this->authorizeAdminPermission('admin.sliders.edit');

        Slider::query()->findOrFail($id)->delete();
        $this->selectedSliderId = Slider::query()->orderBy('id')->value('id');
        $this->resetSliderForm();
        $this->resetItemForm();
        session()->flash('status', 'Đã xóa slider.');
    }

    public function editItem(int $id): void
    {
        $this->authorizeAdminPermission('admin.sliders.edit');

        $item = SliderItem::query()->findOrFail($id);
        $desktopImageMedia = $item->getFirstMedia('image');
        $mobileImageMedia = $item->getFirstMedia('mobile_image');
        $innerImageMedia = $item->getFirstMedia('inner_image');

        $this->editingItemId = $item->id;
        $this->itemImageUpload = null;
        $this->itemMobileImageUpload = null;
        $this->itemInnerImageUpload = null;
        $this->selectedLibraryMediaSelections = array_filter([
            'itemImageUpload' => $desktopImageMedia
                ? (int) data_get($desktopImageMedia->custom_properties, 'source_library_media_id')
                : null,
            'itemMobileImageUpload' => $mobileImageMedia
                ? (int) data_get($mobileImageMedia->custom_properties, 'source_library_media_id')
                : null,
            'itemInnerImageUpload' => $innerImageMedia
                ? (int) data_get($innerImageMedia->custom_properties, 'source_library_media_id')
                : null,
        ]);
        $this->itemForm = [
            'title' => $item->title,
            'subtitle' => $item->subtitle,
            'description' => $item->description,
            'image_alt' => $item->image_alt,
            'image_link' => $item->image_link,
            'video_url' => $item->video_url,
            'effect' => $item->effect,
            'primary_label' => $item->primary_label ?: $item->cta_label,
            'primary_url' => $item->primary_url ?: $item->cta_url,
            'secondary_label' => $item->secondary_label,
            'secondary_url' => $item->secondary_url,
            'cta_label' => $item->cta_label,
            'cta_url' => $item->cta_url,
            'order' => $item->order,
            'is_active' => $item->is_active,
            'show_inner_media' => $item->show_inner_media,
            'show_overlay' => $item->show_overlay,
        ];
    }

    public function render()
    {
        $selectedSlider = $this->selectedSliderId
            ? Slider::query()
                ->with(['items' => fn ($query) => $query->with('media')->orderBy('order')])
                ->find($this->selectedSliderId)
            : null;
        $selectedUploadMedia = $this->resolveSelectedUploadMediaPayload([
            'itemImageUpload',
            'itemMobileImageUpload',
            'itemInnerImageUpload',
        ]);

        return view($this->resolveView(), [
            'editingItem' => $this->editingItemId ? SliderItem::query()->with('media')->find($this->editingItemId) : null,
            'effectGroups' => SliderAnimationEffects::groupedOptions(),
            'selectedInnerLibraryMedia' => $selectedUploadMedia['itemInnerImageUpload'] ?? null,
            'selectedLibraryMedia' => $selectedUploadMedia['itemImageUpload'] ?? null,
            'selectedMobileLibraryMedia' => $selectedUploadMedia['itemMobileImageUpload'] ?? null,
            'selectedSlider' => $selectedSlider,
            'sliderLocationOptions' => SliderLocations::options(),
            'sliders' => Slider::query()
                ->withCount('items')
                ->when($this->search !== '', function ($query) {
                    $query->where(function ($nested) {
                        $nested
                            ->where('name', 'like', '%'.$this->search.'%')
                            ->orWhere('location', 'like', '%'.$this->search.'%');
                    });
                })
                ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
                ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
                ->orderBy('name')
                ->paginate(10),
        ]);
    }

    public function clearLibraryMediaSelection(): void
    {
        $this->authorizeAdminPermission('admin.sliders.edit');

        $this->clearLibraryMediaSelectionForUpload('itemImageUpload');
    }

    public function saveItem(): void
    {
        $this->authorizeAdminPermission('admin.sliders.edit');

        $validated = $this->validate([
            'selectedSliderId' => ['required', 'exists:sliders,id'],
            'itemForm.title' => ['nullable', 'string', 'max:255'],
            'itemForm.subtitle' => ['nullable', 'string', 'max:255'],
            'itemForm.description' => ['nullable', 'string'],
            'itemForm.image_alt' => ['nullable', 'string', 'max:255'],
            'itemForm.image_link' => ['nullable', 'string', 'max:255'],
            'itemForm.video_url' => ['nullable', 'string', 'max:500'],
            'itemForm.effect' => ['required', 'string', 'max:255', Rule::in(SliderAnimationEffects::values())],
            'itemForm.primary_label' => ['nullable', 'string', 'max:255'],
            'itemForm.primary_url' => ['nullable', 'string', 'max:255'],
            'itemForm.secondary_label' => ['nullable', 'string', 'max:255'],
            'itemForm.secondary_url' => ['nullable', 'string', 'max:255'],
            'itemForm.cta_label' => ['nullable', 'string', 'max:255'],
            'itemForm.cta_url' => ['nullable', 'string', 'max:255'],
            'itemForm.order' => ['nullable', 'integer', 'min:0'],
            'itemForm.is_active' => ['boolean'],
            'itemForm.show_overlay' => ['boolean'],
            'itemForm.show_inner_media' => ['boolean'],
        ]);

        $item = SliderItem::query()->updateOrCreate(
            ['id' => $this->editingItemId],
            [
                'slider_id' => $validated['selectedSliderId'],
                'title' => $validated['itemForm']['title'],
                'subtitle' => $validated['itemForm']['subtitle'],
                'description' => $this->plainEditorContent($validated['itemForm']['description'] ?? null),
                'image_alt' => $validated['itemForm']['image_alt'],
                'image_link' => $validated['itemForm']['image_link'],
                'video_url' => $validated['itemForm']['video_url'],
                'effect' => $validated['itemForm']['effect'],
                'primary_label' => $validated['itemForm']['primary_label'] ?: $validated['itemForm']['cta_label'],
                'primary_url' => $validated['itemForm']['primary_url'] ?: $validated['itemForm']['cta_url'],
                'secondary_label' => $validated['itemForm']['secondary_label'],
                'secondary_url' => $validated['itemForm']['secondary_url'],
                'cta_label' => $validated['itemForm']['primary_label'] ?: $validated['itemForm']['cta_label'],
                'cta_url' => $validated['itemForm']['primary_url'] ?: $validated['itemForm']['cta_url'],
                'order' => $validated['itemForm']['order'] ?? 0,
                'is_active' => $validated['itemForm']['is_active'] ?? true,
                'show_inner_media' => $validated['itemForm']['show_inner_media'] ?? true,
                'show_overlay' => $validated['itemForm']['show_overlay'] ?? true,
            ],
        );

        $this->syncSingleImageSelection($item, 'itemImageUpload', 'image', [
            'alt' => $validated['itemForm']['image_alt'] ?? null,
        ]);
        $this->syncSingleImageSelection($item, 'itemMobileImageUpload', 'mobile_image', [
            'alt' => $validated['itemForm']['image_alt'] ?? null,
        ]);
        $this->syncSingleImageSelection($item, 'itemInnerImageUpload', 'inner_image', [
            'alt' => $validated['itemForm']['image_alt'] ?? null,
        ]);

        $this->itemImageUpload = null;
        $this->itemMobileImageUpload = null;
        $this->itemInnerImageUpload = null;
        $this->editItem($item->id);

        session()->flash('status', 'Đã lưu slider item.');
    }

    public function selectLibraryMedia(int $mediaId, ?string $alt = null): void
    {
        $this->authorizeAdminPermission('admin.sliders.edit');

        $this->selectLibraryMediaForUpload('itemImageUpload', $mediaId, $alt, 'itemForm.image_alt');
    }

    public function saveSlider(): void
    {
        $this->authorizeAdminPermission('admin.sliders.edit');

        $validated = $this->validate([
            'sliderForm.name' => ['required', 'string', 'max:255'],
            'sliderForm.location' => ['nullable', 'string', 'max:255'],
            'sliderForm.description' => ['nullable', 'string'],
            'sliderForm.is_active' => ['boolean'],
            'sliderForm.autoplay_delay' => ['nullable', 'integer', 'min:0'],
        ]);

        $slider = Slider::query()->updateOrCreate(
            ['id' => $this->selectedSliderId],
            [
                'name' => $validated['sliderForm']['name'],
                'location' => $validated['sliderForm']['location'] ? Str::slug($validated['sliderForm']['location']) : null,
                'description' => $this->plainEditorContent($validated['sliderForm']['description'] ?? null),
                'is_active' => $validated['sliderForm']['is_active'] ?? true,
                'autoplay_delay' => $validated['sliderForm']['autoplay_delay'],
            ],
        );

        $this->selectedSliderId = $slider->id;
        $this->selectSlider($slider->id);

        session()->flash('status', 'Đã lưu slider.');
        $this->redirectRoute('admin.sliders.edit', ['slider' => $slider], navigate: true);
    }

    public function selectSlider(int $id): void
    {
        $slider = Slider::query()->findOrFail($id);

        $this->selectedSliderId = $slider->id;
        $this->sliderForm = [
            'name' => $slider->name,
            'location' => $slider->location,
            'description' => $slider->description,
            'is_active' => $slider->is_active,
            'autoplay_delay' => $slider->autoplay_delay,
        ];
        $this->resetItemForm();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function ensureDefaultSlider(): void
    {
        Slider::query()->firstOrCreate(
            ['location' => 'home-hero'],
            [
                'name' => 'Home Hero Slider',
                'is_active' => true,
                'autoplay_delay' => 5000,
            ],
        );
    }

    protected function resetItemForm(): void
    {
        $this->editingItemId = null;
        $this->itemImageUpload = null;
        $this->itemMobileImageUpload = null;
        $this->itemInnerImageUpload = null;
        $this->selectedLibraryMediaSelections = [];
        $this->itemForm = [
            'title' => '',
            'subtitle' => '',
            'description' => '',
            'image_alt' => '',
            'image_link' => '',
            'video_url' => '',
            'effect' => 'animate__fadeInUp',
            'primary_label' => '',
            'primary_url' => '',
            'secondary_label' => '',
            'secondary_url' => '',
            'cta_label' => '',
            'cta_url' => '',
            'order' => $this->defaultItemOrder(),
            'is_active' => true,
            'show_inner_media' => true,
            'show_overlay' => true,
        ];
    }

    protected function defaultItemOrder(): int
    {
        if (! $this->selectedSliderId) {
            return 0;
        }

        return ((int) SliderItem::query()
            ->where('slider_id', $this->selectedSliderId)
            ->max('order')) + 1;
    }

    protected function resetSliderForm(): void
    {
        $this->sliderForm = [
            'name' => '',
            'location' => '',
            'description' => '',
            'is_active' => true,
            'autoplay_delay' => 5000,
        ];
    }

    protected function resolveView(): string
    {
        return $this->currentRouteName === 'admin.sliders'
            ? 'livewire.admin.cms.sliders.index'
            : 'livewire.admin.cms.sliders-manager';
    }
}
