<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Bảng giá dự toán</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý price book versioned cho `level_2` và `level_3`. Mỗi dòng giá dùng cú pháp `item_code=price`.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <form wire:submit="save" class="space-y-6">
        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Danh sách bảng giá</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Dùng `status=published` để bật cho frontsite và nội bộ.</p>
                </div>

                <button type="button" wire:click="addBook" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                    Thêm bảng giá
                </button>
            </div>

            <div class="mt-5 space-y-4">
                @foreach ($books as $index => $book)
                    <div wire:key="price-book-{{ $index }}" class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Code</span>
                                <input type="text" wire:model.defer="books.{{ $index }}.code" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2 xl:col-span-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tên bảng giá</span>
                                <input type="text" wire:model.defer="books.{{ $index }}.name" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Version</span>
                                <input type="text" wire:model.defer="books.{{ $index }}.version" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Status</span>
                                <input type="text" wire:model.defer="books.{{ $index }}.status" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="flex items-center gap-3 pt-8">
                                <input type="checkbox" wire:model.defer="books.{{ $index }}.is_active" class="size-4 rounded border-zinc-300 text-red-600">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Đang hoạt động</span>
                            </label>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Level support</span>
                                <input type="text" wire:model.defer="books.{{ $index }}.level_support_text" placeholder="level_2 hoặc level_3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Building types</span>
                                <input type="text" wire:model.defer="books.{{ $index }}.building_types_text" placeholder="townhouse, villa, office" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                        </div>

                        <label class="mt-4 block space-y-2">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Mô tả</span>
                            <textarea rows="2" wire:model.defer="books.{{ $index }}.description" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                        </label>

                        <label class="mt-4 block space-y-2">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Item prices</span>
                            <textarea rows="10" wire:model.defer="books.{{ $index }}.item_prices_text" placeholder="living_room_standard=68000000" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                        </label>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white">
                Lưu bảng giá
            </button>
        </div>
    </form>
</div>
