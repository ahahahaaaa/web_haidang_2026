<div class="space-y-4">
    @include('livewire.admin.cms.partials.tours-submenu')

    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Flash Sale tour</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Giá ưu đãi gắn theo đúng lịch khởi hành và chỉ có hiệu lực trong thời gian campaign.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            @can('admin.tours.edit')
                <a href="{{ route('admin.tour-flash-sales.create') }}" wire:navigate class="rounded-2xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white">Tạo Flash Sale</a>
            @endcan
        </div>
    </div>

    @if (! $isEditor)
        <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 grid gap-3 md:grid-cols-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên hoặc slug..." class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <select wire:model.live="statusFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Đang diễn ra</option>
                    <option value="inactive">Đang tắt</option>
                </select>
            </div>

            <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                        <tr class="text-left text-[11px] uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">
                            <th class="px-4 py-3">Campaign</th>
                            <th class="px-4 py-3">Thời gian</th>
                            <th class="px-4 py-3">Tour</th>
                            <th class="px-4 py-3 text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($campaigns as $campaign)
                            <tr wire:key="tour-flash-sale-{{ $campaign->id }}">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $campaign->title }}</p>
                                    <p class="mt-1 text-xs text-zinc-500">{{ $campaign->slug }}</p>
                                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $campaign->isCurrentlyActive() ? 'bg-emerald-100 text-emerald-700' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                        {{ $campaign->isCurrentlyActive() ? 'Đang diễn ra' : ($campaign->is_active ? 'Ngoài thời gian' : 'Đang tắt') }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                    <p>{{ $campaign->starts_at?->format('d/m/Y H:i') }}</p>
                                    <p class="mt-1">đến {{ $campaign->ends_at?->format('d/m/Y H:i') }}</p>
                                </td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $campaign->items_count }} lịch khởi hành</td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        @can('admin.tours.edit')
                                            <a href="{{ route('admin.tour-flash-sales.edit', $campaign) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">Sửa</a>
                                            <button type="button" wire:click="deleteFlashSale({{ $campaign->id }})" wire:confirm="Xóa campaign Flash Sale này?" class="rounded-2xl border border-red-200 px-3 py-2 font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">Xóa</button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-10 text-center text-zinc-500">Chưa có campaign Flash Sale.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">{{ $campaigns->links() }}</div>
        </section>
    @else
        <form wire:submit="save" class="space-y-5 rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
            <div>
                <a href="{{ route('admin.tour-flash-sales') }}" wire:navigate class="text-sm font-semibold text-teal-700 dark:text-teal-300">Quay lại danh sách</a>
                <h2 class="mt-3 text-xl font-semibold text-zinc-900 dark:text-white">{{ $selectedId ? 'Cập nhật Flash Sale' : 'Tạo Flash Sale mới' }}</h2>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tiêu đề</span>
                    <input type="text" wire:model.live.debounce.300ms="form.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.title') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Slug</span>
                    <input type="text" wire:model="form.slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.slug') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block space-y-2 md:col-span-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Mô tả</span>
                    <textarea rows="3" wire:model="form.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                    @error('form.description') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Icon tiêu đề (Font Awesome)</span>
                    <input type="text" wire:model="form.icon_class" placeholder="fa-solid fa-bolt" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </label>
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Thứ tự</span>
                    <input type="number" min="0" wire:model="form.sort_order" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </label>
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Bắt đầu</span>
                    <input type="datetime-local" wire:model="form.starts_at" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.starts_at') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Kết thúc</span>
                    <input type="datetime-local" wire:model="form.ends_at" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.ends_at') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Label liên kết xem thêm</span>
                    <input type="text" wire:model="form.cta_label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </label>
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">URL xem thêm</span>
                    <input type="text" wire:model="form.cta_url" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </label>
            </div>

            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                <input type="checkbox" wire:model="form.is_active" class="rounded border-zinc-300 text-teal-600">
                Bật campaign
            </label>

            <section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Tour và giá Flash Sale</h3>
                        <p class="mt-1 text-xs leading-6 text-zinc-500">Mỗi giá áp dụng cho đúng một lịch khởi hành. Một vé tương ứng một khách, không phân biệt người lớn hay trẻ em.</p>
                    </div>
                    <button type="button" wire:click="addItem" class="rounded-2xl border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">Thêm tour</button>
                </div>

                @foreach (($form['items'] ?? []) as $itemIndex => $item)
                    <div wire:key="flash-sale-item-{{ $itemIndex }}-{{ $item['tour_departure_id'] ?? 'new' }}" class="grid gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 md:grid-cols-[minmax(0,1fr)_190px_160px_100px_auto] md:items-end">
                        <label class="block space-y-2">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tour / lịch khởi hành</span>
                            <select wire:model="form.items.{{ $itemIndex }}.tour_departure_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <option value="">Chọn lịch khởi hành</option>
                                @foreach ($departureOptions as $departureOption)
                                    <option value="{{ $departureOption->id }}">
                                        {{ $departureOption->tour?->title }} — {{ $departureOption->departure_date?->format('d/m/Y') ?: 'Chưa chốt ngày' }} — {{ number_format((int) ($departureOption->sale_price ?: $departureOption->base_price), 0, ',', '.') }} đ
                                    </option>
                                @endforeach
                            </select>
                            @error('form.items.'.$itemIndex.'.tour_departure_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label class="block space-y-2">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Giá Flash Sale</span>
                            <input type="number" min="1" step="1" wire:model="form.items.{{ $itemIndex }}.flash_price" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error('form.items.'.$itemIndex.'.flash_price') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label class="block space-y-2">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tổng số vé</span>
                            <input type="number" min="1" max="100000" step="1" wire:model="form.items.{{ $itemIndex }}.ticket_quantity" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            <span class="block text-xs text-zinc-500">Đã đặt {{ (int) ($item['booked_quantity'] ?? 0) }} vé</span>
                            @error('form.items.'.$itemIndex.'.ticket_quantity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        </label>
                        <label class="block space-y-2">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Thứ tự</span>
                            <input type="number" min="0" wire:model="form.items.{{ $itemIndex }}.sort_order" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </label>
                        <button type="button" wire:click="removeItem({{ $itemIndex }})" class="rounded-2xl border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 dark:border-red-500/30 dark:text-red-300">Xóa</button>
                    </div>
                @endforeach
                @error('form.items') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </section>

            <div class="sticky bottom-4 z-10 flex flex-wrap justify-end gap-2 rounded-2xl border border-zinc-200 bg-white/95 p-3 shadow-lg backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
                <a href="{{ route('admin.tour-flash-sales') }}" wire:navigate class="rounded-2xl border border-zinc-200 px-5 py-3 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">Hủy</a>
                <button type="submit" wire:loading.attr="disabled" class="rounded-2xl bg-teal-600 px-5 py-3 text-sm font-semibold text-white disabled:opacity-60">Lưu Flash Sale</button>
            </div>
        </form>
    @endif
</div>
