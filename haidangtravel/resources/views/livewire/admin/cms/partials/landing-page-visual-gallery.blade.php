<section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Visual Gallery</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Gallery có thể lấy toàn bộ item từ slider hoặc dùng từng ảnh riêng từ Media popup.</p>
        </div>

        <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
            <input type="checkbox" wire:model.defer="form.visual_config.gallery.enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
            Bật gallery visual
        </label>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <select wire:model.defer="form.visual_config.gallery.source" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <option value="none">Không dùng gallery riêng</option>
            <option value="slider">Dùng slider</option>
            <option value="media">Dùng Media popup / upload</option>
        </select>
        <input type="text" value="{{ \App\Support\LandingPageVisuals::sliderLocation((string) ($form['page_key'] ?? 'landing'), 'gallery') }}" readonly class="rounded-2xl border border-zinc-200 bg-zinc-100 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
        <input type="text" wire:model.defer="form.visual_config.gallery.eyebrow" placeholder="Eyebrow gallery" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.visual_config.gallery.title" placeholder="Tiêu đề gallery" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.visual_config.gallery.description" placeholder="Mô tả gallery" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
    </div>

    @if (($form['visual_config']['gallery']['source'] ?? 'none') === 'slider')
        <div class="grid gap-4 md:grid-cols-2">
            <select wire:model.defer="form.visual_config.gallery.slider_id" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <option value="">Tự tìm theo banner-location</option>
                @foreach ($sliders as $slider)
                    <option value="{{ $slider->id }}">{{ $slider->name }} {{ $slider->location ? '('.$slider->location.')' : '' }} - {{ $slider->active_items_count }} item</option>
                @endforeach
            </select>

            <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                Nếu không chọn slider cụ thể, frontsite sẽ tự lấy slider active có <span class="font-semibold">banner-location = {{ \App\Support\LandingPageVisuals::sliderLocation((string) ($form['page_key'] ?? 'landing'), 'gallery') }}</span>.
            </div>
        </div>
    @endif

    @if (($form['visual_config']['gallery']['source'] ?? 'none') === 'media')
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Từng item gallery hỗ trợ tiêu đề, subtitle, alt và link riêng.</p>

            <button type="button" wire:click="addGalleryItem" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-teal-300 hover:text-teal-600 dark:border-zinc-700 dark:text-zinc-200">
                Thêm item gallery
            </button>
        </div>

        <div class="space-y-5">
            @foreach (($form['visual_config']['gallery']['items'] ?? []) as $index => $item)
                @php
                    $galleryCollection = \App\Support\LandingPageVisuals::galleryCollection((string) ($item['uuid'] ?? 'gallery-'.$index));
                    $galleryPreview = ($galleryUploads[$index] ?? null)
                        ? $galleryUploads[$index]->temporaryUrl()
                        : (($selectedGalleryLibraryMedia['galleryUploads.'.$index] ?? null)?->getUrl() ?: $selectedPage?->getFirstMediaUrl($galleryCollection));
                @endphp

                <div wire:key="landing-gallery-item-{{ $item['uuid'] ?? $index }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-teal-600">Gallery item {{ $loop->iteration }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Upload ảnh mới hoặc chọn ảnh từ media library.</p>
                        </div>

                        @if (count($form['visual_config']['gallery']['items'] ?? []) > 1)
                            <button type="button" wire:click="removeGalleryItem({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                Xóa item
                            </button>
                        @endif
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <input type="text" wire:model.defer="form.visual_config.gallery.items.{{ $index }}.title" placeholder="Tiêu đề item" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <input type="text" wire:model.defer="form.visual_config.gallery.items.{{ $index }}.subtitle" placeholder="Subtitle / mô tả ngắn" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <input type="text" wire:model.defer="form.visual_config.gallery.items.{{ $index }}.url" placeholder="Link khi click item" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <input type="text" wire:model.defer="form.visual_config.gallery.items.{{ $index }}.image_alt" placeholder="Alt ảnh" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="mt-4">
                        <x-admin.image-dropzone
                            label="Ảnh gallery"
                            :model="'galleryUploads.'.$index"
                            :preview="$galleryPreview"
                            hint="Upload ảnh mới hoặc chọn lại từ Media popup."
                        />
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            data-admin-media-picker-trigger
                            data-livewire-id="{{ $this->getId() }}"
                            data-pick-method="selectLibraryMediaForUpload"
                            data-pick-target="galleryUploads.{{ $index }}"
                            data-pick-alt-target="form.visual_config.gallery.items.{{ $index }}.image_alt"
                            data-button-label="Chọn ảnh gallery này"
                            class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-teal-300 hover:text-teal-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                        >
                            <i class="fa-regular fa-images"></i>
                            Chọn từ Media popup
                        </button>

                        @if (isset($selectedGalleryLibraryMedia['galleryUploads.'.$index]))
                            <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                <i class="fa-solid fa-circle-check"></i>
                                Đã chọn ảnh thư viện
                            </div>

                            <button type="button" wire:click="clearLibraryMediaSelectionForUpload('galleryUploads.{{ $index }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                <i class="fa-solid fa-rotate-left"></i>
                                Bỏ chọn ảnh thư viện
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
