@php
    $customPreviewUrl = blank($form['page_key'] ?? null) && filled($form['slug'] ?? null)
        ? url('/' . ltrim((string) $form['slug'], '/'))
        : null;
    $isHtmlEditor = ($form['editor_mode'] ?? \Src\Domains\Cms\Models\LandingPage::EDITOR_MODE_BLOCKS) === \Src\Domains\Cms\Models\LandingPage::EDITOR_MODE_HTML;
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $selectedId ? 'Biên tập landing page' : 'Tạo landing page' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Trang chi tiết hỗ trợ cả block builder lẫn HTML thủ công, kèm clone cấu hình, SEO và publish state.</p>
        </div>

        <a href="{{ route('admin.landing-pages') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
            <i class="fa-solid fa-arrow-left"></i>
            Về danh sách
        </a>
    </div>

    <x-admin.form-feedback />

    <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Landing editor</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Preset template tạo khung nhanh cho block builder, hoặc chuyển sang HTML thủ công khi bạn cần một page rỗng.</p>
                </div>

            <div class="flex flex-wrap gap-3">
                @if ($selectedPage && blank($selectedPage->page_key) && $canEdit)
                    <button type="button" wire:click="deletePage({{ $selectedPage->id }})" wire:confirm="Bạn có chắc chắn muốn xóa landing page custom này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-rose-200 px-4 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-300 dark:hover:bg-rose-500/10">
                        <i class="fa-solid fa-trash"></i>
                        Xóa page
                    </button>
                @endif

                @if ($customPreviewUrl)
                    <a href="{{ $customPreviewUrl }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        Xem landing
                    </a>
                @endif
            </div>
        </div>

            <div class="mb-6 rounded-3xl border border-zinc-200 bg-zinc-50/80 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Clone nội dung từ landing page khác</label>
                        <select wire:model="cloneSourceId" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <option value="">Chọn landing page nguồn</option>
                            @foreach ($pageOptions as $page)
                                @if ($page->id !== $selectedId)
                                    <option value="{{ $page->id }}">{{ $page->title }} {{ $page->page_key ? '(' . $page->page_key . ')' : '(' . ($page->slug ?: 'custom') . ')' }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    @if ($canEdit)
                        <div class="flex items-end">
                            <button type="button" wire:click="cloneFromPage" class="inline-flex items-center gap-2 rounded-2xl border border-teal-200 px-5 py-3 text-sm font-semibold text-teal-700 transition hover:bg-teal-50 dark:border-teal-500/30 dark:text-teal-300 dark:hover:bg-teal-500/10">
                                <i class="fa-regular fa-copy"></i>
                                Clone block
                            </button>
                        </div>
                    @endif
                </div>
                <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">Clone giữ lại identity của page hiện tại nếu bạn đang sửa một landing đã tồn tại, nhưng sao chép block, SEO và media source để chỉnh tiếp.</p>
            </div>

            <form wire:submit="save" class="space-y-6 pb-32 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu landing page...">
                <section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Thông tin chung</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">System page dùng route cố định. Custom landing cần slug gốc không trùng reserved routes.</p>
                        </div>

                        <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                            <input type="checkbox" wire:model.defer="form.is_active" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                            Bật landing page
                        </label>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Template preset</label>
                            <div class="flex flex-wrap gap-3">
                                <select wire:model.defer="form.template_key" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    @foreach ($templates as $templateKey => $templateLabel)
                                        <option value="{{ $templateKey }}">{{ $templateLabel }}</option>
                                    @endforeach
                                </select>

                                @if ($canEdit)
                                    <button type="button" wire:click="applyTemplatePreset" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                                        Áp preset
                                    </button>

                                    <button
                                        type="button"
                                        wire:click="useBlankHtmlMode"
                                        wire:confirm="Chuyển landing này sang mode HTML thủ công và làm trống block hiện tại trong form?"
                                        class="inline-flex items-center gap-2 rounded-2xl border border-amber-200 px-4 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 dark:border-amber-500/30 dark:text-amber-300 dark:hover:bg-amber-500/10"
                                    >
                                        <i class="fa-solid fa-code"></i>
                                        Landing rỗng HTML
                                    </button>
                                @endif
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Preset <span class="font-semibold">Landing rỗng</span> dùng khi bạn muốn bắt đầu từ page trắng. Nút bên cạnh sẽ chuyển ngay sang mode HTML thủ công và xóa block trong form.</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Loại landing</label>
                            @if ($selectedPage && filled($form['page_key'] ?? null))
                                <input type="text" value="{{ $systemPages[$form['page_key']] ?? $form['page_key'] }}" readonly class="w-full rounded-2xl border border-zinc-200 bg-zinc-100 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            @else
                                <select wire:model.defer="form.page_key" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    <option value="">Landing custom tại /{slug}</option>
                                    @foreach ($systemPages as $pageKey => $pageLabel)
                                        <option value="{{ $pageKey }}">{{ $pageLabel }} ({{ $pageKey }})</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Chế độ biên tập</label>
                            <select wire:model.live="form.editor_mode" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                @foreach ($editorModes as $editorModeKey => $editorModeLabel)
                                    <option value="{{ $editorModeKey }}">{{ $editorModeLabel }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Block builder phù hợp preset và query block. HTML thủ công sẽ render trực tiếp đoạn code bạn dán vào frontsite.</p>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề landing</label>
                            <input type="text" wire:model.defer="form.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug public</label>
                            <input type="text" wire:model.defer="form.slug" placeholder="{{ blank($form['page_key'] ?? null) ? 'landing-moi' : 'tu-chon-neu-can' }}" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                @if (blank($form['page_key'] ?? null))
                                    Có thể để trống để hệ thống tự sinh slug từ tiêu đề. Custom landing sẽ public tại <span class="font-semibold">{{ $customPreviewUrl ?: 'https://.../{slug}' }}</span>.
                                @else
                                    System page vẫn dùng route hệ thống; nếu có nhập slug thì chỉ lưu nội bộ.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-4 text-xs text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        Reserved root slugs: {{ implode(', ', $reservedSlugs) }}
                    </div>
                </section>

                @if ($isHtmlEditor)
                    <section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">HTML thủ công</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Landing sẽ render trực tiếp đoạn HTML bên dưới trong layout frontsite hiện tại. Dùng khi bạn cần một campaign page rỗng rồi tự dán code để chạy.</p>
                        </div>

                        <div class="rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                            Mode này không đi qua Quill hay block renderer. CSS hoặc JavaScript bạn dán vào sẽ được xuất thẳng ra frontsite, nên chỉ dùng với mã đã kiểm tra kỹ.
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mã HTML landing</label>
                            <textarea wire:model.defer="form.html_content" rows="22" class="w-full rounded-2xl border border-zinc-200 bg-zinc-950 px-4 py-3 font-mono text-sm text-white outline-none transition focus:border-teal-400 dark:border-zinc-700">{{ $form['html_content'] ?? '' }}</textarea>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Nếu chỉ muốn render nội dung trong phần thân trang, bạn không cần dán đủ thẻ <span class="font-semibold">&lt;html&gt;</span>, <span class="font-semibold">&lt;head&gt;</span>, <span class="font-semibold">&lt;body&gt;</span>.</p>
                        </div>
                    </section>
                @endif

                @if (($form['page_key'] ?? null) === 'home')
                    <section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Cấu hình block trang chủ</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Các block dưới đây vẫn lấy dữ liệu runtime từ tour, điểm đến, dịch vụ và blog đã publish; CMS chỉ điều khiển copy, CTA và danh sách item ưu tiên hiển thị.</p>
                        </div>

                        <div class="grid gap-4 xl:grid-cols-2">
                            <div class="space-y-4 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <h4 class="text-base font-semibold text-zinc-900 dark:text-white">Search bar</h4>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Điều chỉnh placeholder và label nút tìm kiếm dưới hero.</p>
                                    </div>
                                    <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        <input type="checkbox" wire:model.defer="form.home_config.search.is_enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                        Bật block
                                    </label>
                                </div>

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
                            </div>

                            <div class="space-y-4 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <h4 class="text-base font-semibold text-zinc-900 dark:text-white">Slider điểm đến</h4>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Đổi heading và chọn các điểm đến ưu tiên cho slider nằm trên section Điểm đến yêu thích.</p>
                                    </div>
                                    <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        <input type="checkbox" wire:model.defer="form.home_config.destination_slider.is_enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                        Bật block
                                    </label>
                                </div>

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
                            </div>

                            <div class="space-y-4 rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                    <div>
                                        <h4 class="text-base font-semibold text-zinc-900 dark:text-white">Chủ đề tour</h4>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Bật/tắt rail chủ đề tour cố định. Dữ liệu vẫn lấy từ các chủ đề đang publish và có tour hoạt động.</p>
                                    </div>
                                    <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                        <input type="checkbox" wire:model.defer="form.home_config.topic_rail.is_enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                        Bật block
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <h4 class="text-base font-semibold text-zinc-900 dark:text-white">Tab tour nổi bật</h4>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Mỗi tab vẫn query tour live theo scope. Bạn có thể điều chỉnh nhãn tab, title, mô tả, CTA và danh mục ưu tiên dùng để gom tour nổi bật.</p>
                                </div>
                                <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                    <input type="checkbox" wire:model.defer="form.home_config.featured_tours.is_enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                    Bật block
                                </label>
                            </div>

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
                                @foreach (['domestic' => 'Tour trong nước', 'international' => 'Tour nước ngoài', 'group' => 'Tour đoàn'] as $scopeKey => $scopeLabel)
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
                        </div>
                    </section>

                    <section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Dịch vụ hỗ trợ</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Section này lấy card từ dịch vụ publish. Bạn có thể đổi heading, CTA và chọn một danh sách dịch vụ ưu tiên để render trước.</p>
                            </div>
                            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                <input type="checkbox" wire:model.defer="form.home_config.services.is_enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                Bật block
                            </label>
                        </div>

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
                    </section>

                    <section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Block Lý do khách chọn Hải Đăng</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Section này nên thiên về proof ngắn thay vì paragraph dài. Icon dùng class Font Awesome; bốn chỉ số bên dưới section vẫn lấy từ Cấu hình theme.</p>
                            </div>
                            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                <input type="checkbox" wire:model.defer="form.home_config.trust.is_enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                Bật block
                            </label>
                        </div>

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

                        <div class="grid gap-4 xl:grid-cols-3">
                            @foreach (($form['home_config']['trust']['cards'] ?? []) as $index => $card)
                                <div wire:key="landing-home-trust-card-{{ $card['uuid'] ?? $index }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-teal-600 dark:text-teal-300">Lý do {{ $loop->iteration }}</p>

                                        @if (count($form['home_config']['trust']['cards'] ?? []) > 1)
                                            <button type="button" wire:click="removeHomeTrustCard({{ $index }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">
                                                Xóa
                                            </button>
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

                        <div class="flex flex-wrap justify-end">
                            <button type="button" wire:click="addHomeTrustCard" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:text-zinc-200">
                                Thêm lý do
                            </button>
                        </div>
                    </section>

                    <section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Quy trình tư vấn</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Tùy chỉnh title, mô tả và các bước hiển thị trong section quy trình.</p>
                            </div>
                            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                <input type="checkbox" wire:model.defer="form.home_config.process.is_enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                Bật block
                            </label>
                        </div>

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

                        <div class="grid gap-4 xl:grid-cols-2">
                            @foreach (($form['home_config']['process']['cards'] ?? []) as $index => $card)
                                @php
                                    $processImageUrl = trim((string) ($card['image_url'] ?? ''));
                                @endphp
                                <div wire:key="landing-home-process-card-{{ $card['uuid'] ?? $index }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                    <div class="mb-4 flex items-center justify-between gap-3">
                                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-teal-600 dark:text-teal-300">Bước {{ $loop->iteration }}</p>

                                        @if (count($form['home_config']['process']['cards'] ?? []) > 1)
                                            <button type="button" wire:click="removeHomeProcessCard({{ $index }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">
                                                Xóa
                                            </button>
                                        @endif
                                    </div>

                                    <div class="grid gap-3">
                                        <div class="space-y-3">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Ảnh bước</label>

                                            <div class="overflow-hidden rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-950/50">
                                                @if ($processImageUrl !== '')
                                                    <img src="{{ $processImageUrl }}" alt="{{ $card['image_alt'] ?? $card['title'] ?? 'Ảnh bước quy trình' }}" class="h-44 w-full object-cover">
                                                @else
                                                    <div class="flex h-44 items-center justify-center px-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                                        Chưa có ảnh cho bước này. Có thể chọn 1 ảnh từ Media popup để tăng tính trực quan ở frontsite.
                                                    </div>
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

                        <div class="flex flex-wrap justify-end">
                            <button type="button" wire:click="addHomeProcessCard" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:text-zinc-200">
                                Thêm bước
                            </button>
                        </div>
                    </section>

                    <section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Blog preview</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Điều chỉnh heading, CTA, giới hạn card và chọn bài viết ưu tiên nếu không muốn homepage tự lấy các bài mới nhất.</p>
                            </div>
                            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                <input type="checkbox" wire:model.defer="form.home_config.blog_preview.is_enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                Bật block
                            </label>
                        </div>

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
                    </section>
                @endif

                @if (! $isHtmlEditor)
                <section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Blocks</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Sắp xếp block theo thứ tự hiển thị trên frontsite. Query blocks chỉ lấy dữ liệu published.</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        @forelse ($form['blocks'] ?? [] as $blockIndex => $block)
                            @php
                                $blockType = $block['type'] ?? \App\Support\LandingPageBlocks::TYPE_RICH_TEXT;
                                $blockEnabled = (bool) ($block['is_enabled'] ?? true);
                                $blockUuid = $block['uuid'] ?? ('block-' . $blockIndex);
                                $selectedMedia = data_get($selectedBlockMedia, $blockUuid . '.media');
                                $heroMediaPreview = $selectedMedia?->getUrl() ?: ($selectedPage?->getFirstMediaUrl(\App\Support\LandingPageBlocks::mediaCollection($blockUuid)) ?: null);
                                $locationBase = filled($form['page_key'] ?? null)
                                    ? (string) $form['page_key']
                                    : \Illuminate\Support\Str::slug((string) ($form['slug'] ?: $form['title'] ?: 'landing'));
                            @endphp

                            <article wire:key="landing-block-{{ $blockUuid }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                                <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-teal-600 dark:text-teal-300">Block {{ $blockIndex + 1 }}</p>
                                        <h4 class="mt-2 text-lg font-semibold text-zinc-900 dark:text-white">{{ $blockTypes[$blockType] ?? $blockType }}</h4>
                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $blockEnabled ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' }}">
                                                {{ $blockEnabled ? 'Đang bật' : 'Đang tắt' }}
                                            </span>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Block tắt sẽ được giữ trong CMS nhưng không render ra landing page.</p>
                                        </div>
                                    </div>

                                    @if ($canEdit)
                                        <div class="flex flex-wrap gap-2">
                                            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                <input type="checkbox" wire:model.defer="form.blocks.{{ $blockIndex }}.is_enabled" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                                Bật block
                                            </label>
                                            <button type="button" wire:click="moveBlockUp({{ $blockIndex }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">Lên</button>
                                            <button type="button" wire:click="moveBlockDown({{ $blockIndex }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">Xuống</button>
                                            <button type="button" wire:click="duplicateBlock({{ $blockIndex }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">Nhân đôi</button>
                                            <button type="button" wire:click="removeBlock({{ $blockIndex }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">Xóa</button>
                                        </div>
                                    @endif
                                </div>

                                @if (! $blockEnabled)
                                    <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                        Block này đang ở trạng thái tắt. Bạn vẫn có thể chỉnh nội dung, đổi thứ tự hoặc bật lại khi cần.
                                    </div>
                                @endif

                                @if (in_array($blockType, [\App\Support\LandingPageBlocks::TYPE_HERO_SLIDER, \App\Support\LandingPageBlocks::TYPE_HERO_MEDIA, \App\Support\LandingPageBlocks::TYPE_GALLERY_SLIDER, \App\Support\LandingPageBlocks::TYPE_GALLERY_MEDIA], true))
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Eyebrow</label>
                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.eyebrow" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
                                            <x-admin.quill-editor wire:key="landing-block-description-{{ $blockUuid }}" model="form.blocks.{{ $blockIndex }}.description" :value="$block['description'] ?? ''" rows="4" placeholder="Mô tả ngắn cho hero hoặc gallery." />
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_HERO_SLIDER || $blockType === \App\Support\LandingPageBlocks::TYPE_GALLERY_SLIDER)
                                    @php
                                        $defaultSliderLocation = $locationBase.'-'.($blockType === \App\Support\LandingPageBlocks::TYPE_HERO_SLIDER ? 'hero' : 'gallery');
                                        $sliderPurposeLabel = $blockType === \App\Support\LandingPageBlocks::TYPE_HERO_SLIDER ? 'hero slider' : 'gallery slider';
                                    @endphp
                                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nguồn slider</label>
                                            <select wire:model.defer="form.blocks.{{ $blockIndex }}.slider_id" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                <option value="">Dùng slider mặc định: {{ $defaultSliderLocation }}</option>
                                                @foreach ($sliders as $slider)
                                                    <option value="{{ $slider->id }}">Chọn slider có sẵn: {{ $slider->name }} {{ $slider->location ? '(' . $slider->location . ')' : '' }} - {{ $slider->active_items_count }} item</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                            Banner-location mặc định cho block này là <span class="font-semibold">{{ $defaultSliderLocation }}</span>.
                                            @if (($form['page_key'] ?? null) === 'home' && $blockType === \App\Support\LandingPageBlocks::TYPE_HERO_SLIDER)
                                                Trang chủ sẽ tự lấy <span class="font-semibold">home-hero</span> nếu bạn để trống.
                                            @endif
                                        </div>
                                        <div class="rounded-2xl bg-teal-50 px-4 py-3 text-sm text-teal-700 dark:bg-teal-500/10 dark:text-teal-300 md:col-span-2">
                                            Bạn có thể để trống để dùng {{ $sliderPurposeLabel }} mặc định theo <span class="font-semibold">{{ $defaultSliderLocation }}</span>, hoặc chọn một slider đã tạo sẵn trong danh sách để override riêng cho landing page này.
                                        </div>
                                    </div>

                                    @if ($blockType === \App\Support\LandingPageBlocks::TYPE_HERO_SLIDER)
                                        <div class="mt-5 grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">CTA chính</label>
                                                <div class="grid gap-3 md:grid-cols-2">
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.primary_label" placeholder="Label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.primary_url" placeholder="/lien-he" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">CTA phụ</label>
                                                <div class="grid gap-3 md:grid-cols-2">
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.secondary_label" placeholder="Label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.secondary_url" placeholder="/tour-trong-nuoc" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_HERO_MEDIA)
                                    <div class="mt-5 space-y-4">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt media</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.media_alt" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Xem trước</label>
                                                <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">Hero media dùng collection theo block UUID để clone và reorder an toàn.</div>
                                            </div>
                                        </div>

                                        <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
                                            <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-950">
                                                @if ($heroMediaPreview)
                                                    <img src="{{ $heroMediaPreview }}" alt="{{ $block['media_alt'] ?? $block['title'] ?? 'Hero media' }}" class="h-64 w-full object-cover">
                                                @else
                                                    <div class="flex h-64 items-center justify-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có media cho block này.</div>
                                                @endif
                                            </div>

                                            <div class="flex flex-col gap-3">
                                                <button
                                                    type="button"
                                                    data-admin-media-picker-trigger
                                                    data-livewire-id="{{ $this->getId() }}"
                                                    data-pick-method="selectLibraryMediaForUpload"
                                                    data-pick-target="blockUploads.{{ $blockUuid }}.media"
                                                    data-pick-alt-target="form.blocks.{{ $blockIndex }}.media_alt"
                                                    data-button-label="Chọn media cho hero"
                                                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                                                >
                                                    <i class="fa-regular fa-images"></i>
                                                    Chọn từ Media popup
                                                </button>

                                                @if ($selectedMedia)
                                                    <button type="button" wire:click="clearLibraryMediaSelectionForUpload('blockUploads.{{ $blockUuid }}.media')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                                                        <i class="fa-solid fa-rotate-left"></i>
                                                        Bỏ chọn
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">CTA chính</label>
                                                <div class="grid gap-3 md:grid-cols-2">
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.primary_label" placeholder="Label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.primary_url" placeholder="/lien-he" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">CTA phụ</label>
                                                <div class="grid gap-3 md:grid-cols-2">
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.secondary_label" placeholder="Label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.secondary_url" placeholder="/tour-trong-nuoc" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE)
                                    <div class="space-y-5">
                                        <div class="rounded-2xl border border-sky-200 bg-sky-50/80 px-4 py-4 text-sm leading-7 text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">
                                            Block này tái sử dụng section hero demo từ homepage: nền ưu tiên ảnh demo bạn chọn bên dưới, nếu trống sẽ lấy từ cover tour nổi bật đầu tiên. Panel bên phải tự gom nhanh ba nhóm tour trong nước, nước ngoài và tour đoàn.
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề hero</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề panel</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.panel_title" placeholder="Bắt đầu từ nhóm tour phù hợp nhất" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2 md:col-span-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả hero</label>
                                                <textarea rows="4" wire:model.defer="form.blocks.{{ $blockIndex }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">CTA chính</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.primary_label" placeholder="Gửi yêu cầu tư vấn" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">CTA chính luôn mở popup Travel Inquiry để nhận lead nhanh.</p>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">CTA phụ</label>
                                                <div class="grid gap-3 md:grid-cols-2">
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.secondary_label" placeholder="Xem tour nổi bật" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.secondary_url" placeholder="/tour-trong-nuoc" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="grid gap-4 lg:grid-cols-[1fr_auto]">
                                            <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-950">
                                                @if ($heroMediaPreview)
                                                    <img src="{{ $heroMediaPreview }}" alt="{{ $block['media_alt'] ?? $block['title'] ?? 'Hero demo media' }}" class="h-64 w-full object-cover">
                                                @else
                                                    <div class="flex h-64 items-center justify-center px-4 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa chọn ảnh demo. Frontsite sẽ dùng cover tour nổi bật đầu tiên.</div>
                                                @endif
                                            </div>

                                            <div class="flex flex-col gap-3">
                                                <div class="space-y-2">
                                                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt ảnh nền</label>
                                                    <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.media_alt" placeholder="Mô tả ảnh nền hero demo" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white lg:w-72">
                                                </div>

                                                <button
                                                    type="button"
                                                    data-admin-media-picker-trigger
                                                    data-livewire-id="{{ $this->getId() }}"
                                                    data-pick-method="selectLibraryMediaForUpload"
                                                    data-pick-target="blockUploads.{{ $blockUuid }}.media"
                                                    data-pick-alt-target="form.blocks.{{ $blockIndex }}.media_alt"
                                                    data-button-label="Chọn ảnh nền demo"
                                                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                                                >
                                                    <i class="fa-regular fa-images"></i>
                                                    Chọn ảnh demo
                                                </button>

                                                @if ($selectedMedia)
                                                    <button type="button" wire:click="clearLibraryMediaSelectionForUpload('blockUploads.{{ $blockUuid }}.media')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                                                        <i class="fa-solid fa-rotate-left"></i>
                                                        Bỏ chọn ảnh mới
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Danh mục tour</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.category_slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    <option value="">Tất cả</option>
                                                    @foreach ($tourCategories as $category)
                                                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Điểm đến</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.destination_slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    <option value="">Tất cả</option>
                                                    @foreach ($destinations as $destination)
                                                        <option value="{{ $destination->slug }}">{{ $destination->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Vùng miền</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.region_slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    <option value="">Tất cả</option>
                                                    @foreach ($regions as $region)
                                                        <option value="{{ $region->slug }}">{{ $region->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Scope ưu tiên</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.scope" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    <option value="">Tự chọn nhóm có tour</option>
                                                    @foreach ($scopeOptions as $scopeValue => $scopeLabel)
                                                        <option value="{{ $scopeValue }}">{{ $scopeLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Sort</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.sort" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    @foreach ($tourSortOptions as $sortOption)
                                                        <option value="{{ $sortOption }}">{{ $sortOption }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số tour mỗi nhóm</label>
                                                <input type="number" min="1" max="12" wire:model.defer="form.blocks.{{ $blockIndex }}.limit" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                <input type="checkbox" wire:model.defer="form.blocks.{{ $blockIndex }}.featured" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                                Chỉ lấy tour featured
                                            </label>
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_RICH_TEXT)
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Eyebrow</label>
                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.eyebrow" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Excerpt</label>
                                            <x-admin.quill-editor wire:key="landing-rich-excerpt-{{ $blockUuid }}" model="form.blocks.{{ $blockIndex }}.excerpt" :value="$block['excerpt'] ?? ''" rows="3" placeholder="Đoạn mô tả mở đầu." />
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Body</label>
                                            <x-admin.quill-editor wire:key="landing-rich-body-{{ $blockUuid }}" model="form.blocks.{{ $blockIndex }}.body" :value="$block['body'] ?? ''" mode="rich" :allow-images="true" rows="10" placeholder="Nội dung chính của landing." />
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_HTML_WIDGET)
                                    <div class="space-y-4">
                                        <div class="rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-4 text-sm leading-7 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                                            Block này render trực tiếp HTML tại đúng vị trí trong stack landing page. Mặc định block rỗng để bạn dán widget, snippet hoặc markup đã kiểm tra.
                                        </div>

                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mã HTML widget</label>
                                            <textarea wire:model.defer="form.blocks.{{ $blockIndex }}.html" rows="14" class="w-full rounded-2xl border border-zinc-200 bg-zinc-950 px-4 py-3 font-mono text-sm text-white outline-none transition focus:border-teal-400 dark:border-zinc-700" placeholder="<section>...</section>">{{ $block['html'] ?? '' }}</textarea>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Để trống nếu bạn chỉ muốn giữ sẵn một vị trí widget và bật nội dung sau.</p>
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_TRUST_PROOF)
                                    <div class="space-y-5">
                                        <div class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50/70 px-4 py-4 text-sm leading-7 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-400">
                                            Block này dành cho các cụm `Vì sao chọn chúng tôi`, proof nhanh hoặc điểm khác biệt ngắn. Frontsite ưu tiên 1 card nổi bật + 2 card phụ, có icon Font Awesome và chỉ số thật nếu được nhập.
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2 md:col-span-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả ngắn</label>
                                                <textarea rows="3" wire:model.defer="form.blocks.{{ $blockIndex }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                                            </div>
                                        </div>

                                        <div class="grid gap-4 xl:grid-cols-3">
                                            @foreach (($block['cards'] ?? []) as $cardIndex => $card)
                                                <div wire:key="landing-trust-proof-card-{{ $blockUuid }}-{{ $card['uuid'] ?? $cardIndex }}" class="rounded-3xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                                                    <div class="mb-4 flex items-center justify-between gap-3">
                                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">Proof card {{ $cardIndex + 1 }}</p>

                                                        @if ($canEdit && count($block['cards'] ?? []) > 1)
                                                            <button type="button" wire:click="removeTrustProofCard({{ $blockIndex }}, {{ $cardIndex }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">
                                                                Xóa
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <div class="grid gap-3">
                                                        <div class="space-y-2">
                                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Icon Font Awesome</label>
                                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.cards.{{ $cardIndex }}.icon" placeholder="fa-solid fa-route" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                        </div>
                                                        <div class="space-y-2">
                                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nhãn nổi bật</label>
                                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.cards.{{ $cardIndex }}.highlight" placeholder="Ví dụ: So sánh dễ hơn" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                        </div>
                                                        <div class="space-y-2">
                                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.cards.{{ $cardIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                        </div>
                                                        <div class="space-y-2">
                                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Bằng chứng ngắn</label>
                                                            <textarea rows="4" wire:model.defer="form.blocks.{{ $blockIndex }}.cards.{{ $cardIndex }}.text" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if ($canEdit)
                                            <div class="flex flex-wrap justify-end">
                                                <button type="button" wire:click="addTrustProofCard({{ $blockIndex }})" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                                    <i class="fa-solid fa-plus"></i>
                                                    Thêm proof card
                                                </button>
                                            </div>
                                        @endif

                                        <div class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                                <div>
                                                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white">Chỉ số tin cậy</h4>
                                                    <p class="mt-1 text-xs leading-5 text-zinc-500 dark:text-zinc-400">Tùy chọn. Chỉ nhập số liệu thật; bỏ trống nếu landing page chưa có số liệu phù hợp.</p>
                                                </div>

                                                @if ($canEdit)
                                                    <button type="button" wire:click="addTrustProofStat({{ $blockIndex }})" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                                        <i class="fa-solid fa-plus"></i>
                                                        Thêm chỉ số
                                                    </button>
                                                @endif
                                            </div>

                                            @if (empty($block['stats'] ?? []))
                                                <div class="rounded-2xl border border-dashed border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                                                    Chưa có chỉ số. Frontsite sẽ chỉ render proof cards.
                                                </div>
                                            @else
                                                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                                                    @foreach (($block['stats'] ?? []) as $statIndex => $stat)
                                                        <div wire:key="landing-trust-proof-stat-{{ $blockUuid }}-{{ $stat['uuid'] ?? $statIndex }}" class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-600 dark:text-teal-300">Chỉ số {{ $statIndex + 1 }}</p>

                                                                @if ($canEdit)
                                                                    <button type="button" wire:click="removeTrustProofStat({{ $blockIndex }}, {{ $statIndex }})" class="rounded-xl border border-rose-200 px-2.5 py-1.5 text-xs font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">
                                                                        Xóa
                                                                    </button>
                                                                @endif
                                                            </div>

                                                            <div class="grid gap-3">
                                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.stats.{{ $statIndex }}.icon" placeholder="fa-solid fa-award" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.stats.{{ $statIndex }}.value" placeholder="19+" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.stats.{{ $statIndex }}.label" placeholder="Năm kinh nghiệm" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_CTA)
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="space-y-2 md:col-span-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề CTA</label>
                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả CTA</label>
                                            <x-admin.quill-editor wire:key="landing-cta-description-{{ $blockUuid }}" model="form.blocks.{{ $blockIndex }}.description" :value="$block['description'] ?? ''" rows="3" placeholder="Đoạn mô tả trước nút hành động." />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Button chính</label>
                                            <div class="grid gap-3 md:grid-cols-2">
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.primary_label" placeholder="Label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.primary_url" placeholder="/lien-he" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Button phụ</label>
                                            <div class="grid gap-3 md:grid-cols-2">
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.secondary_label" placeholder="Label" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.secondary_url" placeholder="/tour-doan" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_GALLERY_MEDIA)
                                    <div class="mt-5 space-y-5">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Kiểu gallery</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.variant" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    @foreach (($galleryVariants ?? []) as $variantKey => $variantLabel)
                                                        <option value="{{ $variantKey }}">{{ $variantLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="rounded-2xl border border-dashed border-zinc-300 px-4 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                                `Gallery tab ảnh` sẽ nhóm item theo trường <span class="font-semibold">Nhãn tab</span> và render kiểu mosaic giống block landing khám phá điểm đến. Mỗi tab cần ít nhất 1 item để hiển thị.
                                            </div>
                                        </div>

                                        @foreach ($block['items'] ?? [] as $itemIndex => $item)
                                            @php
                                                $galleryMedia = data_get($selectedBlockMedia, $blockUuid . '.gallery.' . ($item['uuid'] ?? ''));
                                                $galleryPreview = $galleryMedia?->getUrl()
                                                    ?: ($selectedPage?->getFirstMediaUrl(\App\Support\LandingPageBlocks::galleryItemCollection($blockUuid, (string) ($item['uuid'] ?? ''))) ?: null)
                                                    ?: (filled($item['image_url'] ?? null) ? (string) $item['image_url'] : null);
                                            @endphp

                                            <div wire:key="landing-gallery-item-{{ $item['uuid'] ?? $itemIndex }}" class="rounded-3xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                                                <div class="mb-4 flex items-center justify-between gap-3">
                                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">Gallery item {{ $itemIndex + 1 }}</p>
                                                    @if ($canEdit)
                                                        <button type="button" wire:click="removeGalleryItem({{ $blockIndex }}, {{ $itemIndex }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">Xóa item</button>
                                                    @endif
                                                </div>

                                                <div class="grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
                                                    <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-950">
                                                        @if ($galleryPreview)
                                                            <img src="{{ $galleryPreview }}" alt="{{ $item['image_alt'] ?? $item['title'] ?? 'Gallery item' }}" class="h-52 w-full object-cover">
                                                        @else
                                                            <div class="flex h-52 items-center justify-center text-sm text-zinc-500 dark:text-zinc-400">Chưa chọn media.</div>
                                                        @endif
                                                    </div>

                                                    <div class="space-y-4">
                                                        <div class="grid gap-4 md:grid-cols-2">
                                                            <div class="space-y-2">
                                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nhãn tab</label>
                                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.items.{{ $itemIndex }}.tab_label" placeholder="Ví dụ: Miền Bắc, Châu Á..." class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                            </div>
                                                            <div class="space-y-2">
                                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.items.{{ $itemIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                            </div>
                                                            <div class="space-y-2">
                                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Subtitle</label>
                                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.items.{{ $itemIndex }}.subtitle" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                            </div>
                                                            <div class="space-y-2">
                                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Kiểu tile</label>
                                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.items.{{ $itemIndex }}.tile_size" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                                    @foreach (($galleryTileSizes ?? []) as $tileKey => $tileLabel)
                                                                        <option value="{{ $tileKey }}">{{ $tileLabel }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="space-y-2 md:col-span-2">
                                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Link</label>
                                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.items.{{ $itemIndex }}.url" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                            </div>
                                                            <div class="space-y-2 md:col-span-2">
                                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt text</label>
                                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.items.{{ $itemIndex }}.image_alt" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                            </div>
                                                            <div class="space-y-2 md:col-span-2">
                                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
                                                                <x-admin.quill-editor wire:key="landing-gallery-description-{{ $item['uuid'] ?? $itemIndex }}" model="form.blocks.{{ $blockIndex }}.items.{{ $itemIndex }}.description" :value="$item['description'] ?? ''" rows="3" placeholder="Mô tả ngắn cho item gallery." />
                                                            </div>
                                                        </div>

                                                        <div class="flex flex-wrap gap-3">
                                                            <button
                                                                type="button"
                                                                data-admin-media-picker-trigger
                                                                data-livewire-id="{{ $this->getId() }}"
                                                                data-pick-method="selectLibraryMediaForUpload"
                                                                data-pick-target="blockUploads.{{ $blockUuid }}.gallery.{{ $item['uuid'] }}"
                                                                data-pick-alt-target="form.blocks.{{ $blockIndex }}.items.{{ $itemIndex }}.image_alt"
                                                                data-button-label="Chọn ảnh cho gallery item"
                                                                class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"
                                                            >
                                                                <i class="fa-regular fa-images"></i>
                                                                Chọn từ Media popup
                                                            </button>

                                                            @if ($galleryMedia)
                                                                <button type="button" wire:click="clearLibraryMediaSelectionForUpload('blockUploads.{{ $blockUuid }}.gallery.{{ $item['uuid'] }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                                                                    <i class="fa-solid fa-rotate-left"></i>
                                                                    Bỏ chọn
                                                                </button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach

                                        @if ($canEdit)
                                            <div class="flex flex-wrap justify-end">
                                                <button type="button" wire:click="addGalleryItem({{ $blockIndex }})" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                                    <i class="fa-solid fa-plus"></i>
                                                    Thêm item gallery
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_FAQ)
                                    <div class="space-y-4">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề khối FAQ</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả khối FAQ</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                        </div>

                                        @foreach ($block['items'] ?? [] as $itemIndex => $item)
                                            <div wire:key="landing-faq-item-{{ $blockUuid }}-{{ $itemIndex }}" class="rounded-3xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                                                <div class="mb-4 flex items-center justify-between gap-3">
                                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">FAQ item {{ $itemIndex + 1 }}</p>
                                                    @if ($canEdit)
                                                        <button type="button" wire:click="removeFaqItem({{ $blockIndex }}, {{ $itemIndex }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">Xóa item</button>
                                                    @endif
                                                </div>

                                                <div class="space-y-4">
                                                    <div class="space-y-2">
                                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Câu hỏi</label>
                                                        <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.items.{{ $itemIndex }}.question" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    </div>
                                                    <div class="space-y-2">
                                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Trả lời</label>
                                                        <x-admin.quill-editor wire:key="landing-faq-answer-{{ $blockUuid }}-{{ $itemIndex }}" model="form.blocks.{{ $blockIndex }}.items.{{ $itemIndex }}.answer" :value="$item['answer'] ?? ''" rows="4" placeholder="Nội dung trả lời." />
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach

                                        @if ($canEdit)
                                            <div class="flex flex-wrap justify-end">
                                                <button type="button" wire:click="addFaqItem({{ $blockIndex }})" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                                    <i class="fa-solid fa-plus"></i>
                                                    Thêm FAQ item
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_REGION_RAIL)
                                    <div class="space-y-5">
                                        <div class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50/70 px-4 py-4 text-sm leading-7 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-400">
                                            Block này dùng layout carousel cùng family với <span class="font-semibold">Điểm đến nổi bật</span> và <span class="font-semibold">Chủ đề tour nổi bật</span> ở trang chủ, nhưng lấy dữ liệu live từ taxonomy vùng miền đang có tour publish.
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label CTA trên card</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.card_cta_label" placeholder="Xem hub vùng miền" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2 md:col-span-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả block</label>
                                                <textarea rows="4" wire:model.defer="form.blocks.{{ $blockIndex }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Scope</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.scope" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    <option value="">Tất cả</option>
                                                    @foreach ($scopeOptions as $scopeValue => $scopeLabel)
                                                        <option value="{{ $scopeValue }}">{{ $scopeLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số lượng card</label>
                                                <input type="number" min="1" max="8" wire:model.defer="form.blocks.{{ $blockIndex }}.limit" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Tối đa 8 hub vùng miền để giữ carousel gọn và đồng nhất với block taxonomy trên homepage.</p>
                                            </div>
                                            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 md:col-span-2">
                                                <input type="checkbox" wire:model.defer="form.blocks.{{ $blockIndex }}.featured" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                                Chỉ lấy vùng miền featured
                                            </label>
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_REGION_TAXONOMY_TABS)
                                    <div class="space-y-5">
                                        <div class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50/70 px-4 py-4 text-sm leading-7 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-400">
                                            Block này lấy <span class="font-semibold">vùng miền làm tablist</span>. Mỗi tab sẽ tự query dữ liệu live theo vùng miền, còn phần card bên phải có thể chuyển giữa <span class="font-semibold">Điểm đến</span> hoặc <span class="font-semibold">Chủ đề tour</span>. Mobile hiển thị tab ở trên, desktop chuyển sang cột trái.
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">CTA vùng miền</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.cta_label" placeholder="Xem hub vùng miền" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2 md:col-span-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả block</label>
                                                <textarea rows="4" wire:model.defer="form.blocks.{{ $blockIndex }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Loại card trong tab</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.card_source_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    @foreach ($regionTaxonomyCardTypes as $cardType => $cardTypeLabel)
                                                        <option value="{{ $cardType }}">{{ $cardTypeLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">CTA trên card</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.card_cta_label" placeholder="Để trống sẽ tự lấy theo loại card" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Scope</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.scope" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    <option value="">Tất cả</option>
                                                    @foreach ($scopeOptions as $scopeValue => $scopeLabel)
                                                        <option value="{{ $scopeValue }}">{{ $scopeLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số tab vùng miền</label>
                                                <input type="number" min="1" max="8" wire:model.defer="form.blocks.{{ $blockIndex }}.tab_limit" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Tối đa 8 vùng miền để desktop sidebar vẫn gọn và dễ quét.</p>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số card mỗi tab</label>
                                                <input type="number" min="1" max="8" wire:model.defer="form.blocks.{{ $blockIndex }}.limit" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Giới hạn card dùng chung cho mọi tab vùng miền trong block này.</p>
                                            </div>
                                            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 md:col-span-2">
                                                <input type="checkbox" wire:model.defer="form.blocks.{{ $blockIndex }}.featured" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                                Chỉ lấy vùng miền featured làm tab
                                            </label>
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_TOPIC_RAIL)
                                    <div class="space-y-5">
                                        <div class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50/70 px-4 py-4 text-sm leading-7 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-400">
                                            Block này dùng đúng pattern <span class="font-semibold">Chủ đề tour</span> ở homepage: card icon gọn, chỉ còn ảnh và tiêu đề. Dữ liệu mặc định lấy các chủ đề featured hiện đang dùng ở homepage; field nào để trống thì frontsite sẽ ẩn field đó thay vì tự bơm fallback.
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Eyebrow</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.eyebrow" placeholder="Ví dụ: Khởi đầu từ nhu cầu" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2 md:col-span-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả block</label>
                                                <textarea rows="4" wire:model.defer="form.blocks.{{ $blockIndex }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số lượng card</label>
                                                <input type="number" min="1" max="8" wire:model.defer="form.blocks.{{ $blockIndex }}.limit" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Mặc định lấy các chủ đề featured như homepage, tối đa 8 item để giữ rail gọn.</p>
                                            </div>
                                            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                <input type="checkbox" wire:model.defer="form.blocks.{{ $blockIndex }}.show_navigation" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                                Hiển thị navigator prev / next
                                            </label>
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_TOUR_TAXONOMY_TABS)
                                    <div class="space-y-5">
                                        <div class="rounded-3xl border border-dashed border-zinc-300 bg-zinc-50/70 px-4 py-4 text-sm leading-7 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-950/40 dark:text-zinc-400">
                                            Block này dùng cùng visual family với cụm tab tour nổi bật ở homepage, nhưng mỗi tab có thể trỏ đến một <span class="font-semibold">Vùng miền</span>, <span class="font-semibold">Điểm đến</span> hoặc <span class="font-semibold">Chủ đề tour</span> để kéo danh sách tour live theo taxonomy tương ứng.
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề block</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label CTA đầu block</label>
                                                <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.cta_label" placeholder="Xem danh sách tour" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            </div>
                                            <div class="space-y-2 md:col-span-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả block</label>
                                                <textarea rows="4" wire:model.defer="form.blocks.{{ $blockIndex }}.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Scope</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.scope" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    <option value="">Tất cả</option>
                                                    @foreach ($scopeOptions as $scopeValue => $scopeLabel)
                                                        <option value="{{ $scopeValue }}">{{ $scopeLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Sort</label>
                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.sort" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                    @foreach ($tourSortOptions as $sortOption)
                                                        <option value="{{ $sortOption }}">{{ $sortOption }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số lượng card mỗi tab</label>
                                                <input type="number" min="1" max="12" wire:model.defer="form.blocks.{{ $blockIndex }}.limit" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Mỗi tab dùng chung giới hạn card để giữ layout đồng đều và dễ so sánh.</p>
                                            </div>
                                            <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                <input type="checkbox" wire:model.defer="form.blocks.{{ $blockIndex }}.featured" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                                Chỉ lấy tour featured
                                            </label>
                                        </div>

                                        <div class="space-y-4">
                                            @foreach (($block['tabs'] ?? []) as $tabIndex => $tab)
                                                @php
                                                    $tabSourceType = (string) ($tab['source_type'] ?? 'region');
                                                @endphp

                                                <div wire:key="landing-tour-taxonomy-tab-{{ $blockUuid }}-{{ $tab['uuid'] ?? $tabIndex }}" class="rounded-3xl border border-zinc-200 bg-zinc-50/80 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                                                    <div class="mb-4 flex items-center justify-between gap-3">
                                                        <p class="text-sm font-semibold text-zinc-900 dark:text-white">Tab {{ $tabIndex + 1 }}</p>
                                                        @if ($canEdit)
                                                            <button type="button" wire:click="removeTourTaxonomyTab({{ $blockIndex }}, {{ $tabIndex }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">
                                                                Xóa tab
                                                            </button>
                                                        @endif
                                                    </div>

                                                    <div class="grid gap-4 md:grid-cols-2">
                                                        <div class="space-y-2">
                                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nguồn tab</label>
                                                            <select wire:model.live="form.blocks.{{ $blockIndex }}.tabs.{{ $tabIndex }}.source_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                                <option value="region">Vùng miền</option>
                                                                <option value="destination">Điểm đến</option>
                                                                <option value="tour_category">Chủ đề tour</option>
                                                            </select>
                                                        </div>

                                                        @if ($tabSourceType === 'destination')
                                                            <div class="space-y-2">
                                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Chọn điểm đến</label>
                                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.tabs.{{ $tabIndex }}.source_slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                                    <option value="">Chọn điểm đến</option>
                                                                    @foreach ($destinations as $destination)
                                                                        <option value="{{ $destination->slug }}">{{ $destination->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        @elseif ($tabSourceType === 'tour_category')
                                                            <div class="space-y-2">
                                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Chọn chủ đề tour</label>
                                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.tabs.{{ $tabIndex }}.source_slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                                    <option value="">Chọn chủ đề tour</option>
                                                                    @foreach ($tourCategories as $category)
                                                                        <option value="{{ $category->slug }}">{{ $category->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        @else
                                                            <div class="space-y-2">
                                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Chọn vùng miền</label>
                                                                <select wire:model.defer="form.blocks.{{ $blockIndex }}.tabs.{{ $tabIndex }}.source_slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                                    <option value="">Chọn vùng miền</option>
                                                                    @foreach ($regions as $region)
                                                                        <option value="{{ $region->slug }}">{{ $region->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                        @endif

                                                        <div class="space-y-2">
                                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Label tab</label>
                                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.tabs.{{ $tabIndex }}.label" placeholder="Để trống sẽ dùng tên taxonomy" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                        </div>
                                                        <div class="space-y-2">
                                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề khi tab active</label>
                                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.tabs.{{ $tabIndex }}.title" placeholder="Để trống sẽ dùng tên taxonomy" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                        </div>
                                                        <div class="space-y-2 md:col-span-2">
                                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả tab active</label>
                                                            <textarea rows="4" wire:model.defer="form.blocks.{{ $blockIndex }}.tabs.{{ $tabIndex }}.description" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white" placeholder="Để trống sẽ ưu tiên excerpt của taxonomy hoặc mô tả mặc định."></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach

                                            @if ($canEdit)
                                                <div class="flex flex-wrap justify-end">
                                                    <button type="button" wire:click="addTourTaxonomyTab({{ $blockIndex }})" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                                        <i class="fa-solid fa-plus"></i>
                                                        Thêm tab taxonomy
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_TOUR_LIST)
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Eyebrow</label>
                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.eyebrow" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
                                            <x-admin.quill-editor wire:key="landing-tour-list-description-{{ $blockUuid }}" model="form.blocks.{{ $blockIndex }}.description" :value="$block['description'] ?? ''" rows="3" placeholder="Giới thiệu logic query cho block danh sách tour." />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Danh mục tour</label>
                                            <select wire:model.defer="form.blocks.{{ $blockIndex }}.category_slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                <option value="">Tất cả</option>
                                                @foreach ($tourCategories as $category)
                                                    <option value="{{ $category->slug }}">{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Điểm đến</label>
                                            <select wire:model.defer="form.blocks.{{ $blockIndex }}.destination_slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                <option value="">Tất cả</option>
                                                @foreach ($destinations as $destination)
                                                    <option value="{{ $destination->slug }}">{{ $destination->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Vùng miền</label>
                                            <select wire:model.defer="form.blocks.{{ $blockIndex }}.region_slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                <option value="">Tất cả</option>
                                                @foreach ($regions as $region)
                                                    <option value="{{ $region->slug }}">{{ $region->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Scope</label>
                                            <select wire:model.defer="form.blocks.{{ $blockIndex }}.scope" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                <option value="">Tất cả</option>
                                                @foreach ($scopeOptions as $scopeValue => $scopeLabel)
                                                    <option value="{{ $scopeValue }}">{{ $scopeLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Sort</label>
                                            <select wire:model.defer="form.blocks.{{ $blockIndex }}.sort" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                @foreach ($tourSortOptions as $sortOption)
                                                    <option value="{{ $sortOption }}">{{ $sortOption }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số lượng card</label>
                                            <input type="number" min="1" max="12" wire:model.defer="form.blocks.{{ $blockIndex }}.limit" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Tối đa {{ \App\Support\FrontsiteCardGrid::MAX_ITEMS }} card, tương ứng tối đa {{ \App\Support\FrontsiteCardGrid::MAX_ROWS }} hàng với {{ \App\Support\FrontsiteCardGrid::DESKTOP_COLUMNS }} cột trên desktop.</p>
                                        </div>
                                        <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                            <input type="checkbox" wire:model.defer="form.blocks.{{ $blockIndex }}.featured" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                            Chỉ lấy tour featured
                                        </label>
                                    </div>
                                @endif

                                @if ($blockType === \App\Support\LandingPageBlocks::TYPE_BLOG_LIST)
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Eyebrow</label>
                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.eyebrow" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                                            <input type="text" wire:model.defer="form.blocks.{{ $blockIndex }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                        </div>
                                        <div class="space-y-2 md:col-span-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
                                            <x-admin.quill-editor wire:key="landing-blog-list-description-{{ $blockUuid }}" model="form.blocks.{{ $blockIndex }}.description" :value="$block['description'] ?? ''" rows="3" placeholder="Giới thiệu logic query cho block blog." />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Danh mục blog</label>
                                            <select wire:model.defer="form.blocks.{{ $blockIndex }}.category_slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                <option value="">Tất cả</option>
                                                @foreach ($blogCategories as $category)
                                                    <option value="{{ $category->slug }}">{{ $category->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Sort</label>
                                            <select wire:model.defer="form.blocks.{{ $blockIndex }}.sort" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                                @foreach ($blogSortOptions as $sortOption)
                                                    <option value="{{ $sortOption }}">{{ $sortOption }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Số lượng card</label>
                                            <input type="number" min="1" max="12" wire:model.defer="form.blocks.{{ $blockIndex }}.limit" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Tối đa {{ \App\Support\FrontsiteCardGrid::MAX_ITEMS }} card, tương ứng tối đa {{ \App\Support\FrontsiteCardGrid::MAX_ROWS }} hàng với {{ \App\Support\FrontsiteCardGrid::DESKTOP_COLUMNS }} cột trên desktop.</p>
                                        </div>
                                        <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                            <input type="checkbox" wire:model.defer="form.blocks.{{ $blockIndex }}.featured" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
                                            Chỉ lấy bài featured
                                        </label>
                                    </div>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-12 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                Chưa có block nào. Hãy chọn preset hoặc thêm block mới ở cuối section này.
                            </div>
                        @endforelse
                    </div>

                    @if ($canEdit)
                        <div class="space-y-3">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Thêm block mới</p>

                            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                @foreach ($blockTypes as $blockType => $blockLabel)
                                    <button type="button" wire:click="addBlock('{{ $blockType }}')" class="inline-flex items-center justify-between rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-left text-sm font-semibold text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                        <span>{{ $blockLabel }}</span>
                                        <i class="fa-solid fa-plus text-xs"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
                @else
                    <section class="rounded-3xl border border-dashed border-zinc-300 px-6 py-8 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        Block builder đang tạm ẩn vì landing này dùng chế độ <span class="font-semibold">HTML thủ công</span>. Nếu cần quay lại flow block, đổi lại <span class="font-semibold">Chế độ biên tập</span> ở phần thông tin chung.
                    </section>
                @endif

                <section class="space-y-5 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">SEO</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Schema JSON có thể thêm thủ công nếu landing cần cấu trúc đặc biệt ngoài block contract mặc định.</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Meta title</label>
                            <input type="text" wire:model.defer="form.meta_title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">OG title</label>
                            <input type="text" wire:model.defer="form.og_title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Meta description</label>
                            <x-admin.quill-editor wire:key="landing-meta-description-{{ $selectedId ?? 'new' }}" model="form.meta_description" :value="$form['meta_description'] ?? ''" rows="3" placeholder="Mô tả SEO." />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">OG description</label>
                            <x-admin.quill-editor wire:key="landing-og-description-{{ $selectedId ?? 'new' }}" model="form.og_description" :value="$form['og_description'] ?? ''" rows="3" placeholder="Mô tả Open Graph." />
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Canonical URL</label>
                            <input type="text" wire:model.defer="form.canonical_url" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Robots directive</label>
                            <input type="text" wire:model.defer="form.robots_directive" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Schema JSON</label>
                            <textarea wire:model.defer="form.schema_json" rows="10" class="w-full rounded-2xl border border-zinc-200 bg-zinc-950 px-4 py-3 font-mono text-sm text-white outline-none transition focus:border-teal-400 dark:border-zinc-700">{{ $form['schema_json'] ?? '' }}</textarea>
                        </div>
                    </div>
                </section>

                @if ($canEdit)
                    <x-admin.form-action-bar>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-teal-600 to-cyan-600 px-5 py-3 text-sm font-semibold text-white">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Lưu landing page
                        </button>
                    </x-admin.form-action-bar>
                @endif
            </form>
        </section>
</div>
