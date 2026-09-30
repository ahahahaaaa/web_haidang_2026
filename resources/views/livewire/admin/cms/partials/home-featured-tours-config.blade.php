<div class="grid gap-4 md:grid-cols-3">
    <div class="space-y-2 md:col-span-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Chủ đề ưu tiên chung cho pool tour hot</label>
        <select wire:model.defer="form.home_config.featured_tour_category_slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            <option value="">Tự dò Tour Nổi Bật / fallback sang is_featured</option>
            @foreach ($tourCategories as $category)
                <option value="{{ $category->slug }}">{{ $category->name }}</option>
            @endforeach
        </select>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Điều kiện này áp dụng chung trước khi chia tour theo từng filter. Để trống để dùng cờ tour nổi bật.</p>
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số tour mỗi tab</label>
        <input type="number" min="1" max="12" wire:model.defer="form.home_config.featured_tour_limit" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Tối đa {{ \App\Support\FrontsiteCardGrid::MAX_ITEMS }} card cho mỗi tab.</p>
    </div>

    <div class="space-y-2 md:col-span-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label CTA của block</label>
        <input type="text" wire:model.defer="form.home_config.featured_tours.cta_label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Màu nền nút Đặt ngay</label>
        <select wire:model.defer="form.home_config.featured_tours.card_cta_variant" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            @foreach ($tourCardCtaVariants as $variantValue => $variantLabel)
                <option value="{{ $variantValue }}">{{ $variantLabel }}</option>
            @endforeach
        </select>
    </div>

    <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 md:col-span-3">
        <input type="checkbox" wire:model.defer="form.home_config.featured_tours.is_slider" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
        Hiển thị tour dạng slide một hàng
    </label>
    <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 md:col-span-3">
        <input type="checkbox" wire:model.defer="form.home_config.featured_tours.show_filters" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
        Hiển thị filter tour (tắt sẽ lấy tab Tất cả)
    </label>
</div>

<div class="mt-5 rounded-3xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-600 dark:text-teal-300">Tab hệ thống · active mặc định</p>
    <div class="mt-4 grid gap-3 lg:grid-cols-2">
        <div class="space-y-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nhãn tab Tất cả</label>
            <input type="text" wire:model.defer="form.home_config.featured_tours.all.label" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        </div>
        <div class="space-y-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block mặc định</label>
            <input type="text" wire:model.defer="form.home_config.featured_tours.all.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        </div>
        <div class="space-y-2 lg:col-span-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả mặc định</label>
            <textarea rows="3" wire:model.defer="form.home_config.featured_tours.all.description" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
        </div>
    </div>
</div>

<div class="mt-5 space-y-3">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h5 class="text-sm font-semibold text-zinc-900 dark:text-white">Danh sách filter phân loại tour</h5>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Chọn loại tour, điểm đến, chủ đề hoặc vùng miền/châu. Tên tab tự lấy từ đối tượng; tiêu đề và mô tả block dùng chung từ tab Tất cả.</p>
        </div>
        <button
            type="button"
            wire:click="addHomeFeaturedTourFilter"
            wire:loading.attr="disabled"
            wire:target="addHomeFeaturedTourFilter"
            @disabled(count(data_get($form, 'home_config.featured_tours.filters', [])) >= \App\Support\TravelHomePageConfig::FEATURED_TOUR_FILTER_LIMIT)
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-700 transition hover:border-teal-300 hover:bg-teal-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-teal-800 dark:bg-teal-950/50 dark:text-teal-200"
        >
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            Thêm filter
        </button>
    </div>

    @forelse (data_get($form, 'home_config.featured_tours.filters', []) as $filterIndex => $filter)
        @php
            $filterUuid = (string) data_get($filter, 'uuid', 'filter-'.$filterIndex);
            $filterSourceType = (string) data_get($filter, 'source_type', \App\Support\TravelHomePageConfig::FEATURED_TOUR_FILTER_SCOPE);
        @endphp
        <div wire:key="home-featured-tour-filter-mixed-{{ $filterUuid }}" class="rounded-3xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-700 dark:bg-zinc-950/40">
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-600 dark:text-teal-300">Filter {{ $filterIndex + 1 }}</p>
                <button type="button" wire:click="removeHomeFeaturedTourFilter({{ $filterIndex }})" class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-white text-rose-600 transition hover:bg-rose-50 dark:border-rose-900 dark:bg-zinc-900" aria-label="Xóa filter {{ $filterIndex + 1 }}">
                    <i class="fa-solid fa-trash" aria-hidden="true"></i>
                </button>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Phân loại theo</label>
                    <select wire:model.live="form.home_config.featured_tours.filters.{{ $filterIndex }}.source_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        @foreach ($featuredTourFilterTypes as $filterType => $filterTypeLabel)
                            <option value="{{ $filterType }}">{{ $filterTypeLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Đối tượng</label>
                    <select wire:model.defer="form.home_config.featured_tours.filters.{{ $filterIndex }}.source_value" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <option value="">Chọn {{ \Illuminate\Support\Str::lower($featuredTourFilterTypes[$filterSourceType] ?? 'nguồn lọc') }}</option>
                        @if ($filterSourceType === \App\Support\TravelHomePageConfig::FEATURED_TOUR_FILTER_DESTINATION)
                            @foreach ($destinations as $destination)
                                <option value="{{ $destination->slug }}">{{ $destination->name }}</option>
                            @endforeach
                        @elseif ($filterSourceType === \App\Support\TravelHomePageConfig::FEATURED_TOUR_FILTER_TOPIC)
                            @foreach ($tourCategories as $category)
                                <option value="{{ $category->slug }}">{{ $category->name }}</option>
                            @endforeach
                        @elseif ($filterSourceType === \App\Support\TravelHomePageConfig::FEATURED_TOUR_FILTER_REGION)
                            @foreach ($regions as $region)
                                <option value="{{ $region->slug }}">{{ $region->name }}</option>
                            @endforeach
                        @else
                            @foreach ($scopeOptions as $scopeValue => $scopeLabel)
                                <option value="{{ $scopeValue }}">{{ $scopeLabel }}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('form.home_config.featured_tours.filters.'.$filterIndex.'.source_value')
                        <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-6 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Chưa có filter phụ. Trang chủ vẫn hiển thị tab Tất cả mặc định.</div>
    @endforelse
</div>

<div class="mt-5 space-y-3 border-t border-zinc-200 pt-5 dark:border-zinc-700">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h5 class="text-sm font-semibold text-zinc-900 dark:text-white">Tìm kiếm nổi bật</h5>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Mặc định lấy điểm đến con có tour đã xuất bản từ từng tab Miền/Châu. Liên kết thêm ở đây dùng đúng URL đã nhập; chọn tab để hiện cùng điểm đến của tab đó, hoặc để trống để chỉ hiện ở Tất cả.</p>
        </div>
        <button
            type="button"
            wire:click="addHomeFeaturedTourPopularSearch"
            wire:loading.attr="disabled"
            wire:target="addHomeFeaturedTourPopularSearch"
            @disabled(count(data_get($form, 'home_config.featured_tours.popular_searches', [])) >= \App\Support\TravelHomePageConfig::FEATURED_TOUR_POPULAR_SEARCH_LIMIT)
            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-orange-200 bg-orange-50 px-4 py-2 text-sm font-semibold text-orange-700 transition hover:border-orange-300 hover:bg-orange-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-orange-900 dark:bg-orange-950/40 dark:text-orange-200"
        >
            <i class="fa-solid fa-plus" aria-hidden="true"></i>
            Thêm liên kết
        </button>
    </div>

    @forelse (data_get($form, 'home_config.featured_tours.popular_searches', []) as $popularIndex => $popularSearch)
        <div wire:key="home-featured-popular-mixed-{{ data_get($popularSearch, 'uuid', $popularIndex) }}" class="grid gap-3 rounded-2xl border border-zinc-200 bg-zinc-50/80 p-3 md:grid-cols-[minmax(0,0.65fr)_minmax(0,0.8fr)_minmax(0,1.25fr)_auto] md:items-end dark:border-zinc-700 dark:bg-zinc-950/40">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nhãn hiển thị</label>
                <input type="text" wire:model.defer="form.home_config.featured_tours.popular_searches.{{ $popularIndex }}.label" placeholder="Ví dụ: Hà Giang" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-orange-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Thuộc filter</label>
                <select wire:model.defer="form.home_config.featured_tours.popular_searches.{{ $popularIndex }}.filter_uuid" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-orange-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <option value="">Chỉ tab Tất cả</option>
                    @foreach ($featuredTourPopularSearchFilterOptions as $filterUuid => $filterLabel)
                        <option value="{{ $filterUuid }}">{{ $filterLabel }}</option>
                    @endforeach
                </select>
                @error('form.home_config.featured_tours.popular_searches.'.$popularIndex.'.filter_uuid')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">URL</label>
                <input type="text" wire:model.defer="form.home_config.featured_tours.popular_searches.{{ $popularIndex }}.url" placeholder="/tim-tour?destination=ha-giang" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-orange-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                @error('form.home_config.featured_tours.popular_searches.'.$popularIndex.'.url')
                    <p class="text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="button" wire:click="removeHomeFeaturedTourPopularSearch({{ $popularIndex }})" class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-rose-200 bg-white text-rose-600 transition hover:bg-rose-50 dark:border-rose-900 dark:bg-zinc-900" aria-label="Xóa liên kết nổi bật {{ $popularIndex + 1 }}">
                <i class="fa-solid fa-trash" aria-hidden="true"></i>
            </button>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-5 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Chưa thêm URL riêng. Các điểm đến con vẫn được lấy tự động từ tab Miền/Châu.</div>
    @endforelse
</div>
