@php
    $itineraryItems = $form['itinerary_items'] ?? [];
    $pricingItems = $form['pricing_items'] ?? [];
@endphp

<div class="space-y-5">
    <section class="w-full space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Itinerary</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Mỗi chặng có tiêu đề, bữa ăn, ảnh riêng và nội dung biên tập bằng Quill.</p>
        </div>

        <div class="space-y-4">
            @foreach ($itineraryItems as $index => $item)
                @php
                    $itineraryUuid = trim((string) ($item['uuid'] ?? ''));
                    $titleModel = 'form.itinerary_items.'.$index.'.title';
                    $mealsModel = 'form.itinerary_items.'.$index.'.meals';
                    $imageAltModel = 'form.itinerary_items.'.$index.'.image_alt';
                    $imageUrlModel = 'form.itinerary_items.'.$index.'.image_url';
                    $contentModel = 'form.itinerary_items.'.$index.'.content';
                    $imageUploadModel = 'itineraryImageUploads.'.$itineraryUuid;
                    $itineraryCollection = \App\Support\ContentGallery::tourItineraryCollection($itineraryUuid);
                    $itineraryUpload = $itineraryImageUploads[$itineraryUuid] ?? null;
                    $selectedItineraryMedia = $selectedItineraryLibraryMedia[$imageUploadModel] ?? null;
                    $itineraryImagePreview = $itineraryUpload
                        ? $itineraryUpload->temporaryUrl()
                        : ($selectedItineraryMedia?->getUrl()
                            ?: ($editingTour?->getFirstMediaUrl($itineraryCollection) ?: ($item['image_url'] ?? null)));
                @endphp

                <div wire:key="tour-itinerary-item-{{ $itineraryUuid }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Chặng {{ $loop->iteration }}</p>

                        @if (count($itineraryItems) > 1)
                            <button type="button" wire:click="removeItineraryItem({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                Xóa
                            </button>
                        @endif
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề chặng</label>
                            <input type="text" wire:model.defer="{{ $titleModel }}" placeholder="Ví dụ: Ngày 1 - Khởi hành và tham quan trung tâm" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error($titleModel) <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                <i class="fa-solid fa-utensils text-orange-500" aria-hidden="true"></i>
                                Bữa ăn trong ngày
                            </label>
                            <input type="text" wire:model.defer="{{ $mealsModel }}" placeholder="Ví dụ: Sáng, trưa, chiều" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error($mealsModel) <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt ảnh của ngày</label>
                            <input type="text" wire:model.defer="{{ $imageAltModel }}" placeholder="Mô tả ngắn nội dung ảnh" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error($imageAltModel) <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">URL ảnh ngoài (không bắt buộc)</label>
                            <input type="url" wire:model.defer="{{ $imageUrlModel }}" placeholder="https://example.com/anh-ngay-1.jpg" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error($imageUrlModel) <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-3 md:col-span-2">
                            <x-admin.image-dropzone label="Hình của ngày" :model="$imageUploadModel" :preview="$itineraryImagePreview" hint="Upload ảnh mới, chọn từ Media popup hoặc dùng URL ảnh ngoài." />
                            @error($imageUploadModel) <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    data-admin-media-picker-trigger
                                    data-livewire-id="{{ $this->getId() }}"
                                    data-pick-method="selectLibraryMediaForUpload"
                                    data-pick-target="{{ $imageUploadModel }}"
                                    data-pick-alt-target="{{ $imageAltModel }}"
                                    data-button-label="Chọn hình cho chặng này"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-orange-300 hover:text-orange-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-orange-500/40 dark:hover:text-orange-300"
                                >
                                    <i class="fa-regular fa-images" aria-hidden="true"></i>
                                    Chọn từ Media popup
                                </button>

                                @if ($selectedItineraryMedia)
                                    <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                                        Đã chọn ảnh thư viện
                                    </div>

                                    <button type="button" wire:click="clearLibraryMediaSelectionForUpload('{{ $imageUploadModel }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                        Bỏ chọn ảnh mới
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nội dung itinerary</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Có thể xuống dòng, bôi đen và chèn link trong từng chặng.</p>
                            </div>

                            <x-admin.quill-editor
                                wire:key="tour-itinerary-content-{{ $itineraryUuid }}"
                                model="{{ $contentModel }}"
                                :value="$item['content'] ?? ''"
                                mode="rich"
                                :allow-images="true"
                                rows="6"
                                placeholder="Nhập nội dung chi tiết cho chặng này."
                            />
                            @error($contentModel) <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap justify-end">
            <button type="button" wire:click="addItineraryItem" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                Thêm chặng
            </button>
        </div>
    </section>

    <section class="w-full space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Bảng giá tóm tắt</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Thêm từng dòng nhãn và giá để chuẩn hóa phần tóm tắt chi phí của tour.</p>
        </div>

        <div class="space-y-4">
            @foreach ($pricingItems as $index => $item)
                @php
                    $labelModel = 'form.pricing_items.'.$index.'.label';
                    $priceModel = 'form.pricing_items.'.$index.'.price';
                @endphp

                <div wire:key="tour-pricing-item-{{ $index }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Dòng giá {{ $loop->iteration }}</p>

                        @if (count($pricingItems) > 1)
                            <button type="button" wire:click="removePricingItem({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                Xóa
                            </button>
                        @endif
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <input type="text" wire:model.defer="{{ $labelModel }}" placeholder="Ví dụ: Giá từ / Vé người lớn / Phụ thu phòng đơn" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error($labelModel) <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <input type="text" wire:model.defer="{{ $priceModel }}" placeholder="Ví dụ: 5.990.000 đ" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error($priceModel) <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap justify-end">
            <button type="button" wire:click="addPricingItem" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                Thêm dòng giá
            </button>
        </div>
    </section>
</div>
