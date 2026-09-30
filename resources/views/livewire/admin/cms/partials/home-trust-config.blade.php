@php
    $trustStats = $form['home_config']['trust']['stats'] ?? [];
    $trustAwards = $form['home_config']['trust']['awards'] ?? [];
    $wireKeyPrefix = (string) ($wireKeyPrefix ?? 'home-trust');
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <div class="space-y-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề section</label>
        <input type="text" wire:model.defer="form.home_config.trust.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        @error('form.home_config.trust.title') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Dòng giới thiệu ngắn</label>
        <input type="text" wire:model.defer="form.home_config.trust.subtitle" placeholder="Ví dụ: Nâng tầm giá trị cuộc sống" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        @error('form.home_config.trust.subtitle') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>

    <div class="space-y-2 md:col-span-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả doanh nghiệp</label>
        <textarea rows="4" wire:model.defer="form.home_config.trust.description" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
        @error('form.home_config.trust.description') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="font-semibold text-zinc-900 dark:text-white">Số liệu nổi bật</h4>
            <p class="text-xs leading-6 text-zinc-500 dark:text-zinc-400">Tối đa 3 chỉ số, hiển thị thành ba thẻ bên dưới phần giới thiệu.</p>
        </div>

        @if (count($trustStats) < 3)
            <button type="button" wire:click="addHomeTrustStat" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:text-zinc-200">
                Thêm chỉ số
            </button>
        @endif
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        @foreach ($trustStats as $index => $stat)
            <div wire:key="{{ $wireKeyPrefix }}-stat-{{ $stat['uuid'] ?? $index }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-teal-600 dark:text-teal-300">Chỉ số {{ $loop->iteration }}</p>

                    @if (count($trustStats) > 1)
                        <button type="button" wire:click="removeHomeTrustStat({{ $index }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">Xóa</button>
                    @endif
                </div>

                <div class="grid gap-3">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Giá trị</label>
                        <input type="text" wire:model.defer="form.home_config.trust.stats.{{ $index }}.value" placeholder="19+" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error("form.home_config.trust.stats.$index.value") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nhãn</label>
                        <input type="text" wire:model.defer="form.home_config.trust.stats.{{ $index }}.label" placeholder="Năm kinh nghiệm" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error("form.home_config.trust.stats.$index.label") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<div class="mt-6 space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h4 class="font-semibold text-zinc-900 dark:text-white">Ảnh giải thưởng</h4>
            <p class="text-xs leading-6 text-zinc-500 dark:text-zinc-400">Các ảnh hợp lệ sẽ chạy thành slider bên phải. Nên dùng ảnh rõ nét, nền sạch và cùng tỷ lệ.</p>
        </div>

        @if (count($trustAwards) < 12)
            <button type="button" wire:click="addHomeTrustAward" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:text-zinc-200">
                Thêm giải thưởng
            </button>
        @endif
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        @foreach ($trustAwards as $index => $award)
            @php
                $awardImageUrl = trim((string) ($award['image_url'] ?? ''));
            @endphp
            <div wire:key="{{ $wireKeyPrefix }}-award-{{ $award['uuid'] ?? $index }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-teal-600 dark:text-teal-300">Giải thưởng {{ $loop->iteration }}</p>

                    @if (count($trustAwards) > 1)
                        <button type="button" wire:click="removeHomeTrustAward({{ $index }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">Xóa</button>
                    @endif
                </div>

                <div class="grid gap-4">
                    <div class="space-y-3">
                        <div class="overflow-hidden rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950/50">
                            @if ($awardImageUrl !== '')
                                <img src="{{ $awardImageUrl }}" alt="{{ $award['image_alt'] ?? $award['title'] ?? 'Ảnh giải thưởng' }}" class="h-52 w-full object-contain p-3">
                            @else
                                <div class="flex h-52 items-center justify-center px-4 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa chọn ảnh giải thưởng.</div>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <button
                                type="button"
                                data-admin-media-picker-trigger
                                data-livewire-id="{{ $this->getId() }}"
                                data-pick-method="selectHomeTrustAwardLibraryMedia"
                                data-pick-context="{{ $award['uuid'] }}"
                                data-button-label="Chọn ảnh giải thưởng"
                                class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-teal-500/40 dark:hover:text-teal-300"
                            >
                                <i class="fa-regular fa-images"></i>
                                Chọn từ Media popup
                            </button>

                            @if ($awardImageUrl !== '')
                                <button type="button" wire:click="clearHomeTrustAwardImage('{{ $award['uuid'] }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    Xóa ảnh
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tên giải thưởng</label>
                        <input type="text" wire:model.defer="form.home_config.trust.awards.{{ $index }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error("form.home_config.trust.awards.$index.title") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả ngắn</label>
                        <textarea rows="3" wire:model.defer="form.home_config.trust.awards.{{ $index }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                        @error("form.home_config.trust.awards.$index.description") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt ảnh</label>
                        <input type="text" wire:model.defer="form.home_config.trust.awards.{{ $index }}.image_alt" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error("form.home_config.trust.awards.$index.image_alt") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
