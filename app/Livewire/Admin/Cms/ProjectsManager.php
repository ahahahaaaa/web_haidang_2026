<?php

namespace App\Livewire\Admin\Cms;

use App\Support\ContentGallery;
use App\Support\FaqContent;
use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use App\Livewire\Admin\Cms\Concerns\HandlesMediaUploads;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Project;
use Src\Domains\Cms\Models\ProjectType;

#[Layout('layouts.app')]
#[Title('Quản lý dự án')]
class ProjectsManager extends Component
{
    use InteractsWithEditorContent;
    use HandlesMediaUploads;
    use WithFileUploads;
    use WithPagination;

    public array $categoryForm = [];

    public mixed $coverUpload = null;

    public ?int $editingCategoryId = null;

    public ?int $editingTypeId = null;

    public array $form = [];

    public array $galleryUploads = [];

    public string $locationFilter = '';

    public string $search = '';

    public ?string $currentRouteName = null;

    public array $selectedGalleryLibraryMediaIds = [];

    public ?int $selectedLibraryCoverMediaId = null;

    public ?int $selectedId = null;

    public array $typeForm = [];

    public function mount(?Project $project = null, ?ContentCategory $category = null, ?ProjectType $type = null): void
    {
        $this->currentRouteName = request()->route()?->getName();
        $this->resetCategoryForm();
        $this->resetProjectForm();
        $this->resetTypeForm();
        $this->initializeRouteState($project?->getKey(), $category?->getKey(), $type?->getKey());
    }

    public function createProject(): void
    {
        $this->selectedId = null;
        $this->coverUpload = null;
        $this->galleryUploads = [];
        $this->selectedGalleryLibraryMediaIds = [];
        $this->selectedLibraryCoverMediaId = null;
        $this->resetProjectForm();
    }

    public function addGalleryItem(string $type = ContentGallery::TYPE_IMAGE): void
    {
        $this->form['gallery'][] = ContentGallery::defaultItem($type);
    }

    public function addFaqItem(): void
    {
        $this->form['faq_items'][] = FaqContent::blankItem();
    }

    public function addRelatedQuestion(): void
    {
        $this->form['related_questions'][] = '';
    }

    public function deleteCategory(int $id): void
    {
        $category = ContentCategory::query()->findOrFail($id);

        if ($category->projects()->exists()) {
            $this->addError('categoryForm.name', 'Không thể xóa danh mục đang có dự án.');

            return;
        }

        $category->delete();
        session()->flash('status', 'Đã xóa danh mục dự án.');

        if ($this->editingCategoryId === $id || $this->isCategoryEditorRoute()) {
            $this->redirectRoute('admin.projects.categories', navigate: true);
        }
    }

    public function deleteProject(int $id): void
    {
        Project::query()->findOrFail($id)->delete();

        if ($this->selectedId === $id) {
            $this->createProject();
        }

        session()->flash('status', 'Đã xóa dự án.');

        if ($this->selectedId === null && $this->isProjectEditorRoute()) {
            $this->redirectRoute('admin.projects', navigate: true);
        }
    }

    public function deleteType(int $id): void
    {
        $type = ProjectType::query()->findOrFail($id);

        if ($type->projects()->exists()) {
            $this->addError('typeForm.name', 'Không thể xóa loại đang có dự án.');

            return;
        }

        $type->delete();
        session()->flash('status', 'Đã xóa loại dự án.');

        if ($this->editingTypeId === $id || $this->isTypeEditorRoute()) {
            $this->redirectRoute('admin.projects.types', navigate: true);
        }
    }

    public function editCategory(int $id): void
    {
        $category = ContentCategory::query()->findOrFail($id);

        $this->editingCategoryId = $category->id;
        $this->categoryForm = [
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'sort_order' => $category->sort_order,
        ];
    }

    public function editProject(int $id): void
    {
        $project = Project::query()->findOrFail($id);
        $coverMedia = $project->getFirstMedia('cover');

        $this->selectedId = $project->id;
        $this->coverUpload = null;
        $this->galleryUploads = [];
        $this->selectedLibraryCoverMediaId = $coverMedia
            ? (int) data_get($coverMedia->custom_properties, 'source_library_media_id')
            : null;
        $this->selectedGalleryLibraryMediaIds = $this->resolveSelectedLibraryMediaIds(
            $project,
            $project->gallery ?? [],
            fn (string $uuid) => ContentGallery::projectCollection($uuid),
        );
        $this->form = [
            'title' => $project->title,
            'slug' => $project->slug,
            'excerpt' => $project->excerpt,
            'content' => $project->content,
            'status' => $project->status,
            'content_category_id' => $project->content_category_id,
            'project_type_id' => $project->project_type_id,
            'location' => $project->location,
            'area_value' => $project->area_value,
            'area_unit' => $project->area_unit,
            'timeline' => $project->timeline,
            'completion_date' => optional($project->completion_date)?->format('Y-m-d'),
            'is_featured' => $project->is_featured,
            'cover_alt' => $project->cover_alt,
            'meta_title' => $project->meta_title,
            'meta_description' => $project->meta_description,
            'og_title' => $project->og_title,
            'og_description' => $project->og_description,
            'canonical_url' => $project->canonical_url,
            'robots_directive' => $project->robots_directive,
            'faq_items' => FaqContent::prepareItems($project->faq_items),
            'gallery' => ContentGallery::prepare($project->gallery),
            'related_questions' => FaqContent::prepareQuestions($project->related_questions),
        ];

        if ($this->form['faq_items'] === []) {
            $this->form['faq_items'][] = FaqContent::blankItem();
        }

        if ($this->form['related_questions'] === []) {
            $this->form['related_questions'][] = '';
        }
    }

    public function editType(int $id): void
    {
        $type = ProjectType::query()->findOrFail($id);

        $this->editingTypeId = $type->id;
        $this->typeForm = [
            'name' => $type->name,
            'slug' => $type->slug,
            'description' => $type->description,
            'sort_order' => $type->sort_order,
        ];
    }

    public function render()
    {
        return view($this->resolveView(), [
            'categories' => ContentCategory::query()->forTaxonomy('project')->orderBy('sort_order')->get(),
            'editingProject' => $this->isProjectEditorRoute() && $this->selectedId
                ? Project::query()->find($this->selectedId)
                : null,
            'selectedGalleryLibraryMedia' => $this->isProjectEditorRoute()
                ? $this->resolveSelectedMediaPayload($this->selectedGalleryLibraryMediaIds)
                : [],
            'selectedLibraryCoverMedia' => $this->isProjectEditorRoute() && $this->selectedLibraryCoverMediaId
                ? Media::query()->whereKey($this->selectedLibraryCoverMediaId)->where('mime_type', 'like', 'image/%')->first()
                : null,
            'projects' => Project::query()
                ->with(['category', 'type'])
                ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
                ->when($this->locationFilter !== '', fn ($query) => $query->where('location', 'like', '%'.$this->locationFilter.'%'))
                ->orderByDesc('updated_at')
                ->paginate(8),
            'types' => ProjectType::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function clearCoverLibraryMediaSelection(): void
    {
        $this->selectedLibraryCoverMediaId = null;
    }

    public function clearGalleryLibraryMediaSelection(string $uuid): void
    {
        unset($this->selectedGalleryLibraryMediaIds[$uuid]);
    }

    public function saveCategory(): void
    {
        $validated = $this->validate([
            'categoryForm.name' => ['required', 'string', 'max:255'],
            'categoryForm.slug' => ['nullable', 'string', 'max:255'],
            'categoryForm.description' => ['nullable', 'string'],
            'categoryForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category = ContentCategory::query()->updateOrCreate(
            ['id' => $this->editingCategoryId],
            [
                'taxonomy' => 'project',
                'name' => $validated['categoryForm']['name'],
                'slug' => Str::slug($validated['categoryForm']['slug'] ?: $validated['categoryForm']['name']),
                'description' => $this->plainEditorContent($validated['categoryForm']['description'] ?? null),
                'sort_order' => $validated['categoryForm']['sort_order'] ?? 0,
            ],
        );

        session()->flash('status', 'Đã lưu danh mục dự án.');
        $this->redirectRoute('admin.projects.categories.edit', ['category' => $category->getKey()], navigate: true);
    }

    public function saveProject(): void
    {
        $validated = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => ['nullable', 'string', 'max:255'],
            'form.excerpt' => ['nullable', 'string'],
            'form.content' => ['nullable', 'string'],
            'form.status' => ['required', 'string', 'max:50'],
            'form.content_category_id' => ['nullable', 'exists:content_categories,id'],
            'form.project_type_id' => ['nullable', 'exists:project_types,id'],
            'form.location' => ['nullable', 'string', 'max:255'],
            'form.area_value' => ['nullable', 'numeric', 'min:0'],
            'form.area_unit' => ['nullable', 'string', 'max:20'],
            'form.timeline' => ['nullable', 'string', 'max:255'],
            'form.completion_date' => ['nullable', 'date'],
            'form.is_featured' => ['boolean'],
            'form.cover_alt' => ['nullable', 'string', 'max:255'],
            'form.meta_title' => ['nullable', 'string', 'max:255'],
            'form.meta_description' => ['nullable', 'string', 'max:500'],
            'form.og_title' => ['nullable', 'string', 'max:255'],
            'form.og_description' => ['nullable', 'string', 'max:500'],
            'form.canonical_url' => ['nullable', 'url'],
            'form.robots_directive' => ['nullable', 'string', 'max:255'],
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
            'galleryUploads.*' => ['nullable', 'image', 'max:4096'],
        ]);

        $faqItems = FaqContent::normalizeItems($validated['form']['faq_items'] ?? []);
        $gallery = ContentGallery::normalize($validated['form']['gallery'] ?? []);
        $existingProject = $this->selectedId ? Project::query()->find($this->selectedId) : null;
        $oldGallery = $existingProject?->gallery ?? [];
        $relatedQuestions = FaqContent::normalizeQuestions($validated['form']['related_questions'] ?? []);

        foreach ($gallery as $index => $item) {
            if (($item['type'] ?? ContentGallery::TYPE_IMAGE) === ContentGallery::TYPE_IMAGE) {
                continue;
            }

            if (blank($item['video_url'] ?? null)) {
                $this->addError("form.gallery.{$index}.video_url", 'Vui lòng nhập URL video hợp lệ.');

                continue;
            }

            if (
                $item['type'] === ContentGallery::TYPE_YOUTUBE
                && ! ContentGallery::youtubeId($item['video_url'])
            ) {
                $this->addError("form.gallery.{$index}.video_url", 'URL YouTube chưa đúng định dạng hỗ trợ.');
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $project = Project::query()->updateOrCreate(
            ['id' => $this->selectedId],
            [
                'title' => $validated['form']['title'],
                'slug' => Str::slug($validated['form']['slug'] ?: $validated['form']['title']),
                'excerpt' => $this->plainEditorContent($validated['form']['excerpt'] ?? null),
                'content' => $this->richEditorContent($validated['form']['content'] ?? null),
                'status' => $validated['form']['status'],
                'content_category_id' => $validated['form']['content_category_id'],
                'project_type_id' => $validated['form']['project_type_id'],
                'location' => $validated['form']['location'],
                'area_value' => filled($validated['form']['area_value'] ?? null)
                    ? $validated['form']['area_value']
                    : null,
                'area_unit' => $validated['form']['area_unit'] ?: 'm2',
                'timeline' => $validated['form']['timeline'],
                'completion_date' => $validated['form']['completion_date'],
                'is_featured' => $validated['form']['is_featured'] ?? false,
                'cover_alt' => $validated['form']['cover_alt'],
                'meta_title' => $validated['form']['meta_title'],
                'meta_description' => $this->plainEditorContent($validated['form']['meta_description'] ?? null),
                'og_title' => $validated['form']['og_title'],
                'og_description' => $this->plainEditorContent($validated['form']['og_description'] ?? null),
                'canonical_url' => $validated['form']['canonical_url'],
                'robots_directive' => $validated['form']['robots_directive'] ?: 'index,follow',
                'faq_items' => $faqItems,
                'gallery' => $gallery,
                'related_questions' => $relatedQuestions,
            ],
        );

        $this->selectedId = $project->id;
        $uploadedNewCover = $this->syncSingleImage($project, 'coverUpload', 'cover', [
            'alt' => $validated['form']['cover_alt'] ?? null,
        ]);

        if (! $uploadedNewCover) {
            $this->syncSingleImageFromLibrary($project, $this->selectedLibraryCoverMediaId, 'cover', [
                'alt' => $validated['form']['cover_alt'] ?? null,
            ]);
        }

        $this->syncGalleryMedia($project, $oldGallery, $gallery);
        $this->coverUpload = null;
        $this->galleryUploads = [];
        $this->editProject($project->id);

        session()->flash('status', 'Đã lưu dự án.');
        $this->redirectRoute('admin.projects.edit', ['project' => $project->getRouteKey()], navigate: true);
    }

    public function saveType(): void
    {
        $validated = $this->validate([
            'typeForm.name' => ['required', 'string', 'max:255'],
            'typeForm.slug' => ['nullable', 'string', 'max:255'],
            'typeForm.description' => ['nullable', 'string'],
            'typeForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $type = ProjectType::query()->updateOrCreate(
            ['id' => $this->editingTypeId],
            [
                'name' => $validated['typeForm']['name'],
                'slug' => Str::slug($validated['typeForm']['slug'] ?: $validated['typeForm']['name']),
                'description' => $this->plainEditorContent($validated['typeForm']['description'] ?? null),
                'sort_order' => $validated['typeForm']['sort_order'] ?? 0,
            ],
        );

        session()->flash('status', 'Đã lưu loại dự án.');
        $this->redirectRoute('admin.projects.types.edit', ['type' => $type->getKey()], navigate: true);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function selectCoverLibraryMedia(int $mediaId, ?string $alt = null): void
    {
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
        $this->form['gallery'][$index]['image_alt'] = trim((string) $alt) !== ''
            ? trim((string) $alt)
            : ((string) data_get($media->custom_properties, 'alt', '') ?: $media->name);
    }

    public function removeFaqItem(int $index): void
    {
        $this->removeIndexedValue('form.faq_items', $index);

        if (($this->form['faq_items'] ?? []) === []) {
            $this->form['faq_items'][] = FaqContent::blankItem();
        }
    }

    public function removeRelatedQuestion(int $index): void
    {
        $this->removeIndexedValue('form.related_questions', $index);

        if (($this->form['related_questions'] ?? []) === []) {
            $this->form['related_questions'][] = '';
        }
    }

    protected function resetCategoryForm(): void
    {
        $this->editingCategoryId = null;
        $this->categoryForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'sort_order' => 0,
        ];
    }

    protected function resetProjectForm(): void
    {
        $this->selectedLibraryCoverMediaId = null;
        $this->selectedGalleryLibraryMediaIds = [];
        $this->form = [
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'content' => '',
            'status' => 'draft',
            'content_category_id' => null,
            'project_type_id' => null,
            'location' => '',
            'area_value' => '',
            'area_unit' => 'm2',
            'timeline' => '',
            'completion_date' => '',
            'is_featured' => false,
            'cover_alt' => '',
            'meta_title' => '',
            'meta_description' => '',
            'og_title' => '',
            'og_description' => '',
            'canonical_url' => '',
            'robots_directive' => 'index,follow',
            'faq_items' => [FaqContent::blankItem()],
            'gallery' => [],
            'related_questions' => [''],
        ];
    }

    protected function resetTypeForm(): void
    {
        $this->editingTypeId = null;
        $this->typeForm = [
            'name' => '',
            'slug' => '',
            'description' => '',
            'sort_order' => 0,
        ];
    }

    protected function syncGalleryMedia(Project $project, array $oldGallery, array $newGallery): void
    {
        $oldUuids = collect($oldGallery)->pluck('uuid')->filter()->values()->all();
        $newUuids = collect($newGallery)->pluck('uuid')->filter()->values()->all();

        foreach (array_diff($oldUuids, $newUuids) as $uuid) {
            $project->clearMediaCollection(ContentGallery::projectCollection($uuid));
        }

        foreach ($newGallery as $index => $item) {
            if (($item['type'] ?? ContentGallery::TYPE_IMAGE) !== ContentGallery::TYPE_IMAGE) {
                continue;
            }

            $collection = ContentGallery::projectCollection($item['uuid']);
            $uploadedImage = $this->galleryUploads[$index] ?? null;

            if ($uploadedImage) {
                $this->syncUploadedImage(
                    $project,
                    $uploadedImage,
                    $collection,
                    ['alt' => $item['image_alt'] ?: $item['title'] ?: $project->title],
                );

                continue;
            }

            $this->syncSingleImageFromLibrary(
                $project,
                $this->selectedGalleryLibraryMediaIds[$item['uuid']] ?? null,
                $collection,
                ['alt' => $item['image_alt'] ?: $item['title'] ?: $project->title],
            );
        }
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

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  callable(string): string  $collectionResolver
     * @return array<string, int>
     */
    protected function resolveSelectedLibraryMediaIds(Project $project, array $items, callable $collectionResolver): array
    {
        $selectedIds = [];

        foreach ($items as $item) {
            $uuid = (string) data_get($item, 'uuid', '');

            if ($uuid === '') {
                continue;
            }

            $media = $project->getFirstMedia($collectionResolver($uuid));
            $sourceLibraryMediaId = (int) data_get($media?->custom_properties, 'source_library_media_id', 0);

            if ($sourceLibraryMediaId > 0) {
                $selectedIds[$uuid] = $sourceLibraryMediaId;
            }
        }

        return $selectedIds;
    }

    /**
     * @param  array<string, int>  $selectedIds
     * @return array<string, \Spatie\MediaLibrary\MediaCollections\Models\Media>
     */
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

    protected function removeIndexedValue(string $path, int $index): void
    {
        $values = data_get($this, $path, []);

        if (! is_array($values) || ! array_key_exists($index, $values)) {
            return;
        }

        unset($values[$index]);
        data_set($this, $path, array_values($values));
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

    protected function initializeRouteState(?int $projectId = null, ?int $categoryId = null, ?int $typeId = null): void
    {
        if ($this->isProjectEditorRoute()) {
            $projectId ??= $this->resolveRouteKey('project');

            if ($projectId) {
                $this->editProject($projectId);
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

        if ($this->isTypeEditorRoute()) {
            $typeId ??= $this->resolveRouteKey('type');

            if ($typeId) {
                $this->editType($typeId);
            }
        }
    }

    protected function isProjectIndexRoute(): bool
    {
        return $this->currentRouteName === null || $this->currentRouteName === 'admin.projects';
    }

    protected function isProjectEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.projects.create', 'admin.projects.edit'], true);
    }

    protected function isCategoryEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.projects.categories.create', 'admin.projects.categories.edit'], true);
    }

    protected function isTypeEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.projects.types.create', 'admin.projects.types.edit'], true);
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

    protected function resolveView(): string
    {
        return match (true) {
            $this->isProjectIndexRoute() => 'livewire.admin.cms.projects.index',
            $this->isProjectEditorRoute() => 'livewire.admin.cms.projects.editor',
            $this->currentRouteName === 'admin.projects.categories' => 'livewire.admin.cms.projects.categories-index',
            $this->isCategoryEditorRoute() => 'livewire.admin.cms.projects.categories-editor',
            $this->currentRouteName === 'admin.projects.types' => 'livewire.admin.cms.projects.types-index',
            $this->isTypeEditorRoute() => 'livewire.admin.cms.projects.types-editor',
            default => 'livewire.admin.cms.projects.index',
        };
    }
}
