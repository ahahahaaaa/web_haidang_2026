<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $selectedId ? 'Biên tập tour' : 'Tạo tour' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý metadata, gallery và danh sách ngày khởi hành có giá/tiêu chuẩn ngay trong trang detail.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.tours') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <i class="fa-solid fa-arrow-left"></i>
                Về danh sách
            </a>
            @if ($editingTour)
                <a href="{{ route('admin.tours.reviews.index', $editingTour) }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-amber-200 px-4 py-3 text-sm font-medium text-amber-700 dark:border-amber-500/30 dark:text-amber-300">
                    <i class="fa-solid fa-star"></i>
                    Quản lý đánh giá
                </a>
            @endif
            @if ($selectedId)
                <button type="button" wire:click="deleteTour({{ $selectedId }})" wire:confirm="Bạn có chắc chắn muốn xóa tour này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                    <i class="fa-solid fa-trash"></i>
                    Xóa tour
                </button>
            @endif
        </div>
    </div>

    @include('livewire.admin.cms.partials.tours-submenu')

    <x-admin.form-feedback />

    @php
        $avatarPreview = $avatarUpload
            ? $avatarUpload->temporaryUrl()
            : ($selectedLibraryAvatarMedia?->getUrl() ?: ($editingTour?->getFirstMediaUrl('cover') ?: ($form['cover_image_url'] ?? null)));
    @endphp

    <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit.prevent="saveTour" class="space-y-5 pb-32 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu tour...">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <input type="text" wire:model.defer="form.title" placeholder="Tên tour" class="md:col-span-2 xl:col-span-4 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <div class="space-y-2">
                    <input type="text" wire:model.defer="form.slug" placeholder="Slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Có thể để trống để hệ thống tự sinh slug từ tên tour.</p>
                </div>
                <select wire:model.defer="form.status" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="draft">draft</option>
                    <option value="published">published</option>
                    <option value="archived">archived</option>
                </select>
                <select wire:model.defer="form.scope" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @foreach ($scopeOptions as $scopeOption)
                        <option value="{{ $scopeOption->value }}">{{ $scopeOption->label() }}</option>
                    @endforeach
                </select>
                <input type="datetime-local" wire:model.defer="form.published_at" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-zinc-900 dark:text-white">Danh mục tour chính</label>
                    <select wire:model.defer="form.tour_category_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Chọn danh mục chính</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-zinc-900 dark:text-white">Điểm đến chính</label>
                    <select wire:model.defer="form.destination_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Chọn điểm đến chính</option>
                        @foreach ($destinations as $destination)
                            <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-zinc-900 dark:text-white">Vùng miền chính</label>
                    <select wire:model.defer="form.region_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Chọn vùng miền chính</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2 xl:col-span-2">
                    <label class="text-sm font-semibold text-zinc-900 dark:text-white">Danh mục tour bổ sung</label>
                    <select
                        wire:model.defer="form.tour_category_ids"
                        multiple
                        size="5"
                        data-admin-multiselect
                        data-admin-multiselect-placeholder="Chọn một hoặc nhiều danh mục tour"
                        data-admin-multiselect-search="true"
                        aria-invalid="{{ $errors->has('form.tour_category_ids') ? 'true' : 'false' }}"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white {{ $errors->has('form.tour_category_ids') ? 'is-invalid' : '' }}"
                    >
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2 xl:col-span-2">
                    <label class="text-sm font-semibold text-zinc-900 dark:text-white">Điểm đến bổ sung</label>
                    <select
                        wire:model.defer="form.destination_ids"
                        multiple
                        size="5"
                        data-admin-multiselect
                        data-admin-multiselect-placeholder="Chọn một hoặc nhiều điểm đến"
                        data-admin-multiselect-search="true"
                        aria-invalid="{{ $errors->has('form.destination_ids') ? 'true' : 'false' }}"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white {{ $errors->has('form.destination_ids') ? 'is-invalid' : '' }}"
                    >
                        @foreach ($destinations as $destination)
                            <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2 xl:col-span-2">
                    <label class="text-sm font-semibold text-zinc-900 dark:text-white">Vùng miền bổ sung</label>
                    <select
                        wire:model.defer="form.region_ids"
                        multiple
                        size="5"
                        data-admin-multiselect
                        data-admin-multiselect-placeholder="Chọn một hoặc nhiều vùng miền"
                        data-admin-multiselect-search="true"
                        aria-invalid="{{ $errors->has('form.region_ids') ? 'true' : 'false' }}"
                        class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white {{ $errors->has('form.region_ids') ? 'is-invalid' : '' }}"
                    >
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>

                <input type="text" wire:model.defer="form.transport" placeholder="Phương tiện chính" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.departure_location" placeholder="Điểm khởi hành mặc định" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.contact_phone" placeholder="Số điện thoại liên hệ tour" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                @if (! auth()->user()?->hasRole('sale'))
                    <select wire:model.defer="form.managed_by_user_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Chưa gán Sale phụ trách</option>
                        @foreach ($saleUsers as $saleUser)
                            <option value="{{ $saleUser->id }}">{{ $saleUser->name }}{{ $saleUser->phone ? ' - '.$saleUser->phone : '' }}</option>
                        @endforeach
                    </select>
                @else
                    <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        Sale phụ trách: {{ auth()->user()->name }}
                    </div>
                @endif
                <input type="number" wire:model.defer="form.duration_days" placeholder="Số ngày" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="number" wire:model.defer="form.duration_nights" placeholder="Số đêm" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.standard_label" placeholder="Tiêu chuẩn tour" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="number" wire:model.defer="form.base_price" placeholder="Giá gốc" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="number" wire:model.defer="form.sale_price" placeholder="Giá bán" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="number" step="0.1" wire:model.defer="form.rating_average" placeholder="Điểm tổng đánh giá ảo" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="number" wire:model.defer="form.rating_count" placeholder="Tổng lượt đánh giá ảo" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <select wire:model.defer="form.cta_mode" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="book">book</option>
                    <option value="contact">contact</option>
                </select>
                <input type="number" wire:model.defer="form.sort_order" placeholder="Thứ tự" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                <div class="md:col-span-2 xl:col-span-4 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    Tổng điểm và tổng lượt đánh giá ở đây dùng để tạo aggregate rating ảo cho SEO/schema. Các review item chi tiết được quản lý ở màn <span class="font-semibold text-zinc-900 dark:text-white">Quản lý đánh giá</span>.
                </div>

                <label class="md:col-span-2 xl:col-span-4 flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <input type="checkbox" wire:model.defer="form.is_featured" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                    Tour nổi bật
                </label>
            </div>

            <x-admin.quill-editor wire:key="tour-excerpt-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['excerpt'] ?? '')) }}" model="form.excerpt" :value="$form['excerpt'] ?? ''" rows="3" placeholder="Tóm tắt tour" />
            <x-admin.quill-editor wire:key="tour-content-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['content'] ?? '')) }}" model="form.content" :value="$form['content'] ?? ''" mode="rich" :allow-images="true" rows="12" placeholder="Nội dung chi tiết tour" />

            <x-admin.image-dropzone
                label="Avatar / cover tour"
                model="avatarUpload"
                :preview="$avatarPreview"
                hint="Upload ảnh cover mới, chọn từ Media popup hoặc dùng URL ảnh ngoài."
            />

            <div class="grid gap-4 md:grid-cols-2">
                <input type="text" wire:model.defer="form.cover_alt" placeholder="Alt text cover" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="url" wire:model.defer="form.cover_image_url" placeholder="URL ảnh ngoài cho cover" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    data-admin-media-picker-trigger
                    data-livewire-id="{{ $this->getId() }}"
                    data-pick-method="selectAvatarLibraryMedia"
                    data-button-label="Chọn ảnh này làm cover tour"
                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                >
                    <i class="fa-regular fa-images"></i>
                    Chọn từ Media popup
                </button>

                @if ($selectedLibraryAvatarMedia)
                    <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <i class="fa-solid fa-circle-check"></i>
                        Đã chọn cover từ thư viện: {{ $selectedLibraryAvatarMedia->name }}
                    </div>

                    <button type="button" wire:click="clearAvatarLibraryMediaSelection" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                        <i class="fa-solid fa-rotate-left"></i>
                        Bỏ chọn ảnh thư viện
                    </button>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <input type="text" wire:model.defer="form.meta_title" placeholder="Meta title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.og_title" placeholder="OG title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.canonical_url" placeholder="Canonical URL" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.robots_directive" placeholder="Robots directive" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>

            <x-admin.quill-editor wire:key="tour-meta-description-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['meta_description'] ?? '')) }}" model="form.meta_description" :value="$form['meta_description'] ?? ''" rows="3" placeholder="Meta description" />
            <x-admin.quill-editor wire:key="tour-og-description-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['og_description'] ?? '')) }}" model="form.og_description" :value="$form['og_description'] ?? ''" rows="3" placeholder="OG description" />

            @include('livewire.admin.cms.partials.tour-itinerary-pricing-fields')

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-zinc-900 dark:text-white">Chip ngày khởi hành bổ sung</label>
                    <textarea rows="4" wire:model.defer="form.departure_schedules_text" placeholder="Mỗi dòng một ngày hoặc ghi chú hiển thị ở frontsite" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-zinc-900 dark:text-white">Bao gồm</label>
                    <textarea rows="4" wire:model.defer="form.inclusions_text" placeholder="Mỗi dòng một mục bao gồm" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                </div>
            </div>

            <section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Departure theo tour</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Thêm hoặc xóa từng ngày khởi hành với giá, phương tiện và tiêu chuẩn lưu trú.</p>
                </div>

                <div class="space-y-4">
                    @foreach (($form['departures'] ?? []) as $index => $departure)
                        <div wire:key="tour-departure-{{ $index }}-{{ $departure['id'] ?? 'new' }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Departure {{ $loop->iteration }}</p>

                                @if (count($form['departures'] ?? []) > 1)
                                    <button type="button" wire:click="removeDeparture({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                        Xóa
                                    </button>
                                @endif
                            </div>

                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <input type="date" wire:model.defer="form.departures.{{ $index }}.departure_date" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <input type="number" wire:model.defer="form.departures.{{ $index }}.sale_price" placeholder="Giá bán" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <input type="number" wire:model.defer="form.departures.{{ $index }}.base_price" placeholder="Giá gốc" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <input type="text" wire:model.defer="form.departures.{{ $index }}.standard_label" placeholder="Tiêu chuẩn sao/lưu trú" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <input type="date" wire:model.defer="form.departures.{{ $index }}.return_date" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <input type="text" wire:model.defer="form.departures.{{ $index }}.departure_location" placeholder="Điểm khởi hành" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <input type="text" wire:model.defer="form.departures.{{ $index }}.transport_label" placeholder="Phương tiện" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <select wire:model.defer="form.departures.{{ $index }}.status" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                    <option value="scheduled">scheduled</option>
                                    <option value="published">published</option>
                                    <option value="sold_out">sold_out</option>
                                    <option value="finished">finished</option>
                                    <option value="cancelled">cancelled</option>
                                </select>
                                <input type="number" wire:model.defer="form.departures.{{ $index }}.available_slots" placeholder="Số chỗ" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <input type="number" wire:model.defer="form.departures.{{ $index }}.sort_order" placeholder="Thứ tự" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                                <label class="xl:col-span-2 flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                    <input type="checkbox" wire:model.defer="form.departures.{{ $index }}.is_featured" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                                    Departure nổi bật
                                </label>

                                <input type="text" wire:model.defer="form.departures.{{ $index }}.pricing_note" placeholder="Ghi chú giá / khuyến mại" class="md:col-span-2 xl:col-span-4 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-wrap justify-end">
                    <button type="button" wire:click="addDeparture" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                        Thêm departure
                    </button>
                </div>
            </section>

            @include('livewire.admin.cms.partials.tour-gallery', [
                'galleryItems' => $form['gallery'] ?? [],
                'galleryPathPrefix' => 'form.gallery',
                'galleryContext' => 'tour',
                'editingModel' => $editingTour,
                'galleryTitle' => 'Gallery nội dung tour',
                'galleryDescription' => 'Dùng cho slider, lightbox hoặc media minh họa trong tour detail.',
            ])

            @include('livewire.admin.cms.partials.faq-related-fields', [
                'faqTitle' => 'Điều khoản tour riêng',
                'faqDescription' => 'Nếu để trống, trang tour detail sẽ tự dùng mẫu Điều khoản tour từ Cấu hình theme.',
                'faqItems' => $form['tour_terms_items'] ?? [],
                'faqWireKeyPrefix' => 'tour-term-item',
                'faqQuestionPathPrefix' => 'form.tour_terms_items',
                'faqAnswerPathPrefix' => 'form.tour_terms_items',
                'addFaqAction' => 'addTourTermItem',
                'removeFaqAction' => 'removeTourTermItem',
                'faqItemLabel' => 'Điều khoản',
                'addFaqLabel' => 'Thêm điều khoản',
                'faqQuestionPlaceholder' => 'Tiêu đề điều khoản',
                'faqAnswerLabel' => 'Nội dung điều khoản',
                'faqAnswerPlaceholder' => 'Nhập nội dung điều khoản áp dụng riêng cho tour này.',
                'showRelatedQuestions' => false,
            ])

            @include('livewire.admin.cms.partials.faq-related-fields', [
                'faqTitle' => 'FAQ tour',
                'faqDescription' => 'Các câu hỏi này sẽ hiển thị ở cuối tour detail và hỗ trợ FAQ schema.',
                'relatedTitle' => 'Câu hỏi liên quan',
                'relatedDescription' => 'Các query phụ giúp mở rộng intent tìm kiếm cho trang tour.',
            ])

            <x-admin.form-action-bar>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveTour" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600 disabled:cursor-not-allowed disabled:opacity-70">
                    <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="saveTour"></i>
                    <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="saveTour"></i>
                    Lưu tour
                </button>
            </x-admin.form-action-bar>
        </form>
    </section>
</div>
