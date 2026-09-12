<div class="space-y-4 pb-28">
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
        <p class="text-xs text-zinc-500 dark:text-zinc-400">SEOer có thể sửa trực tiếp phần đề xuất bên dưới. Khi chấm lại, server chỉ giữ đơn vị thay đổi làm tổng điểm tăng; HTML, quyền, phiên bản nguồn và dữ liệu chứng minh vẫn được kiểm tra lại.</p>
        @if(data_get($proposal->qa, 'authorization.kind') === 'server_policy')
            <p class="text-sm text-amber-800 dark:text-amber-200">Được server tự áp dụng theo cấu hình Luôn publish. Đây không phải lượt duyệt thủ công. Cấu hình bởi tài khoản {{ $configuredByName }}.</p>
        @endif
        @if(data_get($proposal->qa, 'human_override.source_changed') || data_get($proposal->qa, 'human_override.strategy_changed'))
            <p class="text-sm text-amber-800 dark:text-amber-200">Người duyệt đã ưu tiên nội dung đề xuất trên phiên bản CMS mới nhất. Nếu các trường nội dung này tiếp tục thay đổi sau lần duyệt, thao tác áp dụng sẽ yêu cầu duyệt lại.</p>
        @endif
        @include('livewire.admin.seo-optimization.partials.score-comparison', ['proposal' => $proposal])
        @if(data_get($proposal->qa, 'seo_gate.companion_scores'))
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Điểm phụ sau đề xuất: ADA/WCAG content readiness {{ data_get($proposal->qa, 'seo_gate.companion_scores.accessibility.score', '—') }}/100 · Search Console readiness nội bộ {{ data_get($proposal->qa, 'seo_gate.companion_scores.search_console_readiness.score', '—') }}/100.</p>
        @endif
        @if($proposal->audit && data_get($proposal->audit->report, 'companion_scores'))
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Điểm phụ sau tái kiểm tra CMS: ADA/WCAG content readiness {{ data_get($proposal->audit->report, 'companion_scores.accessibility.score', '—') }}/100 · Search Console readiness nội bộ {{ data_get($proposal->audit->report, 'companion_scores.search_console_readiness.score', '—') }}/100.</p>
        @endif
        @if($proposal->backup)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Backup bất biến trước khi ghi: {{ $proposal->backup->id }} · checksum {{ $proposal->backup->checksum }}</p>
        @endif
    </section>
    @if ($proposal->missing_facts)
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950"><h2 class="font-semibold text-amber-900 dark:text-amber-100">NEED_DATA — cần bổ sung dữ liệu</h2><pre class="mt-2 overflow-auto whitespace-pre-wrap break-words text-sm text-amber-900 dark:text-amber-100">{{ json_encode($proposal->missing_facts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre></section>
    @endif
    <section class="space-y-4 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">So sánh nội dung trước / sau</h2>
            @if(data_get($proposal->task?->snapshot, 'source_fields.editor_mode'))
                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    LandingPage: {{ data_get($proposal->task?->snapshot, 'source_fields.editor_mode') === 'html' ? 'HTML thủ công' : 'Block widgets' }}
                </span>
            @endif
        </div>
        @forelse ($comparisonRows as $row)
            <div wire:key="patch-{{ md5($row['key']) }}" class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    @if($row['group'])
                        <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-200">{{ $row['group'] }}</span>
                    @endif
                    <h3 class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $row['label'] }}</h3>
                    <span class="text-xs text-zinc-400">{{ $row['key'] }}</span>
                </div>
                <div class="grid gap-3 lg:grid-cols-2">
                    <div class="min-w-0 rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-950">
                        <p class="mb-2 text-xs font-semibold text-zinc-500 dark:text-zinc-400">TRƯỚC</p>
                        @if($row['kind'] === 'faq' && is_array($row['before']))
                            <div class="max-h-96 space-y-2 overflow-auto">
                                @forelse($row['before'] as $faqIndex => $faq)
                                    <div class="rounded-xl border border-zinc-200 bg-white/70 p-2 text-xs dark:border-zinc-700 dark:bg-zinc-900/60">
                                        <p class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $faqIndex + 1 }}. {{ data_get($faq, 'question') }}</p>
                                        <p class="mt-1 whitespace-pre-wrap break-words text-zinc-600 dark:text-zinc-300">{{ \App\Support\RichText::normalizePlain((string) data_get($faq, 'answer')) }}</p>
                                    </div>
                                @empty
                                    <p class="text-xs text-zinc-500">Không có FAQ.</p>
                                @endforelse
                            </div>
                        @else
                            <pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words text-xs text-zinc-700 dark:text-zinc-200">{{ is_string($row['before']) || is_null($row['before']) ? $row['before'] : json_encode($row['before'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        @endif
                    </div>
                    <div class="min-w-0 rounded-2xl bg-emerald-50 p-3 dark:bg-emerald-950/40">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">{{ $canEditProposal && $row['edit_model'] ? 'SỬA ĐỀ XUẤT' : 'ĐỀ XUẤT' }}</p>
                            @if($canEditProposal && $row['edit_model'])<span class="text-xs text-emerald-600 dark:text-emerald-300">Chưa ghi CMS</span>@endif
                        </div>
                        @if($canEditProposal && $row['edit_model'])
                            @if($row['kind'] === 'faq' && is_array($row['edit_value']))
                                <div class="space-y-3">
                                    @forelse($row['edit_value'] as $faqIndex => $faq)
                                        <div wire:key="faq-edit-{{ md5($row['key']) }}-{{ $faqIndex }}" class="space-y-2 rounded-xl border border-emerald-200 bg-white/80 p-3 dark:border-emerald-900 dark:bg-zinc-900/70">
                                            <flux:input label="Câu hỏi {{ $faqIndex + 1 }}" wire:model.defer="{{ $row['edit_model'] }}.{{ $faqIndex }}.question" maxlength="500" />
                                            <x-admin.quill-editor wire:key="faq-answer-{{ md5($row['key']) }}-{{ $faqIndex }}" model="{{ $row['edit_model'] }}.{{ $faqIndex }}.answer" :value="data_get($faq, 'answer', '')" rows="4" placeholder="Câu trả lời hiển thị trên trang." />
                                        </div>
                                    @empty
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Không có FAQ trong đề xuất.</p>
                                    @endforelse
                                </div>
                            @elseif($row['kind'] === 'rich_html')
                                <x-admin.quill-editor wire:key="proposal-rich-{{ md5($row['key']) }}" model="{{ $row['edit_model'] }}" :value="$row['edit_value'] ?? ''" mode="rich" rows="12" placeholder="Chỉnh nội dung đề xuất; chỉ dùng H2–H6 trong phần nội dung." />
                            @elseif($row['kind'] === 'manual_html')
                                <textarea wire:model.defer="{{ $row['edit_model'] }}" rows="18" class="w-full rounded-2xl border border-zinc-700 bg-zinc-950 px-4 py-3 font-mono text-sm text-white outline-none transition focus:border-emerald-400"></textarea>
                                <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-300">LandingPage HTML thủ công: giữ nguyên cấu trúc cần thiết; script, iframe, form và event handler sẽ bị chặn.</p>
                            @elseif(is_string($row['edit_value']) && mb_strlen($row['edit_value']) > 180)
                                <flux:textarea wire:model.defer="{{ $row['edit_model'] }}" rows="6" />
                            @else
                                <flux:input wire:model.defer="{{ $row['edit_model'] }}" />
                            @endif
                        @elseif($row['kind'] === 'faq' && is_array($row['after']))
                            <div class="max-h-96 space-y-2 overflow-auto">
                                @foreach($row['after'] as $faqIndex => $faq)
                                    <div class="rounded-xl border border-emerald-200 bg-white/70 p-2 text-xs dark:border-emerald-900 dark:bg-zinc-900/60">
                                        <p class="font-semibold text-zinc-700 dark:text-zinc-200">{{ $faqIndex + 1 }}. {{ data_get($faq, 'question') }}</p>
                                        <p class="mt-1 whitespace-pre-wrap break-words text-zinc-600 dark:text-zinc-300">{{ \App\Support\RichText::normalizePlain((string) data_get($faq, 'answer')) }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <pre class="max-h-96 overflow-auto whitespace-pre-wrap break-words text-xs text-zinc-700 dark:text-zinc-200">{{ is_string($row['after']) || is_null($row['after']) ? $row['after'] : json_encode($row['after'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Đề xuất chưa có trường nội dung để áp dụng.</p>
        @endforelse
    </section>
    @include('livewire.admin.seo-optimization.partials.qa-report', ['proposal' => $proposal])
    @can('admin.seo-optimization.approve')
        @if (in_array($proposal->status, ['in_review', 'need_data', 'approved', 'stale'], true))
            <form wire:submit="reject" data-admin-feedback-form class="space-y-3 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900"><flux:textarea label="Lý do từ chối / yêu cầu chỉnh sửa" wire:model="rejectionReason" rows="3" maxlength="2000" /><flux:button type="submit" wire:loading.attr="disabled">Từ chối đề xuất</flux:button></form>
        @endif
    @endcan
    <div class="sticky bottom-3 z-10 rounded-2xl border border-zinc-200 bg-white/95 p-3 shadow-xl shadow-zinc-950/10 backdrop-blur dark:border-zinc-700 dark:bg-zinc-900/95 dark:shadow-black/30">
        <div class="flex flex-wrap items-center justify-end gap-2">
            <flux:button href="{{ $contentPublicUrl }}" target="_blank" rel="noopener noreferrer" variant="outline" size="sm">Xem bài viết</flux:button>
            @if($contentEditUrl)
                <flux:button href="{{ $contentEditUrl }}" target="_blank" rel="noopener noreferrer" variant="primary" color="amber" size="sm">Chỉnh sửa bài viết</flux:button>
            @endif
            @can('admin.seo-optimization.approve')
                @if ($canEditProposal)
                    @can('admin.seo-optimization.apply')
                        <form wire:submit="rescoreAndApply" data-admin-feedback-form data-admin-loading-text="Đang chấm lại và áp dụng phần tăng điểm...">
                            <flux:button type="submit" variant="primary" color="violet" size="sm" wire:confirm="Chấm lại từng phần chỉnh sửa, bỏ qua phần không làm tăng tổng điểm và tự áp dụng lên CMS nếu điểm tăng? Hệ thống sẽ tạo backup trước khi ghi." wire:loading.attr="disabled">Chấm lại & áp dụng nếu tăng điểm</flux:button>
                        </form>
                    @else
                        <form wire:submit="rescore" data-admin-feedback-form data-admin-loading-text="Đang chấm lại đề xuất...">
                            <flux:button type="submit" variant="primary" color="blue" size="sm" wire:loading.attr="disabled">Lưu & chấm lại</flux:button>
                        </form>
                    @endcan
                @endif
            @endcan
            @can('admin.seo-optimization.approve')
                @if (in_array($proposal->status, ['in_review', 'stale'], true))
                    <form wire:submit="approve" data-admin-feedback-form><flux:button type="submit" variant="primary" color="emerald" size="sm" wire:confirm="Duyệt và ưu tiên nội dung đề xuất này trên phiên bản CMS mới nhất? Hệ thống vẫn chặn nếu nội dung thay đổi thêm sau lần duyệt." wire:loading.attr="disabled">Duyệt đề xuất</flux:button></form>
                @endif
            @endcan
            @can('admin.seo-optimization.apply')
                @if (in_array($proposal->status, ['applied', 'verify_failed'], true))
                    <form wire:submit="verify" data-admin-feedback-form><flux:button type="submit" variant="primary" color="cyan" size="sm" wire:loading.attr="disabled">Kiểm tra lại</flux:button></form>
                @endif
                @if ($proposal->status === 'approved' && ($scoreImproved || $isRestoreProposal))
                    <form wire:submit="apply" data-admin-feedback-form data-admin-loading-text="Đang áp dụng và kiểm tra lại..."><flux:button type="submit" variant="primary" color="red" size="sm" wire:confirm="{{ $isRestoreProposal ? 'Áp dụng bản khôi phục đã duyệt lên CMS?' : 'Áp dụng lên CMS vì điểm SEO mới lớn hơn điểm SEO cũ?' }}" wire:loading.attr="disabled">{{ $isRestoreProposal ? 'Áp dụng bản khôi phục' : 'Áp dụng lên CMS' }}</flux:button></form>
                @endif
            @endcan
            @can('admin.seo-optimization.rollback')
                @if (in_array($proposal->status, ['applied', 'verify_failed'], true))
                    <form wire:submit="rollback" data-admin-feedback-form><flux:button type="submit" variant="danger" size="sm" wire:loading.attr="disabled">Tạo đề xuất hoàn tác</flux:button></form>
                @endif
            @endcan
        </div>
    </div>
</div>
