<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Cấu hình theme và SEO</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý theme, thông tin doanh nghiệp, social, email và các media quan trọng.</p>
        </div>
    </div>

    <x-admin.form-feedback />

    <form
        wire:submit="save"
        x-data="{ activeThemeSettingsTab: 'general' }"
        class="space-y-6 pb-32 md:pb-6"
        data-admin-feedback-form
        data-admin-loading-text="Đang lưu cấu hình theme và SEO..."
    >
        <section class="rounded-3xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap gap-2" role="tablist" aria-label="Nhóm cấu hình theme">
                <button
                    type="button"
                    role="tab"
                    data-theme-settings-tab="general"
                    :aria-selected="activeThemeSettingsTab === 'general'"
                    @click="activeThemeSettingsTab = 'general'"
                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold transition"
                    :class="activeThemeSettingsTab === 'general' ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300' : 'border-transparent text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white'"
                >
                    <i class="fa-solid fa-sliders"></i>
                    Cấu hình chung
                </button>

                <button
                    type="button"
                    role="tab"
                    data-theme-settings-tab="frontsite-headings"
                    :aria-selected="activeThemeSettingsTab === 'frontsite-headings'"
                    @click="activeThemeSettingsTab = 'frontsite-headings'"
                    class="inline-flex items-center gap-2 rounded-2xl border px-4 py-3 text-sm font-semibold transition"
                    :class="activeThemeSettingsTab === 'frontsite-headings' ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300' : 'border-transparent text-zinc-600 hover:bg-zinc-50 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white'"
                >
                    <i class="fa-solid fa-heading"></i>
                    Heading section frontsite
                </button>
            </div>
        </section>

        <div x-show="activeThemeSettingsTab === 'general'" class="grid gap-6">
        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Media chính</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Dàn ảnh nhận diện chính theo một hàng full-width để biên tập nhanh logo, favicon và OG image.</p>
            </div>

            <div class="grid gap-5 xl:grid-cols-3">
                <div class="space-y-4">
                    <x-admin.image-dropzone label="Logo" model="logoUpload" :preview="$logoUpload ? $logoUpload->temporaryUrl() : (($selectedLogoLibraryMedia?->getUrl()) ?: $settings->getFirstMediaUrl('logo'))" />
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            data-admin-media-picker-trigger
                            data-pick-method="selectLibraryMediaForUpload"
                            data-pick-target="logoUpload"
                            data-button-label="Chọn ảnh này làm logo"
                            class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                        >
                            <i class="fa-regular fa-images"></i>
                            Chọn từ Media popup
                        </button>

                        @if ($selectedLogoLibraryMedia)
                            <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                <i class="fa-solid fa-circle-check"></i>
                                Đã chọn logo từ thư viện
                            </div>

                            <button type="button" wire:click="clearLibraryMediaSelectionForUpload('logoUpload')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                <i class="fa-solid fa-rotate-left"></i>
                                Bỏ chọn ảnh thư viện
                            </button>
                        @endif
                    </div>
                </div>

                <div class="space-y-4">
                    <x-admin.image-dropzone label="Favicon" model="faviconUpload" :preview="$faviconUpload ? $faviconUpload->temporaryUrl() : (($selectedFaviconLibraryMedia?->getUrl()) ?: $settings->getFirstMediaUrl('favicon'))" />
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            data-admin-media-picker-trigger
                            data-pick-method="selectLibraryMediaForUpload"
                            data-pick-target="faviconUpload"
                            data-button-label="Chọn ảnh này làm favicon"
                            class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                        >
                            <i class="fa-regular fa-images"></i>
                            Chọn từ Media popup
                        </button>

                        @if ($selectedFaviconLibraryMedia)
                            <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                <i class="fa-solid fa-circle-check"></i>
                                Đã chọn favicon từ thư viện
                            </div>

                            <button type="button" wire:click="clearLibraryMediaSelectionForUpload('faviconUpload')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                <i class="fa-solid fa-rotate-left"></i>
                                Bỏ chọn ảnh thư viện
                            </button>
                        @endif
                    </div>
                </div>

                <div class="space-y-4">
                    <x-admin.image-dropzone label="OG Image" model="ogImageUpload" :preview="$ogImageUpload ? $ogImageUpload->temporaryUrl() : (($selectedOgImageLibraryMedia?->getUrl()) ?: $settings->getFirstMediaUrl('og_image'))" />
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            type="button"
                            data-admin-media-picker-trigger
                            data-pick-method="selectLibraryMediaForUpload"
                            data-pick-target="ogImageUpload"
                            data-button-label="Chọn ảnh này làm OG image"
                            class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                        >
                            <i class="fa-regular fa-images"></i>
                            Chọn từ Media popup
                        </button>

                        @if ($selectedOgImageLibraryMedia)
                            <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                                <i class="fa-solid fa-circle-check"></i>
                                Đã chọn OG image từ thư viện
                            </div>

                            <button type="button" wire:click="clearLibraryMediaSelectionForUpload('ogImageUpload')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                <i class="fa-solid fa-rotate-left"></i>
                                Bỏ chọn ảnh thư viện
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Thông tin hệ thống</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Tên site, theme đang dùng và mô tả tổng quan.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tên website</label>
                        <input type="text" wire:model.defer="form.site_name" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.site_name') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tagline</label>
                        <input type="text" wire:model.defer="form.site_tagline" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Theme đang dùng</label>
                        <select wire:model.defer="form.active_theme" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            <option value="haidangtravel">haidangtravel</option>
                        </select>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả ngắn</label>
                        <x-admin.quill-editor wire:key="theme-site-description-{{ md5((string) ($form['site_description'] ?? '')) }}" model="form.site_description" :value="$form['site_description'] ?? ''" rows="4" />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tóm tắt doanh nghiệp</label>
                        <x-admin.quill-editor wire:key="theme-about-summary-{{ md5((string) ($form['about_summary'] ?? '')) }}" model="form.about_summary" :value="$form['about_summary'] ?? ''" rows="4" />
                    </div>
                </div>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            @php
                $cacheEnabled = (bool) data_get($frontsiteCacheStatus, 'enabled');
                $responseCacheEnabled = (bool) data_get($frontsiteCacheStatus, 'response_enabled');
                $clearSucceeded = data_get($frontsiteCacheClearResult, 'status') === 'success';
            @endphp

            <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Cache frontsite</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Theo dõi trạng thái cache public và xóa cache frontsite khi cần cập nhật nóng nội dung hiển thị.</p>
                </div>

                <button
                    type="button"
                    wire:click="clearFrontsiteCache"
                    wire:loading.attr="disabled"
                    wire:target="clearFrontsiteCache"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-red-200 px-5 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-70 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10"
                >
                    <i class="fa-solid fa-broom" wire:loading.remove wire:target="clearFrontsiteCache"></i>
                    <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="clearFrontsiteCache"></i>
                    <span wire:loading.remove wire:target="clearFrontsiteCache">Xóa cache frontsite</span>
                    <span wire:loading wire:target="clearFrontsiteCache">Đang xóa...</span>
                </button>
            </div>

            @error('frontsiteCache')
                <p class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300">{{ $message }}</p>
            @enderror

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Data cache</p>
                    <p class="mt-2 text-base font-semibold {{ $cacheEnabled ? 'text-emerald-700 dark:text-emerald-300' : 'text-zinc-500 dark:text-zinc-400' }}">{{ data_get($frontsiteCacheStatus, 'enabled_label', 'Không rõ') }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">FRONTSITE_CACHE_ENABLED</p>
                </div>

                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">HTML response</p>
                    <p class="mt-2 text-base font-semibold {{ $responseCacheEnabled ? 'text-emerald-700 dark:text-emerald-300' : 'text-zinc-500 dark:text-zinc-400' }}">{{ data_get($frontsiteCacheStatus, 'response_enabled_label', 'Không rõ') }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ data_get($frontsiteCacheStatus, 'header', 'X-Frontsite-Cache') }}</p>
                </div>

                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Cache store</p>
                    <p class="mt-2 text-base font-semibold text-zinc-900 dark:text-white">{{ data_get($frontsiteCacheStatus, 'store', '-') }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Driver: {{ data_get($frontsiteCacheStatus, 'driver', '-') }}</p>
                </div>

                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">TTL HTML</p>
                    <p class="mt-2 text-base font-semibold text-zinc-900 dark:text-white">{{ data_get($frontsiteCacheStatus, 'ttl_labels.response', '-') }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Query: {{ data_get($frontsiteCacheStatus, 'ttl_labels.query', '-') }}</p>
                </div>
            </div>

            <div class="mt-4 grid gap-3 md:grid-cols-3">
                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Stale sitemap</p>
                    <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ data_get($frontsiteCacheStatus, 'stale_enabled_label', 'Không rõ') }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">TTL sitemap: {{ data_get($frontsiteCacheStatus, 'ttl_labels.sitemap', '-') }}</p>
                </div>

                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Version global</p>
                    <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">all = {{ data_get($frontsiteCacheStatus, 'global_version', 1) }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ data_get($frontsiteCacheStatus, 'tracked_group_count', 0) }} nhóm đang có version riêng</p>
                </div>

                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/50">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Kiểm tra lúc</p>
                    <p class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">{{ data_get($frontsiteCacheStatus, 'checked_at_label', '-') }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Chỉ áp dụng cho frontsite public.</p>
                </div>
            </div>

            @if ($frontsiteCacheClearResult)
                <div class="mt-4 rounded-2xl border px-4 py-3 text-sm {{ $clearSucceeded ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300' : 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300' }}">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <p class="font-semibold">{{ data_get($frontsiteCacheClearResult, 'message') }}</p>
                        <p class="text-xs opacity-80">{{ data_get($frontsiteCacheClearResult, 'cleared_at_label') }}</p>
                    </div>

                    @if ($clearSucceeded)
                        <p class="mt-2 text-xs opacity-80">
                            Nhóm đã xóa: {{ implode(', ', data_get($frontsiteCacheClearResult, 'groups', [])) }}.
                            Version all: {{ data_get($frontsiteCacheClearResult, 'before.all', 1) }} → {{ data_get($frontsiteCacheClearResult, 'after.all', 1) }}.
                        </p>
                    @endif
                </div>
            @elseif (data_get($frontsiteCacheStatus, 'last_clear_label'))
                <div class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950/50 dark:text-zinc-300">
                    Lần xóa gần nhất: {{ data_get($frontsiteCacheStatus, 'last_clear_label') }}
                    @if (data_get($frontsiteCacheStatus, 'last_clear_groups_label'))
                        với nhóm {{ data_get($frontsiteCacheStatus, 'last_clear_groups_label') }}.
                    @endif
                </div>
            @endif
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Liên hệ và social</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Thông tin hiển thị ở header, footer và contact card.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ([
                        'company_name' => 'Tên công ty',
                        'address' => 'Địa chỉ',
                        'phone' => 'Điện thoại',
                        'hotline' => 'Hotline',
                        'primary_email' => 'Email chính',
                        'support_email' => 'Email hỗ trợ',
                        'sales_email' => 'Email kinh doanh',
                        'mail_from_name' => 'Mail from name',
                        'mail_from_address' => 'Mail from address',
                        'mail_contact_recipient' => 'Email nhận liên hệ',
                        'facebook_url' => 'Facebook URL',
                        'youtube_url' => 'YouTube URL',
                        'tiktok_url' => 'TikTok URL',
                        'instagram_url' => 'Instagram URL',
                        'zalo_url' => 'Zalo URL',
                        'messenger_url' => 'Messenger URL',
                        'linkedin_url' => 'LinkedIn URL',
                        'map_embed_url' => 'Google Maps embed URL',
                    ] as $field => $label)
                        <div class="space-y-2 {{ $field === 'address' || $field === 'map_embed_url' ? 'md:col-span-2' : '' }}">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</label>
                            <input type="text" wire:model.defer="form.{{ $field }}" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @if ($field === 'map_embed_url')
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Dán link Google Maps, link chia sẻ vị trí, hoặc mã iframe Embed a map. Hệ thống sẽ tự chuyển về URL nhúng an toàn cho trang Liên hệ.</p>
                            @endif
                            @error("form.$field") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Số liệu và SEO mặc định</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Dữ liệu trust metrics và metadata mặc định cho toàn site.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ([
                        'experience_years' => 'Số năm kinh nghiệm',
                        'completed_projects_count' => 'Số dự án hoàn thành',
                        'team_size' => 'Quy mô nhân sự',
                        'quality_badge_label' => 'Nhãn chất lượng',
                        'copyright_text' => 'Dòng copyright',
                        'seo_title' => 'SEO title mặc định',
                        'seo_keywords' => 'SEO keywords',
                        'seo_robots' => 'Robots',
                    ] as $field => $label)
                        <div class="space-y-2">
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</label>
                            <input type="text" wire:model.defer="form.{{ $field }}" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            @error("form.$field") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                    @endforeach

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">SEO description mặc định</label>
                        <x-admin.quill-editor wire:key="theme-seo-description-{{ md5((string) ($form['seo_description'] ?? '')) }}" model="form.seo_description" :value="$form['seo_description'] ?? ''" rows="4" />
                    </div>
                </div>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Quy định điều khoản tour mẫu</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Khối này là mẫu mặc định trong theme setting. Tour detail sẽ ưu tiên điều khoản riêng của từng tour, chỉ fallback về mẫu này khi tour chưa khai báo điều khoản.</p>
                </div>

                <div class="space-y-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                        <input type="text" wire:model.defer="form.tour_terms_title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.tour_terms_title') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Danh sách điều khoản</h3>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Mỗi mục gồm tiêu đề và nội dung, sẽ bung/mở theo dạng collapse ngoài frontsite.</p>
                    </div>

                    <div class="space-y-4">
                        @foreach (($form['tour_terms_items'] ?? []) as $index => $item)
                            <div wire:key="tour-term-item-{{ $index }}" class="rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                                <div class="mb-4 flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Điều khoản {{ $loop->iteration }}</p>

                                    @if (count($form['tour_terms_items'] ?? []) > 1)
                                        <button type="button" wire:click="removeTourTermItem({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                            Xóa
                                        </button>
                                    @endif
                                </div>

                                <div class="grid gap-4">
                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Title</label>
                                        <input type="text" wire:model.defer="form.tour_terms_items.{{ $index }}.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    </div>

                                    <div class="space-y-2">
                                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Content</label>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Có thể bôi đen nội dung để chèn link liên kết.</p>
                                        <x-admin.quill-editor
                                            wire:key="tour-term-content-{{ $index }}"
                                            model="form.tour_terms_items.{{ $index }}.content"
                                            :value="$item['content'] ?? ''"
                                            mode="rich"
                                            rows="5"
                                            placeholder="Nhập nội dung điều khoản, quy định hoặc lưu ý áp dụng chung cho tour."
                                        />
                                        @error("form.tour_terms_items.$index.content") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-wrap justify-end">
                        <button type="button" wire:click="addTourTermItem" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                            Thêm điều khoản
                        </button>
                    </div>
                </div>
        </section>

        </div>

        <div x-show="activeThemeSettingsTab === 'frontsite-headings'" style="display: none;" class="grid gap-6">
        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            @php($frontsiteHeadingGroups = \App\Support\FrontsiteSectionHeadings::definitions())

            <div class="mb-5">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Heading section frontsite</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Cho phép đổi tiêu đề, mô tả hoặc ẩn phần heading của các section đang cố định trong theme. Có thể dùng placeholder <code class="rounded bg-zinc-100 px-1 py-0.5 text-xs dark:bg-zinc-800">{title}</code> cho tiêu đề trang hiện tại.</p>
            </div>

            <div class="space-y-5">
                @foreach ($frontsiteHeadingGroups as $groupKey => $group)
                    <div wire:key="frontsite-heading-group-{{ $groupKey }}" class="rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ $group['label'] }}</h3>

                        <div class="mt-4 grid gap-4 xl:grid-cols-2">
                            @foreach (($group['items'] ?? []) as $headingKey => $headingDefinition)
                                @php($headingHasDescription = array_key_exists('description', $headingDefinition))

                                <div wire:key="frontsite-heading-{{ $headingKey }}" class="rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $headingDefinition['label'] }}</p>
                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $headingKey }}</p>
                                        </div>

                                        <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                            <input type="checkbox" wire:model.defer="form.frontsite_section_headings.{{ $headingKey }}.is_visible" class="rounded border-zinc-300 text-red-600 focus:ring-red-500 dark:border-zinc-700 dark:bg-zinc-900">
                                            Hiện heading
                                        </label>
                                    </div>

                                    <div class="mt-4 space-y-3">
                                        <div class="space-y-2">
                                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                                            <input type="text" wire:model.defer="form.frontsite_section_headings.{{ $headingKey }}.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                            @error("form.frontsite_section_headings.$headingKey.title") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                        </div>

                                        @if ($headingHasDescription)
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
                                                <textarea wire:model.defer="form.frontsite_section_headings.{{ $headingKey }}.description" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                                                @error("form.frontsite_section_headings.$headingKey.description") <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        </div>

        <div x-show="activeThemeSettingsTab === 'general'" class="grid gap-6">
        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Tracking sitewide</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Các mã này được nhúng tự động trên toàn bộ frontsite public, không áp dụng cho admin CMS.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">GA4 Measurement ID</label>
                        <input type="text" wire:model.defer="form.ga_measurement_id" placeholder="Ví dụ: G-AB12C34DEF" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm uppercase outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Dùng cho Google Analytics 4 sitewide. Chỉ nhập mã dạng `G-XXXXXXXXXX`.</p>
                        @error('form.ga_measurement_id') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Facebook Pixel ID</label>
                        <input type="text" wire:model.defer="form.facebook_pixel_id" placeholder="Ví dụ: 123456789012345" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Dùng cho Meta Pixel sitewide. Chỉ nhập chuỗi số của Pixel ID.</p>
                        @error('form.facebook_pixel_id') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">After header HTML</label>
                        <textarea
                            wire:model.defer="form.after_header_html"
                            rows="7"
                            spellcheck="false"
                            placeholder="<div data-sitewide-banner>...</div>"
                            class="w-full rounded-2xl border border-zinc-200 bg-zinc-950 px-4 py-3 font-mono text-xs leading-6 text-zinc-100 outline-none transition placeholder:text-zinc-500 focus:border-red-400 dark:border-zinc-700"
                        ></textarea>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">HTML thô được render ngay sau header của theme `haidangtravel` trên toàn bộ frontsite public.</p>
                        @error('form.after_header_html') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">End body HTML</label>
                        <textarea
                            wire:model.defer="form.end_body_html"
                            rows="7"
                            spellcheck="false"
                            placeholder="<script>...</script>"
                            class="w-full rounded-2xl border border-zinc-200 bg-zinc-950 px-4 py-3 font-mono text-xs leading-6 text-zinc-100 outline-none transition placeholder:text-zinc-500 focus:border-red-400 dark:border-zinc-700"
                        ></textarea>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">HTML thô được render ngay trước thẻ đóng `&lt;/body&gt;`. Chỉ dùng cho mã đã tin cậy vì nội dung sẽ không được escape.</p>
                        @error('form.end_body_html') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Schema trang chủ</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Bổ sung dữ liệu cho `Organization`, `LocalBusiness` và `PostalAddress` trên trang chủ.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Ảnh schema doanh nghiệp</label>
                        <input type="text" wire:model.defer="form.structured_data.organization.image_url" placeholder="https://example.com/schema-image.jpg" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Dùng cho field `image` của `Organization`. Để trống nếu muốn fallback sang OG image, rồi đến logo hiện có.</p>
                        @error('form.structured_data.organization.image_url') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Price range</label>
                        <input type="text" wire:model.defer="form.structured_data.local_business.price_range" placeholder="Ví dụ: $$ hoặc Từ 2.500.000đ/m²" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.structured_data.local_business.price_range') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Postal code</label>
                        <input type="text" wire:model.defer="form.structured_data.address.postal_code" placeholder="Ví dụ: 700000" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.structured_data.address.postal_code') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Street address</label>
                        <input type="text" wire:model.defer="form.structured_data.address.street_address" placeholder="Ví dụ: 123 Đường Mẫu" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.structured_data.address.street_address') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Địa phương / thành phố</label>
                        <input type="text" wire:model.defer="form.structured_data.address.address_locality" placeholder="Ví dụ: Thành phố Hồ Chí Minh" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.structured_data.address.address_locality') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tỉnh / vùng</label>
                        <input type="text" wire:model.defer="form.structured_data.address.address_region" placeholder="Ví dụ: Hồ Chí Minh" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.structured_data.address.address_region') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Quốc gia</label>
                        <input type="text" wire:model.defer="form.structured_data.address.address_country" placeholder="Ví dụ: VN hoặc Việt Nam" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Các field địa chỉ này được dùng cho `PostalAddress` của `Organization` và `LocalBusiness` ngoài frontsite.</p>
                        @error('form.structured_data.address.address_country') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
        </section>
        </div>

        <div>
            <x-admin.form-action-bar>
                <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600 disabled:cursor-not-allowed disabled:opacity-70">
                    <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="save"></i>
                    <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="save"></i>
                    Lưu
                </button>
            </x-admin.form-action-bar>
        </div>
    </form>
</div>
