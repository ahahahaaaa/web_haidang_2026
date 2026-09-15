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
        Codex Schedule tự chọn bài trực tiếp từ CMS. Mặc định chỉ tạo đề xuất; chế độ Luôn publish tự ghi khi điểm sau lớn hơn điểm trước và luôn tạo backup trước khi ghi.
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
        <div class="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-950 md:flex-row md:items-center md:justify-between">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Áp dụng cho toàn bộ URL khớp bộ lọc, không chỉ trang phân trang hiện tại. Hệ thống ưu tiên từ khóa trong bộ SEO chính khi khớp chính xác; brief đã chỉnh thủ công không bị ghi đè.</p>
            @can('admin.seo-optimization.propose')
                <form wire:submit="syncDefaultKeywords" data-admin-feedback-form data-admin-loading-text="Đang đối soát từ khóa mặc định..." class="shrink-0">
                    <flux:button type="submit" wire:loading.attr="disabled">Đối soát từ khóa theo bộ lọc</flux:button>
                </form>
            @endcan
        </div>
        @if($canSelect)
            <div class="flex flex-col gap-3 rounded-2xl border border-teal-200 bg-teal-50/60 p-3 dark:border-teal-900 dark:bg-teal-950/20 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <flux:checkbox
                        wire:click="toggleSelectAllFiltered"
                        :checked="$selectAllFiltered"
                        label="Chọn toàn bộ {{ number_format($bulkFilteredCount) }} kết quả lọc"
                    />
                    <p class="text-xs text-zinc-600 dark:text-zinc-300">
                        Đã chọn <strong>{{ number_format($selectedCount) }}</strong> URL{{ $selectAllFiltered && $excludedPageIds !== [] ? ' · loại trừ '.number_format(count($excludedPageIds)).' URL' : '' }}.
                        @if($canAudit && $selectedAuditCount !== $selectedCount)
                            Có {{ number_format($selectedAuditCount) }} URL đủ quyền kiểm tra.
                        @endif
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    @if($selectedCount > 0)
                        <flux:button type="button" variant="ghost" wire:click="clearSelection">Bỏ chọn</flux:button>
                    @endif
                    @if($canAudit)
                        <form wire:submit="auditSelected" data-admin-feedback-form data-admin-loading-text="Đang kiểm tra SEO cho các URL đã chọn...">
                            <flux:button type="submit" wire:loading.attr="disabled" wire:target="auditSelected" :disabled="$selectedAuditCount === 0">
                                Kiểm tra SEO hàng loạt ({{ number_format($selectedAuditCount) }})
                            </flux:button>
                        </form>
                    @endif
                    @can('admin.seo-optimization.propose')
                        <flux:modal.trigger name="bulk-keyword-brief">
                            <flux:button type="button" variant="primary" :disabled="$selectedBriefCount === 0">
                                Bổ sung Brief hàng loạt ({{ number_format($selectedBriefCount) }})
                            </flux:button>
                        </flux:modal.trigger>
                    @endcan
                </div>
            </div>
        @endif
        <div class="overflow-x-auto rounded-2xl border border-zinc-200 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50 text-xs text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400"><tr>@if($canSelect)<th class="w-12 px-4 py-3"><span class="sr-only">Chọn URL</span></th>@endif<th class="px-4 py-3">Trang / URL</th><th class="px-4 py-3">Loại</th><th class="px-4 py-3">Từ khóa chính</th><th class="px-4 py-3" aria-sort="{{ $scoreSort === 'asc' ? 'ascending' : ($scoreSort === 'desc' ? 'descending' : 'none') }}"><button type="button" wire:click="sortByScore" wire:loading.attr="disabled" wire:target="sortByScore" class="inline-flex items-center gap-1.5 font-semibold text-zinc-600 hover:text-teal-700 disabled:cursor-wait disabled:opacity-60 dark:text-zinc-300 dark:hover:text-teal-300" aria-label="Sắp xếp điểm SEO {{ $scoreSort === 'desc' ? 'tăng dần' : 'giảm dần' }}">Điểm SEO <span class="text-[10px]" aria-hidden="true">{{ $scoreSort === 'asc' ? 'ASC ↑' : ($scoreSort === 'desc' ? 'DESC ↓' : '↕') }}</span></button></th>@if($canAudit)<th class="px-4 py-3">Thao tác</th>@endif<th class="px-4 py-3">Lịch sử</th><th class="px-4 py-3">Đối soát</th></tr></thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($pages as $page)
                        <tr wire:key="seo-page-{{ $page->id }}" class="align-top">
                            @if($canSelect)
                                <td class="px-4 py-3">
                                    @if(in_array($page->page_type, $selectableTypes, true))
                                        <flux:checkbox
                                            wire:click="togglePageSelection('{{ $page->id }}')"
                                            :checked="$this->isPageSelected((string) $page->id)"
                                            aria-label="Chọn {{ $page->title ?: $page->path }}"
                                        />
                                    @else
                                        <span class="text-zinc-300 dark:text-zinc-700" title="Không có quyền thao tác với loại nội dung này">—</span>
                                    @endif
                                </td>
                            @endif
                            <td class="max-w-lg px-4 py-3"><a wire:navigate href="{{ route('admin.seo-optimization.pages.show', $page) }}" class="font-semibold text-teal-700 hover:underline dark:text-teal-300">{{ $page->title ?: $page->path }}</a><p class="mt-1 break-all text-xs text-zinc-500 dark:text-zinc-400">{{ $page->path }}</p></td>
                            <td class="whitespace-nowrap px-4 py-3 text-zinc-700 dark:text-zinc-200">{{ $this->pageTypeLabel($page->page_type) }}<p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $this->statusLabel($page->classification) }}</p></td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                <p>{{ data_get($page->keyword_brief, 'primary_keyword') ?: 'Chưa cấu hình' }}</p>
                                @if (data_get($page->keyword_brief, 'primary_keyword'))
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $this->keywordOriginLabel(data_get($page->keyword_brief, 'origin')) }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($page->latestAudit)
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $this->auditScoreLabel($page->latestAudit->score) }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $this->auditGradeStatusLabel($page->latestAudit->grade, $page->latestAudit->status) }}</p>
                                    @if ($page->latestAudit->score === null)
                                        <p class="mt-1 max-w-sm whitespace-normal text-xs text-amber-700 dark:text-amber-300">{{ $this->auditCoverageLabel($page->latestAudit->report ?? []) }}</p>
                                    @endif
                                    <p @class([
                                        'mt-1 text-xs',
                                        'text-amber-700 dark:text-amber-300' => $page->latestAudit->source_version !== $page->source_version,
                                        'text-zinc-400 dark:text-zinc-500' => $page->latestAudit->source_version === $page->source_version,
                                    ])>
                                        {{ $page->latestAudit->source_version !== $page->source_version ? 'Kết quả cũ' : 'Kiểm tra gần nhất' }}:
                                        {{ $page->latestAudit->created_at?->format('d/m/Y H:i') ?: '—' }}
                                    </p>
                                @else
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">N/A</span>
                                @endif
                            </td>
                            @if($canAudit)
                                <td class="whitespace-nowrap px-4 py-3">
                                    <form wire:submit="auditPage('{{ $page->id }}')" data-admin-feedback-form data-admin-loading-text="Đang kiểm tra SEO cho {{ $page->title ?: $page->path }}...">
                                        <flux:button type="submit" size="sm" wire:loading.attr="disabled" wire:target="auditPage('{{ $page->id }}')">
                                            Kiểm tra
                                        </flux:button>
                                    </form>
                                </td>
                            @endif
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $page->audits_count }} lần kiểm tra<br>{{ $page->proposals_count }} đề xuất · {{ $page->tasks_count }} yêu cầu</td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $page->last_seen_at?->format('d/m/Y H:i') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ 6 + ($canSelect ? 1 : 0) + ($canAudit ? 1 : 0) }}" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">Chưa có URL phù hợp. Người quản trị có thể đồng bộ danh sách URL, hoặc điều chỉnh bộ lọc.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $pages->links() }}
    </section>

    @can('admin.seo-optimization.propose')
        <flux:modal name="bulk-keyword-brief" focusable class="max-w-4xl">
            <form wire:submit="applyBulkBrief" data-admin-feedback-form data-admin-loading-text="Đang cập nhật Brief..." class="space-y-5">
                <div>
                    <flux:heading size="lg">Bổ sung Brief từ khóa hàng loạt</flux:heading>
                    <flux:subheading>
                        Áp dụng cho {{ number_format($selectedBriefCount) }} URL đã chọn và có quyền sửa trong bộ lọc hiện tại. Ô trống giữ nguyên; trường danh sách được cộng thêm và loại trùng, còn từ khóa chính, intent và ghi chú sẽ thay thế khi có nhập.
                    </flux:subheading>
                </div>

                @error('bulkBrief')
                    <div class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200">{{ $message }}</div>
                @enderror

                <div class="grid gap-4 md:grid-cols-2">
                    <flux:input label="Từ khóa chính" wire:model="bulkPrimaryKeyword" placeholder="Để trống để giữ nguyên từng URL" />
                    <flux:select label="Ý định tìm kiếm" wire:model="bulkSearchIntent">
                        <option value="">Giữ nguyên từng URL</option>
                        @foreach($intents as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:textarea label="Từ khóa phụ" wire:model="bulkSecondaryKeywords" rows="5" placeholder="Mỗi dòng một mục" />
                    <flux:textarea label="Thuật ngữ ngữ nghĩa" wire:model="bulkSemanticTerms" rows="5" placeholder="Mỗi dòng một mục" />
                    <flux:textarea label="Thực thể cần đề cập" wire:model="bulkEntities" rows="5" placeholder="Mỗi dòng một mục" />
                    <flux:textarea label="Chủ đề cần có" wire:model="bulkRequiredTopics" rows="5" placeholder="Mỗi dòng một mục" />
                    <flux:textarea label="Liên kết nội bộ bắt buộc" wire:model="bulkRequiredInternalLinks" rows="5" placeholder="Mỗi dòng một URL canonical" />
                    <flux:textarea label="Ghi chú biên tập" wire:model="bulkNotes" rows="5" placeholder="Để trống để giữ nguyên từng URL" />
                </div>

                <p class="text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                    URL đang chờ hoặc đang được Codex xử lý sẽ được giữ nguyên. Nếu nhiều URL nhận cùng từ khóa chính và intent, hệ thống chỉ lưu khi không xung đột quyền sở hữu từ khóa.
                </p>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="filled">Đóng</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="applyBulkBrief" :disabled="$selectedBriefCount === 0">
                        Lưu cho {{ number_format($selectedBriefCount) }} URL
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan
</div>
