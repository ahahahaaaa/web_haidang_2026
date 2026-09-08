<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Kiến trúc mang tầm vóc</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Gallery upload bằng Spatie Media hoặc chọn ảnh từ Media popup.</p>
        </div>

        <button type="button" wire:click="addHomeGalleryItem" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm ảnh gallery
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.home_config.gallery.eyebrow" placeholder="Eyebrow section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.gallery.title" placeholder="Tiêu đề section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.home_config.gallery.description" placeholder="Mô tả section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
    </div>

    <div class="space-y-5">
        @foreach (($form['home_config']['gallery']['items'] ?? []) as $index => $item)
            @php
                $galleryCollection = \App\Support\HomePageContent::galleryCollection($item['uuid']);
                $galleryUpload = $homeGalleryUploads[$index] ?? null;
                $galleryPreview = $galleryUpload ? $galleryUpload->temporaryUrl() : (($selectedHomeGalleryLibraryMedia[$item['uuid']] ?? null)?->getUrl() ?: $selectedPage?->getFirstMediaUrl($galleryCollection));
            @endphp

            <div wire:key="landing-home-gallery-item-{{ $item['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Ảnh gallery {{ $loop->iteration }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Có thể gắn title, subtitle và link riêng cho từng ảnh.</p>
                    </div>

                    @if (count($form['home_config']['gallery']['items'] ?? []) > 1)
                        <button type="button" wire:click="removeHomeGalleryItem({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa ảnh
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" wire:model.defer="form.home_config.gallery.items.{{ $index }}.title" placeholder="Tiêu đề ảnh" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.gallery.items.{{ $index }}.subtitle" placeholder="Mô tả ngắn / địa điểm" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.gallery.items.{{ $index }}.url" placeholder="URL khi click ảnh" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.gallery.items.{{ $index }}.image_alt" placeholder="Alt ảnh" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>

                <div class="mt-4">
                    <x-admin.image-dropzone label="Ảnh gallery" :model="'homeGalleryUploads.'.$index" :preview="$galleryPreview" hint="Upload ảnh mới hoặc chọn từ Media popup." />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        data-admin-media-picker-trigger
                        data-livewire-id="{{ $this->getId() }}"
                        data-pick-method="selectHomeGalleryLibraryMedia"
                        data-pick-context="{{ $item['uuid'] }}"
                        data-button-label="Chọn ảnh này cho gallery"
                        class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                    >
                        <i class="fa-regular fa-images"></i>
                        Chọn từ Media popup
                    </button>

                    @if (isset($selectedHomeGalleryLibraryMedia[$item['uuid']]))
                        <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check"></i>
                            Đã chọn ảnh thư viện
                        </div>

                        <button type="button" wire:click="clearHomeGalleryLibraryMediaSelection('{{ $item['uuid'] }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                            <i class="fa-solid fa-rotate-left"></i>
                            Bỏ chọn ảnh thư viện
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Quy trình làm việc</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Desktop grid tự cân cho 3 đến 6 card, mobile chuyển sang slide 1 card.</p>
        </div>

        <button type="button" wire:click="addHomeProcessCard" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm bước
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.home_config.process.eyebrow" placeholder="Eyebrow section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.process.title" placeholder="Tiêu đề section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.home_config.process.description" placeholder="Mô tả section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach (($form['home_config']['process']['cards'] ?? []) as $index => $card)
            <div wire:key="landing-home-process-card-{{ $card['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Bước {{ $loop->iteration }}</p>

                    @if (count($form['home_config']['process']['cards'] ?? []) > 1)
                        <button type="button" wire:click="removeHomeProcessCard({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa
                        </button>
                    @endif
                </div>

                <div class="grid gap-3">
                    <input type="text" wire:model.defer="form.home_config.process.cards.{{ $index }}.title" placeholder="Tiêu đề bước" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <textarea rows="4" wire:model.defer="form.home_config.process.cards.{{ $index }}.description" placeholder="Mô tả bước" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Cảm nhận khách hàng</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý block testimonial trên homepage. Mobile hiển thị 1 card, desktop hiển thị 2 card trong slider.</p>
        </div>

        <button type="button" wire:click="addHomeValueCard" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm testimonial
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.home_config.values.eyebrow" placeholder="Nhãn phụ section, ví dụ: Cảm nhận khách hàng" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.values.title" placeholder="Tiêu đề section testimonial" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.home_config.values.description" placeholder="Mô tả ngắn dẫn vào các phản hồi khách hàng" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach (($form['home_config']['values']['cards'] ?? []) as $index => $card)
            @php
                $valueCollection = \App\Support\HomePageContent::valueCardCollection($card['uuid']);
                $valueUpload = $homeValueUploads[$index] ?? null;
                $valuePreview = $valueUpload
                    ? $valueUpload->temporaryUrl()
                    : (($selectedHomeValueLibraryMedia['homeValueUploads.'.$index] ?? null)?->getUrl() ?: $selectedPage?->getFirstMediaUrl($valueCollection));
            @endphp

            <div wire:key="landing-home-value-card-{{ $card['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Testimonial {{ $loop->iteration }}</p>

                    @if (count($form['home_config']['values']['cards'] ?? []) > 1)
                        <button type="button" wire:click="removeHomeValueCard({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa testimonial
                        </button>
                    @endif
                </div>

                <div class="grid gap-3">
                    <input type="text" wire:model.defer="form.home_config.values.cards.{{ $index }}.title" placeholder="Tên khách hàng" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.values.cards.{{ $index }}.role" placeholder="Chức danh / loại công trình / khu vực" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.values.cards.{{ $index }}.image_alt" placeholder="Alt ảnh đại diện khách hàng" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <textarea rows="4" wire:model.defer="form.home_config.values.cards.{{ $index }}.text" placeholder="Nội dung cảm nhận của khách hàng" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                </div>

                <div class="mt-4">
                    <x-admin.image-dropzone label="Ảnh đại diện khách hàng" :model="'homeValueUploads.'.$index" :preview="$valuePreview" hint="Upload avatar hoặc ảnh chân dung dùng cho testimonial này." />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        data-admin-media-picker-trigger
                        data-pick-method="selectLibraryMediaForUpload"
                        data-pick-target="homeValueUploads.{{ $index }}"
                        data-pick-alt-target="form.home_config.values.cards.{{ $index }}.image_alt"
                        data-button-label="Chọn ảnh testimonial này"
                        class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                    >
                        <i class="fa-regular fa-images"></i>
                        Chọn từ Media popup
                    </button>

                    @if (isset($selectedHomeValueLibraryMedia['homeValueUploads.'.$index]))
                        <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check"></i>
                            Đã chọn ảnh thư viện
                        </div>

                        <button type="button" wire:click="clearLibraryMediaSelectionForUpload('homeValueUploads.{{ $index }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                            <i class="fa-solid fa-rotate-left"></i>
                            Bỏ chọn ảnh thư viện
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
