<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}" />
@if (request()->routeIs('admin.*'))
    <meta name="robots" content="noindex, nofollow, noarchive" />
@endif
<meta name="admin-media-browser-url" content="{{ route('admin.media.browser.images') }}" />
<meta name="admin-media-manager-url" content="{{ route('admin.media') }}" />
<meta name="admin-media-upload-url" content="{{ route('admin.media.browser.images.upload') }}" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

@php
    $siteSettings ??= app(\App\Services\Cms\SiteSettingsManager::class)->current();
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
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,700|dm-sans:400,500,700" rel="stylesheet" />

@vite(['resources/css/admin.css', 'resources/js/admin.js'])
@fluxAppearance
