<div class="space-y-4">
    <header class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">SEO AI Optimize</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Từ khóa, kiểm tra nội dung và đề xuất tối ưu cho URL đang có trong CMS.</p>
        </div>
        @can('admin.seo-optimization.settings')
            @can('admin.seo-optimization.audit')
                <form wire:submit="syncInventory" data-admin-feedback-form data-admin-loading-text="Đang đối soát URL...">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">Đồng bộ danh sách URL</flux:button>
                </form>
            @endcan
        @endcan
    </header>
    @include('livewire.admin.cms.partials.admin-group-submenu', ['groupKey' => 'seo-optimization'])
    @include('livewire.admin.seo-optimization.feedback')

    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-100">
        Codex chạy theo lịch chỉ tạo đề xuất. Người có quyền phải duyệt và áp dụng riêng; điểm SEO không tự cho phép xuất bản.
    </div>
    <section class="grid grid-cols-2 gap-3 xl:grid-cols-4" aria-label="Tổng quan trong phạm vi quyền của bạn">
        @foreach (['total' => 'URL trong phạm vi', 'indexable' => 'Trang SEO', 'audited' => 'Đã kiểm tra', 'proposals' => 'Trang có đề xuất chờ duyệt'] as $key => $label)
            <div wire:key="seo-stat-{{ $key }}" class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format($stats[$key]) }}</p>
            </div>
        @endforeach
    </section>
    <section class="space-y-4 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <flux:input label="Tìm URL / tiêu đề" wire:model.live.debounce.350ms="search" placeholder="Ví dụ: Đà Lạt" />
            <flux:select label="Loại trang" wire:model.live="pageType">
                <option value="">Tất cả loại trang</option>
                @foreach ($pageTypes as $type)
                    <option wire:key="seo-type-{{ $type }}" value="{{ $type }}">{{ $this->pageTypeLabel($type) }}</option>
                @endforeach
            </flux:select>
            <flux:select label="Phân loại URL" wire:model.live="classification">
                <option value="">Tất cả phân loại</option>
                @foreach ($classifications as $type)
                    <option wire:key="seo-classification-{{ $type }}" value="{{ $type }}">{{ $this->statusLabel($type) }}</option>
                @endforeach
            </flux:select>
            <flux:select label="Brief từ khóa" wire:model.live="briefFilter">
                <option value="">Tất cả</option><option value="missing">Chưa có từ khóa chính</option><option value="configured">Đã có từ khóa chính</option>
            </flux:select>
        </div>
        <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400"><tr><th class="px-4 py-3">Trang / URL</th><th class="px-4 py-3">Loại</th><th class="px-4 py-3">Từ khóa chính</th><th class="px-4 py-3">Lịch sử</th><th class="px-4 py-3">Đối soát</th></tr></thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($pages as $page)
                        <tr wire:key="seo-page-{{ $page->id }}" class="align-top">
                            <td class="max-w-lg px-4 py-3"><a wire:navigate href="{{ route('admin.seo-optimization.pages.show', $page) }}" class="font-semibold text-teal-700 hover:underline dark:text-teal-300">{{ $page->title ?: $page->path }}</a><p class="mt-1 break-all text-xs text-zinc-500 dark:text-zinc-400">{{ $page->path }}</p></td>
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $this->pageTypeLabel($page->page_type) }}<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $this->statusLabel($page->classification) }}</p></td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ data_get($page->keyword_brief, 'primary_keyword') ?: 'Chưa cấu hình' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $page->audits_count }} lần kiểm tra<br>{{ $page->proposals_count }} đề xuất · {{ $page->tasks_count }} yêu cầu</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $page->last_seen_at?->format('d/m/Y H:i') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">Chưa có URL phù hợp. Người quản trị có thể đồng bộ danh sách URL, hoặc điều chỉnh bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $pages->links() }}
    </section>
</div>
