<div class="space-y-4 pb-24">
    <header class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div><h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Duyệt đề xuất SEO</h1><p class="mt-1 break-all text-sm text-zinc-500 dark:text-zinc-400">{{ $proposal->page->title }} · {{ $proposal->page->path }}</p></div>
        <flux:button href="{{ route('admin.seo-optimization.pages.show', $proposal->page) }}" wire:navigate>Về chi tiết URL</flux:button>
    </header>
    @include('livewire.admin.cms.partials.admin-group-submenu', ['groupKey' => 'seo-optimization'])
    @include('livewire.admin.seo-optimization.feedback')
    <section class="space-y-3 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center gap-3"><h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $this->statusLabel($proposal->status) }}</h2><span class="text-xs text-zinc-500 dark:text-zinc-400">Tạo {{ $proposal->created_at?->format('d/m/Y H:i') }} · {{ $proposal->id }}</span></div>
        <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $proposal->notes ?: 'Chưa có ghi chú bổ sung.' }}</p>
        <div class="flex flex-wrap gap-4 text-xs text-zinc-500 dark:text-zinc-400"><span>Người tạo: {{ $proposal->created_by ?: '—' }}</span><span>Người duyệt: {{ $proposal->approved_by ?: '—' }}</span><span>Duyệt: {{ $proposal->approved_at?->format('d/m/Y H:i') ?: '—' }}</span><span>Áp dụng: {{ $proposal->applied_at?->format('d/m/Y H:i') ?: '—' }}</span></div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">HTML được hiển thị dưới dạng văn bản an toàn để rà soát. Mỗi lần duyệt và áp dụng đều kiểm tra lại quyền, phiên bản nguồn và dữ liệu chứng minh.</p>
        @if(data_get($proposal->qa, 'authorization.kind') === 'server_policy')
            <p class="text-sm text-amber-800 dark:text-amber-200">Được server tự áp dụng theo cấu hình Luôn publish. Đây không phải lượt duyệt thủ công. Cấu hình bởi tài khoản {{ data_get($proposal->qa, 'authorization.configured_by') }}.</p>
        @endif
    </section>
    @if ($proposal->missing_facts)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950"><h2 class="font-semibold text-amber-900 dark:text-amber-100">NEED_DATA — cần bổ sung dữ liệu</h2><pre class="mt-2 overflow-auto whitespace-pre-wrap break-words text-sm text-amber-900 dark:text-amber-100">{{ json_encode($proposal->missing_facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></section>
    @endif
    <section class="space-y-4 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">So sánh nội dung trước / sau</h2>
        @forelse (($proposal->patch ?? []) as $field => $value)
            <div wire:key="patch-{{ $field }}" class="space-y-2"><h3 class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $field }}</h3><div class="grid gap-3 lg:grid-cols-2">
                <div class="min-w-0 rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-950"><p class="mb-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400">TRƯỚC</p><pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words text-xs text-zinc-700 dark:text-zinc-200">{{ is_string(data_get($proposal->before, $field)) ? data_get($proposal->before, $field) : json_encode(data_get($proposal->before, $field), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></div>
                <div class="min-w-0 rounded-2xl bg-emerald-50 p-3 dark:bg-emerald-950/40"><p class="mb-2 text-xs font-semibold text-emerald-700 dark:text-emerald-300">ĐỀ XUẤT</p><pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words text-xs text-zinc-700 dark:text-zinc-200">{{ is_string($value) ? $value : json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></div>
            </div></div>
        @empty
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Đề xuất chưa có trường nội dung để áp dụng.</p>
        @endforelse
    </section>
    <section class="space-y-3 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Kiểm tra an toàn và nguồn chứng minh</h2>
        <details open><summary class="cursor-pointer text-sm font-medium text-zinc-700 dark:text-zinc-200">QA của đề xuất</summary><pre class="mt-2 max-h-96 overflow-auto whitespace-pre-wrap break-words rounded-2xl bg-zinc-50 p-3 text-xs text-zinc-600 dark:bg-zinc-950 dark:text-zinc-300">{{ json_encode($proposal->qa, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>
        <details><summary class="cursor-pointer text-sm font-medium text-zinc-700 dark:text-zinc-200">Các khẳng định và nguồn</summary><pre class="mt-2 max-h-96 overflow-auto whitespace-pre-wrap break-words rounded-2xl bg-zinc-50 p-3 text-xs text-zinc-600 dark:bg-zinc-950 dark:text-zinc-300">{{ json_encode($proposal->claims, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>
    </section>
    @can('admin.seo-optimization.approve')
        @if (in_array($proposal->status, ['in_review', 'need_data', 'approved', 'stale'], true))
            <form wire:submit="reject" data-admin-feedback-form class="space-y-3 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"><flux:textarea label="Lý do từ chối / yêu cầu chỉnh sửa" wire:model="rejectionReason" rows="3" maxlength="2000" /><flux:button type="submit" wire:loading.attr="disabled">Từ chối đề xuất</flux:button></form>
        @endif
    @endcan
    <div class="sticky bottom-3 z-10 flex flex-wrap items-center justify-end gap-3 rounded-2xl border border-zinc-200 bg-white/95 p-3 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95">
        <p class="mr-auto text-xs text-zinc-500 dark:text-zinc-400">Duyệt và áp dụng là hai thao tác riêng.</p>
        @can('admin.seo-optimization.approve')
            @if ($proposal->status === 'in_review')
                <form wire:submit="approve" data-admin-feedback-form><flux:button type="submit" variant="primary" wire:confirm="Bạn đã kiểm tra nội dung, nguồn facts và đồng ý duyệt đề xuất này?" wire:loading.attr="disabled">Duyệt đề xuất</flux:button></form>
            @endif
        @endcan
        @can('admin.seo-optimization.apply')
            @if (in_array($proposal->status, ['applied', 'verify_failed'], true))
                <form wire:submit="verify" data-admin-feedback-form><flux:button type="submit" wire:loading.attr="disabled">Kiểm tra lại</flux:button></form>
            @endif
            @if ($proposal->status === 'approved')
                <form wire:submit="apply" data-admin-feedback-form data-admin-loading-text="Đang áp dụng và kiểm tra lại..."><flux:button type="submit" variant="primary" wire:confirm="Áp dụng các trường đã duyệt lên CMS? Thay đổi sẽ ảnh hưởng nội dung public của URL này." wire:loading.attr="disabled">Áp dụng lên CMS</flux:button></form>
            @endif
        @endcan
        @can('admin.seo-optimization.rollback')
            @if (in_array($proposal->status, ['applied', 'verify_failed'], true))
                <form wire:submit="rollback" data-admin-feedback-form><flux:button type="submit" wire:loading.attr="disabled">Tạo đề xuất hoàn tác</flux:button></form>
            @endif
        @endcan
    </div>
</div>
