@switch($sectionKey)
    @case('search')
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Placeholder</label>
                <input type="text" wire:model.defer="form.home_config.search.placeholder" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label nút</label>
                <input type="text" wire:model.defer="form.home_config.search.button_label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>
        </div>
        @break

    @case('destination_slider')
        <div class="grid gap-4">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
                <input type="text" wire:model.defer="form.home_config.destination_slider.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả block</label>
                <textarea rows="4" wire:model.defer="form.home_config.destination_slider.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label CTA trên card</label>
                <input type="text" wire:model.defer="form.home_config.destination_slider.card_cta_label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Điểm đến ưu tiên</label>
                <select
                    wire:model.defer="form.home_config.featured_destination_slugs"
                    multiple
                    size="5"
                    data-admin-multiselect
                    data-admin-multiselect-placeholder="Chọn tối đa 8 điểm đến cho slider"
                    data-admin-multiselect-search="true"
                    class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                >
                    @foreach ($destinations as $destination)
                        <option value="{{ $destination->slug }}">{{ $destination->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Nếu để trống, frontsite sẽ tự lấy các điểm đến nổi bật đang có tour hoạt động.</p>
            </div>
        </div>
        @break

    @case('topic_rail')
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Eyebrow</label>
                <input type="text" wire:model.defer="form.home_config.topic_rail.eyebrow" placeholder="Ví dụ: Khởi đầu từ nhu cầu" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
                <input type="text" wire:model.defer="form.home_config.topic_rail.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>
            <div class="space-y-2 md:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả block</label>
                <textarea rows="4" wire:model.defer="form.home_config.topic_rail.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
            </div>
            <p class="text-xs leading-6 text-zinc-500 dark:text-zinc-400 md:col-span-2">Dữ liệu card vẫn lấy live từ các chủ đề đang publish và có tour hoạt động.</p>
        </div>
        @break

    @case('featured_tours')
        <div class="grid gap-4 md:grid-cols-3">
            <div class="space-y-2 md:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Danh mục ưu tiên cho tour nổi bật</label>
                <select wire:model.defer="form.home_config.featured_tour_category_slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Tự dò Tour Nổi Bật / fallback sang is_featured</option>
                    @foreach ($tourCategories as $category)
                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số tour mỗi tab</label>
                <input type="number" min="1" max="12" wire:model.defer="form.home_config.featured_tour_limit" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Tối đa {{ \App\Support\FrontsiteCardGrid::MAX_ITEMS }} card cho mỗi tab.</p>
            </div>

            <div class="space-y-2 md:col-span-3">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label CTA của block</label>
                <input type="text" wire:model.defer="form.home_config.featured_tours.cta_label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-3">
            @foreach (['international' => 'Tour nước ngoài', 'domestic' => 'Tour trong nước', 'group' => 'Tour đoàn'] as $scopeKey => $scopeLabel)
                <div class="rounded-3xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-teal-600 dark:text-teal-300">{{ $scopeLabel }}</p>
                    <div class="mt-4 grid gap-3">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nhãn tab</label>
                            <input type="text" wire:model.defer="form.home_config.featured_tours.tabs.{{ $scopeKey }}.label" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề khi tab active</label>
                            <input type="text" wire:model.defer="form.home_config.featured_tours.tabs.{{ $scopeKey }}.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả khi tab active</label>
                            <textarea rows="5" wire:model.defer="form.home_config.featured_tours.tabs.{{ $scopeKey }}.description" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @break

    @case('services')
        <div class="grid gap-4 xl:grid-cols-2">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
                <input type="text" wire:model.defer="form.home_config.services.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label CTA</label>
                <input type="text" wire:model.defer="form.home_config.services.cta_label" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            </div>
            <div class="space-y-2 xl:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả block</label>
                <textarea rows="4" wire:model.defer="form.home_config.services.description" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
            </div>
            <div class="space-y-2 xl:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">URL CTA</label>
                <input type="text" wire:model.defer="form.home_config.services.cta_url" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            </div>
            <div class="space-y-2 xl:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Dịch vụ ưu tiên</label>
                <select
                    wire:model.defer="form.home_config.featured_service_slugs"
                    multiple
                    size="5"
                    data-admin-multiselect
                    data-admin-multiselect-placeholder="Chọn tối đa 4 dịch vụ"
                    data-admin-multiselect-search="true"
                    class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                >
                    @foreach ($serviceOptions as $service)
                        <option value="{{ $service->slug }}">{{ $service->title }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Nếu để trống, homepage sẽ tự lấy các dịch vụ đang được đánh dấu nổi bật.</p>
            </div>
        </div>
        @break

    @case('trust')
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề section</label>
                <input type="text" wire:model.defer="form.home_config.trust.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                @error('form.home_config.trust.title') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-2 md:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả ngắn</label>
                <textarea rows="3" wire:model.defer="form.home_config.trust.description" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                @error('form.home_config.trust.description') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-3">
            @foreach (($form['home_config']['trust']['cards'] ?? []) as $index => $card)
                <div wire:key="landing-home-trust-card-mixed-{{ $card['uuid'] ?? $index }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-teal-600 dark:text-teal-300">Lý do {{ $loop->iteration }}</p>

                        @if (count($form['home_config']['trust']['cards'] ?? []) > 1)
                            <button type="button" wire:click="removeHomeTrustCard({{ $index }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">Xóa</button>
                        @endif
                    </div>

                    <div class="grid gap-3">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Icon Font Awesome</label>
                            <input type="text" wire:model.defer="form.home_config.trust.cards.{{ $index }}.icon" placeholder="fa-solid fa-route" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error("form.home_config.trust.cards.$index.icon") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nhãn nổi bật</label>
                            <input type="text" wire:model.defer="form.home_config.trust.cards.{{ $index }}.highlight" placeholder="Ví dụ: Một đầu mối xử lý" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error("form.home_config.trust.cards.$index.highlight") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề card</label>
                            <input type="text" wire:model.defer="form.home_config.trust.cards.{{ $index }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error("form.home_config.trust.cards.$index.title") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Bằng chứng ngắn</label>
                            <textarea rows="4" wire:model.defer="form.home_config.trust.cards.{{ $index }}.text" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                            @error("form.home_config.trust.cards.$index.text") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex flex-wrap justify-end">
            <button type="button" wire:click="addHomeTrustCard" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:text-zinc-200">Thêm lý do</button>
        </div>
        @break

    @case('process')
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
                <input type="text" wire:model.defer="form.home_config.process.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            </div>
            <div class="space-y-2 md:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả block</label>
                <textarea rows="4" wire:model.defer="form.home_config.process.description" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
            </div>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-2">
            @foreach (($form['home_config']['process']['cards'] ?? []) as $index => $card)
                @php
                    $processImageUrl = trim((string) ($card['image_url'] ?? ''));
                @endphp
                <div wire:key="landing-home-process-card-mixed-{{ $card['uuid'] ?? $index }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-teal-600 dark:text-teal-300">Bước {{ $loop->iteration }}</p>

                        @if (count($form['home_config']['process']['cards'] ?? []) > 1)
                            <button type="button" wire:click="removeHomeProcessCard({{ $index }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">Xóa</button>
                        @endif
                    </div>

                    <div class="grid gap-3">
                        <div class="space-y-3">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Ảnh bước</label>

                            <div class="overflow-hidden rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950/50">
                                @if ($processImageUrl !== '')
                                    <img src="{{ $processImageUrl }}" alt="{{ $card['image_alt'] ?? $card['title'] ?? 'Ảnh bước quy trình' }}" class="h-44 w-full object-cover">
                                @else
                                    <div class="flex h-44 items-center justify-center px-4 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có ảnh cho bước này.</div>
                                @endif
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <button
                                    type="button"
                                    data-admin-media-picker-trigger
                                    data-livewire-id="{{ $this->getId() }}"
                                    data-pick-method="selectHomeProcessLibraryMedia"
                                    data-pick-context="{{ $card['uuid'] }}"
                                    data-button-label="Chọn ảnh cho bước này"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-teal-500/40 dark:hover:text-teal-300"
                                >
                                    <i class="fa-regular fa-images"></i>
                                    Chọn từ Media popup
                                </button>

                                @if ($processImageUrl !== '')
                                    <button type="button" wire:click="clearHomeProcessCardImage('{{ $card['uuid'] }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                        <i class="fa-solid fa-rotate-left"></i>
                                        Xóa ảnh bước
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề bước</label>
                            <input type="text" wire:model.defer="form.home_config.process.cards.{{ $index }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error("form.home_config.process.cards.$index.title") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
                            <textarea rows="5" wire:model.defer="form.home_config.process.cards.{{ $index }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                            @error("form.home_config.process.cards.$index.description") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt ảnh</label>
                            <input type="text" wire:model.defer="form.home_config.process.cards.{{ $index }}.image_alt" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error("form.home_config.process.cards.$index.image_alt") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4 flex flex-wrap justify-end">
            <button type="button" wire:click="addHomeProcessCard" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:text-zinc-200">Thêm bước</button>
        </div>
        @break

    @case('blog_preview')
        <div class="grid gap-4 xl:grid-cols-2">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
                <input type="text" wire:model.defer="form.home_config.blog_preview.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label CTA</label>
                <input type="text" wire:model.defer="form.home_config.blog_preview.cta_label" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            </div>
            <div class="space-y-2 xl:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả block</label>
                <textarea rows="4" wire:model.defer="form.home_config.blog_preview.description" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
            </div>
            <div class="space-y-2 xl:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">URL CTA</label>
                <input type="text" wire:model.defer="form.home_config.blog_preview.cta_url" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số BlogCard</label>
                <input type="number" min="1" max="12" wire:model.defer="form.home_config.featured_blog_limit" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Tối đa {{ \App\Support\FrontsiteCardGrid::MAX_ITEMS }} card cho widget blog.</p>
            </div>
            <div class="space-y-2 xl:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Bài viết ưu tiên</label>
                <select
                    wire:model.defer="form.home_config.featured_blog_slugs"
                    multiple
                    size="6"
                    data-admin-multiselect
                    data-admin-multiselect-placeholder="Chọn các bài viết muốn ưu tiên hiển thị"
                    data-admin-multiselect-search="true"
                    class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                >
                    @foreach ($blogPostOptions as $post)
                        <option value="{{ $post->slug }}">{{ $post->title }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Nếu để trống, homepage sẽ tự lấy các bài blog publish mới nhất.</p>
            </div>
        </div>
        @break

    @default
        <div class="rounded-2xl border border-dashed border-zinc-300 bg-zinc-50 px-4 py-4 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950/50 dark:text-zinc-400">
            Section này hiện chỉ có bật/tắt và vị trí render trong stack homepage.
        </div>
@endswitch
