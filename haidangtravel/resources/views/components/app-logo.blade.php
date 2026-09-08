@props([
    'sidebar' => false,
])

@php
    $siteSettings = app(\App\Services\Cms\SiteSettingsManager::class)->current();
    $logoUrl = $siteSettings->getFirstMediaUrl('logo');
@endphp

@if($sidebar)
    <flux:sidebar.brand name="CMS Haidang Travel" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#2E3A8C] to-[#1d4ed8] text-white shadow-sm ring-1 ring-blue-200/60 dark:ring-blue-400/20">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteSettings->site_name ?: 'CMS Phong Thanh Dat' }}" class="size-full rounded-xl object-contain bg-white p-1">
            @else
                <span class="text-[11px] font-black tracking-[0.28em] text-white translate-x-[0.14em]">Haidang Travel</span>
            @endif
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="CMS Haidang Travel" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-9 items-center justify-center rounded-xl bg-gradient-to-br from-[#2E3A8C] to-[#1d4ed8] text-white shadow-sm ring-1 ring-blue-200/60 dark:ring-blue-400/20">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $siteSettings->site_name ?: 'CMS Phong Thanh Dat' }}" class="size-full rounded-xl object-contain bg-white p-1">
            @else
                <span class="text-[11px] font-black tracking-[0.28em] text-white translate-x-[0.14em]">Haidang Travel</span>
            @endif
        </x-slot>
    </flux:brand>
@endif
