<x-layouts::app :title="'SEO Editor'">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">SEO Editor</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Biên tập nội dung, metadata và kiểm tra QA cho từng trang SEO AI trước khi xuất bản.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.seo.pages.preview', $page) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                    <i class="fa-solid fa-eye"></i>
                    Xem trước frontsite
                </a>
                @if ($page->status->value === 'published')
                    <a href="{{ $page->canonical_url ?: route('seo-pages.show', $page->slug) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:text-emerald-300">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        Mở trang live
                    </a>
                @endif
                <a href="{{ route('admin.seo.pages.index') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                    <i class="fa-solid fa-arrow-left"></i>
                    Về danh sách SEO Pages
                </a>
            </div>
        </div>

        <livewire:admin.seo.seo-page-editor :page="$page" />
    </div>
</x-layouts::app>
