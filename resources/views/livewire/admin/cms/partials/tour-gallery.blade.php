@php
    $galleryItems = $galleryItems ?? [];
    $galleryTitle = $galleryTitle ?? 'Gallery nội dung';
    $galleryDescription = $galleryDescription ?? 'Thêm ảnh hoặc video để làm giàu trải nghiệm trang chi tiết.';
    $galleryPathPrefix = $galleryPathPrefix ?? 'form.gallery';
    $galleryContext = $galleryContext ?? 'tour';
    $editingModel = $editingModel ?? null;
@endphp

<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $galleryTitle }}</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $galleryDescription }}</p>
    </div>

    @if ($galleryItems === [])
        <div class="rounded-3xl border border-dashed border-zinc-300 bg-white/80 px-5 py-6 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900/60 dark:text-zinc-400">
            Chưa có gallery. Hãy thêm ảnh hoặc video để tăng chiều sâu cho landing/trang chi tiết.
        </div>
    @endif

    <div class="space-y-5">
        @foreach ($galleryItems as $index => $item)
            @php
                $galleryCollection = $galleryContext === 'tour'
                    ? \App\Support\ContentGallery::tourCollection($item['uuid'])
                    : \App\Support\ContentGallery::taxonomyCollection($galleryContext, $item['uuid']);
                $galleryUpload = $galleryUploads[$index] ?? null;
                $galleryPreview = $galleryUpload
                    ? $galleryUpload->temporaryUrl()
                    : (($selectedGalleryLibraryMedia[$item['uuid']] ?? null)?->getUrl()
                        ?: ($editingModel?->getFirstMediaUrl($galleryCollection) ?: ($item['image_url'] ?? null)));
                $isImage = ($item['type'] ?? 'image') === 'image';
            @endphp

            <div wire:key="tour-gallery-item-{{ $item['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Gallery item {{ $loop->iteration }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Có thể là ảnh, video YouTube hoặc file MP4 công khai.</p>
                    </div>

                    <button type="button" wire:click="removeGalleryItem({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                        Xóa item
                    </button>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Loại media</label>
                        <select wire:model.live="{{ $galleryPathPrefix }}.{{ $index }}.type" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            <option value="image">Ảnh</option>
                            <option value="youtube">YouTube</option>
                            <option value="mp4">MP4</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                        <input type="text" wire:model.defer="{{ $galleryPathPrefix }}.{{ $index }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả ngắn</label>
                        <textarea rows="3" wire:model.defer="{{ $galleryPathPrefix }}.{{ $index }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                    </div>

                    @if ($isImage)
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt ảnh</label>
                            <input type="text" wire:model.defer="{{ $galleryPathPrefix }}.{{ $index }}.image_alt" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">URL ảnh ngoài</label>
                            <input type="url" wire:model.defer="{{ $galleryPathPrefix }}.{{ $index }}.image_url" placeholder="https://example.com/image.jpg" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>
                    @else
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">URL video</label>
                            <input type="url" wire:model.defer="{{ $galleryPathPrefix }}.{{ $index }}.video_url" placeholder="{{ ($item['type'] ?? 'image') === 'youtube' ? 'https://www.youtube.com/watch?v=...' : 'https://example.com/video.mp4' }}" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>
                    @endif
                </div>

                @if ($isImage)
                    <div class="mt-4">
                        <x-admin.image-dropzone label="Ảnh gallery" :model="'galleryUploads.'.$index" :preview="$galleryPreview" hint="Upload ảnh mới, chọn từ Media popup hoặc giữ URL ảnh ngoài." />
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            data-admin-media-picker-trigger
                            data-livewire-id="{{ $this->getId() }}"
                            data-pick-method="selectGalleryLibraryMedia"
                            data-pick-context="{{ $item['uuid'] }}"
                            data-button-label="Chọn ảnh này cho gallery"
                            class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                        >
                            <i class="fa-regular fa-images"></i>
                            Chọn từ Media popup
                        </button>

                        @if (isset($selectedGalleryLibraryMedia[$item['uuid']]))
                            <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                <i class="fa-solid fa-circle-check"></i>
                                Đã chọn ảnh thư viện
                            </div>

                            <button type="button" wire:click="clearGalleryLibraryMediaSelection('{{ $item['uuid'] }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                <i class="fa-solid fa-rotate-left"></i>
                                Bỏ chọn ảnh thư viện
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap justify-end gap-2">
        <button type="button" wire:click="addGalleryItem('image')" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm ảnh
        </button>
        <button type="button" wire:click="addGalleryItem('youtube')" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm YouTube
        </button>
        <button type="button" wire:click="addGalleryItem('mp4')" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm MP4
        </button>
    </div>
</section>
