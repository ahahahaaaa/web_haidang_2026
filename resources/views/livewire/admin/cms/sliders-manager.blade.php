<div class="space-y-4">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $selectedSliderId ? 'Biên tập slider' : 'Tạo slider' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Trang detail riêng cho cấu hình slider và danh sách item, không trộn với index nữa.</p>
        </div>

        <a href="{{ route('admin.sliders') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
            <i class="fa-solid fa-arrow-left"></i>
            Về danh sách
        </a>
    </div>

    <x-admin.form-feedback />

    <div class="grid gap-4 xl:grid-cols-[0.72fr_1.28fr]">
        <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Thông tin slider</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Banner-location giúp landing page hero hoặc gallery tìm đúng slider cần render.</p>
            </div>

            <form wire:submit="saveSlider" class="space-y-4 rounded-3xl bg-zinc-50 p-4 dark:bg-zinc-800/70" data-admin-feedback-form data-admin-loading-text="Đang lưu cấu hình slider...">
                @php
                    $currentSliderLocation = trim((string) ($sliderForm['location'] ?? ''));
                    $currentSliderLocationIsCustom = $currentSliderLocation !== ''
                        && ! array_key_exists($currentSliderLocation, $sliderLocationOptions ?? []);
                @endphp
                <input type="text" wire:model.defer="sliderForm.name" placeholder="Tên slider" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <div class="space-y-2">
                    <select wire:model.live="sliderForm.location" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">Không gắn banner-location</option>
                        @if ($currentSliderLocationIsCustom)
                            <option value="{{ $currentSliderLocation }}">Tùy chỉnh hiện tại: {{ $currentSliderLocation }}</option>
                        @endif
                        @foreach (($sliderLocationOptions ?? []) as $locationValue => $locationOption)
                            <option value="{{ $locationValue }}">{{ $locationOption['label'] }} - {{ $locationValue }}</option>
                        @endforeach
                    </select>
                    @if ($currentSliderLocation === \App\Support\SliderLocations::HOME_POPUP)
                        <p class="rounded-2xl bg-orange-50 px-4 py-3 text-sm text-orange-700 dark:bg-orange-500/10 dark:text-orange-200">
                            Popup Trang chủ sẽ tự hiển thị sau khi trang chủ tải xong 2 giây. Khi khách đóng popup, trình duyệt sẽ ẩn lại trong 1 ngày.
                        </p>
                    @else
                        <p class="text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                            Chọn banner-location để frontsite biết slider này dùng cho hero, gallery hoặc popup trang chủ.
                        </p>
                    @endif
                </div>
                <x-admin.quill-editor wire:key="slider-description-{{ $selectedSliderId ?? 'new' }}-{{ md5((string) ($sliderForm['description'] ?? '')) }}" model="sliderForm.description" :value="$sliderForm['description'] ?? ''" rows="3" placeholder="Mô tả" />
                <input type="number" wire:model.defer="sliderForm.autoplay_delay" placeholder="Autoplay delay (ms)" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                    <input type="checkbox" wire:model.defer="sliderForm.is_active" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                    Đang kích hoạt
                </label>
                <div class="flex gap-3">
                    <button type="submit" class="rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">Lưu slider</button>
                    @if ($selectedSliderId)
                        <button type="button" wire:click="deleteSlider({{ $selectedSliderId }})" wire:confirm="Bạn có chắc chắn muốn xóa slider này? Toàn bộ item của slider cũng sẽ bị xóa." class="rounded-2xl border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 dark:border-red-500/30 dark:text-red-300">Xóa slider</button>
                    @endif
                </div>
            </form>
        </section>

        <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            @if ($selectedSlider)
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Slider items</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý nội dung và hiệu ứng cho slider `{{ $selectedSlider->name }}`.</p>
                </div>

                <div class="mb-6 space-y-3">
                    @foreach ($selectedSlider->items as $item)
                        <div wire:key="slider-item-{{ $item->id }}" class="flex flex-col gap-3 rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/70 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ $item->title ?: 'Không có tiêu đề' }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $item->effect }}
                                    {{ $item->video_url ? ' · Video' : ' · Image' }}
                                    {{ $item->show_overlay ? ' · Overlay bật' : ' · Overlay tắt' }}
                                    {{ $item->show_inner_media ? ' · Hình mini bật' : ' · Hình mini tắt' }}
                                    {{ $item->getFirstMedia('mobile_image') ? ' · Có ảnh mobile' : '' }}
                                    {{ $item->getFirstMedia('inner_image') ? ' · Có hình mini riêng' : '' }}
                                    {{ $item->image_link ? ' · '.$item->image_link : '' }}
                                </p>
                            </div>

                            <div class="flex gap-2">
                                <button type="button" wire:click="editItem({{ $item->id }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">Sửa</button>
                                <button type="button" wire:click="deleteItem({{ $item->id }})" wire:confirm="Bạn có chắc chắn muốn xóa slider item này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">Xóa</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form wire:submit="saveItem" class="space-y-5 rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70" data-admin-feedback-form data-admin-loading-text="Đang lưu slider item...">
                    <div class="grid gap-4 md:grid-cols-2">
                        <input type="text" wire:model.defer="itemForm.title" placeholder="Tiêu đề" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="itemForm.subtitle" placeholder="Sub title" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </div>

                    <x-admin.quill-editor wire:key="slider-item-description-{{ $editingItemId ?? 'new' }}-{{ md5((string) ($itemForm['description'] ?? '')) }}" model="itemForm.description" :value="$itemForm['description'] ?? ''" rows="3" placeholder="Mô tả" />

                    <div class="rounded-3xl border border-dashed border-zinc-300 bg-white/70 p-4 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900/50 dark:text-zinc-300">
                        Ảnh desktop/chính dùng cho nền slider trên desktop và làm fallback chung. Ảnh mobile chỉ thay nền ở màn hình nhỏ. Hình mini bên trong dùng cho panel media bên phải; nếu để trống, frontsite sẽ tự lấy ảnh hoặc thumbnail chính khi panel đang bật.
                    </div>

                    <div class="grid gap-5 xl:grid-cols-3">
                        <div class="space-y-4">
                            <x-admin.image-dropzone
                                label="Ảnh desktop / chính"
                                model="itemImageUpload"
                                :preview="$itemImageUpload ? $itemImageUpload->temporaryUrl() : ($selectedLibraryMedia?->getUrl() ?: $editingItem?->getFirstMediaUrl('image'))"
                                hint="Ảnh nền chính cho desktop và fallback."
                            />

                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    data-admin-media-picker-trigger
                                    data-livewire-id="{{ $this->getId() }}"
                                    data-pick-method="selectLibraryMedia"
                                    data-button-label="Chọn ảnh desktop cho slider"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                                >
                                    <i class="fa-regular fa-images"></i>
                                    Chọn từ Media popup
                                </button>

                                @if ($selectedLibraryMedia)
                                    <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Đã chọn ảnh thư viện: {{ $selectedLibraryMedia->name }}
                                    </div>

                                    <button type="button" wire:click="clearLibraryMediaSelection" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        Bỏ chọn ảnh thư viện
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-4">
                            <x-admin.image-dropzone
                                label="Ảnh mobile"
                                model="itemMobileImageUpload"
                                :preview="$itemMobileImageUpload ? $itemMobileImageUpload->temporaryUrl() : ($selectedMobileLibraryMedia?->getUrl() ?: $editingItem?->getFirstMediaUrl('mobile_image'))"
                                hint="Ảnh nền riêng cho mobile. Có thể để trống để dùng ảnh desktop."
                            />

                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    data-admin-media-picker-trigger
                                    data-livewire-id="{{ $this->getId() }}"
                                    data-pick-method="selectLibraryMediaForUpload"
                                    data-pick-target="itemMobileImageUpload"
                                    data-pick-alt-target="itemForm.image_alt"
                                    data-button-label="Chọn ảnh mobile cho slider"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                                >
                                    <i class="fa-regular fa-images"></i>
                                    Chọn từ Media popup
                                </button>

                                @if ($selectedMobileLibraryMedia)
                                    <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Ảnh mobile: {{ $selectedMobileLibraryMedia->name }}
                                    </div>

                                    <button type="button" wire:click="clearLibraryMediaSelectionForUpload('itemMobileImageUpload')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        Bỏ chọn
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-4">
                            <x-admin.image-dropzone
                                label="Hình mini bên trong"
                                model="itemInnerImageUpload"
                                :preview="$itemInnerImageUpload ? $itemInnerImageUpload->temporaryUrl() : ($selectedInnerLibraryMedia?->getUrl() ?: $editingItem?->getFirstMediaUrl('inner_image'))"
                                hint="Ảnh cho panel media bên phải của hero. Có thể để trống để fallback."
                            />

                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    data-admin-media-picker-trigger
                                    data-livewire-id="{{ $this->getId() }}"
                                    data-pick-method="selectLibraryMediaForUpload"
                                    data-pick-target="itemInnerImageUpload"
                                    data-pick-alt-target="itemForm.image_alt"
                                    data-button-label="Chọn hình mini cho slider"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                                >
                                    <i class="fa-regular fa-images"></i>
                                    Chọn từ Media popup
                                </button>

                                @if ($selectedInnerLibraryMedia)
                                    <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Hình mini: {{ $selectedInnerLibraryMedia->name }}
                                    </div>

                                    <button type="button" wire:click="clearLibraryMediaSelectionForUpload('itemInnerImageUpload')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        Bỏ chọn
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <input type="text" wire:model.defer="itemForm.image_alt" placeholder="Alt text" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="itemForm.image_link" placeholder="Link khi click slide" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="itemForm.video_url" placeholder="Video URL (YouTube hoặc MP4, nếu có)" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2">
                        <select wire:model.defer="itemForm.effect" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            @foreach ($effectGroups as $group)
                                <optgroup label="{{ $group['label'] }}">
                                    @foreach ($group['options'] as $effect)
                                        <option value="{{ $effect['value'] }}">{{ $effect['label'] }} · {{ $effect['value'] }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <input type="number" wire:model.defer="itemForm.order" placeholder="Thứ tự" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="itemForm.primary_label" placeholder="Label nút chính" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="itemForm.primary_url" placeholder="URL nút chính" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="itemForm.secondary_label" placeholder="Label nút phụ" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="itemForm.secondary_url" placeholder="URL nút phụ" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </div>

                    <div class="grid gap-3 md:grid-cols-3">
                        <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                            <input type="checkbox" wire:model.defer="itemForm.is_active" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                            Đang hiển thị
                        </label>
                        <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                            <input type="checkbox" wire:model.defer="itemForm.show_overlay" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                            Bật overlay trên ảnh/video
                        </label>
                        <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                            <input type="checkbox" wire:model.defer="itemForm.show_inner_media" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                            Bật hình mini bên trong
                        </label>
                    </div>

                    <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600">
                        <i class="fa-solid fa-images"></i>
                        {{ $editingItemId ? 'Cập nhật item' : 'Thêm slider item' }}
                    </button>
                </form>
            @else
                <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Tạo hoặc chọn một slider để bắt đầu.
                </div>
            @endif
        </section>
    </div>
</div>
