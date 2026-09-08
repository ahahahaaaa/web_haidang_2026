<?php

namespace App\Livewire\Admin\Cms;

use App\Services\Cms\SiteSettingsManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Src\Domains\Cms\Models\Menu;
use Src\Domains\Cms\Models\MenuItem;

#[Layout('layouts.app')]
#[Title('Quản lý menu')]
class MenuManager extends Component
{
    public ?int $editingItemId = null;

    public array $itemForm = [];

    public array $menuForm = [];

    public ?int $selectedMenuId = null;

    public function mount(): void
    {
        $this->ensureDefaultMenus();

        $this->selectedMenuId = Menu::query()->orderBy('id')->value('id');
        $this->resetMenuForm();
        $this->resetItemForm();
    }

    public function createMenu(): void
    {
        $this->selectedMenuId = null;
        $this->resetMenuForm();
    }

    public function deleteItem(int $id, SiteSettingsManager $site): void
    {
        MenuItem::query()->findOrFail($id)->delete();

        if ($this->editingItemId === $id) {
            $this->resetItemForm();
        }

        $site->refresh();
        session()->flash('status', 'Đã xóa item menu.');
    }

    public function deleteMenu(int $id, SiteSettingsManager $site): void
    {
        Menu::query()->findOrFail($id)->delete();
        $this->selectedMenuId = Menu::query()->orderBy('id')->value('id');
        $this->resetMenuForm();
        $this->resetItemForm();
        $site->refresh();
        session()->flash('status', 'Đã xóa menu.');
    }

    public function editItem(int $id): void
    {
        $item = MenuItem::query()->findOrFail($id);

        $this->editingItemId = $item->id;
        $this->itemForm = [
            'parent_id' => $item->parent_id,
            'label' => $item->label,
            'url' => $item->url,
            'target' => $item->target,
            'icon' => $item->icon,
            'order' => $item->order,
            'is_active' => $item->is_active,
        ];
    }

    public function render()
    {
        $menus = Menu::query()
            ->with(['items' => fn ($query) => $query->orderBy('order')->orderBy('id')])
            ->orderBy('name')
            ->get();

        if (! $this->selectedMenuId && $menus->isNotEmpty()) {
            $this->selectMenu((int) $menus->first()->id);
        }

        $selectedMenu = $menus->firstWhere('id', $this->selectedMenuId);
        $selectedMenuItems = $selectedMenu?->items ?? collect();
        $selectedMenuItemRows = collect($this->flattenMenuTree(
            $this->buildMenuTree($selectedMenuItems),
        ));

        return view('livewire.admin.cms.menu-manager', [
            'menus' => $menus,
            'parentOptions' => $this->buildParentOptions($selectedMenuItemRows),
            'selectedMenu' => $selectedMenu,
            'selectedMenuItemRows' => $selectedMenuItemRows,
        ]);
    }

    public function saveItem(SiteSettingsManager $site): void
    {
        $validated = $this->validate([
            'selectedMenuId' => ['required', 'exists:menus,id'],
            'itemForm.parent_id' => ['nullable', 'integer'],
            'itemForm.label' => ['required', 'string', 'max:255'],
            'itemForm.url' => ['required', 'string', 'max:255'],
            'itemForm.target' => ['required', 'string', 'max:20'],
            'itemForm.icon' => ['nullable', 'string', 'max:255'],
            'itemForm.order' => ['nullable', 'integer', 'min:0'],
            'itemForm.is_active' => ['boolean'],
        ]);

        $parentId = $this->validateParentSelection($validated);

        MenuItem::query()->updateOrCreate(
            ['id' => $this->editingItemId],
            [
                'menu_id' => $validated['selectedMenuId'],
                'parent_id' => $parentId,
                'label' => $validated['itemForm']['label'],
                'url' => $validated['itemForm']['url'],
                'target' => $validated['itemForm']['target'],
                'icon' => $validated['itemForm']['icon'],
                'order' => $validated['itemForm']['order'] ?? 0,
                'is_active' => $validated['itemForm']['is_active'] ?? true,
            ],
        );

        $this->resetItemForm();
        $site->refresh();
        session()->flash('status', 'Đã lưu item menu.');
    }

    public function saveMenu(SiteSettingsManager $site): void
    {
        $validated = $this->validate([
            'menuForm.name' => ['required', 'string', 'max:255'],
            'menuForm.location' => ['required', 'string', 'max:255'],
            'menuForm.description' => ['nullable', 'string', 'max:255'],
        ]);

        $menu = Menu::query()->updateOrCreate(
            ['id' => $this->selectedMenuId],
            [
                'name' => $validated['menuForm']['name'],
                'location' => Str::slug($validated['menuForm']['location'], '_'),
                'description' => $validated['menuForm']['description'],
            ],
        );

        $this->selectedMenuId = $menu->id;
        $this->menuForm = [
            'name' => $menu->name,
            'location' => $menu->location,
            'description' => $menu->description,
        ];
        $site->refresh();

        session()->flash('status', 'Đã lưu menu.');
    }

    public function selectMenu(int $id): void
    {
        $menu = Menu::query()->findOrFail($id);

        $this->selectedMenuId = $menu->id;
        $this->menuForm = [
            'name' => $menu->name,
            'location' => $menu->location,
            'description' => $menu->description,
        ];

        $this->resetItemForm();
    }

    protected function ensureDefaultMenus(): void
    {
        Menu::query()->firstOrCreate(['location' => 'header'], ['name' => 'Header Menu']);
        Menu::query()->firstOrCreate(['location' => 'footer'], [
            'name' => 'Footer Menu',
            'description' => 'Đi nhanh',
        ]);
        Menu::query()->firstOrCreate(['location' => 'footer_secondary'], [
            'name' => 'Footer Secondary Menu',
            'description' => 'Thông tin',
        ]);
    }

    protected function resetItemForm(): void
    {
        $this->editingItemId = null;
        $this->itemForm = [
            'parent_id' => null,
            'label' => '',
            'url' => '',
            'target' => '_self',
            'icon' => '',
            'order' => 0,
            'is_active' => true,
        ];
    }

    protected function resetMenuForm(): void
    {
        $this->menuForm = [
            'name' => '',
            'location' => '',
            'description' => '',
        ];
    }

    /**
     * @return array<int, array{children: array<int, mixed>, depth: int, item: \Src\Domains\Cms\Models\MenuItem}>
     */
    protected function buildMenuTree(Collection $items, ?int $parentId = null, int $depth = 1): array
    {
        return $items
            ->filter(fn (MenuItem $item) => $item->parent_id === $parentId)
            ->sortBy([
                ['order', 'asc'],
                ['id', 'asc'],
            ])
            ->map(fn (MenuItem $item) => [
                'item' => $item,
                'depth' => $depth,
                'children' => $this->buildMenuTree($items, $item->id, $depth + 1),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{children: array<int, mixed>, depth: int, item: \Src\Domains\Cms\Models\MenuItem}>  $tree
     * @return array<int, array{depth: int, item: \Src\Domains\Cms\Models\MenuItem, parentLabel: string|null}>
     */
    protected function flattenMenuTree(array $tree, ?string $parentLabel = null): array
    {
        $rows = [];

        foreach ($tree as $node) {
            $rows[] = [
                'item' => $node['item'],
                'depth' => $node['depth'],
                'parentLabel' => $parentLabel,
            ];

            $rows = [
                ...$rows,
                ...$this->flattenMenuTree($node['children'], $node['item']->label),
            ];
        }

        return $rows;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array{depth: int, item: \Src\Domains\Cms\Models\MenuItem, parentLabel: string|null}>  $rows
     * @return \Illuminate\Support\Collection<int, array{id: int, label: string}>
     */
    protected function buildParentOptions(Collection $rows): Collection
    {
        return $rows
            ->filter(function (array $row): bool {
                if ($row['depth'] >= 3) {
                    return false;
                }

                return $row['item']->id !== $this->editingItemId;
            })
            ->map(fn (array $row) => [
                'id' => $row['item']->id,
                'label' => str_repeat('-- ', max(0, $row['depth'] - 1)).$row['item']->label,
            ])
            ->values();
    }

    protected function resolveMenuItemDepth(MenuItem $item): int
    {
        $depth = 1;
        $current = $item;

        while ($current->parent_id) {
            $current = $current->parent()->first();

            if (! $current) {
                break;
            }

            $depth++;

            if ($depth > 10) {
                break;
            }
        }

        return $depth;
    }

    protected function validateParentSelection(array $validated): ?int
    {
        $parentId = (int) ($validated['itemForm']['parent_id'] ?? 0) ?: null;

        if (! $parentId) {
            return null;
        }

        $parent = MenuItem::query()
            ->whereKey($parentId)
            ->where('menu_id', $validated['selectedMenuId'])
            ->first();

        if (! $parent) {
            throw ValidationException::withMessages([
                'itemForm.parent_id' => 'Mục cha đã chọn không hợp lệ.',
            ]);
        }

        if ($this->editingItemId && $parent->id === $this->editingItemId) {
            throw ValidationException::withMessages([
                'itemForm.parent_id' => 'Bạn không thể chọn chính item hiện tại làm mục cha.',
            ]);
        }

        if ($this->resolveMenuItemDepth($parent) >= 3) {
            throw ValidationException::withMessages([
                'itemForm.parent_id' => 'Chỉ hỗ trợ submenu tối đa 3 cấp.',
            ]);
        }

        $current = $parent;

        while ($current) {
            if ($this->editingItemId && $current->id === $this->editingItemId) {
                throw ValidationException::withMessages([
                    'itemForm.parent_id' => 'Không thể chuyển item vào chính nhánh con của nó.',
                ]);
            }

            $current = $current->parent()->first();
        }

        return $parent->id;
    }
}
