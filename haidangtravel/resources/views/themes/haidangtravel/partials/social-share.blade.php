@php
    $shareUrl = trim((string) ($url ?? ($seo['canonical'] ?? url()->current())));
    $shareTitle = trim((string) ($title ?? ($seo['og_title'] ?? $seo['title'] ?? $siteSettings->site_name)));
    $shareText = trim((string) ($description ?? ($seo['og_description'] ?? $seo['description'] ?? $siteSettings->seo_description)));
    $heading = trim((string) ($heading ?? 'Chia sẻ trang này'));
    $intro = trim((string) ($intro ?? 'Gửi nhanh liên kết qua các kênh người dùng Việt Nam thường sử dụng.'));
    $ariaHeading = $heading !== '' ? $heading : 'Chia sẻ trang này';
    $variant = trim((string) ($variant ?? 'panel'));
    $isCompact = $variant === 'compact';
    $containerClass = trim((string) ($containerClass ?? ''));
    $panelClass = $isCompact
        ? trim('frontsite-text-reveal space-y-3 '.$containerClass)
        : trim('theme-panel frontsite-text-reveal p-6 sm:p-7 '.$containerClass);
    $layoutClass = $isCompact
        ? 'space-y-3'
        : 'flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between';
    $headingClass = $isCompact
        ? 'font-heading text-base font-extrabold leading-tight text-slate-950'
        : 'frontsite-h2-compact';
    $introClass = $isCompact
        ? 'text-sm leading-6 text-slate-500'
        : 'text-sm leading-7 text-slate-600';
    $buttonBase = $isCompact
        ? 'inline-flex min-h-10 items-center justify-center gap-2 rounded-[0.85rem] border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-200 hover:bg-orange-50 hover:text-primary focus:outline-none focus:ring-2 focus:ring-orange-200'
        : 'inline-flex min-h-11 items-center justify-center gap-2 rounded-[0.95rem] border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-200 hover:bg-orange-50 hover:text-primary focus:outline-none focus:ring-2 focus:ring-orange-200';
    $iconButtonBase = $isCompact
        ? 'inline-flex size-10 items-center justify-center rounded-[0.85rem] border border-slate-200 bg-white p-0 text-xl font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-orange-200 hover:bg-orange-50 hover:text-primary focus:outline-none focus:ring-2 focus:ring-orange-200'
        : $buttonBase;
    $nativeShareButtonBase = $isCompact ? $iconButtonBase : $buttonBase;
    $zaloLogoPath = asset('images/zalo-footer-logo.svg');
    $facebookShareUrl = 'https://www.facebook.com/sharer/sharer.php?'.http_build_query(['u' => $shareUrl]);
    $zaloShareUrl = 'https://zalo.me/share?'.http_build_query(['u' => $shareUrl]);
    $telegramShareUrl = 'https://t.me/share/url?'.http_build_query(['url' => $shareUrl, 'text' => $shareTitle]);
@endphp

<section
    class="{{ $panelClass }}"
    data-reveal="panel"
    data-social-share
    data-share-title="{{ $shareTitle }}"
    data-share-text="{{ $shareText }}"
    data-share-url="{{ $shareUrl }}"
>
    <div class="{{ $layoutClass }}">
        @unless ($isCompact)
            @if ($heading !== '' || $intro !== '')
                <div class="max-w-2xl space-y-2">
                    @if ($heading !== '')
                        <h2 class="{{ $headingClass }}">{{ $heading }}</h2>
                    @endif
                    @if ($intro !== '')
                    <p class="{{ $introClass }}">{{ $intro }}</p>
                    @endif
                </div>
            @endif
        @endunless

        <div class="flex flex-wrap gap-2" aria-label="{{ $ariaHeading }}">
            <button type="button" class="{{ $nativeShareButtonBase }}" data-social-share-native aria-label="Chia sẻ" title="Chia sẻ">
                <i class="fa-solid fa-share-nodes text-primary" aria-hidden="true"></i>
                @unless ($isCompact)
                    <span>Chia sẻ</span>
                @endunless
            </button>

            <a href="{{ $zaloShareUrl }}" class="{{ $iconButtonBase }}" target="_blank" rel="nofollow noopener noreferrer" aria-label="Chia sẻ qua Zalo" title="Zalo">
                <img src="{{ $zaloLogoPath }}" alt="" class="{{ $isCompact ? 'size-8' : 'size-5' }}" loading="lazy" aria-hidden="true">
                @unless ($isCompact)
                    <span>Zalo</span>
                @endunless
            </a>

            <a href="{{ $facebookShareUrl }}" class="{{ $iconButtonBase }}" target="_blank" rel="nofollow noopener noreferrer" aria-label="Chia sẻ lên Facebook" title="Facebook">
                <i class="fa-brands fa-facebook-f text-[#1877F2]" aria-hidden="true"></i>
                @unless ($isCompact)
                    <span>Facebook</span>
                @endunless
            </a>

            <button type="button" class="{{ $iconButtonBase }}" data-social-share-native aria-label="Chia sẻ qua Instagram" title="Instagram">
                <i class="fa-brands fa-instagram text-[#E4405F]" aria-hidden="true"></i>
                @unless ($isCompact)
                    <span>Instagram</span>
                @endunless
            </button>

            <a href="{{ $telegramShareUrl }}" class="{{ $iconButtonBase }}" target="_blank" rel="nofollow noopener noreferrer" aria-label="Chia sẻ qua Telegram" title="Telegram">
                <i class="fa-brands fa-telegram text-[#229ED9]" aria-hidden="true"></i>
                @unless ($isCompact)
                    <span>Telegram</span>
                @endunless
            </a>

            <button type="button" class="{{ $iconButtonBase }}" data-social-share-copy aria-label="Sao chép link" title="Sao chép link">
                <i class="fa-solid fa-link text-secondary" aria-hidden="true"></i>
                @unless ($isCompact)
                    <span data-social-share-copy-label>Sao chép link</span>
                @endunless
            </button>
        </div>
    </div>

    <p class="sr-only" aria-live="polite" data-social-share-status></p>
</section>
