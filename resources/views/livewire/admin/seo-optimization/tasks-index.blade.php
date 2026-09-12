<div class="space-y-4" wire:poll.15s.visible>
    <header class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Hàng chờ SEO AI Optimize</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Theo dõi yêu cầu Codex theo lịch. Server tự publish khi policy Luôn publish và điểm sau lớn hơn điểm trước; các trường hợp còn lại chờ duyệt.</p>
        </div>
        @if ($canManageTasks && $finishedTasksCount > 0)
            <flux:button
                variant="danger"
                wire:click="deleteFinishedTasks"
                wire:confirm="Xóa {{ $finishedTasksCount }} task đã kết thúc khỏi hàng chờ? Proposal, asset, backup và nhật ký liên quan vẫn được giữ."
                wire:loading.attr="disabled"
                wire:target="deleteFinishedTasks"
            >
                <span wire:loading.remove wire:target="deleteFinishedTasks">Xóa tất cả đã kết thúc ({{ $finishedTasksCount }})</span>
                <span wire:loading wire:target="deleteFinishedTasks">Đang xóa...</span>
            </flux:button>
        @endif
    </header>
    @include('livewire.admin.cms.partials.admin-group-submenu', ['groupKey' => 'seo-optimization'])
    @include('livewire.admin.seo-optimization.feedback')
    <section class="space-y-4 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-3 md:grid-cols-2"><flux:input label="Tìm trang / URL" wire:model.live.debounce.350ms="search" /><flux:select label="Trạng thái" wire:model.live="status"><option value="">Tất cả trạng thái</option>@foreach ($statuses as $value)<option wire:key="task-status-{{ $value }}" value="{{ $value }}">{{ $this->statusLabel($value) }}</option>@endforeach</flux:select></div>
        <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-800"><table class="min-w-full text-left text-sm"><thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400"><tr><th class="px-4 py-3">Trang</th><th class="px-4 py-3">Trạng thái / số lần</th><th class="px-4 py-3">Thời gian</th><th class="px-4 py-3">Thông tin</th><th class="px-4 py-3 text-right">Hành động</th></tr></thead><tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($tasks as $task)
                <tr wire:key="task-{{ $task->id }}" class="align-top"><td class="max-w-md px-4 py-3"><a wire:navigate href="{{ route('admin.seo-optimization.pages.show', $task->page) }}" class="font-semibold text-teal-700 hover:underline dark:text-teal-300">{{ $task->page->title }}</a><p class="mt-1 break-all text-xs text-zinc-500 dark:text-zinc-400">{{ $task->page->path }}</p></td><td class="whitespace-nowrap px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $this->statusLabel($task->status) }}<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $task->attempts }} lần nhận xử lý</p></td><td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400"><p>Tạo: {{ $task->created_at?->format('d/m/Y H:i') }}</p><p>Hết phiên: {{ $task->leased_until?->format('d/m/Y H:i') ?: '—' }}</p><p>Kết thúc: {{ $task->completed_at?->format('d/m/Y H:i') ?: '—' }}</p></td><td class="max-w-md break-words px-4 py-3 text-xs text-zinc-600 dark:text-zinc-300"><p>{{ $task->id }}</p><p class="mt-1">{{ $task->last_error ?: '—' }}</p></td><td class="whitespace-nowrap px-4 py-3 text-right">
                    @if ($canManageTasks && in_array($task->page->page_type, $editablePageTypes, true) && $task->canBeDeletedFromQueue())
                        <flux:button
                            size="sm"
                            variant="danger"
                            wire:click="deleteTask('{{ $task->id }}')"
                            wire:confirm="Xóa task này khỏi hàng chờ? Nếu task đang chờ, Codex sẽ không thể nhận task. Proposal, asset, backup và nhật ký liên quan vẫn được giữ."
                            wire:loading.attr="disabled"
                            wire:target="deleteTask('{{ $task->id }}')"
                        >
                            <span wire:loading.remove wire:target="deleteTask('{{ $task->id }}')">Xóa</span>
                            <span wire:loading wire:target="deleteTask('{{ $task->id }}')">Đang xóa...</span>
                        </flux:button>
                    @else
                        <span class="text-xs text-zinc-400">—</span>
                    @endif
                </td></tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">Chưa có yêu cầu phù hợp. Mở một URL để gửi yêu cầu tạo đề xuất.</td></tr>
            @endforelse
        </tbody></table></div>
        {{ $tasks->links() }}
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Danh sách tự cập nhật mỗi 15 giây. Xóa chỉ gỡ task khỏi hàng chờ; proposal, asset, backup và nhật ký audit vẫn được giữ. Task Codex đang xử lý không thể xóa.</p>
    </section>
</div>
