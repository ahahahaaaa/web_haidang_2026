<!DOCTYPE html>
<html lang="vi">
    <head>
        @include('themes.haidangtravel.partials.head')
    </head>
    <body class="frontsite-theme bg-[linear-gradient(180deg,var(--color-bg)_0%,#ffffff_18%,var(--color-bg-soft)_100%)] font-body text-slate-900">
        @include('themes.haidangtravel.partials.tracking-body')
        <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[120] focus:rounded-full focus:bg-secondary focus:px-5 focus:py-3 focus:text-sm focus:font-semibold focus:text-white focus:shadow-xl focus:outline-none focus:ring-2 focus:ring-white">
            Bỏ qua điều hướng & tới nội dung chính
        </a>
        @include('themes.haidangtravel.partials.header')
        @if (filled($siteSettings->after_header_html))
            {!! $siteSettings->after_header_html !!}
        @endif

        <main id="main-content" tabindex="-1">
            @yield('content')
        </main>

        @include('themes.haidangtravel.partials.footer')
        @include('themes.haidangtravel.partials.inquiry-modal')
        @if (filled($siteSettings->end_body_html))
            {!! $siteSettings->end_body_html !!}
        @endif
    </body>
</html>
