<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Block tiêu chuẩn chéo</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Trang public sẽ tự động đảo layout trái/phải theo thứ tự block.</p>
    </div>

    <div class="space-y-5">
        @foreach (($form['detail_config']['feature_blocks'] ?? []) as $blockIndex => $block)
            @php
                $featureCollection = \App\Support\ServiceDetailContent::featureBlockCollection($block['uuid']);
                $featureUpload = $featureBlockUploads[$blockIndex] ?? null;
                $featurePreview = $featureUpload ? $featureUpload->temporaryUrl() : (($selectedFeatureBlockLibraryMedia[$block['uuid']] ?? null)?->getUrl() ?: $editingService?->getFirstMediaUrl($featureCollection));
            @endphp

            <div wire:key="service-feature-block-{{ $block['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Block {{ $loop->iteration }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Phù hợp các section như “Giải pháp kiến trúc” hoặc “Thi công chính xác”.</p>
                    </div>

                    @if (count($form['detail_config']['feature_blocks'] ?? []) > 1)
                        <button type="button" wire:click="removeFeatureBlock({{ $blockIndex }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa block
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Eyebrow</label>
                        <input type="text" wire:model.defer="form.detail_config.feature_blocks.{{ $blockIndex }}.eyebrow" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt ảnh</label>
                        <input type="text" wire:model.defer="form.detail_config.feature_blocks.{{ $blockIndex }}.image_alt" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                        <input type="text" wire:model.defer="form.detail_config.feature_blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
                        <textarea rows="4" wire:model.defer="form.detail_config.feature_blocks.{{ $blockIndex }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Highlights</label>
                    </div>

                    <div class="space-y-3">
                        @foreach (($block['highlights'] ?? []) as $highlightIndex => $highlight)
                            <div wire:key="service-feature-highlight-{{ $block['uuid'] }}-{{ $highlightIndex }}" class="flex gap-3">
                                <input type="text" wire:model.defer="form.detail_config.feature_blocks.{{ $blockIndex }}.highlights.{{ $highlightIndex }}" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                @if (count($block['highlights'] ?? []) > 1)
                                    <button type="button" wire:click="removeFeatureHighlight({{ $blockIndex }}, {{ $highlightIndex }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                        Xóa
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap justify-end">
                        <button type="button" wire:click="addFeatureHighlight({{ $blockIndex }})" class="rounded-2xl border border-zinc-300 px-3 py-2 text-xs font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                            Thêm highlight
                        </button>
                    </div>
                </div>

                <div class="mt-4">
                    <x-admin.image-dropzone label="Ảnh block" :model="'featureBlockUploads.'.$blockIndex" :preview="$featurePreview" hint="Upload ảnh mới hoặc chọn ảnh có sẵn cho block này." />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        data-admin-media-picker-trigger
                        data-livewire-id="{{ $this->getId() }}"
                        data-pick-method="selectFeatureBlockLibraryMedia"
                        data-pick-context="{{ $block['uuid'] }}"
                        data-button-label="Chọn ảnh này cho block"
                        class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                    >
                        <i class="fa-regular fa-images"></i>
                        Chọn từ Media popup
                    </button>

                    @if (isset($selectedFeatureBlockLibraryMedia[$block['uuid']]))
                        <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check"></i>
                            Đã chọn ảnh thư viện cho block {{ $loop->iteration }}
                        </div>

                        <button type="button" wire:click="clearFeatureBlockLibraryMediaSelection('{{ $block['uuid'] }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                            <i class="fa-solid fa-rotate-left"></i>
                            Bỏ chọn ảnh thư viện
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap justify-end">
        <button type="button" wire:click="addFeatureBlock" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm block
        </button>
    </div>
</section>
