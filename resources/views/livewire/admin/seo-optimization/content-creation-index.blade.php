<div class="space-y-4">
    <header class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Nội dung Codex tạo mới</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Theo dõi payload đã nhận, media và record draft/inactive được tạo đúng loại trong CMS.</p>
        </div>
        <a href="{{ route('admin.seo-optimization.settings') }}" wire:navigate class="rounded-xl bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">Cấu hình token</a>
    </header>

    @include('livewire.admin.cms.partials.admin-group-submenu', ['groupKey' => 'seo-optimization'])
    @include('livewire.admin.seo-optimization.feedback')

    <section class="rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="grid gap-3 md:grid-cols-3">
            <flux:input wire:model.live.debounce.350ms="search" label="Tìm yêu cầu / từ khóa" placeholder="Nhập nội dung cần tìm" />
            <flux:select wire:model.live="contentType" label="Loại nội dung">
                <option value="">Tất cả loại</option>
                @foreach($contracts as $type => $contract)
                    <option value="{{ $type }}">{{ $contract['label'] }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="status" label="Trạng thái">
                <option value="">Tất cả trạng thái</option>
                @foreach($statuses as $itemStatus)
                    <option value="{{ $itemStatus }}">{{ $this->statusLabel($itemStatus) }}</option>
                @endforeach
            </flux:select>
        </div>
    </section>

    <section class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Yêu cầu</th>
                        <th class="px-4 py-3">Loại</th>
                        <th class="px-4 py-3">Sẵn sàng SEO</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Người yêu cầu</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse($tasks as $task)
                        @php
                            $score = data_get($task->result, 'seo_readiness.score');
                            $editorUrl = data_get($task->result, 'editor_url');
                            $manual = $task->status === 'ready_for_review';
                        @endphp
                        <tr wire:key="content-creation-{{ $task->id }}">
                            <td class="max-w-lg px-4 py-3">
                                <p class="font-medium text-zinc-900 dark:text-white">{{ data_get($task->brief, 'request') }}</p>
                                <p class="mt-1 text-xs text-zinc-500">Từ khóa: {{ data_get($task->brief, 'primary_keyword', 'N/A') }}</p>
                                <p class="mt-1 font-mono text-[11px] text-zinc-400">{{ $task->id }}</p>
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ data_get($contracts->get($task->content_type), 'label', $this->pageTypeLabel($task->content_type)) }}</td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-zinc-900 dark:text-white">{{ $score === null ? 'N/A' : number_format((float) $score, 1, ',', '.').'/100' }}</span>
                                <p class="text-xs text-zinc-500">{{ data_get($task->result, 'seo_readiness.status', 'Chưa chấm') }}</p>
                            </td>
                            <td class="px-4 py-3"><span class="rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">{{ $this->statusLabel($task->status) }}</span></td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $task->requester?->name ?? 'N/A' }}<br><span class="text-xs text-zinc-400">{{ $task->created_at?->format('d/m/Y H:i') }}</span></td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @if($manual && auth()->user()?->can('admin.seo-optimization.approve'))
                                        <flux:button size="sm" variant="primary" wire:click="approve('{{ $task->id }}')" wire:loading.attr="disabled" wire:target="approve('{{ $task->id }}')" wire:confirm="Tạo taxonomy này trong CMS? Loại này chưa có trạng thái draft và có thể xuất hiện trong danh mục quản trị/frontsite.">Xác nhận tạo</flux:button>
                                    @endif
                                    @if($editorUrl)
                                        <a href="{{ $editorUrl }}" target="_blank" rel="noopener" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Chỉnh sửa</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">Chưa có task tạo nội dung phù hợp bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">{{ $tasks->links() }}</div>
    </section>
</div>
