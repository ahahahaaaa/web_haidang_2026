<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Quản lý media</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Thư viện ảnh tập trung cho toàn bộ Spatie Media Library, có lọc theo collection và đối tượng.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.82fr_1.18fr]">
        <div class="space-y-6">
            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Tải ảnh vào thư viện chung</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Ảnh mới sẽ được lưu vào collection `library` của thiết lập site để có thể tái sử dụng ở mọi editor.</p>
                </div>

                <form wire:submit="uploadToLibrary" class="space-y-5">
                    <x-admin.image-dropzone
                        label="Ảnh thư viện"
                        model="uploadImage"
                        :preview="$uploadImage && method_exists($uploadImage, 'temporaryUrl') ? $uploadImage->temporaryUrl() : null"
                        hint="Dùng cho bài viết, landing page hoặc nội dung SEO. Sau khi chọn hoặc kéo ảnh, bấm nút tải để lưu."
                    />

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tên media</label>
                            <input type="text" wire:model.defer="uploadForm.name" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt text mặc định</label>
                            <input type="text" wire:model.defer="uploadForm.alt" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </div>
                    </div>

                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        Tải ảnh lên thư viện
                    </button>
                </form>
            </section>

            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Lọc thư viện</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Tìm nhanh theo tên file, collection hoặc model sở hữu ảnh.</p>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tìm kiếm</label>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tên ảnh hoặc file..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Collection</label>
                            <select wire:model.live="collectionFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <option value="">Tất cả collection</option>
                                @foreach ($collections as $collection)
                                    <option value="{{ $collection['value'] }}">{{ $collection['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Đối tượng</label>
                            <select wire:model.live="modelFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <option value="">Tất cả đối tượng</option>
                                @foreach ($modelOptions as $model)
                                    <option value="{{ $model['value'] }}">{{ $model['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Kiểm tra file thất lạc</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Quét file gốc của ảnh trên disk theo bộ lọc hiện tại, thống kê record lỗi và hỗ trợ xóa hàng loạt.</p>
                        @if (filled($search) || filled($collectionFilter) || filled($modelFilter))
                            <p class="mt-2 text-xs font-medium uppercase tracking-[0.2em] text-amber-600 dark:text-amber-300">Đang áp dụng bộ lọc hiện tại cho lần quét này.</p>
                        @endif
                    </div>

                    <button type="button" wire:click="auditMissingMediaFiles" wire:loading.attr="disabled" wire:target="auditMissingMediaFiles,deleteMissingMediaFiles" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-5 py-3 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-70 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span wire:loading.remove wire:target="auditMissingMediaFiles">Quét file gốc</span>
                        <span wire:loading wire:target="auditMissingMediaFiles">Đang quét...</span>
                    </button>
                </div>

                @if ($missingMediaAudit)
                    <div class="space-y-4">
                        <div class="grid gap-3 md:grid-cols-3">
                            <div class="rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Đã quét</p>
                                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $missingMediaAudit['scanned_count'] }}</p>
                            </div>

                            <div class="rounded-3xl bg-emerald-50 p-4 dark:bg-emerald-500/10">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600 dark:text-emerald-300">Còn file gốc</p>
                                <p class="mt-2 text-2xl font-semibold text-emerald-700 dark:text-emerald-200">{{ $missingMediaAudit['healthy_count'] }}</p>
                            </div>

                            <div class="rounded-3xl bg-red-50 p-4 dark:bg-red-500/10">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-red-600 dark:text-red-300">Mất file gốc</p>
                                <p class="mt-2 text-2xl font-semibold text-red-700 dark:text-red-200">{{ $missingMediaAudit['missing_count'] }}</p>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800/70 dark:text-zinc-300">
                            <p>Tổng dung lượng record lỗi: <span class="font-semibold text-zinc-900 dark:text-white">{{ $missingMediaAudit['missing_size'] }}</span></p>
                            <p class="mt-2">Lần quét gần nhất: {{ $missingMediaAudit['ran_at'] }}</p>
                            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Chỉ những record mất file gốc mới được đưa vào danh sách xóa. Nếu ảnh chỉ thiếu conversion, bạn nên regenerate thay vì xóa.</p>
                        </div>

                        @if ($missingMediaAudit['missing_count'] > 0)
                            <div class="flex flex-wrap gap-3">
                                <button type="button" wire:click="deleteMissingMediaFiles" wire:confirm="Bạn có chắc chắn muốn xóa toàn bộ record media đang bị mất file gốc trong kết quả quét hiện tại? Hành động này không thể hoàn tác." wire:loading.attr="disabled" wire:target="auditMissingMediaFiles,deleteMissingMediaFiles" class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-5 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-70 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                                    <i class="fa-solid fa-trash"></i>
                                    <span wire:loading.remove wire:target="deleteMissingMediaFiles">Xóa {{ $missingMediaAudit['missing_count'] }} record lỗi</span>
                                    <span wire:loading wire:target="deleteMissingMediaFiles">Đang xóa...</span>
                                </button>
                            </div>

                            <div class="overflow-hidden rounded-3xl border border-zinc-200 dark:border-zinc-700">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                                        <thead class="bg-zinc-50 dark:bg-zinc-800/70">
                                            <tr class="text-left text-xs uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">
                                                <th class="px-4 py-3 font-semibold">Media</th>
                                                <th class="px-4 py-3 font-semibold">Đối tượng</th>
                                                <th class="px-4 py-3 font-semibold">Đường dẫn lỗi</th>
                                                <th class="px-4 py-3 font-semibold">Kích thước</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                                            @foreach ($missingMediaAudit['items'] as $missingItem)
                                                <tr wire:key="missing-media-{{ $missingItem['id'] }}">
                                                    <td class="px-4 py-4 align-top">
                                                        <p class="font-semibold text-zinc-900 dark:text-white">{{ $missingItem['name'] }}</p>
                                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $missingItem['file_name'] }}</p>
                                                        <p class="mt-1 text-xs uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ $missingItem['collection_name'] }}</p>
                                                    </td>
                                                    <td class="px-4 py-4 align-top">
                                                        <p class="text-zinc-700 dark:text-zinc-200">{{ $missingItem['model_label'] }}</p>
                                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $missingItem['created_at'] }}</p>
                                                    </td>
                                                    <td class="px-4 py-4 align-top">
                                                        <p class="font-mono text-xs text-zinc-700 dark:text-zinc-200">{{ $missingItem['disk'] }}/{{ $missingItem['path'] }}</p>
                                                    </td>
                                                    <td class="px-4 py-4 align-top text-zinc-700 dark:text-zinc-200">{{ $missingItem['size'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                @if ($missingMediaAudit['items_truncated'])
                                    <div class="border-t border-zinc-200 bg-zinc-50 px-4 py-3 text-xs text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800/70 dark:text-zinc-400">
                                        Đang hiển thị 12 record đầu tiên. Nút xóa vẫn áp dụng cho toàn bộ record lỗi trong kết quả quét hiện tại.
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="rounded-3xl border border-dashed border-emerald-300 px-6 py-8 text-center text-sm text-emerald-700 dark:border-emerald-500/40 dark:text-emerald-300">
                                Không phát hiện record media nào bị mất file gốc trong phạm vi đang quét.
                            </div>
                        @endif
                    </div>
                @else
                    <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        Chưa chạy quét file gốc. Bấm nút “Quét file gốc” để thống kê record media bị thất lạc file vật lý.
                    </div>
                @endif
            </section>

            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Metadata ảnh</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Chọn một ảnh ở cột bên phải để sửa tên media và alt text mặc định.</p>
                </div>

                @if ($selectedMedia && $selectedMediaPayload)
                    <div class="space-y-5">
                        <div class="overflow-hidden rounded-[1.75rem] border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/70">
                            <img src="{{ $selectedMediaPayload['url'] }}" alt="{{ $selectedMediaPayload['alt'] ?: $selectedMediaPayload['name'] }}" class="h-72 w-full object-cover">
                        </div>

                        <div class="rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $selectedMediaPayload['model_label'] }}</p>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $selectedMediaPayload['collection_name'] }} · {{ $selectedMediaPayload['size'] }} · {{ $selectedMediaPayload['created_at'] }}</p>
                        </div>

                        <form wire:submit="saveMetadata" class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tên media</label>
                                <input type="text" wire:model.defer="editForm.name" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt text</label>
                                <input type="text" wire:model.defer="editForm.alt" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            </div>

                            <div class="flex flex-wrap gap-3">
                                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Lưu metadata
                                </button>

                                <button type="button" wire:click="deleteMedia({{ $selectedMedia->id }})" wire:confirm="Bạn có chắc chắn muốn xóa ảnh này khỏi thư viện media? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-5 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                                    <i class="fa-solid fa-trash"></i>
                                    Xóa ảnh
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        Chưa có ảnh nào được chọn. Bấm vào một thẻ ảnh ở bên phải để sửa metadata.
                    </div>
                @endif
            </section>
        </div>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-5 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Tất cả hình ảnh</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Hiển thị toàn bộ ảnh từ Spatie Media Library, bao gồm cover, slider và thư viện chung.</p>
                </div>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $mediaItems->total() }} ảnh</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($mediaItems as $item)
                    <button type="button" wire:key="media-item-{{ $item->id }}" wire:click="selectMedia({{ $item->id }})" class="overflow-hidden rounded-[1.75rem] border text-left transition {{ $selectedMediaId === $item->id ? 'border-red-500 shadow-lg shadow-red-500/10' : 'border-zinc-200 hover:border-red-300 dark:border-zinc-700 dark:hover:border-red-500/40' }}">
                        <div class="aspect-[4/3] overflow-hidden bg-zinc-100 dark:bg-zinc-800">
                            <img src="{{ $item->getUrl() }}" alt="{{ data_get($item->custom_properties, 'alt', $item->name) }}" class="h-full w-full object-cover transition duration-300 hover:scale-[1.02]">
                        </div>
                        <div class="space-y-2 bg-white p-4 dark:bg-zinc-900">
                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $item->name }}</p>
                            <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ class_basename($item->model_type) }} · {{ $item->collection_name }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ \Illuminate\Support\Number::fileSize($item->size) }}</p>
                        </div>
                    </button>
                @empty
                    <div class="md:col-span-2 xl:col-span-3 rounded-3xl border border-dashed border-zinc-300 px-6 py-12 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        Chưa có ảnh nào trong thư viện theo bộ lọc hiện tại.
                    </div>
                @endforelse
            </div>

            <div class="mt-6">
                {{ $mediaItems->links() }}
            </div>
        </section>
    </div>
</div>
