@php
    $zaloLogoPath = asset('images/zalo-footer-logo.svg');

    $socialLinks = collect([
        ['label' => 'Zalo', 'url' => $siteSettings->zalo_url, 'type' => 'zalo'],
        ['label' => 'Facebook', 'url' => $siteSettings->facebook_url, 'icon' => 'fa-brands fa-facebook-f'],
        ['label' => 'YouTube', 'url' => $siteSettings->youtube_url, 'icon' => 'fa-brands fa-youtube'],
        ['label' => 'TikTok', 'url' => $siteSettings->tiktok_url, 'icon' => 'fa-brands fa-tiktok'],
        ['label' => 'Instagram', 'url' => $siteSettings->instagram_url, 'icon' => 'fa-brands fa-instagram'],
    ])->filter(fn ($social) => filled($social['url']))->values();

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
            <div class="mb-6 font-heading text-2xl font-bold text-white">{{ $siteSettings->company_name ?: $siteSettings->site_name }}</div>
            <p class="mb-6 text-sm leading-relaxed text-slate-200">{{ strip_tags((string) ($siteSettings->about_summary ?: $siteSettings->site_description)) }}</p>

            <div class="space-y-2 text-sm text-slate-200">
                <p>{{ $siteSettings->address ?: 'Địa chỉ đang được cập nhật' }}</p>
                <p>{{ $siteSettings->phone }} @if ($siteSettings->hotline) · {{ $siteSettings->hotline }} @endif</p>
                <p>{{ $siteSettings->primary_email }}</p>
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
                                        href="{{ $item->url }}"
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
            <div class="space-y-3 text-sm text-slate-200">
                <p>Đặt tour: {{ $siteSettings->phone }}</p>
                <p>Tư vấn: {{ $siteSettings->hotline ?: $siteSettings->phone }}</p>
                <p>Email: {{ $siteSettings->mail_contact_recipient ?: $siteSettings->primary_email }}</p>
            </div>

            @if ($socialLinks->isNotEmpty())
                <div class="mt-6 flex flex-wrap gap-3">
                    @foreach ($socialLinks as $social)
                        <a
                            href="{{ $social['url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label="{{ $social['label'] }}"
                            title="{{ $social['label'] }}"
                            class="{{ ($social['type'] ?? null) === 'zalo'
                                ? 'inline-flex min-h-11 items-center justify-center px-1 transition duration-200 hover:-translate-y-0.5'
                                : 'inline-flex h-11 w-11 items-center justify-center rounded-full border border-white/15 bg-white/8 text-lg text-white transition duration-200 hover:-translate-y-0.5 hover:border-white/30 hover:bg-white/14' }}"
                        >
                            @if (($social['type'] ?? null) === 'zalo')
                                <img
                                    src="{{ $zaloLogoPath }}"
                                    alt=""
                                    class="h-11 w-auto"
                                    width="50"
                                    height="50"
                                    loading="lazy"
                                    decoding="async"
                                >
                            @else
                                <i class="{{ $social['icon'] }}" aria-hidden="true"></i>
                            @endif
                            <span class="sr-only">{{ $social['label'] }}</span>
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
