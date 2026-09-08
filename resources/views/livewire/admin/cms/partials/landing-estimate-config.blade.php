<section class="space-y-6 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="space-y-2">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Cấu hình landing dự toán</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý thông số đầu vào, 3 cấp dự toán, text kết quả và popup xác nhận thông tin khách hàng.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.estimate_config.hero_slider.eyebrow" placeholder="Eyebrow block hero slider" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.estimate_config.hero_slider.title" placeholder="Tiêu đề quản trị hero slider" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.estimate_config.hero_slider.description" placeholder="Mô tả block hero slider trong admin" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <input type="text" wire:model.defer="form.estimate_config.inputs.eyebrow" placeholder="Eyebrow section đầu vào" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.estimate_config.inputs.title" placeholder="Tiêu đề section đầu vào" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.estimate_config.inputs.description" placeholder="Mô tả section đầu vào" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
    </div>

    <div class="space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h4 class="text-base font-semibold text-zinc-900 dark:text-white">Hero slider trang dự toán</h4>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý ảnh nền, text, CTA và hiệu ứng xuất hiện cho block đầu trang.</p>
            </div>

            @if (count($form['estimate_config']['hero_slider']['slides'] ?? []) < 5)
                <button type="button" wire:click="addEstimateHeroSlide" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                    Thêm slide
                </button>
            @endif
        </div>

        @foreach (($form['estimate_config']['hero_slider']['slides'] ?? []) as $index => $slide)
            @php
                $heroCollection = \App\Support\EstimatePageContent::heroSlideCollection($slide['uuid']);
                $heroUpload = $estimateHeroUploads[$index] ?? null;
                $heroPreview = $heroUpload ? $heroUpload->temporaryUrl() : (($selectedEstimateHeroLibraryMedia[$slide['uuid']] ?? null)?->getUrl() ?: $selectedPage?->getFirstMediaUrl($heroCollection));
            @endphp

            <div wire:key="estimate-hero-slide-{{ $slide['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Slide hero {{ $loop->iteration }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Dùng cho phần đầu trang dự toán. Gợi ý ảnh ngang 1920x1080.</p>
                    </div>

                    @if (count($form['estimate_config']['hero_slider']['slides'] ?? []) > 1)
                        <button type="button" wire:click="removeEstimateHeroSlide({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa slide
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" wire:model.defer="form.estimate_config.hero_slider.slides.{{ $index }}.eyebrow" placeholder="Eyebrow slide" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.estimate_config.hero_slider.slides.{{ $index }}.image_alt" placeholder="Alt ảnh" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <x-admin.quill-editor
                        wire:key="estimate-hero-title-{{ $selectedId ?? 'new' }}-{{ $slide['uuid'] }}-{{ md5((string) ($slide['title'] ?? '')) }}"
                        :model="'form.estimate_config.hero_slider.slides.'.$index.'.title'"
                        :value="$slide['title'] ?? ''"
                        mode="rich"
                        rows="3"
                        placeholder="Tiêu đề slide"
                        class="md:col-span-2"
                    />
                    <x-admin.quill-editor
                        wire:key="estimate-hero-description-{{ $selectedId ?? 'new' }}-{{ $slide['uuid'] }}-{{ md5((string) ($slide['description'] ?? '')) }}"
                        :model="'form.estimate_config.hero_slider.slides.'.$index.'.description'"
                        :value="$slide['description'] ?? ''"
                        mode="rich"
                        rows="4"
                        placeholder="Mô tả slide"
                        class="md:col-span-2"
                    />
                    <input type="text" wire:model.defer="form.estimate_config.hero_slider.slides.{{ $index }}.primary_label" placeholder="Label CTA chính" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.estimate_config.hero_slider.slides.{{ $index }}.primary_url" placeholder="URL CTA chính" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.estimate_config.hero_slider.slides.{{ $index }}.secondary_label" placeholder="Label CTA phụ" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.estimate_config.hero_slider.slides.{{ $index }}.secondary_url" placeholder="URL CTA phụ" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <select wire:model.defer="form.estimate_config.hero_slider.slides.{{ $index }}.text_effect" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">
                        @foreach (\App\Support\EstimatePageContent::heroEffectOptions() as $effect)
                            <option value="{{ $effect['value'] }}">{{ $effect['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mt-4">
                    <x-admin.image-dropzone label="Ảnh background slide" :model="'estimateHeroUploads.'.$index" :preview="$heroPreview" hint="Upload ảnh mới hoặc chọn ảnh có sẵn cho từng slide." />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        data-admin-media-picker-trigger
                        data-livewire-id="{{ $this->getId() }}"
                        data-pick-method="selectEstimateHeroLibraryMedia"
                        data-pick-context="{{ $slide['uuid'] }}"
                        data-button-label="Chọn ảnh này cho hero slider dự toán"
                        class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                    >
                        <i class="fa-regular fa-images"></i>
                        Chọn từ Media popup
                    </button>

                    @if (isset($selectedEstimateHeroLibraryMedia[$slide['uuid']]))
                        <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check"></i>
                            Đã chọn ảnh thư viện cho slide {{ $loop->iteration }}
                        </div>

                        <button type="button" wire:click="clearEstimateHeroLibraryMediaSelection('{{ $slide['uuid'] }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                            <i class="fa-solid fa-rotate-left"></i>
                            Bỏ chọn ảnh thư viện
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h4 class="text-base font-semibold text-zinc-900 dark:text-white">Thông số đầu vào</h4>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Có thể thêm hoặc xóa field để phù hợp từng chiến dịch dự toán.</p>
            </div>

            <button type="button" wire:click="addEstimateInputField" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                Thêm thông số
            </button>
        </div>

        @foreach (($form['estimate_config']['inputs']['fields'] ?? []) as $index => $field)
            <div wire:key="estimate-input-field-{{ $field['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Thông số {{ $loop->iteration }}</p>

                    @if (count($form['estimate_config']['inputs']['fields'] ?? []) > 1)
                        <button type="button" wire:click="removeEstimateInputField({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa
                        </button>
                    @endif
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" wire:model.defer="form.estimate_config.inputs.fields.{{ $index }}.label" placeholder="Label hiển thị" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.estimate_config.inputs.fields.{{ $index }}.slug" placeholder="Slug field" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <select wire:model.defer="form.estimate_config.inputs.fields.{{ $index }}.type" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="select">Select</option>
                        <option value="textarea">Textarea</option>
                    </select>
                    <input type="text" wire:model.defer="form.estimate_config.inputs.fields.{{ $index }}.placeholder" placeholder="Placeholder" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <textarea rows="3" wire:model.defer="form.estimate_config.inputs.fields.{{ $index }}.help" placeholder="Mô tả/help text" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2"></textarea>
                    <textarea rows="4" wire:model.defer="form.estimate_config.inputs.fields.{{ $index }}.options_text" placeholder="Options, mỗi dòng 1 giá trị. Có thể dùng Label|value" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2"></textarea>
                </div>

                <label class="mt-4 inline-flex items-center gap-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input type="checkbox" wire:model.defer="form.estimate_config.inputs.fields.{{ $index }}.required" value="1" class="size-4 rounded border-zinc-300 text-red-600 focus:ring-red-500">
                    Bắt buộc nhập
                </label>
            </div>
        @endforeach
    </div>

    <div class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
            <input type="text" wire:model.defer="form.estimate_config.tiers.eyebrow" placeholder="Eyebrow section gói dự toán" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <input type="text" wire:model.defer="form.estimate_config.tiers.title" placeholder="Tiêu đề section gói dự toán" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <textarea rows="3" wire:model.defer="form.estimate_config.tiers.description" placeholder="Mô tả section gói dự toán" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        </div>

        @foreach (($form['estimate_config']['tiers']['items'] ?? []) as $index => $tier)
            <div wire:key="estimate-tier-{{ $tier['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="mb-4 text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Cấp dự toán {{ $loop->iteration }}</p>

                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.name" placeholder="Tên cấp dự toán" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.code" placeholder="Code" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.badge" placeholder="Badge" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.button_label" placeholder="Label nút CTA" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <textarea rows="3" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.description" placeholder="Mô tả cấp dự toán" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2"></textarea>
                </div>

                <label class="mt-4 inline-flex items-center gap-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input type="checkbox" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.requires_key" value="1" class="size-4 rounded border-zinc-300 text-red-600 focus:ring-red-500">
                    Yêu cầu key để gửi yêu cầu dự toán
                </label>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <input type="text" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.key_label" placeholder="Label key" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.result_title" placeholder="Tiêu đề preview kết quả" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <textarea rows="3" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.key_help" placeholder="Mô tả key/help text" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2"></textarea>
                    <textarea rows="3" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.result_summary" placeholder="Mô tả preview kết quả" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2"></textarea>
                    <textarea rows="4" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.result_bullets_text" placeholder="Các bullet kết quả, mỗi dòng 1 ý" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2"></textarea>
                    <textarea rows="3" wire:model.defer="form.estimate_config.tiers.items.{{ $index }}.delivery_text" placeholder="Text kênh nhận kết quả" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2"></textarea>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.estimate_config.result.eyebrow" placeholder="Eyebrow section kết quả" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.estimate_config.result.title" placeholder="Tiêu đề section kết quả" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.estimate_config.result.description" placeholder="Mô tả section kết quả" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <input type="text" wire:model.defer="form.estimate_config.result.empty_state_title" placeholder="Tiêu đề empty state" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.estimate_config.popup.button_label" placeholder="Label nút popup" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.estimate_config.result.empty_state_description" placeholder="Mô tả empty state" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.estimate_config.popup.eyebrow" placeholder="Eyebrow popup" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.estimate_config.popup.title" placeholder="Tiêu đề popup" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.estimate_config.popup.description" placeholder="Mô tả popup" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <input type="text" wire:model.defer="form.estimate_config.popup.success_message" placeholder="Thông báo thành công" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.estimate_config.popup.privacy_note" placeholder="Ghi chú bảo mật/collection" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
    </div>

    <div class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <h4 class="text-base font-semibold text-zinc-900 dark:text-white">Kênh nhận kết quả</h4>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Cho phép lưu collection nội bộ, gửi email hoặc dùng đồng thời cả hai.</p>

        <div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center">
            <label class="inline-flex items-center gap-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                <input type="checkbox" wire:model.defer="form.estimate_config.delivery.store_collection" value="1" class="size-4 rounded border-zinc-300 text-red-600 focus:ring-red-500">
                Lưu vào collection form nội bộ
            </label>

            <label class="inline-flex items-center gap-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                <input type="checkbox" wire:model.defer="form.estimate_config.delivery.send_email" value="1" class="size-4 rounded border-zinc-300 text-red-600 focus:ring-red-500">
                Gửi email cho bộ phận phụ trách
            </label>

            <label class="inline-flex items-center gap-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                <input type="checkbox" wire:model.defer="form.estimate_config.delivery.send_customer_email" value="1" class="size-4 rounded border-zinc-300 text-red-600 focus:ring-red-500">
                Gửi phiếu tiếp nhận cho khách hàng
            </label>
        </div>

        <textarea rows="3" wire:model.defer="form.estimate_config.delivery.notice" placeholder="Text thông báo kênh nhận kết quả" class="mt-4 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
    </div>
</section>
