<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Danh sách slider</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Index riêng để lọc theo banner-location và kiểm tra nhanh trạng thái từng slider trước khi vào editor.</p>
        </div>

        <a href="{{ route('admin.sliders.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
            <i class="fa-solid fa-plus"></i>
            Tạo slider
        </a>
    </div>

    <x-admin.form-feedback />

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 grid gap-3 xl:grid-cols-2">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên hoặc banner-location..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

            <select wire:model.live="statusFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả trạng thái</option>
                <option value="active">active</option>
                <option value="inactive">inactive</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">Slider</th>
                        <th class="px-4 py-3">Banner-location</th>
                        <th class="px-4 py-3">Items</th>
                        <th class="px-4 py-3">Autoplay</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($sliders as $slider)
                        <tr wire:key="slider-index-row-{{ $slider->id }}" class="align-top">
                            <td class="px-4 py-4 font-semibold text-zinc-900 dark:text-white">{{ $slider->name }}</td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">{{ $slider->location ?: 'Chưa có' }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $slider->items_count }}</td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">{{ $slider->autoplay_delay ? number_format($slider->autoplay_delay) . ' ms' : 'Tắt' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $slider->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                    {{ $slider->is_active ? 'active' : 'inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end">
                                    <a href="{{ route('admin.sliders.edit', $slider) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                        Sửa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có slider nào khớp bộ lọc hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $sliders->links() }}
        </div>
    </section>
</div>
