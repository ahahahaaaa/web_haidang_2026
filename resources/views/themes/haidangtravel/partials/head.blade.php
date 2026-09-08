<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<noscript>
    <style>.frontsite-theme [data-tour-details-expandable] { max-height: none !important; }</style>
</noscript>
<meta name="csrf-token" content="{{ csrf_token() }}">
@php
    $canonicalUrl = \App\Support\FrontsiteUrls::canonicalUrl($seo['canonical'] ?? url()->current());
@endphp
<title>{{ $seo['title'] ?? $siteSettings->site_name }}</title>
<meta name="description" content="{{ $seo['description'] ?? $siteSettings->seo_description }}">
<meta name="keywords" content="{{ $seo['keywords'] ?? $siteSettings->seo_keywords }}">
<meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
<meta name="theme-color" content="#FF6A00">
<meta property="og:site_name" content="{{ $siteSettings->site_name }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

<meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
<meta property="og:title" content="{{ $seo['og_title'] ?? $seo['title'] ?? $siteSettings->site_name }}">
<meta property="og:description" content="{{ $seo['og_description'] ?? $seo['description'] ?? $siteSettings->seo_description }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:locale" content="vi">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seo['og_title'] ?? $seo['title'] ?? $siteSettings->site_name }}">
<meta name="twitter:description" content="{{ $seo['og_description'] ?? $seo['description'] ?? $siteSettings->seo_description }}">
@if (! empty($seo['og_image']))
    <meta property="og:image" content="{{ $seo['og_image'] }}">
    <meta name="twitter:image" content="{{ $seo['og_image'] }}">
@endif

@php
    $faviconUrl = \App\Support\FrontsiteMedia::modelUrl(
        $siteSettings,
        'favicon',
        \App\Support\FrontsiteMedia::SIZE_FULL,
        'favicon_url',
    );
@endphp

@if ($faviconUrl)
    <link rel="icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
@endif

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@php
    $frontsiteFontHref = 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap';
@endphp
<link rel="preload" href="{{ $frontsiteFontHref }}" as="style">
<link rel="stylesheet" href="{{ $frontsiteFontHref }}" media="print" onload="this.media='all'">
<noscript>
    <link rel="stylesheet" href="{{ $frontsiteFontHref }}">
</noscript>

@vite(['resources/css/frontsite.css', 'resources/js/frontsite.js'])

@include('themes.haidangtravel.partials.tracking-head')

@if (! empty($seo['schema']))
    <script type="application/ld+json">{!! json_encode($seo['schema'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif