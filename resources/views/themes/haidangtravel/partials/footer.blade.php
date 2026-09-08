@php
    $socialLinks = collect(\App\Support\FooterSocialLinks::footerItems($siteSettings));
    $footerLogoMedia = \App\Support\FrontsiteMedia::responsiveUrls($siteSettings, 'logo', 'logo_url');
    $footerLogoUrl = $footerLogoMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? null;
    $footerLogoSmallUrl = $footerLogoMedia[\App\Support\FrontsiteMedia::SIZE_SMALL] ?? $footerLogoUrl;
    $footerBrandName = $siteSettings->company_name ?: $siteSettings->site_name;
    $footerTravelLicense = trim((string) data_get($siteSettings->structured_data, 'company.international_travel_license')) ?: trim((string) config('frontsite_seo.company.international_travel_license_fallback'));

    $footerMenuGroups = collect($footerMenuGroups ?? [
        [
            'title' => 'Đi nhanh',
            'items' => $footerMenu ?? collect(),
        ],
    ])->filter(fn (array $group) => collect($group['items'] ?? [])->isNotEmpty())->values();

    $complianceBadges = collect([
        [
            'label' => 'Đã thông báo Bộ Công Thương',
            'url' => 'http://online.gov.vn/Home/WebDetails/15656',
            'image' => 'https://haidangtravel.com/images/logoSaleNoti.png',
            'class' => 'w-full max-w-[198px]',
            'width' => 198,
            'height' => 75,
        ],
        [
            'label' => 'DMCA.com Protection Status',
            'url' => 'https://www.dmca.com/Protection/Status.aspx?ID=0b22a7b7-ed93-4820-acbf-7bd6d1caaf34',
            'image' => 'https://haidangtravel.com/images/dmca_protected_sml_120m.png',
            'class' => 'w-[121px] max-w-full',
            'width' => 121,
            'height' => 24,
        ],
    ]);
    $supportHeading = \App\Support\FrontsiteSectionHeadings::resolve(
        data_get($siteSettings->structured_data, \App\Support\FrontsiteSectionHeadings::STRUCTURED_DATA_KEY),
        'footer_support',
    );
@endphp

<footer class="mt-20 w-full bg-secondary pb-8 pt-16 text-white">
    <div class="mx-auto grid max-w-7xl grid-cols-1 gap-10 px-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1.1fr)_minmax(0,0.95fr)] lg:gap-12 lg:px-8">
        <div class="max-w-xl">
            @if ($footerLogoUrl)
                <a href="{{ route('home') }}" class="mb-6 block w-full max-w-[280px]" aria-label="Về trang chủ {{ $footerBrandName }}">
                    <picture class="block w-full">
                        @if ($footerLogoSmallUrl)
                            <source media="(max-width: 767px)" srcset="{{ $footerLogoSmallUrl }}">
                        @endif
                        <img
                            src="{{ $footerLogoUrl }}"
                            alt="{{ $footerBrandName }}"
                            class="h-auto w-full object-contain [filter:brightness(0)_saturate(100%)_invert(100%)]"
                            width="280"
                            height="72"
                            loading="lazy"
                            decoding="async"
                        >
                    </picture>
                </a>
            @else
                <div class="mb-6 font-heading text-2xl font-bold text-white">{{ $footerBrandName }}</div>
            @endif
            <p class="mb-6 text-sm leading-relaxed text-slate-200">{{ strip_tags((string) ($siteSettings->about_summary ?: $siteSettings->site_description)) }}</p>

            <div class="space-y-3 text-sm text-slate-200">
                <p class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center text-orange-100">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                    </span>
                    <span>{{ $siteSettings->address ?: 'Địa chỉ đang được cập nhật' }}</span>
                </p>
                <p class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center text-orange-100">
                        <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    </span>
                    <span>{{ $siteSettings->phone }} @if ($siteSettings->hotline) · {{ $siteSettings->hotline }} @endif</span>
                </p>
                <p class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center text-orange-100">
                        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    </span>
                    <span>{{ $siteSettings->primary_email }}</span>
                </p>
                <p class="flex items-start gap-3">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center text-orange-100">
                        <i class="fa-solid fa-file-shield" aria-hidden="true"></i>
                    </span>
                    <span>Giấy phép lữ hành: {{ $footerTravelLicense ?: 'Đang cập nhật' }}</span>
                </p>
            </div>
        </div>

        @if ($footerMenuGroups->isNotEmpty())
            <div class="grid gap-8 {{ $footerMenuGroups->count() > 1 ? 'grid-cols-2' : 'grid-cols-1' }}">
                @foreach ($footerMenuGroups as $group)
                    <div>
                        <h2 class="mb-6 text-base font-semibold text-white">{{ $group['title'] }}</h2>
                        <ul class="flex flex-col gap-3">
                            @foreach ($group['items'] as $item)
                                <li>
                                    <a
                                        href="{{ \App\Support\FrontsiteUrls::cleanInternalUrl($item->url) ?? $item->url }}"
                                        @if ($item->target)
                                            target="{{ $item->target }}"
                                        @endif
                                        @if ($item->target === '_blank')
                                            rel="noopener noreferrer"
                                        @endif
                                        class="text-sm text-slate-200 transition-colors hover:text-orange-100"
                                    >
                                        {{ $item->label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif

        <div>
            @if ($supportHeading['is_visible'] && $supportHeading['title'] !== '')
                <h2 class="mb-6 text-base font-semibold text-white">{{ $supportHeading['title'] }}</h2>
            @endif

            @if ($socialLinks->isNotEmpty())
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1">
                    @foreach ($socialLinks as $social)
                        <a
                            href="{{ $social['url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="{{ $social['label'] }}"
                            title="{{ $social['label'] }}"
                            class="group inline-flex min-h-11 w-full items-center gap-2.5 rounded-xl border border-white/15 bg-white px-3 py-2 text-secondary shadow-sm shadow-black/5 transition duration-200 hover:-translate-y-0.5 hover:border-primary/60 hover:bg-primary-soft focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
                        >
                            <span class="inline-flex shrink-0 items-center justify-center text-lg leading-none text-primary">
                                @if (! empty($social['image']))
                                    <img
                                        src="{{ asset($social['image']) }}"
                                        alt=""
                                        class="h-5 w-auto"
                                        width="50"
                                        height="50"
                                        loading="lazy"
                                        decoding="async"
                                        aria-hidden="true"
                                    >
                                @else
                                    <i class="{{ $social['icon'] ?? 'fa-brands fa-facebook-f' }}" aria-hidden="true"></i>
                                @endif
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-bold leading-5 text-secondary">{{ $social['label'] }}</span>
                                @if (! empty($social['description']))
                                    <span class="block truncate text-xs font-medium leading-5 text-slate-500">{{ $social['description'] }}</span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="mt-6 flex flex-col gap-3">
                @foreach ($complianceBadges as $badge)
                    <a
                        href="{{ $badge['url'] }}"
                        target="_blank"
                        rel="noopener noreferrer nofollow"
                        aria-label="{{ $badge['label'] }}"
                        title="{{ $badge['label'] }}"
                        class="inline-flex w-fit"
                    >
                        <img
                            src="{{ $badge['image'] }}"
                            alt="{{ $badge['label'] }}"
                            class="{{ $badge['class'] }} h-auto"
                            width="{{ $badge['width'] }}"
                            height="{{ $badge['height'] }}"
                            loading="lazy"
                            decoding="async"
                        >
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mx-auto mt-12 max-w-7xl border-t border-white/10 px-6 pt-8 text-sm text-slate-300 lg:px-8">
        {{ $siteSettings->copyright_text ?: '© Haidang Travel' }}
    </div>
</footer>
