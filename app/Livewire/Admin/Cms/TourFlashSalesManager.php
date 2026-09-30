<?php

namespace App\Livewire\Admin\Cms;

use App\Livewire\Admin\Cms\Concerns\AuthorizesAdminPermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourDeparture;
use Src\Domains\Cms\Models\TourFlashSale;
use Src\Domains\Cms\Models\TourFlashSaleItem;

#[Layout('layouts.app')]
#[Title('Flash Sale tour')]
class TourFlashSalesManager extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public ?string $currentRouteName = null;

    public array $form = [];

    public string $search = '';

    public ?int $selectedId = null;

    public string $statusFilter = '';

    public function mount(?TourFlashSale $flashSale = null): void
    {
        $this->currentRouteName = request()->route()?->getName();
        $this->resetForm();

        if ($flashSale?->exists) {
            $this->editFlashSale((int) $flashSale->getKey());
        }
    }

    public function updatedFormTitle(mixed $value): void
    {
        if (! $this->selectedId && blank($this->form['slug'] ?? null)) {
            $this->form['slug'] = Str::slug((string) $value);
        }
    }

    public function addItem(): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');
        $items = is_array($this->form['items'] ?? null) ? $this->form['items'] : [];
        $items[] = $this->blankItem(count($items));
        $this->form['items'] = array_values($items);
    }

    public function removeItem(int $index): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');
        $items = is_array($this->form['items'] ?? null) ? $this->form['items'] : [];

        if (isset($items[$index])) {
            unset($items[$index]);
        }

        $this->form['items'] = array_values($items);
    }

    public function editFlashSale(int $id): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');

        $flashSale = TourFlashSale::query()->with('items.departure')->findOrFail($id);
        $this->selectedId = (int) $flashSale->getKey();
        $this->form = [
            'title' => (string) $flashSale->title,
            'slug' => (string) $flashSale->slug,
            'description' => (string) $flashSale->description,
            'icon_class' => (string) $flashSale->icon_class,
            'cta_label' => (string) $flashSale->cta_label,
            'cta_url' => (string) $flashSale->cta_url,
            'starts_at' => $flashSale->starts_at?->format('Y-m-d\TH:i') ?? '',
            'ends_at' => $flashSale->ends_at?->format('Y-m-d\TH:i') ?? '',
            'is_active' => (bool) $flashSale->is_active,
            'sort_order' => (int) $flashSale->sort_order,
            'items' => $flashSale->items
                ->map(fn ($item): array => [
                    'tour_departure_id' => (int) $item->tour_departure_id,
                    'flash_price' => (int) $item->flash_price,
                    'ticket_quantity' => $item->effectiveTicketQuantity(),
                    'booked_quantity' => (int) $item->booked_quantity,
                    'sort_order' => (int) $item->sort_order,
                ])
                ->values()
                ->all(),
        ];
    }

    public function deleteFlashSale(int $id): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');
        $flashSale = TourFlashSale::query()->with('items')->findOrFail($id);

        if ($flashSale->items->contains(fn (TourFlashSaleItem $item): bool => (int) $item->booked_quantity > 0)) {
            session()->flash('status', 'Campaign đã có vé được ghi nhận nên không thể xóa. Hãy tắt campaign để bảo toàn lịch sử đặt tour.');

            return;
        }

        $flashSale->delete();
        session()->flash('status', 'Đã xóa campaign Flash Sale.');

        if ($this->selectedId === $id || $this->isEditor()) {
            $this->redirectRoute('admin.tour-flash-sales', navigate: true);
        }
    }

    public function save(): void
    {
        $this->authorizeAdminPermission('admin.tours.edit');
        $this->form['slug'] = Str::slug((string) ($this->form['slug'] ?? $this->form['title'] ?? ''));
        $this->form['is_active'] = (bool) ($this->form['is_active'] ?? false);

        $validated = $this->validate([
            'form.title' => ['required', 'string', 'max:255'],
            'form.slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('tour_flash_sales', 'slug')->ignore($this->selectedId),
            ],
            'form.description' => ['nullable', 'string', 'max:1000'],
            'form.icon_class' => ['nullable', 'string', 'max:255'],
            'form.cta_label' => ['nullable', 'string', 'max:120'],
            'form.cta_url' => ['nullable', 'string', 'max:2048'],
            'form.starts_at' => ['required', 'date'],
            'form.ends_at' => ['required', 'date', 'after:form.starts_at'],
            'form.is_active' => ['boolean'],
            'form.sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'form.items' => ['required', 'array', 'min:1', 'max:12'],
            'form.items.*.tour_departure_id' => ['required', 'integer', 'distinct', 'exists:tour_departures,id'],
            'form.items.*.flash_price' => ['required', 'integer', 'min:1'],
            'form.items.*.ticket_quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'form.items.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], [
            'form.ends_at.after' => 'Thời điểm kết thúc phải sau thời điểm bắt đầu.',
            'form.items.*.tour_departure_id.distinct' => 'Mỗi lịch khởi hành chỉ được chọn một lần trong campaign.',
        ]);

        $departureIds = collect($validated['form']['items'])
            ->pluck('tour_departure_id')
            ->map(fn ($id): int => (int) $id)
            ->values();
        $departures = TourDeparture::query()
            ->with('tour')
            ->whereKey($departureIds)
            ->get()
            ->keyBy(fn (TourDeparture $departure): int => (int) $departure->getKey());

        foreach ($departureIds as $index => $departureId) {
            $departure = $departures->get($departureId);

            if (! $departure || ! $departure->tour || ! Tour::query()->published()->whereKey($departure->tour_id)->exists()) {
                throw ValidationException::withMessages([
                    'form.items.'.$index.'.tour_departure_id' => 'Lịch khởi hành phải thuộc một tour đang xuất bản.',
                ]);
            }

            $regularPrice = $departure->sale_price ?: $departure->base_price;

            if (is_numeric($regularPrice) && (int) $regularPrice > 0 && (int) data_get($validated, 'form.items.'.$index.'.flash_price') >= (int) $regularPrice) {
                throw ValidationException::withMessages([
                    'form.items.'.$index.'.flash_price' => 'Giá Flash Sale phải thấp hơn giá đang bán của lịch khởi hành.',
                ]);
            }

        }

        $flashSale = DB::transaction(function () use ($validated, $departures): TourFlashSale {
            $payload = $validated['form'];
            $items = $payload['items'];
            unset($payload['items']);
            $payload['icon_class'] = trim((string) ($payload['icon_class'] ?? '')) ?: 'fa-solid fa-bolt';
            $payload['cta_label'] = trim((string) ($payload['cta_label'] ?? ''));
            $payload['cta_url'] = trim((string) ($payload['cta_url'] ?? ''));
            $payload['sort_order'] = (int) ($payload['sort_order'] ?? 0);

            $flashSale = TourFlashSale::query()->updateOrCreate(
                ['id' => $this->selectedId],
                $payload,
            );

            $departureIds = collect($items)
                ->pluck('tour_departure_id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $existingItems = TourFlashSaleItem::query()
                ->where('tour_flash_sale_id', $flashSale->getKey())
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (TourFlashSaleItem $item): int => (int) $item->tour_departure_id);
            $removedItemWithBookings = $existingItems
                ->reject(fn (TourFlashSaleItem $item, int $departureId): bool => in_array($departureId, $departureIds, true))
                ->first(fn (TourFlashSaleItem $item): bool => (int) $item->booked_quantity > 0);

            if ($removedItemWithBookings) {
                throw ValidationException::withMessages([
                    'form.items' => 'Không thể xóa lịch khởi hành đã có vé Flash Sale được ghi nhận. Hãy giữ dòng này và tắt campaign nếu không muốn tiếp tục bán.',
                ]);
            }

            foreach ($items as $index => $item) {
                $bookedQuantity = (int) $existingItems->get((int) $item['tour_departure_id'])?->booked_quantity;

                if ((int) $item['ticket_quantity'] < $bookedQuantity) {
                    throw ValidationException::withMessages([
                        'form.items.'.$index.'.ticket_quantity' => 'Số vé Flash Sale không được thấp hơn '.$bookedQuantity.' vé đã ghi nhận.',
                    ]);
                }
            }

            $flashSale->items()
                ->whereNotIn('tour_departure_id', $departureIds)
                ->where('booked_quantity', 0)
                ->delete();

            foreach ($items as $index => $item) {
                $departure = $departures->get((int) $item['tour_departure_id']);
                $flashSale->items()->updateOrCreate(
                    ['tour_departure_id' => (int) $departure->getKey()],
                    [
                        'tour_id' => (int) $departure->tour_id,
                        'flash_price' => (int) $item['flash_price'],
                        'ticket_quantity' => (int) $item['ticket_quantity'],
                        'sort_order' => (int) ($item['sort_order'] ?? $index),
                    ],
                );
            }

            return $flashSale;
        });

        $this->selectedId = (int) $flashSale->getKey();
        session()->flash('status', 'Đã lưu campaign Flash Sale.');
        $this->redirectRoute('admin.tour-flash-sales.edit', ['flashSale' => $flashSale], navigate: true);
    }

    public function render()
    {
        $campaigns = TourFlashSale::query()
            ->withCount('items')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($nested): void {
                    $nested
                        ->where('title', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->statusFilter === 'active', fn ($query) => $query->active())
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(10);
        $departureOptions = TourDeparture::query()
            ->upcomingPublic()
            ->whereHas('tour', fn ($tourQuery) => $tourQuery->published())
            ->with('tour:id,title,slug,status,published_at')
            ->orderBy('departure_date')
            ->orderBy('sort_order')
            ->limit(500)
            ->get();

        return view('livewire.admin.cms.tour-flash-sales-manager', [
            'campaigns' => $campaigns,
            'departureOptions' => $departureOptions,
            'isEditor' => $this->isEditor(),
        ]);
    }

    protected function blankItem(int $sortOrder = 0): array
    {
        return [
            'tour_departure_id' => null,
            'flash_price' => null,
            'ticket_quantity' => null,
            'booked_quantity' => 0,
            'sort_order' => $sortOrder,
        ];
    }

    protected function isEditor(): bool
    {
        return in_array($this->currentRouteName, ['admin.tour-flash-sales.create', 'admin.tour-flash-sales.edit'], true);
    }

    protected function resetForm(): void
    {
        $this->form = [
            'title' => 'Ưu đãi giờ chót',
            'slug' => '',
            'description' => '',
            'icon_class' => 'fa-solid fa-bolt',
            'cta_label' => 'Xem thêm',
            'cta_url' => '',
            'starts_at' => now()->format('Y-m-d\TH:i'),
            'ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'is_active' => false,
            'sort_order' => 0,
            'items' => [$this->blankItem()],
        ];
    }
}
