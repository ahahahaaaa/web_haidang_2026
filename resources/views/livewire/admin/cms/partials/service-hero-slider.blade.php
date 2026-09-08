<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Block Hero Slider</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý ảnh nền, tiêu đề và CTA cho phần hero của service detail.</p>
    </div>

    <div class="space-y-5">
        @foreach (($form['detail_config']['hero_slides'] ?? []) as $index => $slide)
            @php
                $heroCollection = \App\Support\ServiceDetailContent::heroSlideCollection($slide['uuid']);
                $heroUpload = $heroSlideUploads[$index] ?? null;
                $heroPreview = $heroUpload ? $heroUpload->temporaryUrl() : (($selectedHeroSlideLibraryMedia[$slide['uuid']] ?? null)?->getUrl() ?: $editingService?->getFirstMediaUrl($heroCollection));
            @endphp

            <div wire:key="service-hero-slide-{{ $slide['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Hero slide {{ $loop->iteration }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Ảnh ngang gợi ý 1920x1080.</p>
                    </div>

                    @if (count($form['detail_config']['hero_slides'] ?? []) > 1)
                        <button type="button" wire:click="removeHeroSlide({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa slide
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Eyebrow</label>
                        <input type="text" wire:model.defer="form.detail_config.hero_slides.{{ $index }}.eyebrow" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt ảnh</label>
                        <input type="text" wire:model.defer="form.detail_config.hero_slides.{{ $index }}.image_alt" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                        <x-admin.quill-editor
                            wire:key="service-hero-title-{{ $selectedId ?? 'new' }}-{{ $slide['uuid'] }}-{{ md5((string) ($slide['title'] ?? '')) }}"
                            :model="'form.detail_config.hero_slides.'.$index.'.title'"
                            :value="$slide['title'] ?? ''"
                            mode="rich"
                            rows="4"
                            placeholder="Tiêu đề hero, có thể xuống dòng và tô màu để nhấn nội dung."
                        />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            Dùng toolbar để xuống dòng hoặc tô màu các cụm chữ trong tiêu đề hero.
                        </p>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
                        <textarea rows="4" wire:model.defer="form.detail_config.hero_slides.{{ $index }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label nút chính</label>
                        <input type="text" wire:model.defer="form.detail_config.hero_slides.{{ $index }}.primary_label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label nút phụ</label>
                        <input type="text" wire:model.defer="form.detail_config.hero_slides.{{ $index }}.secondary_label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>
                </div>

                <div class="mt-4">
                    <x-admin.image-dropzone label="Ảnh hero" :model="'heroSlideUploads.'.$index" :preview="$heroPreview" hint="Upload ảnh mới hoặc chọn ảnh có sẵn cho từng slide." />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        data-admin-media-picker-trigger
                        data-livewire-id="{{ $this->getId() }}"
                        data-pick-method="selectHeroSlideLibraryMedia"
                        data-pick-context="{{ $slide['uuid'] }}"
                        data-button-label="Chọn ảnh này cho hero slide"
                        class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                    >
                        <i class="fa-regular fa-images"></i>
                        Chọn từ Media popup
                    </button>

                    @if (isset($selectedHeroSlideLibraryMedia[$slide['uuid']]))
                        <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check"></i>
                            Đã chọn ảnh thư viện cho slide {{ $loop->iteration }}
                        </div>

                        <button type="button" wire:click="clearHeroSlideLibraryMediaSelection('{{ $slide['uuid'] }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                            <i class="fa-solid fa-rotate-left"></i>
                            Bỏ chọn ảnh thư viện
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap justify-end">
        <button type="button" wire:click="addHeroSlide" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm slide
        </button>
    </div>
</section>
