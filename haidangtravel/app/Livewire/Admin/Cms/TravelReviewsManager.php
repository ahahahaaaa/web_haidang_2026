<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use App\Livewire\Admin\Cms\Concerns\InteractsWithEditorContent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Src\Domains\Cms\Models\Destination;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Src\Domains\Cms\Models\TravelReview;

#[Layout('layouts.app')]
#[Title('Quản lý đánh giá')]
class TravelReviewsManager extends Component
{
    use AuthorizesAdminPermissions;
    use InteractsWithEditorContent;
    use WithPagination;

    public string $currentRouteName = '';

    public array $form = [];

    public ?int $ownerId = null;

    public string $ownerType = '';

    public string $search = '';

    public ?int $selectedId = null;

    public string $statusFilter = '';

    public function mount(
        ?Tour $tour = null,
        ?TourCategory $category = null,
        ?Destination $destination = null,
        ?TravelReview $review = null,
    ): void {
        $this->currentRouteName = (string) (request()->route()?->getName() ?? '');
        $this->resetForm();

        $owner = match (true) {
            $tour !== null => ['type' => 'tour', 'model' => $tour],
            $category !== null => ['type' => 'category', 'model' => $category],
            $destination !== null => ['type' => 'destination', 'model' => $destination],
            default => null,
        };

        abort_if($owner === null, 404);

        $this->ownerType = $owner['type'];
        $this->ownerId = (int) $owner['model']->getKey();
        $this->authorizeAdminPermission($this->ownerPermission());

        if ($review !== null) {
            $this->editReview($review->id);
        }
    }

    public function render()
    {
        $owner = $this->ownerModel();
        $query = $this->reviewsQuery();

        return view($this->resolveView(), [
            'editingReview' => $this->selectedId ? $this->reviewsQuery()->find($this->selectedId) : null,
            'owner' => $owner,
            'ownerConfig' => $this->ownerConfig(),
            'reviews' => $query
                ->when($this->search !== '', function ($builder): void {
                    $like = '%'.$this->search.'%';

                    $builder->where(function ($nested) use ($like): void {
                        $nested
                            ->where('title', 'like', $like)
                            ->orWhere('author_name', 'like', $like)
                            ->orWhere('author_title', 'like', $like)
                            ->orWhere('content', 'like', $like);
                    });
                })
                ->when($this->statusFilter !== '', fn ($builder) => $builder->where('status', $this->statusFilter))
                ->ordered()
                ->paginate(10),
            'reviewStats' => [
                'all' => $this->reviewsQuery()->count(),
                'published' => $this->reviewsQuery()->where('status', 'published')->count(),
            ],
        ]);
    }

    public function deleteReview(int $id): void
    {
        $this->authorizeAdminPermission($this->ownerPermission());

        $review = $this->reviewsQuery()->findOrFail($id);
        $review->delete();

        if ($this->selectedId === $id) {
            $this->selectedId = null;
            $this->resetForm();
        }

        session()->flash('status', 'Đã xóa đánh giá.');

        if ($this->isEditorRoute()) {
            $this->redirectRoute($this->ownerConfig()['reviews_index_route'], $this->ownerRouteParameters(), navigate: true);
        }
    }

    public function editReview(int $id): void
    {
        $review = $this->reviewsQuery()->findOrFail($id);

        $this->selectedId = (int) $review->getKey();
        $this->form = [
            'author_name' => (string) $review->author_name,
            'author_title' => (string) $review->author_title,
            'content' => (string) $review->content,
            'is_featured' => (bool) $review->is_featured,
            'published_at' => optional($review->published_at)->format('Y-m-d'),
            'rating_value' => $review->rating_value,
            'sort_order' => (int) $review->sort_order,
            'status' => (string) $review->status,
            'title' => (string) $review->title,
        ];
    }

    public function saveReview(): void
    {
        $this->authorizeAdminPermission($this->ownerPermission());

        $validated = $this->validate([
            'form.title' => ['nullable', 'string', 'max:255'],
            'form.author_name' => ['required', 'string', 'max:255'],
            'form.author_title' => ['nullable', 'string', 'max:255'],
            'form.content' => ['required', 'string', 'max:5000'],
            'form.rating_value' => ['required', 'numeric', 'between:1,5'],
            'form.status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'form.is_featured' => ['boolean'],
            'form.sort_order' => ['nullable', 'integer', 'min:0'],
            'form.published_at' => ['nullable', 'date'],
        ]);

        $payload = [
            'author_name' => Str::limit($this->plainEditorContent($validated['form']['author_name']), 255, ''),
            'author_title' => Str::limit($this->plainEditorContent($validated['form']['author_title'] ?? null), 255, ''),
            'content' => $this->plainEditorContent($validated['form']['content']),
            'is_featured' => (bool) ($validated['form']['is_featured'] ?? false),
            'published_at' => $validated['form']['published_at'] ?: null,
            'rating_value' => $validated['form']['rating_value'],
            'sort_order' => (int) ($validated['form']['sort_order'] ?? 0),
            'status' => $validated['form']['status'],
            'title' => Str::limit($this->plainEditorContent($validated['form']['title'] ?? null), 255, ''),
        ];

        if ($this->selectedId) {
            $review = $this->reviewsQuery()->findOrFail($this->selectedId);
            $review->fill($payload);
            $review->save();
        } else {
            $review = $this->ownerReviewsRelation()->create($payload);
        }

        $this->selectedId = (int) $review->getKey();
        session()->flash('status', 'Đã lưu đánh giá.');

        $this->redirectRoute(
            $this->ownerConfig()['reviews_edit_route'],
            array_merge($this->ownerRouteParameters(), ['review' => $review->getKey()]),
            navigate: true,
        );
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    protected function isEditorRoute(): bool
    {
        return in_array($this->currentRouteName, [
            'admin.tours.reviews.create',
            'admin.tours.reviews.edit',
            'admin.tours.categories.reviews.create',
            'admin.tours.categories.reviews.edit',
            'admin.tours.destinations.reviews.create',
            'admin.tours.destinations.reviews.edit',
        ], true);
    }

    protected function ownerConfig(): array
    {
        return match ($this->ownerType) {
            'tour' => [
                'owner_label' => 'tour',
                'owner_plural_label' => 'tour',
                'owner_title' => 'Tour',
                'owner_field' => 'title',
                'owner_param' => 'tour',
                'owner_type_label' => 'Tour',
                'edit_route' => 'admin.tours.edit',
                'reviews_create_route' => 'admin.tours.reviews.create',
                'reviews_edit_route' => 'admin.tours.reviews.edit',
                'reviews_index_route' => 'admin.tours.reviews.index',
            ],
            'category' => [
                'owner_label' => 'chủ đề tour',
                'owner_plural_label' => 'chủ đề tour',
                'owner_title' => 'Chủ đề tour',
                'owner_field' => 'name',
                'owner_param' => 'category',
                'owner_type_label' => 'Chủ đề tour',
                'edit_route' => 'admin.tours.categories.edit',
                'reviews_create_route' => 'admin.tours.categories.reviews.create',
                'reviews_edit_route' => 'admin.tours.categories.reviews.edit',
                'reviews_index_route' => 'admin.tours.categories.reviews.index',
            ],
            'destination' => [
                'owner_label' => 'điểm đến',
                'owner_plural_label' => 'điểm đến',
                'owner_title' => 'Điểm đến',
                'owner_field' => 'name',
                'owner_param' => 'destination',
                'owner_type_label' => 'Điểm đến',
                'edit_route' => 'admin.tours.destinations.edit',
                'reviews_create_route' => 'admin.tours.destinations.reviews.create',
                'reviews_edit_route' => 'admin.tours.destinations.reviews.edit',
                'reviews_index_route' => 'admin.tours.destinations.reviews.index',
            ],
            default => [],
        };
    }

    protected function ownerModel(): Model
    {
        $modelClass = match ($this->ownerType) {
            'tour' => Tour::class,
            'category' => TourCategory::class,
            'destination' => Destination::class,
            default => Tour::class,
        };

        return $modelClass::query()
            ->when(
                $this->ownerType === 'tour' && $this->currentUserIsSale(),
                fn ($query) => $query->where('managed_by_user_id', auth()->id()),
            )
            ->findOrFail($this->ownerId);
    }

    protected function ownerPermission(): string
    {
        return match ($this->ownerType) {
            'tour' => 'admin.tours.edit',
            'category' => 'admin.tours.categories.edit',
            'destination' => 'admin.tours.destinations.edit',
            default => 'admin.tours.edit',
        };
    }

    protected function ownerReviewableType(): string
    {
        return match ($this->ownerType) {
            'tour' => Tour::class,
            'category' => TourCategory::class,
            'destination' => Destination::class,
            default => Tour::class,
        };
    }

    protected function ownerRouteParameters(): array
    {
        $config = $this->ownerConfig();

        return [
            $config['owner_param'] => $this->ownerModel(),
        ];
    }

    protected function ownerReviewsRelation(): MorphMany
    {
        /** @var MorphMany $relation */
        $relation = $this->ownerModel()->reviews();

        return $relation;
    }

    protected function resetForm(): void
    {
        $this->form = [
            'title' => '',
            'author_name' => '',
            'author_title' => '',
            'content' => '',
            'rating_value' => 5,
            'status' => 'draft',
            'is_featured' => false,
            'sort_order' => 0,
            'published_at' => '',
        ];
    }

    protected function currentUserIsSale(): bool
    {
        return (bool) auth()->user()?->hasRole('sale');
    }

    protected function resolveView(): string
    {
        return $this->isEditorRoute()
            ? 'livewire.admin.cms.travel-reviews.editor'
            : 'livewire.admin.cms.travel-reviews.index';
    }

    protected function reviewsQuery()
    {
        return TravelReview::query()
            ->where('reviewable_type', $this->ownerReviewableType())
            ->where('reviewable_id', $this->ownerId);
    }
}
