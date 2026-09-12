@php
    $compact = $compact ?? false;
    $baseline = data_get($proposal->qa, 'baseline_seo_gate');
    $projected = data_get($proposal->qa, 'seo_gate');
    $audit = $proposal->relationLoaded('audit') ? $proposal->audit : null;
    $hasBaseline = is_array($baseline);
    $hasProjected = is_array($projected);
    $hasActual = $audit !== null;
    $beforeScore = $hasBaseline ? data_get($baseline, 'score') : null;
    $beforeGrade = $hasBaseline ? data_get($baseline, 'grade') : null;
    $projectedScore = $hasProjected ? data_get($projected, 'score') : null;
    $projectedGrade = $hasProjected ? data_get($projected, 'grade') : null;
    $actualScore = $hasActual ? ($audit->score ?? data_get($audit->report, 'score')) : null;
    $actualGrade = $hasActual ? ($audit->grade ?? data_get($audit->report, 'grade')) : null;
    $afterScore = $hasActual ? $actualScore : $projectedScore;
    $afterGrade = $hasActual ? $actualGrade : $projectedGrade;
    $delta = is_numeric($beforeScore) && is_numeric($afterScore) ? round((float) $afterScore - (float) $beforeScore, 1) : null;
    $formatScore = static function ($score): string {
        if (! is_numeric($score)) {
            return 'Chưa thể tính tổng';
        }

        $score = (float) $score;

        return ($score === floor($score) ? number_format($score, 0, ',', '.') : number_format($score, 1, ',', '.')).'/100';
    };
    $tone = static fn ($score): string => match (true) {
        ! is_numeric($score) => 'bg-zinc-100 text-zinc-700 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700',
        (float) $score >= 90 => 'bg-emerald-100 text-emerald-800 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-200 dark:ring-emerald-800',
        (float) $score >= 80 => 'bg-sky-100 text-sky-800 ring-sky-200 dark:bg-sky-950 dark:text-sky-200 dark:ring-sky-800',
        (float) $score >= 65 => 'bg-amber-100 text-amber-800 ring-amber-200 dark:bg-amber-950 dark:text-amber-200 dark:ring-amber-800',
        default => 'bg-red-100 text-red-800 ring-red-200 dark:bg-red-950 dark:text-red-200 dark:ring-red-800',
    };
    $deltaLabel = $delta === null ? 'Chưa tính được' : (($delta > 0 ? '+' : '').number_format($delta, 1, ',', '.').' điểm');
@endphp

@if($compact)
    <div class="flex min-w-72 flex-wrap items-center gap-2 text-xs">
        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-semibold ring-1 {{ $tone($beforeScore) }}">Trước: {{ $hasBaseline ? $formatScore($beforeScore) : 'Không có dữ liệu nền' }}{{ $beforeGrade ? ' · '.$beforeGrade : '' }}</span>
        <span class="text-zinc-400" aria-hidden="true">→</span>
        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 font-semibold ring-1 {{ $tone($afterScore) }}">{{ $hasActual ? 'Sau kiểm tra' : 'Sau dự kiến' }}: {{ $formatScore($afterScore) }}{{ $afterGrade ? ' · '.$afterGrade : '' }}</span>
        @if($delta !== null)
            <span class="rounded-full bg-orange-100 px-2.5 py-1 font-semibold text-orange-800 dark:bg-orange-950 dark:text-orange-200">{{ $deltaLabel }}</span>
        @endif
        @if($hasProjected && ! is_numeric($projectedScore))
            <span class="basis-full text-amber-700 dark:text-amber-300">{{ $this->auditCoverageLabel($projected) }}</span>
        @endif
    </div>
@else
    <div class="space-y-3">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h3 class="font-semibold text-zinc-900 dark:text-white">Điểm SEO trước / sau</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Điểm nội bộ theo cùng rule version; không phải cam kết thứ hạng Google.</p>
            </div>
            <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800 dark:bg-orange-950 dark:text-orange-200">{{ $deltaLabel }}</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-zinc-50 p-3 dark:bg-zinc-950">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Trước tối ưu</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $hasBaseline ? $formatScore($beforeScore) : '—' }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $hasBaseline ? $this->auditGradeStatusLabel($beforeGrade, data_get($baseline, 'status')) : 'Proposal cũ không có dữ liệu nền.' }}</p>
                @if($hasBaseline)
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $this->auditCoverageLabel($baseline) }}</p>
                @endif
            </div>
            <div class="rounded-2xl bg-violet-50 p-3 dark:bg-violet-950/50">
                <div class="flex flex-wrap items-center gap-2"><p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Sau đề xuất</p><span class="rounded-full bg-violet-600 px-2 py-0.5 text-[10px] font-semibold text-white">DỰ KIẾN</span></div>
                <p class="mt-2 text-2xl font-semibold text-violet-950 dark:text-violet-100">{{ $formatScore($projectedScore) }}</p>
                <p class="mt-1 text-xs text-violet-700 dark:text-violet-300">{{ $hasProjected ? $this->auditGradeStatusLabel($projectedGrade, data_get($projected, 'status')) : 'Chưa có đánh giá SEO' }}</p>
                @if($hasProjected)
                    <p class="mt-1 text-xs text-violet-700 dark:text-violet-300">{{ $this->auditCoverageLabel($projected) }}</p>
                @endif
            </div>
            <div class="rounded-2xl p-3 {{ $hasActual ? 'bg-emerald-50 dark:bg-emerald-950/50' : 'bg-zinc-50 dark:bg-zinc-950' }}">
                <div class="flex flex-wrap items-center gap-2"><p class="text-xs font-semibold uppercase tracking-wide {{ $hasActual ? 'text-emerald-700 dark:text-emerald-300' : 'text-zinc-500 dark:text-zinc-400' }}">Sau áp dụng</p>@if($hasActual)<span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-semibold text-white">ĐÃ TÁI KIỂM TRA CMS</span>@endif</div>
                <p class="mt-2 text-2xl font-semibold {{ $hasActual ? 'text-emerald-950 dark:text-emerald-100' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $hasActual ? $formatScore($actualScore) : '—' }}</p>
                <p class="mt-1 text-xs {{ $hasActual ? 'text-emerald-700 dark:text-emerald-300' : 'text-zinc-500 dark:text-zinc-400' }}">{{ $hasActual ? $this->auditGradeStatusLabel($actualGrade, $audit->status) : 'Chưa áp dụng hoặc chưa kiểm tra lại.' }}</p>
                @if($hasActual)
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">{{ $this->auditCoverageLabel($audit->report ?? []) }}</p>
                    <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">{{ $audit->created_at?->format('d/m/Y H:i') ?: 'Đã kiểm tra' }}</p>
                @endif
            </div>
            <div class="rounded-2xl bg-orange-50 p-3 dark:bg-orange-950/50">
                <p class="text-xs font-semibold uppercase tracking-wide text-orange-700 dark:text-orange-300">Mức thay đổi</p>
                <p class="mt-2 text-2xl font-semibold text-orange-950 dark:text-orange-100">{{ $deltaLabel }}</p>
                <p class="mt-1 text-xs text-orange-700 dark:text-orange-300">{{ $hasActual ? 'So với điểm tái kiểm tra CMS.' : 'So với điểm dự kiến của đề xuất.' }}</p>
            </div>
        </div>
    </div>
@endif
