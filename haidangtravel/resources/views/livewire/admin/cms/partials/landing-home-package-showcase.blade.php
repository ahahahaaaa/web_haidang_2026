@php
    $packageShowcase = $form['home_config']['package_showcase'] ?? [];
    $packageBackgroundUpload = data_get($homePackageShowcaseUploads ?? [], 'background');
    $packageBackgroundPreview = $packageBackgroundUpload
        ? $packageBackgroundUpload->temporaryUrl()
        : (($selectedHomePackageShowcaseLibraryMedia['background'] ?? null)?->getUrl() ?: $selectedPage?->getFirstMediaUrl(\App\Support\HomePageContent::packageShowcaseBackgroundCollection()));
@endphp

<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Khối giới thiệu gói thi công sau hero</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Hiển thị ngay sau banner hero trên trang chủ, có nền ảnh riêng và các card dẫn sang 3 landing page gói tiêu chuẩn, cao cấp và trang so sánh.</p>
        </div>

        @if (count($packageShowcase['items'] ?? []) < 4)
            <button type="button" wire:click="addHomePackageShowcaseItem" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                Thêm card gói
            </button>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.home_config.package_showcase.eyebrow" placeholder="Eyebrow section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.package_showcase.title" placeholder="Tiêu đề chính của block" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.home_config.package_showcase.description" placeholder="Mô tả ngắn giúp khách hàng hiểu block này dẫn đi đâu" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <input type="text" wire:model.defer="form.home_config.package_showcase.panel_badge" placeholder="Badge phụ trong panel nội dung" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.package_showcase.background_alt" placeholder="Alt ảnh nền" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.package_showcase.panel_title" placeholder="Tiêu đề khối nội dung bên trái" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2">
        <textarea rows="3" wire:model.defer="form.home_config.package_showcase.panel_description" placeholder="Mô tả bổ sung cho panel nội dung" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <input type="text" wire:model.defer="form.home_config.package_showcase.primary_label" placeholder="Label CTA chính" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.package_showcase.primary_url" placeholder="URL CTA chính" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.package_showcase.secondary_label" placeholder="Label CTA phụ" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.package_showcase.secondary_url" placeholder="URL CTA phụ" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
    </div>

    <div class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Ảnh nền block</p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Nên dùng ảnh ngang 1800x1100 hoặc lớn hơn để section hiển thị đủ chiều sâu trên desktop.</p>
            </div>
        </div>

        <div>
            <x-admin.image-dropzone label="Ảnh nền package showcase" model="homePackageShowcaseUploads.background" :preview="$packageBackgroundPreview" hint="Upload ảnh mới hoặc chọn từ Media popup cho nền section." />
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button
                type="button"
                data-admin-media-picker-trigger
                data-livewire-id="{{ $this->getId() }}"
                data-pick-method="selectHomePackageShowcaseLibraryMedia"
                data-button-label="Chọn ảnh này cho nền package showcase"
                class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
            >
                <i class="fa-regular fa-images"></i>
                Chọn từ Media popup
            </button>

            @if (isset($selectedHomePackageShowcaseLibraryMedia['background']))
                <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <i class="fa-solid fa-circle-check"></i>
                    Đã chọn ảnh thư viện cho nền section
                </div>

                <button type="button" wire:click="clearHomePackageShowcaseLibraryMediaSelection" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                    <i class="fa-solid fa-rotate-left"></i>
                    Bỏ chọn ảnh thư viện
                </button>
            @endif
        </div>
    </div>

    <div class="space-y-5">
        @foreach (($packageShowcase['items'] ?? []) as $index => $item)
            <div wire:key="landing-home-package-showcase-item-{{ $item['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Card gói {{ $loop->iteration }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Dùng card này để dẫn sang một landing page cụ thể hoặc một trang so sánh.</p>
                    </div>

                    @if (count($packageShowcase['items'] ?? []) > 1)
                        <button type="button" wire:click="removeHomePackageShowcaseItem({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa card
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <select wire:model.defer="form.home_config.package_showcase.items.{{ $index }}.tone" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="standard">Tone tiêu chuẩn</option>
                        <option value="premium">Tone cao cấp</option>
                        <option value="compare">Tone so sánh</option>
                    </select>
                    <input type="text" wire:model.defer="form.home_config.package_showcase.items.{{ $index }}.badge" placeholder="Badge / nhãn phụ" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.package_showcase.items.{{ $index }}.title" placeholder="Tiêu đề card" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">
                    <textarea rows="3" wire:model.defer="form.home_config.package_showcase.items.{{ $index }}.description" placeholder="Mô tả ngắn giúp người xem hiểu nhanh landing page này" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2"></textarea>
                    <input type="text" wire:model.defer="form.home_config.package_showcase.items.{{ $index }}.link_label" placeholder="Label link trên card" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.package_showcase.items.{{ $index }}.url" placeholder="URL landing page, ví dụ /giai-phap/phan-tho-hoan-thien-tieu-chuan" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>
            </div>
        @endforeach
    </div>
</section>
