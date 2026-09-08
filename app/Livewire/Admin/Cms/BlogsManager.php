<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Cms\Concerns\HandlesGeoConfig;
use App\Livewire\Admin\Cms\Concerns\HandlesMediaUploads;
use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use App\Support\ContentCategoryTree;
use App\Support\FaqContent;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\ContentCategory;
use Src\Domains\Cms\Models\Destination;

#[Layout('layouts.app')]
#[Title('Quản lý blog')]
class BlogsManager extends Component
{
    use AuthorizesAdminPermissions;
    use HandlesGeoConfig;
    use HandlesMediaUploads;
    use InteractsWithEditorContent;
    use WithFileUploads;
    use WithPagination;

    public mixed $avatarUpload = null;

    public array $categoryForm = [];

    public string $categoryFilter = '';

    public string $categorySearch = '';

    public mixed $coverUpload = null;

    public ?string $coverUploadOriginalName = null;

    public ?int $editingCategoryId = null;

    public array $form = [];

    public ?string $currentRouteName = null;

    public ?int $selectedLibraryAvatarMediaId = null;

    public ?int $selectedLibraryCoverMediaId = null;

    public string $search = '';

    public ?int $selectedId = null;

    public string $status = '';

    public function mount(): void
    {
        $this->currentRouteName = request()->route()?->getName();
        $this->resetCategoryForm();
        $this->resetPostForm();
        $this->initializeRouteState();
    }

    public function createPost(): void
    {
        $this->authorizeAdminPermission('admin.blogs.edit');

        $this->selectedId = null;
        $this->coverUpload = null;
        $this->coverUploadOriginalName = null;
        $this->selectedLibraryCoverMediaId = null;
        $this->resetPostForm();
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

    public function deleteCategory(int $id): void
    {
        $this->authorizeAdminPermission('admin.blogs.categories.edit');

        $category = ContentCategory::query()->forTaxonomy('blog')->findOrFail($id);

        if ($category->children()->exists()) {
            $this->addError('categoryForm.parent_id', 'Không thể xóa danh mục đang có danh mục con.');

            return;
        }

        if ($category->blogPosts()->exists()) {
            $this->addError('categoryForm.name', 'Không thể xóa danh mục đang có bài viết.');

            return;
        }

        $category->delete();
        session()->flash('status', 'Đã xóa danh mục blog.');

        if ($this->editingCategoryId === $id || $this->isCategoryEditorRoute()) {
            $this->redirectRoute('admin.blogs.categories', navigate: true);
        }
    }

    public function deletePost(int $id): void
    {
        $this->authorizeAdminPermission('admin.blogs.edit');

        BlogPost::query()->findOrFail($id)->delete();

        if ($this->selectedId === $id) {
            $this->createPost();
        }

        session()->flash('status', 'Đã xóa bài viết.');

        if ($this->selectedId === null && $this->isPostEditorRoute()) {
            $this->redirectRoute('admin.blogs', navigate: true);
        }
    }

    public function editCategory(int $id): void
    {
        $category = ContentCategory::query()->forTaxonomy('blog')->with('parent')->findOrFail($id);
        $avatarMedia = $category->getFirstMedia('avatar');

        $this->editingCategoryId = $category->id;
        $this->avatarUpload = null;
        $this->selectedLibraryAvatarMediaId = $avatarMedia
            ? (int) data_get($avatarMedia->custom_properties, 'source_library_media_id')
            : null;
        $this->categoryForm = [
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'description' => $category->description,
            'geo_config' => $this->defaultGeoConfigForm($category->geo_config),
            'faq_items' => FaqContent::prepareItems($category->faq_items, [FaqContent::blankItem()]),
            'sort_order' => $category->sort_order,
        ];
    }

    public function editPost(int $id): void
    {
        $post = BlogPost::query()->findOrFail($id);
        $coverMedia = $post->getFirstMedia('cover');

        $this->selectedId = $post->id;
        $this->coverUpload = null;
        $this->coverUploadOriginalName = null;
        $this->selectedLibraryCoverMediaId = $coverMedia
            ? (int) data_get($coverMedia->custom_properties, 'source_library_media_id')
            : null;
        $this->form = [
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'faq_items' => FaqContent::prepareItems($post->faq_items, [FaqContent::blankItem()]),
            'status' => $post->status,
            'content_category_id' => $post->content_category_id,
            'country_destination_id' => $post->country_destination_id,
            'destination_id' => $post->destination_id,
            'author_name' => $post->author_name,
            'published_at' => optional($post->published_at)?->format('Y-m-d\TH:i'),
            'is_featured' => $post->is_featured,
            'sort_order' => $post->sort_order,
            'cover_alt' => $post->cover_alt,
            'meta_title' => $post->meta_title,
            'meta_description' => $post->meta_description,
            'og_title' => $post->og_title,
            'og_description' => $post->og_description,
            'canonical_url' => $post->canonical_url,
            'robots_directive' => $post->robots_directive,
            'geo_config' => $this->defaultGeoConfigForm($post->geo_config),
        ];
    }

    public function render()
    {
        $blogCategories = ContentCategory::query()
            ->forTaxonomy('blog')
            ->with('parent')
            ->withCount(['blogPosts'])
            ->ordered()
            ->get();
        $categoryOptions = ContentCategoryTree::flatten($blogCategories);
        $categoryRows = $this->filteredCategoryRows($categoryOptions);
        $categoryFilterIds = $this->selectedCategoryBranchIds($blogCategories);
        $selectedCountryId = (int) ($this->form['country_destination_id'] ?? 0);

        return view($this->resolveView(), [
            'categories' => $categoryOptions,
            'categoryParentOptions' => $this->categoryParentOptions($categoryOptions),
            'categoryRows' => $this->paginateCollection($categoryRows),
            'countryRootOptions' => Destination::query()
                ->countryRoots()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'destinationOptions' => Destination::query()
                ->regularDestinations()
                ->with('country')
                ->when($selectedCountryId > 0, fn ($query) => $query->where('country_id', $selectedCountryId))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'editingCategoryModel' => $this->isCategoryEditorRoute() && $this->editingCategoryId
                ? ContentCategory::query()->forTaxonomy('blog')->find($this->editingCategoryId)
                : null,
            'editingPost' => $this->isPostEditorRoute() && $this->selectedId
                ? BlogPost::query()->with(['countryDestination', 'destination.country'])->find($this->selectedId)
                : null,
            'selectedLibraryAvatarMedia' => $this->isCategoryEditorRoute() && $this->selectedLibraryAvatarMediaId
                ? Media::query()->whereKey($this->selectedLibraryAvatarMediaId)->where('mime_type', 'like', 'image/%')->first()
                : null,
            'selectedLibraryCoverMedia' => $this->isPostEditorRoute() && $this->selectedLibraryCoverMediaId
                ? Media::query()->whereKey($this->selectedLibraryCoverMediaId)->where('mime_type', 'like', 'image/%')->first()
                : null,
            'posts' => BlogPost::query()
                ->with(['category.parent', 'countryDestination', 'destination.country'])
                ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
                ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                ->when(
                    $this->categoryFilter !== '',
                    fn ($query) => $categoryFilterIds === []
                        ? $query->whereRaw('1 = 0')
                        : $query->whereIn('content_category_id', $categoryFilterIds)
                )
                ->orderByDesc('updated_at')
                ->paginate(10),
        ]);
    }

    public function clearCoverLibraryMediaSelection(): void
    {
        $this->authorizeAdminPermission('admin.blogs.edit');

        $this->selectedLibraryCoverMediaId = null;
    }

    public function clearAvatarLibraryMediaSelection(): void
    {
        $this->authorizeAdminPermission('admin.blogs.categories.edit');

        $this->selectedLibraryAvatarMediaId = null;
    }

    public function removeFaqItem(int $index): void
    {
        $path = $this->activeFaqItemsPath();

        if ($path === null) {
            return;
        }

        $items = data_get($this, $path, []);
        unset($items[$index]);
        $items = array_values($items);

        if ($items === []) {
            $items[] = FaqContent::blankItem();
        }

        data_set($this, $path, $items);
    }

    public function saveCategory(): void
    {
        $this->authorizeAdminPermission('admin.blogs.categories.edit');

        $blogCategories = ContentCategory::query()
            ->forTaxonomy('blog')
            ->with('parent')
            ->ordered()
            ->get();

        $validated = $this->validate(array_merge([
            'categoryForm.name' => ['required', 'string', 'max:255'],
            'categoryForm.slug' => ['nullable', 'string', 'max:255'],
            'categoryForm.parent_id' => ['nullable', 'integer', Rule::exists('content_categories', 'id')->where('taxonomy', 'blog')],
            'categoryForm.description' => ['nullable', 'string'],
            'categoryForm.faq_items' => ['nullable', 'array'],
            'categoryForm.faq_items.*.question' => ['nullable', 'string', 'max:500'],
            'categoryForm.faq_items.*.answer' => ['nullable', 'string', 'max:5000'],
            'categoryForm.sort_order' => ['nullable', 'integer', 'min:0'],
        ], $this->geoValidationRules('categoryForm.geo_config')));
        $parentId = $this->resolveCategoryParentId($validated, $blogCategories);

        $category = ContentCategory::query()->updateOrCreate(
            ['id' => $this->editingCategoryId],
            [
                'taxonomy' => 'blog',
                'name' => $validated['categoryForm']['name'],
                'slug' => Str::slug($validated['categoryForm']['slug'] ?: $validated['categoryForm']['name']),
                'parent_id' => $parentId,
                'description' => $this->plainEditorContent($validated['categoryForm']['description'] ?? null),
                ...$this->geoConfigPayload($validated['categoryForm']['geo_config'] ?? []),
                'faq_items' => FaqContent::normalizeItems($validated['categoryForm']['faq_items'] ?? []),
                'sort_order' => $validated['categoryForm']['sort_order'] ?? 0,
            ],
        );

        $uploadedNewAvatar = $this->syncSingleImage($category, 'avatarUpload', 'avatar', [
            'alt' => $category->name,
        ]);

        if (! $uploadedNewAvatar) {
            $this->syncSingleImageFromLibrary($category, $this->selectedLibraryAvatarMediaId, 'avatar', [
                'alt' => $category->name,
            ]);
        }

        session()->flash('status', 'Đã lưu danh mục blog.');
        $this->redirectRoute('admin.blogs.categories.edit', ['category' => $category->getKey()], navigate: true);
    }

    public function savePost(): void
    {
        $this->authorizeAdminPermission('admin.blogs.edit');

        $validated = $this->validate(array_merge([
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => ['nullable', 'string', 'max:255'],
            'form.excerpt' => ['nullable', 'string'],
            'form.content' => ['nullable', 'string'],
            'form.faq_items' => ['nullable', 'array'],
            'form.faq_items.*.question' => ['nullable', 'string', 'max:500'],
            'form.faq_items.*.answer' => ['nullable', 'string', 'max:5000'],
            'form.status' => ['required', 'string', 'max:50'],
            'form.content_category_id' => ['nullable', Rule::exists('content_categories', 'id')->where('taxonomy', 'blog')],
            'form.country_destination_id' => ['nullable', 'integer', Rule::exists('destinations', 'id')->where('is_country_root', true)],
            'form.destination_id' => ['nullable', 'integer', Rule::exists('destinations', 'id')->where(fn ($query) => $query->where('is_country_root', false)->orWhereNull('is_country_root'))],
            'form.author_name' => ['nullable', 'string', 'max:255'],
            'form.published_at' => ['nullable', 'date'],
            'form.is_featured' => ['boolean'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'form.cover_alt' => ['nullable', 'string', 'max:255'],
            'form.meta_title' => ['nullable', 'string', 'max:255'],
            'form.meta_description' => ['nullable', 'string', 'max:500'],
            'form.og_title' => ['nullable', 'string', 'max:255'],
            'form.og_description' => ['nullable', 'string', 'max:500'],
            'form.canonical_url' => ['nullable', 'url'],
            'form.robots_directive' => ['nullable', 'string', 'max:255'],
        ], $this->geoValidationRules('form.geo_config')));
        $blogGeoPayload = $this->validatedBlogGeoPayload($validated['form']);
        $slug = $this->resolvePostSlug(
            $validated['form']['slug'] ?? '',
            $validated['form']['title'],
        );

        $post = BlogPost::query()->updateOrCreate(
            ['id' => $this->selectedId],
            [
                'title' => $validated['form']['title'],
                'slug' => $slug,
                'excerpt' => $this->plainEditorContent($validated['form']['excerpt'] ?? null),
                'content' => $this->richEditorContent($validated['form']['content'] ?? null),
                'faq_items' => FaqContent::normalizeItems($validated['form']['faq_items'] ?? []),
                'status' => $validated['form']['status'],
                'content_category_id' => $validated['form']['content_category_id'],
                'country_destination_id' => $blogGeoPayload['country_destination_id'],
                'destination_id' => $blogGeoPayload['destination_id'],
                'author_name' => $validated['form']['author_name'] ?: auth()->user()->name,
                'published_at' => $validated['form']['published_at'] ?? null,
                'is_featured' => $validated['form']['is_featured'] ?? false,
                'sort_order' => $validated['form']['sort_order'] ?? 0,
                'cover_alt' => $validated['form']['cover_alt'],
                'meta_title' => $validated['form']['meta_title'],
                'meta_description' => $this->plainEditorContent($validated['form']['meta_description'] ?? null),
                'og_title' => $validated['form']['og_title'],
                'og_description' => $this->plainEditorContent($validated['form']['og_description'] ?? null),
                'canonical_url' => $validated['form']['canonical_url'],
                'robots_directive' => $validated['form']['robots_directive'] ?: 'index,follow',
                ...$this->geoConfigPayload($validated['form']['geo_config'] ?? []),
            ],
        );

        $this->selectedId = $post->id;
        $uploadedNewCover = $this->syncSingleImage($post, 'coverUpload', 'cover', [
            'alt' => $validated['form']['cover_alt'] ?? null,
        ], $this->coverUploadOriginalName);

        if (! $uploadedNewCover) {
            $this->syncSingleImageFromLibrary($post, $this->selectedLibraryCoverMediaId, 'cover', [
                'alt' => $validated['form']['cover_alt'] ?? null,
            ]);
        }

        $this->coverUpload = null;
        $this->coverUploadOriginalName = null;
        $this->editPost($post->id);

        session()->flash('status', 'Đã lưu bài viết blog.');
        $this->redirectRoute('admin.blogs.edit', ['post' => $post], navigate: true);
    }

    public function selectCoverLibraryMedia(int $mediaId, ?string $alt = null): void
    {
        $this->authorizeAdminPermission('admin.blogs.edit');

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

    public function selectAvatarLibraryMedia(int $mediaId): void
    {
        $this->authorizeAdminPermission('admin.blogs.categories.edit');

        $media = Media::query()
            ->whereKey($mediaId)
            ->where('mime_type', 'like', 'image/%')
            ->firstOrFail();

        $this->selectedLibraryAvatarMediaId = (int) $media->getKey();
        $this->avatarUpload = null;
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

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatedCoverUpload(mixed $value): void
    {
        $this->coverUploadOriginalName = $value instanceof UploadedFile
            ? $value->getClientOriginalName()
            : null;
    }

    public function updatedFormCountryDestinationId(mixed $value): void
    {
        $countryId = filled($value) ? (int) $value : null;
        $destinationId = filled($this->form['destination_id'] ?? null)
            ? (int) $this->form['destination_id']
            : null;

        if (! $countryId || ! $destinationId) {
            if (! $countryId) {
                $this->form['destination_id'] = null;
            }

            return;
        }

        $belongsToCountry = Destination::query()
            ->regularDestinations()
            ->whereKey($destinationId)
            ->where('country_id', $countryId)
            ->exists();

        if (! $belongsToCountry) {
            $this->form['destination_id'] = null;
        }
    }

    public function updatedFormDestinationId(mixed $value): void
    {
        $destinationId = filled($value) ? (int) $value : null;

        if (! $destinationId) {
            return;
        }

        $countryId = Destination::query()
            ->regularDestinations()
            ->whereKey($destinationId)
            ->value('country_id');

        if ($countryId) {
            $this->form['country_destination_id'] = (int) $countryId;
        }
    }

    protected function resetCategoryForm(): void
    {
        $this->editingCategoryId = null;
        $this->avatarUpload = null;
        $this->selectedLibraryAvatarMediaId = null;
        $this->categoryForm = [
            'name' => '',
            'slug' => '',
            'parent_id' => null,
            'description' => '',
            'geo_config' => $this->defaultGeoConfigForm(),
            'faq_items' => [FaqContent::blankItem()],
            'sort_order' => 0,
        ];
    }

    protected function resetPostForm(): void
    {
        $this->selectedLibraryCoverMediaId = null;
        $this->coverUploadOriginalName = null;
        $this->form = [
            'title' => '',
            'slug' => '',
            'excerpt' => '',
            'content' => '',
            'faq_items' => [FaqContent::blankItem()],
            'status' => 'draft',
            'content_category_id' => null,
            'country_destination_id' => null,
            'destination_id' => null,
            'author_name' => auth()->user()->name,
            'published_at' => null,
            'is_featured' => false,
            'sort_order' => 0,
            'cover_alt' => '',
            'meta_title' => '',
            'meta_description' => '',
            'og_title' => '',
            'og_description' => '',
            'canonical_url' => '',
            'robots_directive' => 'index,follow',
            'geo_config' => $this->defaultGeoConfigForm(),
        ];
    }

    protected function activeFaqItemsPath(): ?string
    {
        return match (true) {
            $this->isPostEditorRoute() => 'form.faq_items',
            $this->isCategoryEditorRoute() => 'categoryForm.faq_items',
            default => null,
        };
    }

    protected function initializeRouteState(): void
    {
        if ($this->isPostEditorRoute()) {
            $postId = $this->resolvePostRouteId();

            if ($postId) {
                $this->editPost($postId);
            }

            return;
        }

        if ($this->isCategoryEditorRoute()) {
            $categoryId = $this->resolveRouteKey('category');

            if ($categoryId) {
                $this->editCategory($categoryId);
            }
        }
    }

    protected function isPostIndexRoute(): bool
    {
        return $this->currentRouteName === null || $this->currentRouteName === 'admin.blogs';
    }

    protected function isPostEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.blogs.create', 'admin.blogs.edit'], true);
    }

    protected function isCategoryEditorRoute(): bool
    {
        return in_array($this->currentRouteName, ['admin.blogs.categories.create', 'admin.blogs.categories.edit'], true);
    }

    protected function resolvePostRouteId(): ?int
    {
        $value = request()->route('post');

        if ($value instanceof BlogPost) {
            return (int) $value->getKey();
        }

        $identifier = trim((string) $value);

        if ($identifier === '') {
            return null;
        }

        $routeKey = (new BlogPost)->getRouteKeyName();
        $postId = BlogPost::query()->where($routeKey, $identifier)->value('id');

        if ($postId) {
            return (int) $postId;
        }

        if (! ctype_digit($identifier)) {
            return null;
        }

        $postId = BlogPost::query()->whereKey((int) $identifier)->value('id');

        return $postId ? (int) $postId : null;
    }

    protected function resolvePostSlug(?string $slugInput, string $title): string
    {
        $manualSlug = trim((string) $slugInput);
        $base = Str::slug($manualSlug !== '' ? $manualSlug : $title);
        $base = $base !== '' ? $base : 'blog-post';

        $slugExists = BlogPost::query()
            ->where('slug', $base)
            ->when($this->selectedId, fn ($query) => $query->whereKeyNot($this->selectedId))
            ->exists();

        if (! $slugExists) {
            return $base;
        }

        if ($manualSlug !== '') {
            throw ValidationException::withMessages([
                'form.slug' => 'Slug này đã được dùng cho bài viết khác. Hãy đổi slug hoặc để trống để hệ thống tự sinh từ tiêu đề.',
            ]);
        }

        return $this->resolveUniquePostSlug($base);
    }

    protected function resolveUniquePostSlug(string $base): string
    {
        $slug = $base;
        $suffix = 2;

        while (
            BlogPost::query()
                ->where('slug', $slug)
                ->when($this->selectedId, fn ($query) => $query->whereKeyNot($this->selectedId))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $form
     * @return array{country_destination_id: int|null, destination_id: int|null}
     */
    protected function validatedBlogGeoPayload(array $form): array
    {
        $countryId = filled($form['country_destination_id'] ?? null) ? (int) $form['country_destination_id'] : null;
        $destinationId = filled($form['destination_id'] ?? null) ? (int) $form['destination_id'] : null;

        if (! $destinationId) {
            return [
                'country_destination_id' => $countryId,
                'destination_id' => null,
            ];
        }

        $destination = Destination::query()
            ->regularDestinations()
            ->select(['id', 'country_id'])
            ->find($destinationId);

        if (! $destination) {
            throw ValidationException::withMessages([
                'form.destination_id' => 'Điểm đến phải là điểm đến con hợp lệ, không phải quốc gia root.',
            ]);
        }

        $destinationCountryId = filled($destination->country_id) ? (int) $destination->country_id : null;
        $countryId ??= $destinationCountryId;

        if (! $countryId) {
            throw ValidationException::withMessages([
                'form.country_destination_id' => 'Bài viết gắn điểm đến cần có quốc gia.',
            ]);
        }

        if ($destinationCountryId !== null && $destinationCountryId !== $countryId) {
            throw ValidationException::withMessages([
                'form.destination_id' => 'Điểm đến đã chọn không thuộc quốc gia của bài viết.',
            ]);
        }

        return [
            'country_destination_id' => $countryId,
            'destination_id' => $destinationId,
        ];
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
            $this->isPostIndexRoute() => 'livewire.admin.cms.blogs.index',
            $this->isPostEditorRoute() => 'livewire.admin.cms.blogs.editor',
            $this->currentRouteName === 'admin.blogs.categories' => 'livewire.admin.cms.blogs.categories-index',
            $this->isCategoryEditorRoute() => 'livewire.admin.cms.blogs.categories-editor',
            default => 'livewire.admin.cms.blogs.index',
        };
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     * @return Collection<int, ContentCategory>
     */
    protected function categoryParentOptions(Collection $categories): Collection
    {
        $allCategories = ContentCategory::query()
            ->forTaxonomy('blog')
            ->ordered()
            ->get();
        $editingHasChildren = $this->editingCategoryId !== null
            && $allCategories->contains(fn (ContentCategory $category) => (int) $category->parent_id === $this->editingCategoryId);
        $descendantIds = $this->editingCategoryId !== null
            ? ContentCategoryTree::descendantIds($allCategories, $this->editingCategoryId, false)
            : [];

        if ($editingHasChildren) {
            return collect();
        }

        return $categories
            ->filter(function (ContentCategory $category): bool {
                if ($this->editingCategoryId !== null && (int) $category->getKey() === $this->editingCategoryId) {
                    return false;
                }

                if ((int) ($category->tree_depth ?? 0) > 0) {
                    return false;
                }

                if ($this->editingCategoryId === null) {
                    return true;
                }

                return true;
            })
            ->reject(fn (ContentCategory $category) => in_array((int) $category->getKey(), $descendantIds, true))
            ->values();
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     * @return Collection<int, ContentCategory>
     */
    protected function filteredCategoryRows(Collection $categories): Collection
    {
        $search = Str::lower(trim($this->categorySearch));

        if ($search === '') {
            return $categories->values();
        }

        return $categories
            ->filter(function (ContentCategory $category) use ($search): bool {
                $parentName = Str::lower(trim((string) $category->parent?->name));
                $pathLabel = Str::lower(ContentCategoryTree::pathLabel($category));

                return Str::contains(Str::lower(trim((string) $category->name)), $search)
                    || Str::contains(Str::lower(trim((string) $category->slug)), $search)
                    || ($parentName !== '' && Str::contains($parentName, $search))
                    || ($pathLabel !== '' && Str::contains($pathLabel, $search));
            })
            ->values();
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     */
    protected function paginateCollection(Collection $categories, int $perPage = 10): LengthAwarePaginator
    {
        $page = $this->getPage();

        return new LengthAwarePaginator(
            $categories->forPage($page, $perPage)->values(),
            $categories->count(),
            $perPage,
            $page,
            [
                'pageName' => 'page',
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        );
    }

    /**
     * @param  array<string, array<string, mixed>>  $validated
     * @param  Collection<int, ContentCategory>  $categories
     */
    protected function resolveCategoryParentId(array $validated, Collection $categories): ?int
    {
        $parentId = (int) ($validated['categoryForm']['parent_id'] ?? 0) ?: null;

        if (! $parentId) {
            return null;
        }

        $parent = $categories->firstWhere('id', $parentId);

        if (! $parent) {
            throw ValidationException::withMessages([
                'categoryForm.parent_id' => 'Danh mục cha đã chọn không hợp lệ.',
            ]);
        }

        if ($this->editingCategoryId !== null && $parent->id === $this->editingCategoryId) {
            throw ValidationException::withMessages([
                'categoryForm.parent_id' => 'Bạn không thể chọn chính danh mục hiện tại làm danh mục cha.',
            ]);
        }

        if ($this->editingCategoryId !== null && $categories->contains(fn (ContentCategory $category) => (int) $category->parent_id === $this->editingCategoryId)) {
            throw ValidationException::withMessages([
                'categoryForm.parent_id' => 'Danh mục đang có nhánh con chỉ có thể đứng ở cấp gốc.',
            ]);
        }

        if ($parent->parent_id) {
            throw ValidationException::withMessages([
                'categoryForm.parent_id' => 'Danh mục blog hiện chỉ hỗ trợ tối đa 2 cấp cha-con.',
            ]);
        }

        if ($this->editingCategoryId !== null && in_array($parentId, ContentCategoryTree::descendantIds($categories, $this->editingCategoryId, false), true)) {
            throw ValidationException::withMessages([
                'categoryForm.parent_id' => 'Không thể chuyển danh mục vào chính nhánh con của nó.',
            ]);
        }

        return $parentId;
    }

    /**
     * @param  Collection<int, ContentCategory>  $categories
     * @return array<int, int>
     */
    protected function selectedCategoryBranchIds(Collection $categories): array
    {
        $categoryId = (int) $this->categoryFilter;

        if ($categoryId <= 0) {
            return [];
        }

        $category = $categories->firstWhere('id', $categoryId);

        if (! $category) {
            return [];
        }

        return ContentCategoryTree::descendantIds($categories, $categoryId);
    }
}
