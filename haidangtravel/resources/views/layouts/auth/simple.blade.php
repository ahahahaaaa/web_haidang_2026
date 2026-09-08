@php
    $siteSettings = app(\App\Services\Cms\SiteSettingsManager::class)->current();
    $logoMedia = \App\Support\FrontsiteMedia::responsiveUrls($siteSettings, 'logo', 'logo_url');
    $logoUrl = $logoMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? null;
    $brandName = trim((string) ($siteSettings->company_name ?: $siteSettings->site_name ?: config('app.name', 'Haidang Travel')));
    $brandTagline = trim((string) ($siteSettings->site_tagline ?: 'Hệ thống quản trị nội dung du lịch'));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-4">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-3 text-center font-medium" wire:navigate>
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="max-h-full max-w-full object-contain">
                    @else
                        <span class="text-sm font-black tracking-[0.18em] text-orange-600">HĐT</span>
                    @endif
                    <span class="space-y-1">
                        <span class="block text-base font-semibold text-zinc-900 dark:text-white">{{ $brandName }}</span>
                        <span class="block text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $brandTagline }}</span>
                    </span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
