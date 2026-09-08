<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Hero slider trang chủ</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý ảnh, text, button và URL cho từng slide hero.</p>
        </div>

        <button type="button" wire:click="addHomeHeroSlide" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm slide
        </button>
    </div>

    <div class="space-y-5">
        @foreach (($form['home_config']['hero']['slides'] ?? []) as $index => $slide)
            @php
                $heroCollection = \App\Support\HomePageContent::heroSlideCollection($slide['uuid']);
                $heroUpload = $homeHeroUploads[$index] ?? null;
                $heroPreview = $heroUpload ? $heroUpload->temporaryUrl() : (($selectedHomeHeroLibraryMedia[$slide['uuid']] ?? null)?->getUrl() ?: $selectedPage?->getFirstMediaUrl($heroCollection));
            @endphp

            <div wire:key="landing-home-hero-slide-{{ $slide['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Hero slide {{ $loop->iteration }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Gợi ý ảnh ngang 1920x1080.</p>
                    </div>

                    @if (count($form['home_config']['hero']['slides'] ?? []) > 1)
                        <button type="button" wire:click="removeHomeHeroSlide({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa slide
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" wire:model.defer="form.home_config.hero.slides.{{ $index }}.eyebrow" placeholder="Eyebrow" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.hero.slides.{{ $index }}.image_alt" placeholder="Alt ảnh" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <x-admin.quill-editor
                        wire:key="landing-home-hero-title-{{ $selectedId ?? 'new' }}-{{ $slide['uuid'] }}-{{ md5((string) ($slide['title'] ?? '')) }}"
                        :model="'form.home_config.hero.slides.'.$index.'.title'"
                        :value="$slide['title'] ?? ''"
                        mode="rich"
                        rows="3"
                        placeholder="Tiêu đề"
                        class="md:col-span-2"
                    />
                    <x-admin.quill-editor
                        wire:key="landing-home-hero-description-{{ $selectedId ?? 'new' }}-{{ $slide['uuid'] }}-{{ md5((string) ($slide['description'] ?? '')) }}"
                        :model="'form.home_config.hero.slides.'.$index.'.description'"
                        :value="$slide['description'] ?? ''"
                        mode="rich"
                        rows="4"
                        placeholder="Mô tả"
                        class="md:col-span-2"
                    />
                    <input type="text" wire:model.defer="form.home_config.hero.slides.{{ $index }}.primary_label" placeholder="Label nút chính" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.hero.slides.{{ $index }}.primary_url" placeholder="URL nút chính" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.hero.slides.{{ $index }}.secondary_label" placeholder="Label nút phụ" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.hero.slides.{{ $index }}.secondary_url" placeholder="URL nút phụ" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>

                <div class="mt-4">
                    <x-admin.image-dropzone label="Ảnh hero" :model="'homeHeroUploads.'.$index" :preview="$heroPreview" hint="Upload ảnh mới hoặc chọn ảnh có sẵn cho từng slide." />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        data-admin-media-picker-trigger
                        data-livewire-id="{{ $this->getId() }}"
                        data-pick-method="selectHomeHeroLibraryMedia"
                        data-pick-context="{{ $slide['uuid'] }}"
                        data-button-label="Chọn ảnh này cho hero slide"
                        class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                    >
                        <i class="fa-regular fa-images"></i>
                        Chọn từ Media popup
                    </button>

                    @if (isset($selectedHomeHeroLibraryMedia[$slide['uuid']]))
                        <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check"></i>
                            Đã chọn ảnh thư viện cho slide {{ $loop->iteration }}
                        </div>

                        <button type="button" wire:click="clearHomeHeroLibraryMediaSelection('{{ $slide['uuid'] }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
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
    <div class="flex items-center justify-between gap-3">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Khối số liệu nổi bật</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Dùng cho năm kinh nghiệm, dự án hoàn thành và các chỉ số chính khác.</p>
        </div>

        <button type="button" wire:click="addHomeStat" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm số liệu
        </button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach (($form['home_config']['stats']['items'] ?? []) as $index => $stat)
            <div wire:key="landing-home-stat-{{ $stat['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Số liệu {{ $loop->iteration }}</p>

                    @if (count($form['home_config']['stats']['items'] ?? []) > 1)
                        <button type="button" wire:click="removeHomeStat({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa
                        </button>
                    @endif
                </div>

                <div class="grid gap-3">
                    <input type="text" wire:model.defer="form.home_config.stats.items.{{ $index }}.value" placeholder="Giá trị, ví dụ 15+" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.stats.items.{{ $index }}.label" placeholder="Label" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Dịch vụ của chúng tôi</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý section text và tối đa 4 card dịch vụ nổi bật.</p>
        </div>

        @if (count($form['home_config']['services']['items'] ?? []) < 4)
            <button type="button" wire:click="addHomeServiceCard" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                Thêm card
            </button>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.home_config.services.eyebrow" placeholder="Eyebrow section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.services.title" placeholder="Tiêu đề section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.home_config.services.description" placeholder="Mô tả section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <input type="text" wire:model.defer="form.home_config.services.cta_label" placeholder="Label CTA" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.services.cta_url" placeholder="URL CTA" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
    </div>

    <div class="space-y-5">
        @foreach (($form['home_config']['services']['items'] ?? []) as $index => $item)
            <div wire:key="landing-home-service-card-{{ $item['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Card dịch vụ {{ $loop->iteration }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Có thể chọn service hiện có hoặc nhập tiêu đề, text và link riêng.</p>
                    </div>

                    @if (count($form['home_config']['services']['items'] ?? []) > 1)
                        <button type="button" wire:click="removeHomeServiceCard({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa card
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Liên kết dịch vụ</label>
                        <select wire:model.defer="form.home_config.services.items.{{ $index }}.service_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            <option value="">Chọn dịch vụ đang có</option>
                            @foreach ($serviceOptions as $service)
                                <option value="{{ $service->id }}">{{ $service->id }} - {{ $service->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="text" wire:model.defer="form.home_config.services.items.{{ $index }}.icon" placeholder="Icon Material Symbols, ví dụ domain" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.services.items.{{ $index }}.title" placeholder="Tiêu đề card" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">
                    <textarea rows="3" wire:model.defer="form.home_config.services.items.{{ $index }}.description" placeholder="Mô tả ngắn" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2"></textarea>
                    <input type="text" wire:model.defer="form.home_config.services.items.{{ $index }}.link_label" placeholder="Label link" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.home_config.services.items.{{ $index }}.url" placeholder="URL override nếu không dùng service đã chọn" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>
            </div>
        @endforeach
    </div>
</section>
