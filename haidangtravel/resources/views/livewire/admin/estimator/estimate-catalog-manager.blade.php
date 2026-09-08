<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Catalog cấu phần dự toán</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý cây thành phần nhà và item báo giá dùng cho Dự toán nâng cao / Dự toán nội bộ.</p>
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
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Cây component node</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Nhập `parent_code` để tạo cây theo thuật ngữ xây dựng.</p>
                </div>

                <button type="button" wire:click="addNode" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                    Thêm node
                </button>
            </div>

            <div class="mt-5 space-y-4">
                @foreach ($nodes as $index => $node)
                    <div wire:key="catalog-node-{{ $index }}" class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Code</span>
                                <input type="text" wire:model.defer="nodes.{{ $index }}.code" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2 xl:col-span-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tên node</span>
                                <input type="text" wire:model.defer="nodes.{{ $index }}.name" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Node type</span>
                                <input type="text" wire:model.defer="nodes.{{ $index }}.node_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Parent code</span>
                                <input type="text" wire:model.defer="nodes.{{ $index }}.parent_code" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="flex items-center gap-3 pt-8">
                                <input type="checkbox" wire:model.defer="nodes.{{ $index }}.is_active" class="size-4 rounded border-zinc-300 text-red-600">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Đang hoạt động</span>
                            </label>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-[1fr_180px]">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Building types</span>
                                <input type="text" wire:model.defer="nodes.{{ $index }}.building_types_text" placeholder="townhouse, villa, office, shophouse" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Sort</span>
                                <input type="number" wire:model.defer="nodes.{{ $index }}.sort_order" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Catalog item báo giá</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Mỗi item gắn vào một node lá và dùng để tính overlay chi tiết.</p>
                </div>

                <button type="button" wire:click="addItem" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                    Thêm item
                </button>
            </div>

            <div class="mt-5 space-y-4">
                @foreach ($items as $index => $item)
                    <div wire:key="catalog-item-{{ $index }}" class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="grid gap-4 xl:grid-cols-6">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Code</span>
                                <input type="text" wire:model.defer="items.{{ $index }}.code" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2 xl:col-span-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tên item</span>
                                <input type="text" wire:model.defer="items.{{ $index }}.name" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Node code</span>
                                <input type="text" wire:model.defer="items.{{ $index }}.component_node_code" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Item type</span>
                                <input type="text" wire:model.defer="items.{{ $index }}.item_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="flex items-center gap-3 pt-8">
                                <input type="checkbox" wire:model.defer="items.{{ $index }}.is_active" class="size-4 rounded border-zinc-300 text-red-600">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Đang hoạt động</span>
                            </label>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Unit</span>
                                <input type="text" wire:model.defer="items.{{ $index }}.unit" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Pricing mode</span>
                                <input type="text" wire:model.defer="items.{{ $index }}.pricing_mode" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2 xl:col-span-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Default quantity formula</span>
                                <input type="text" wire:model.defer="items.{{ $index }}.default_quantity_formula" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Price book code</span>
                                <input type="text" wire:model.defer="items.{{ $index }}.price_book_code" list="price-book-codes" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                        </div>

                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Level support</span>
                                <input type="text" wire:model.defer="items.{{ $index }}.level_support_text" placeholder="level_2, level_3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                            <label class="space-y-2">
                                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Building types</span>
                                <input type="text" wire:model.defer="items.{{ $index }}.building_types_text" placeholder="townhouse, villa" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>

            <datalist id="price-book-codes">
                @foreach ($priceBookCodes as $code)
                    <option value="{{ $code }}"></option>
                @endforeach
            </datalist>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white">
                Lưu catalog dự toán
            </button>
        </div>
    </form>
</div>
