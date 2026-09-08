<div class="space-y-4 pb-24">
    <header class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div class="min-w-0"><h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $page->title }}</h1><p class="mt-1 break-all text-sm text-zinc-500 dark:text-zinc-400">{{ $page->path }} · {{ $this->pageTypeLabel($page->page_type) }} · {{ $this->statusLabel($page->classification) }}</p></div>
        <flux:button href="{{ route('admin.seo-optimization.index') }}" wire:navigate>Danh sách URL</flux:button>
    </header>
    @include('livewire.admin.cms.partials.admin-group-submenu', ['groupKey' => 'seo-optimization'])
    @include('livewire.admin.seo-optimization.feedback')
    <section class="space-y-3 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div><h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Kiểm tra nội dung hiện tại</h2><p class="text-sm text-zinc-500 dark:text-zinc-400">Kết quả gắn với phiên bản nguồn; kiểm tra lại sau khi biên tập CMS.</p></div>
            <div class="flex flex-wrap gap-2">
                @can('admin.seo-optimization.audit')
                    <form wire:submit="runAudit" data-admin-feedback-form data-admin-loading-text="Đang kiểm tra SEO..."><flux:button type="submit" wire:loading.attr="disabled">Kiểm tra SEO</flux:button></form>
                @endcan
                @can('admin.seo-optimization.propose')
                    <form wire:submit="enqueue" data-admin-feedback-form data-admin-loading-text="Đang tạo yêu cầu..."><flux:button type="submit" variant="primary" wire:loading.attr="disabled">Yêu cầu Codex tạo đề xuất</flux:button></form>
                @endcan
            </div>
        </div>
        @if ($latestAudit)
            <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-700 dark:text-zinc-200"><span class="text-2xl font-semibold">{{ $latestAudit->score === null ? 'Chưa đủ dữ liệu' : $latestAudit->score.'/100' }}</span><span>{{ $this->statusLabel($latestAudit->grade) }} · {{ $this->statusLabel($latestAudit->status) }}</span><span class="text-xs text-zinc-500">{{ $latestAudit->created_at?->format('d/m/Y H:i') }}</span></div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Điểm kiểm tra nội bộ, không phải cam kết thứ hạng. Báo cáo chi tiết nêu các tiêu chí đã đánh giá và phần cần người duyệt.</p>
            <details class="rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-950"><summary class="cursor-pointer text-sm font-medium text-zinc-700 dark:text-zinc-200">Xem tiêu chí, bằng chứng và dữ liệu còn thiếu</summary><pre class="mt-3 max-h-96 overflow-auto whitespace-pre-wrap break-words text-xs text-zinc-600 dark:text-zinc-300">{{ json_encode($latestAudit->report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></details>
        @else
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Chưa có kết quả kiểm tra cho URL này.</p>
        @endif
    </section>
    <form wire:submit="saveBrief" data-admin-feedback-form data-admin-loading-text="Đang lưu brief từ khóa..." class="space-y-4 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div><h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Brief từ khóa và nguồn dữ liệu</h2><p class="text-sm text-zinc-500 dark:text-zinc-400">Mỗi dòng một mục. Lưu brief không thay đổi nội dung trang public.</p></div>
        <div class="grid gap-4 md:grid-cols-2">
            <flux:input label="Từ khóa chính" wire:model="primaryKeyword" maxlength="200" />
            <flux:select label="Ý định tìm kiếm" wire:model="searchIntent"><option value="">Chọn ý định tìm kiếm</option>@foreach ($intents as $value => $label)<option wire:key="intent-{{ $loop->index }}" value="{{ $value }}">{{ $label }}</option>@endforeach</flux:select>
            <flux:textarea label="Từ khóa phụ" wire:model="secondaryKeywords" rows="4" />
            <flux:textarea label="Thuật ngữ ngữ nghĩa" wire:model="semanticTerms" rows="4" />
            <flux:textarea label="Thực thể cần đề cập" wire:model="entities" rows="4" />
            <flux:textarea label="Chủ đề cần có" wire:model="requiredTopics" rows="4" />
            <flux:textarea label="Liên kết nội bộ bắt buộc" wire:model="requiredInternalLinks" rows="4" placeholder="Đường dẫn canonical đang hoạt động, mỗi dòng một URL" />
            <flux:textarea label="Ghi chú biên tập" wire:model="notes" rows="4" />
        </div>
        <flux:textarea label="Nguồn chứng minh facts (mảng JSON)" wire:model="factSources" rows="6" />
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Chỉ đưa nguồn có thể kiểm chứng. Giá, lịch đi, visa, chính sách, xếp hạng và đánh giá chưa đủ nguồn phải để NEED_DATA. Không đặt API key, token hoặc dữ liệu cá nhân vào brief.</p>
        @can('admin.seo-optimization.propose')
            <div class="sticky bottom-3 z-10 flex justify-end rounded-2xl border border-zinc-200 bg-white/95 p-3 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95"><flux:button type="submit" variant="primary" wire:loading.attr="disabled">Lưu brief từ khóa</flux:button></div>
        @endcan
    </form>
    <section class="space-y-3 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Đề xuất gần đây</h2>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="text-xs text-zinc-500 dark:text-zinc-400"><tr><th class="py-2">Đề xuất</th><th class="px-3 py-2">Trạng thái</th><th class="px-3 py-2">Ngày tạo</th></tr></thead><tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($proposals as $proposal)
                <tr wire:key="proposal-{{ $proposal->id }}"><td class="py-3"><a wire:navigate href="{{ route('admin.seo-optimization.proposals.show', $proposal) }}" class="font-medium text-teal-700 hover:underline dark:text-teal-300">Xem đề xuất {{ $proposal->id }}</a></td><td class="px-3 py-3 text-zinc-700 dark:text-zinc-200">{{ $this->statusLabel($proposal->status) }}</td><td class="whitespace-nowrap px-3 py-3 text-zinc-500 dark:text-zinc-400">{{ $proposal->created_at?->format('d/m/Y H:i') }}</td></tr>
            @empty
                <tr><td colspan="3" class="py-6 text-center text-zinc-500 dark:text-zinc-400">Chưa có đề xuất. Hãy lưu brief, kiểm tra SEO và đưa URL vào hàng chờ.</td></tr>
            @endforelse
        </tbody></table></div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Hiển thị tối đa 20 đề xuất mới nhất của URL này.</p>
    </section>
    <section class="space-y-3 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Yêu cầu Codex gần đây</h2>
        @forelse ($tasks as $task)
            <div wire:key="page-task-{{ $task->id }}" class="flex flex-wrap items-center gap-3 text-sm text-zinc-600 dark:text-zinc-300">
                <p class="break-all">{{ $task->id }} · {{ $this->statusLabel($task->status) }} · {{ $task->created_at?->format('d/m/Y H:i') }}</p>
                @can('admin.seo-optimization.propose')
                    @if (in_array($task->status, ['queued', 'leased'], true))
                        <flux:button size="sm" wire:click="cancelTask('{{ $task->id }}')" wire:confirm="Hủy yêu cầu này? Codex sẽ không thể gửi kết quả cho lượt đã hủy." wire:loading.attr="disabled">Hủy yêu cầu</flux:button>
                    @endif
                @endcan
            </div>
        @empty
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Chưa có yêu cầu.</p>
        @endforelse
        <flux:button href="{{ route('admin.seo-optimization.tasks.index') }}" wire:navigate>Xem hàng chờ</flux:button>
    </section>
</div>
