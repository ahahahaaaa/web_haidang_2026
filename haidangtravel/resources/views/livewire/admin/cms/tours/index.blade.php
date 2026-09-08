<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Danh sách tour</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý tour theo scope travel, category, điểm đến và lịch khởi hành thực tế.</p>
        </div>

        <a href="{{ route('admin.tours.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
            <i class="fa-solid fa-plus"></i>
            Tour mới
        </a>
    </div>

    @include('livewire.admin.cms.partials.tours-submenu')

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 grid gap-3 xl:grid-cols-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên tour..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

            <select wire:model.live="categoryFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="destinationFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả điểm đến</option>
                @foreach ($destinations as $destination)
                    <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                @endforeach
            </select>

            @if (! auth()->user()?->hasRole('sale'))
                <select wire:model.live="managerFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Tất cả người phụ trách</option>
                    <option value="0">Chưa gán phụ trách</option>
                    @foreach ($saleUsers as $saleUser)
                        <option value="{{ $saleUser->id }}">{{ $saleUser->name }}</option>
                    @endforeach
                </select>
            @endif

            <select wire:model.live="scopeFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả phạm vi</option>
                @foreach ($scopeOptions as $scopeOption)
                    <option value="{{ $scopeOption->value }}">{{ $scopeOption->label() }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả trạng thái</option>
                <option value="draft">draft</option>
                <option value="published">published</option>
                <option value="archived">archived</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">Tour</th>
                        <th class="px-4 py-3">Danh mục</th>
                        <th class="px-4 py-3">Điểm đến</th>
                        <th class="px-4 py-3">Phạm vi</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Giá / khởi hành</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($tours as $tour)
                        <tr wire:key="tour-{{ $tour->id }}" class="align-top">
                            <td class="px-4 py-4">
                                <a href="{{ route('admin.tours.edit', $tour) }}" wire:navigate class="font-semibold text-zinc-900 transition hover:text-red-600 dark:text-white">
                                    {{ $tour->title }}
                                </a>
                                <div class="mt-1 space-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    <p>Khởi hành mặc định: {{ $tour->departure_location ?: 'Chưa cập nhật' }}</p>
                                    <p>Liên hệ: {{ $tour->contact_phone ?: 'Chưa cập nhật' }}</p>
                                    @if ($tour->transport)
                                        <p>Phương tiện: {{ $tour->transport }}</p>
                                    @endif
                                    @if ($tour->manager)
                                        <p>Sale phụ trách: {{ $tour->manager->name }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $tour->primaryCategory?->name ?: 'Chưa gán' }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $tour->destination?->name ?: 'Chưa gán' }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $tour->scope?->label() ?: 'Chưa gán' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $tour->status === 'published' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : ($tour->status === 'archived' ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300') }}">
                                    {{ $tour->status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">
                                <p>{{ $tour->sale_price ? number_format($tour->sale_price, 0, ',', '.') . ' đ' : 'Liên hệ' }}</p>
                                <p class="mt-1 text-xs">Departure: {{ $tour->departures_count }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.tours.reviews.index', $tour) }}" wire:navigate class="rounded-2xl border border-amber-200 px-3 py-2 text-sm font-medium text-amber-700 dark:border-amber-500/30 dark:text-amber-300">
                                        Quản lý đánh giá
                                    </a>
                                    <a href="{{ route('admin.tours.edit', $tour) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                        Sửa
                                    </a>
                                    <button type="button" wire:click="deleteTour({{ $tour->id }})" wire:confirm="Bạn có chắc chắn muốn xóa tour này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có tour nào khớp bộ lọc hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $tours->links() }}
        </div>
    </section>
</div>
