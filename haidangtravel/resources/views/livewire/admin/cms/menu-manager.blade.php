<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Quản lý menu</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Cấu hình menu `header`, `footer` và `footer_secondary` cho frontsite `haidangtravel`.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Danh sách menu</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Tạo menu mới hoặc chọn menu để quản lý item.</p>
                </div>

                <button type="button" wire:click="createMenu" class="rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-red-400 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-300">
                    Menu mới
                </button>
            </div>

            <div class="space-y-3">
                @foreach ($menus as $menu)
                    <button
                        type="button"
                        wire:click="selectMenu({{ $menu->id }})"
                        wire:key="menu-{{ $menu->id }}"
                        class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition {{ $selectedMenuId === $menu->id ? 'border-red-500 bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300' : 'border-zinc-200 bg-zinc-50 text-zinc-700 hover:border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' }}"
                    >
                        <div>
                            <p class="font-semibold">{{ $menu->name }}</p>
                            <p class="text-xs uppercase tracking-[0.2em] opacity-70">{{ $menu->location }}</p>
                        </div>

                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">
                            {{ $menu->items->count() }} items
                        </span>
                    </button>
                @endforeach
            </div>

            <form wire:submit="saveMenu" class="mt-6 space-y-4 rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tên menu</label>
                    <input type="text" wire:model.defer="menuForm.name" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Vị trí</label>
                    <input type="text" wire:model.defer="menuForm.location" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề hiển thị / mô tả</label>
                    <input type="text" wire:model.defer="menuForm.description" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Với menu ở vị trí `footer` hoặc `footer_secondary`, trường này sẽ dùng làm tiêu đề cột ngoài frontsite, ví dụ `Đi nhanh`.</p>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Lưu menu
                    </button>

                    @if ($selectedMenuId)
                        <button type="button" wire:click="deleteMenu({{ $selectedMenuId }})" wire:confirm="Bạn có chắc chắn muốn xóa menu này? Tất cả item thuộc menu cũng sẽ bị xóa." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                            <i class="fa-regular fa-trash-can"></i>
                            Xóa menu
                        </button>
                    @endif
                </div>
            </form>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Item của menu</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Cập nhật liên kết, cấu trúc submenu tối đa 3 cấp, thứ tự, icon Font Awesome và trạng thái hiển thị.</p>
            </div>

            @if ($selectedMenu)
                <div class="mb-6 space-y-3">
                    @forelse ($selectedMenuItemRows as $row)
                        @php
                            $item = $row['item'];
                            $depth = $row['depth'];
                            $parentLabel = $row['parentLabel'];
                        @endphp
                        <div wire:key="menu-item-{{ $item->id }}" class="flex flex-col gap-3 rounded-3xl border border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800/70 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-zinc-500 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">
                                        Cấp {{ $depth }}
                                    </span>
                                    @if ($parentLabel)
                                        <span class="rounded-full bg-white px-3 py-1 text-[11px] font-medium text-zinc-500 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">
                                            Thuộc {{ $parentLabel }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-3 font-semibold text-zinc-900 dark:text-white" style="padding-left: {{ ($depth - 1) * 1.25 }}rem">
                                    {{ $depth > 1 ? str_repeat('-- ', $depth - 1) : '' }}{{ $item->label }}
                                </p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $item->url }}</p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">Thứ tự {{ $item->order }}</span>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $item->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300' }}">
                                    {{ $item->is_active ? 'Đang hiện' : 'Đang ẩn' }}
                                </span>

                                <button type="button" wire:click="editItem({{ $item->id }})" class="rounded-2xl border border-zinc-200 px-4 py-2 font-medium text-zinc-700 transition hover:border-red-400 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">Sửa</button>
                                <button type="button" wire:click="deleteItem({{ $item->id }})" wire:confirm="Bạn có chắc chắn muốn xóa item menu này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-4 py-2 font-medium text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">Xóa</button>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                            Menu này chưa có item nào.
                        </div>
                    @endforelse
                </div>

                <form wire:submit="saveItem" class="grid gap-4 rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nhãn hiển thị</label>
                        <input type="text" wire:model.defer="itemForm.label" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mục cha</label>
                        <select wire:model.defer="itemForm.parent_id" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <option value="">Không có mục cha (cấp 1)</option>
                            @foreach ($parentOptions as $option)
                                <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Chọn mục cha cấp 1 để tạo submenu cấp 2, hoặc mục cha cấp 2 để tạo submenu cấp 3.</p>
                        @error('itemForm.parent_id')
                            <p class="text-xs font-medium text-red-600 dark:text-red-300">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">URL</label>
                        <input type="text" wire:model.defer="itemForm.url" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Target</label>
                        <select wire:model.defer="itemForm.target" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <option value="_self">_self</option>
                            <option value="_blank">_blank</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Icon class</label>
                        <input type="text" wire:model.defer="itemForm.icon" placeholder="fa-solid fa-phone" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Thứ tự</label>
                        <input type="number" wire:model.defer="itemForm.order" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </div>

                    <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                        <input type="checkbox" wire:model.defer="itemForm.is_active" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                        Đang hiển thị
                    </label>

                    <div class="md:col-span-2">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600">
                            <i class="fa-solid fa-link"></i>
                            {{ $editingItemId ? 'Cập nhật item' : 'Thêm item menu' }}
                        </button>
                    </div>
                </form>
            @else
                <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Hãy tạo hoặc chọn một menu để bắt đầu.
                </div>
            @endif
        </section>
    </div>
</div>
