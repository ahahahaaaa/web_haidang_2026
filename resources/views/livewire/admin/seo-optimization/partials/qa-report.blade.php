@php
    $report = data_get($proposal->qa, 'seo_gate', []);
    $dimensions = collect(data_get($report, 'dimensions', []))->filter(fn ($dimension) => is_array($dimension));
    $issues = collect(data_get($report, 'issues', []))->filter(fn ($issue) => is_array($issue));
    $warnings = collect(data_get($proposal->qa, 'warnings', []))->filter();
    $revision = data_get($proposal->qa, 'editor_revision', []);
    $retainedUnits = collect(data_get($revision, 'retained_units', []));
    $ignoredUnits = collect(data_get($revision, 'ignored_units', []));
    $baselineDimensions = collect(data_get($proposal->qa, 'baseline_seo_gate.dimensions', []))->keyBy('dimension');
    $revisionDimensionChanges = collect(data_get($revision, 'dimension_changes', []))->keyBy('dimension');
@endphp

<section class="space-y-5 rounded-3xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Kết quả QA đề xuất</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Điểm, vấn đề và bằng chứng được tách theo từng tiêu chí để SEOer quyết định nhanh.</p>
        </div>
        @if($report)
            <div class="rounded-2xl bg-zinc-100 px-4 py-2 text-right dark:bg-zinc-800">
                <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $this->auditScoreLabel(is_numeric(data_get($report, 'score')) ? (float) data_get($report, 'score') : null) }}</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->auditGradeStatusLabel(data_get($report, 'grade'), data_get($report, 'status')) }}</p>
            </div>
        @endif
    </div>

    @if($revision)
        <div class="grid gap-3 md:grid-cols-2">
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/40">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-emerald-900 dark:text-emerald-100">Đã giữ để áp dụng</h3>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100">{{ $retainedUnits->count() }} phần</span>
                </div>
                <div class="mt-3 space-y-2">
                    @forelse($retainedUnits as $unit)
                        <div class="rounded-xl bg-white/70 p-3 text-sm dark:bg-zinc-900/50">
                            <p class="font-medium text-emerald-900 dark:text-emerald-100">{{ data_get($unit, 'key') }}</p>
                            <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">{{ data_get($unit, 'score_before', 'N/A') }} → {{ data_get($unit, 'score_after', 'N/A') }} · +{{ data_get($unit, 'score_delta', 0) }} điểm</p>
                        </div>
                    @empty
                        <p class="text-sm text-emerald-800 dark:text-emerald-200">Chưa có phần nào được chứng minh làm tăng tổng điểm.</p>
                    @endforelse
                </div>
            </div>
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/40">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-amber-900 dark:text-amber-100">Đã bỏ qua</h3>
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-100">{{ $ignoredUnits->count() }} phần</span>
                </div>
                <div class="mt-3 space-y-2">
                    @forelse($ignoredUnits as $unit)
                        <div class="rounded-xl bg-white/70 p-3 text-sm dark:bg-zinc-900/50">
                            <p class="font-medium text-amber-900 dark:text-amber-100">{{ data_get($unit, 'key') }}</p>
                            <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">Nếu giữ: {{ data_get($unit, 'score_if_kept', 'N/A') }} điểm · {{ data_get($unit, 'reason') }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-amber-800 dark:text-amber-200">Không có phần bị loại ở lần chấm gần nhất.</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if($warnings->isNotEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/40">
            <h3 class="font-semibold text-amber-900 dark:text-amber-100">Cảnh báo cần SEOer kiểm tra</h3>
            <ul class="mt-2 space-y-1 text-sm text-amber-800 dark:text-amber-200">
                @foreach($warnings as $warning)
                    <li>• {{ $warning }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div>
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h3 class="font-semibold text-zinc-900 dark:text-white">12 tiêu chí SEO</h3>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $this->auditCoverageLabel($report) }} · Chọn một tiêu chí để xem rõ dữ liệu trước và sau đề xuất.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">Đạt</span>
                <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-800 dark:bg-amber-950 dark:text-amber-200">Cần cải thiện</span>
                <span class="rounded-full bg-red-100 px-2.5 py-1 text-red-800 dark:bg-red-950 dark:text-red-200">Không đạt</span>
            </div>
        </div>
        <div class="mt-3 grid gap-3 lg:grid-cols-2">
            @forelse($dimensions as $dimension)
                @php
                    $dimensionName = (string) data_get($dimension, 'dimension');
                    $dimensionScore = data_get($dimension, 'score');
                    $dimensionStatus = (string) data_get($dimension, 'status', 'UNASSESSED');
                    $baselineDimension = $baselineDimensions->get($dimensionName, []);
                    $revisionChange = $revisionDimensionChanges->get($dimensionName, []);
                    $beforeScore = data_get($baselineDimension, 'score', data_get($revisionChange, 'score_before'));
                    $beforeStatus = (string) data_get($baselineDimension, 'status', '');
                    $comparison = data_get($qaDimensionComparisons ?? [], $dimensionName, []);
                    $beforeDetails = collect(data_get($comparison, 'before', data_get($baselineDimension, 'details', [])))->filter(fn ($row) => is_array($row));
                    $afterDetails = collect(data_get($comparison, 'after', data_get($dimension, 'details', [])))->filter(fn ($row) => is_array($row));
                    $tone = $dimensionStatus === 'PASS'
                        ? 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-950/20'
                        : (in_array($dimensionStatus, ['IMPROVE', 'NEED_DATA', 'UNASSESSED'], true)
                            ? 'border-amber-200 bg-amber-50/60 dark:border-amber-900 dark:bg-amber-950/20'
                            : 'border-red-200 bg-red-50/60 dark:border-red-900 dark:bg-red-950/20');
                @endphp
                <details wire:key="qa-dimension-{{ data_get($dimension, 'rule_id') }}" class="group rounded-2xl border {{ $tone }}">
                    <summary class="cursor-pointer list-none p-4 [&::-webkit-details-marker]:hidden">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="flex items-center gap-2 font-semibold text-zinc-900 dark:text-white">
                                    {{ $this->auditDimensionLabel($dimensionName) }}
                                    <span class="text-xs text-zinc-400 transition-transform group-open:rotate-180" aria-hidden="true">▼</span>
                                </h4>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ data_get($dimension, 'rule_id') }} · trọng số {{ data_get($dimension, 'weight') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ is_numeric($dimensionScore) ? $dimensionScore.'/100' : 'N/A' }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $this->statusLabel($dimensionStatus) }}</p>
                            </div>
                        </div>
                        <progress class="mt-3 h-2 w-full overflow-hidden rounded-full accent-teal-600" max="100" value="{{ is_numeric($dimensionScore) ? $dimensionScore : 0 }}"></progress>
                        <p class="mt-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ data_get($dimension, 'evidence', 'Chưa có bằng chứng chi tiết.') }}</p>
                    </summary>

                    <div class="border-t border-current/10 p-4 pt-3">
                        <div class="grid gap-3 xl:grid-cols-2">
                            @foreach([
                                ['title' => 'Trước đề xuất', 'score' => $beforeScore, 'status' => $beforeStatus, 'rows' => $beforeDetails],
                                ['title' => 'Sau đề xuất', 'score' => $dimensionScore, 'status' => $dimensionStatus, 'rows' => $afterDetails],
                            ] as $side)
                                <section class="rounded-xl border border-zinc-200 bg-white/80 p-3 dark:border-zinc-700 dark:bg-zinc-900/70">
                                    <div class="flex items-start justify-between gap-3">
                                        <h5 class="font-semibold text-zinc-800 dark:text-zinc-100">{{ $side['title'] }}</h5>
                                        <div class="text-right text-xs">
                                            <span class="block font-semibold text-zinc-800 dark:text-zinc-100">{{ is_numeric($side['score']) ? $side['score'].'/100' : 'N/A' }}</span>
                                            <span class="block text-zinc-500 dark:text-zinc-400">{{ $side['status'] !== '' ? $this->statusLabel($side['status']) : 'Chưa lưu trạng thái' }}</span>
                                        </div>
                                    </div>
                                    <dl class="mt-3 space-y-2">
                                        @foreach($side['rows'] as $row)
                                            @php $value = data_get($row, 'value'); @endphp
                                            <div class="rounded-lg bg-zinc-50 p-2.5 dark:bg-zinc-950/80">
                                                <dt class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ data_get($row, 'label') }}</dt>
                                                <dd class="mt-1 break-words whitespace-pre-wrap text-sm leading-5 text-zinc-700 dark:text-zinc-200">
                                                    @if(is_array($value))
                                                        @forelse($value as $item)
                                                            <span class="block">• {{ is_scalar($item) ? $item : json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</span>
                                                        @empty
                                                            <span class="text-zinc-400">Không có</span>
                                                        @endforelse
                                                    @elseif($value === null || $value === '')
                                                        <span class="text-zinc-400">Không có</span>
                                                    @else
                                                        {{ is_bool($value) ? ($value ? 'Có' : 'Không') : $value }}
                                                    @endif
                                                </dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </section>
                            @endforeach
                        </div>
                    </div>
                </details>
            @empty
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Đề xuất cũ chưa lưu báo cáo chi tiết theo từng tiêu chí.</p>
            @endforelse
        </div>
    </div>

    @if($issues->isNotEmpty())
        <div>
            <h3 class="font-semibold text-zinc-900 dark:text-white">Vấn đề cần xử lý</h3>
            <div class="mt-3 space-y-2">
                @foreach($issues as $issue)
                    @php $severity = (string) data_get($issue, 'severity', 'P3'); @endphp
                    <div class="flex gap-3 rounded-2xl border border-zinc-200 p-3 dark:border-zinc-700">
                        <span class="h-fit rounded-full px-2.5 py-1 text-xs font-semibold {{ in_array($severity, ['P0', 'P1'], true) ? 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' }}">{{ $severity }}</span>
                        <div class="min-w-0">
                            <p class="font-medium text-zinc-800 dark:text-zinc-100">{{ $this->auditDimensionLabel((string) data_get($issue, 'dimension', strtolower(str_replace('.V2', '', (string) data_get($issue, 'rule_id'))))) }}</p>
                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">{{ data_get($issue, 'message') }}</p>
                            @if(data_get($issue, 'code'))<p class="mt-1 text-xs text-zinc-400">{{ data_get($issue, 'code') }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if(data_get($report, 'companion_scores'))
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach(['accessibility' => 'Khả năng truy cập nội dung', 'search_console_readiness' => 'Sẵn sàng cho Search Console'] as $key => $label)
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950">
                    <div class="flex items-center justify-between gap-3"><h3 class="font-medium text-zinc-800 dark:text-zinc-100">{{ $label }}</h3><span class="font-semibold text-zinc-900 dark:text-white">{{ data_get($report, 'companion_scores.'.$key.'.score', 'N/A') }}/100</span></div>
                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ data_get($report, 'companion_scores.'.$key.'.standard') }}</p>
                </div>
            @endforeach
        </div>
    @endif

    <div>
        <h3 class="font-semibold text-zinc-900 dark:text-white">Khẳng định và nguồn chứng minh</h3>
        <div class="mt-3 space-y-2">
            @forelse($proposal->claims ?? [] as $claim)
                <div class="rounded-2xl border border-zinc-200 p-3 dark:border-zinc-700">
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-100">{{ data_get($claim, 'claim') }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Nguồn: {{ data_get($claim, 'source_id', 'Chưa có') }}</p>
                    <p class="mt-2 whitespace-pre-wrap text-sm text-zinc-600 dark:text-zinc-300">{{ data_get($claim, 'quote') }}</p>
                </div>
            @empty
                <p class="rounded-2xl bg-zinc-50 p-3 text-sm text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400">Không có khẳng định mới cần nguồn riêng.</p>
            @endforelse
        </div>
    </div>

    <details>
        <summary class="cursor-pointer text-sm font-medium text-zinc-700 dark:text-zinc-200">Dữ liệu JSON kỹ thuật</summary>
        <pre class="mt-2 max-h-96 overflow-auto whitespace-pre-wrap break-words rounded-2xl bg-zinc-50 p-3 text-xs text-zinc-600 dark:bg-zinc-950 dark:text-zinc-300">{{ json_encode(['qa' => $proposal->qa, 'claims' => $proposal->claims], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
    </details>
</section>
