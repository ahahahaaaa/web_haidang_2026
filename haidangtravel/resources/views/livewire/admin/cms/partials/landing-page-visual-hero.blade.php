<section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Visual Hero</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Chọn nguồn hero từ slider theo banner-location hoặc từ một ảnh do bạn chọn trong Media popup.</p>
        </div>

        <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
            <input type="checkbox" wire:model.defer="form.visual_config.hero.enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
            Bật hero visual
        </label>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <select wire:model.defer="form.visual_config.hero.source" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <option value="none">Không dùng visual riêng</option>
            <option value="slider">Dùng slider</option>
            <option value="media">Dùng Media popup / upload</option>
        </select>
        <input type="text" value="{{ \App\Support\LandingPageVisuals::sliderLocation((string) ($form['page_key'] ?? 'landing'), 'hero') }}" readonly class="rounded-2xl border border-zinc-200 bg-zinc-100 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
    </div>

    @if (($form['visual_config']['hero']['source'] ?? 'none') === 'slider')
        <div class="grid gap-4 md:grid-cols-2">
            <select wire:model.defer="form.visual_config.hero.slider_id" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <option value="">Tự tìm theo banner-location</option>
                @foreach ($sliders as $slider)
                    <option value="{{ $slider->id }}">{{ $slider->name }} {{ $slider->location ? '('.$slider->location.')' : '' }} - {{ $slider->active_items_count }} item</option>
                @endforeach
            </select>

            <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                Nếu không chọn slider cụ thể, frontsite sẽ tự lấy slider active có <span class="font-semibold">banner-location = {{ \App\Support\LandingPageVisuals::sliderLocation((string) ($form['page_key'] ?? 'landing'), 'hero') }}</span>.
            </div>
        </div>
    @endif

    @if (($form['visual_config']['hero']['source'] ?? 'none') === 'media')
        <div class="grid gap-4 md:grid-cols-2">
            <input type="text" wire:model.defer="form.visual_config.hero.media_alt" placeholder="Alt ảnh hero" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                Ảnh hero sẽ được dùng như visual chính cho block hero khi page không chọn slider.
            </div>
        </div>

        <x-admin.image-dropzone
            label="Ảnh hero landing"
            model="heroUpload"
            :preview="$heroUpload ? $heroUpload->temporaryUrl() : ($selectedHeroLibraryMedia?->getUrl() ?: $selectedPage?->getFirstMediaUrl(\App\Support\LandingPageVisuals::heroCollection()))"
            hint="Upload ảnh mới hoặc chọn lại từ Media popup."
        />

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                data-admin-media-picker-trigger
                data-livewire-id="{{ $this->getId() }}"
                data-pick-method="selectLibraryMediaForUpload"
                data-pick-target="heroUpload"
                data-pick-alt-target="form.visual_config.hero.media_alt"
                data-button-label="Chọn ảnh hero này"
                class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-teal-300 hover:text-teal-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
            >
                <i class="fa-regular fa-images"></i>
                Chọn từ Media popup
            </button>

            @if ($selectedHeroLibraryMedia)
                <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <i class="fa-solid fa-circle-check"></i>
                    Đã chọn ảnh thư viện: {{ $selectedHeroLibraryMedia->name }}
                </div>

                <button type="button" wire:click="clearLibraryMediaSelectionForUpload('heroUpload')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                    <i class="fa-solid fa-rotate-left"></i>
                    Bỏ chọn ảnh thư viện
                </button>
            @endif
        </div>
    @endif
</section>
