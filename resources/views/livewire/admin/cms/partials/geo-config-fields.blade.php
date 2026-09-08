@php
    $path = (string) ($path ?? 'form.geo_config');
    $title = $title ?? 'GEO / AI Search';
    $description = $description ?? 'Copy hỗ trợ trích dẫn trong AI Search. Quick facts vẫn sinh tự động từ field thật ngoài frontsite.';
    $showTitleField = (bool) ($showTitleField ?? false);
    $notes = collect(data_get($this, $path.'.decision_notes', []))->values();
    $geoCmsEnabled = (bool) config('frontsite_geo.enabled', true);
@endphp

@if ($geoCmsEnabled)
<section class="space-y-4 rounded-3xl border border-cyan-200 bg-cyan-50/60 p-5 dark:border-cyan-500/20 dark:bg-cyan-500/10">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $title }}</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        </div>

        <label class="inline-flex items-center gap-3 rounded-2xl border border-cyan-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 dark:border-cyan-500/20 dark:bg-zinc-900 dark:text-zinc-200">
            <input type="checkbox" wire:model.defer="{{ $path }}.is_enabled" class="rounded border-zinc-300 text-cyan-600 focus:ring-cyan-500">
            Bật block GEO
        </label>
    </div>

    @if ($showTitleField)
        <div class="space-y-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
            <input type="text" wire:model.defer="{{ $path }}.title" class="w-full rounded-2xl border border-cyan-100 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-400 dark:border-cyan-500/20 dark:bg-zinc-900 dark:text-white">
        </div>
    @endif

    <div class="space-y-2">
        <div class="flex items-center justify-between gap-3">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Answer summary</label>
            <span class="text-xs text-zinc-500 dark:text-zinc-400">Tối đa 600 ký tự, plain text</span>
        </div>
        <textarea rows="4" maxlength="600" wire:model.defer="{{ $path }}.answer_summary" class="w-full rounded-2xl border border-cyan-100 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-400 dark:border-cyan-500/20 dark:bg-zinc-900 dark:text-white" placeholder="Tóm tắt citation-ready, không nhập giá/ngày/rating/availability thủ công."></textarea>
        @error($path.'.answer_summary')
            <p class="text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Decision notes</label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Tối đa 5 dòng, mỗi dòng 180 ký tự. Không nhập dữ liệu thương mại thay cho field thật.</p>
            </div>

            @if ($notes->count() < \App\Support\GeoContent::MAX_DECISION_NOTES)
                <button type="button" wire:click="addGeoDecisionNote('{{ $path }}')" class="inline-flex items-center gap-2 rounded-2xl border border-cyan-200 bg-white px-4 py-2 text-sm font-semibold text-cyan-700 dark:border-cyan-500/20 dark:bg-zinc-900 dark:text-cyan-200">
                    <i class="fa-solid fa-plus"></i>
                    Thêm ý
                </button>
            @endif
        </div>

        <div class="space-y-3">
            @forelse ($notes as $noteIndex => $note)
                <div wire:key="geo-note-{{ md5($path) }}-{{ $noteIndex }}" class="grid gap-3 md:grid-cols-[1fr_auto]">
                    <input type="text" maxlength="180" wire:model.defer="{{ $path }}.decision_notes.{{ $noteIndex }}" class="w-full rounded-2xl border border-cyan-100 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-400 dark:border-cyan-500/20 dark:bg-zinc-900 dark:text-white">
                    <button type="button" wire:click="removeGeoDecisionNote('{{ $path }}', {{ $noteIndex }})" class="rounded-2xl border border-rose-200 px-4 py-3 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">
                        Xóa
                    </button>
                </div>
                @error($path.'.decision_notes.'.$noteIndex)
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            @empty
                <div class="rounded-2xl border border-dashed border-cyan-200 bg-white px-4 py-3 text-sm text-zinc-500 dark:border-cyan-500/20 dark:bg-zinc-900 dark:text-zinc-400">
                    Chưa có decision note thủ công. Frontsite sẽ dùng fallback theo intent trang.
                </div>
            @endforelse
        </div>
    </div>

    @unless ($showTitleField)
        <div class="space-y-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Updated label tùy chọn</label>
            <input type="text" wire:model.defer="{{ $path }}.updated_label" placeholder="Ví dụ: Cập nhật tháng 05/2026" class="w-full rounded-2xl border border-cyan-100 bg-white px-4 py-3 text-sm outline-none transition focus:border-cyan-400 dark:border-cyan-500/20 dark:bg-zinc-900 dark:text-white">
            @error($path.'.updated_label')
                <p class="text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endunless
</section>
@endif
