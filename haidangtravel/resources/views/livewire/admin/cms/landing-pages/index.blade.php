<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Danh sách landing page</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Index riêng cho landing page để lọc nhanh theo loại page, template và trạng thái publish.</p>
        </div>

        @if ($canEdit)
            <a href="{{ route('admin.landing-pages.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
                <i class="fa-solid fa-plus"></i>
                Tạo landing page
            </a>
        @endif
    </div>

    <x-admin.form-feedback />

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 grid gap-3 xl:grid-cols-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tiêu đề, slug hoặc page key..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

            <select wire:model.live="typeFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả loại page</option>
                <option value="system">System</option>
                <option value="custom">Custom</option>
            </select>

            <select wire:model.live="templateFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả template</option>
                @foreach ($templates as $templateKey => $templateLabel)
                    <option value="{{ $templateKey }}">{{ $templateLabel }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả trạng thái</option>
                <option value="active">active</option>
                <option value="inactive">inactive</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">Landing page</th>
                        <th class="px-4 py-3">Loại</th>
                        <th class="px-4 py-3">Template</th>
                        <th class="px-4 py-3">Route</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Cập nhật</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($pages as $page)
                        @php
                            $isSystem = filled($page->page_key);
                        @endphp
                        <tr wire:key="landing-page-row-{{ $page->id }}" class="align-top">
                            <td class="px-4 py-4">
                                <a href="{{ route('admin.landing-pages.edit', $page) }}" wire:navigate class="font-semibold text-zinc-900 transition hover:text-teal-700 dark:text-white">
                                    {{ $page->title }}
                                </a>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $isSystem ? ($systemPages[$page->page_key] ?? $page->page_key) : 'Landing custom' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $isSystem ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' }}">
                                    {{ $isSystem ? 'System' : 'Custom' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $templates[$page->template_key] ?? $page->template_key }}</td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">{{ $isSystem ? ($page->page_key ?: '-') : ('/' . ltrim((string) ($page->slug ?: ''), '/')) }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $page->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                    {{ $page->is_active ? 'active' : 'inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">{{ optional($page->updated_at)->format('d/m/Y H:i') ?: '-' }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.landing-pages.edit', $page) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                        Sửa
                                    </a>
                                    @if (! $isSystem && $canEdit)
                                        <button type="button" wire:click="deletePage({{ $page->id }})" wire:confirm="Bạn có chắc chắn muốn xóa landing page custom này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                                            Xóa
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có landing page nào khớp bộ lọc hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $pages->links() }}
        </div>
    </section>
</div>
