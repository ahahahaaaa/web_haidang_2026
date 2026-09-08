@props(['groupKey'])

@php
    $group = \App\Support\Admin\AdminNavigationRegistry::group($groupKey);
    $actions = \App\Support\Admin\AdminNavigationRegistry::visibleActions($groupKey, auth()->user());
    $currentRouteName = request()->route()?->getName();
    $groupIconMap = [
        'map' => 'fa-solid fa-map-location-dot',
        'briefcase' => 'fa-solid fa-briefcase',
        'newspaper' => 'fa-solid fa-newspaper',
        'rectangle-stack' => 'fa-solid fa-layer-group',
        'photo' => 'fa-regular fa-images',
        'chat-bubble-left-right' => 'fa-regular fa-comments',
        'cog-6-tooth' => 'fa-solid fa-sliders',
        'users' => 'fa-solid fa-users',
    ];
@endphp

@if ($group && $actions !== [])
    <section x-data="{ open: true }" class="rounded-[26px] border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/70">
        <button
            type="button"
            x-on:click="open = !open"
            x-bind:aria-expanded="open"
            class="flex w-full items-center justify-between rounded-2xl px-3 py-2.5 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-800/80"
        >
            <span class="flex items-center gap-3">
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <i class="{{ $groupIconMap[$group['icon']] ?? 'fa-regular fa-folder' }}"></i>
                </span>
                <span>
                    <span class="block text-sm font-semibold text-zinc-900 dark:text-white">{{ $group['label'] }}</span>
                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">Điều hướng nhanh trong cùng nhóm quản trị.</span>
                </span>
            </span>

            <i class="fa-solid fa-chevron-down text-[11px] text-zinc-500 transition dark:text-zinc-400" :class="open ? 'rotate-180' : ''"></i>
        </button>

        <div x-show="open" x-cloak class="mt-2 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($actions as $action)
                <a
                    href="{{ route($action['route']) }}"
                    wire:navigate
                    class="{{ \App\Support\Admin\AdminNavigationRegistry::actionIsCurrent($action, $currentRouteName) ? 'bg-teal-50 text-teal-700 ring-1 ring-teal-200 dark:bg-teal-500/10 dark:text-teal-300 dark:ring-teal-500/30' : 'bg-zinc-50 text-zinc-700 ring-1 ring-zinc-200 transition hover:text-teal-700 hover:ring-teal-200 dark:bg-zinc-950/40 dark:text-zinc-200 dark:ring-zinc-800 dark:hover:text-teal-300 dark:hover:ring-teal-500/20' }} rounded-2xl px-3 py-2.5"
                >
                    <span class="block text-sm font-semibold">{{ $action['label'] }}</span>
                    <span class="mt-1 block text-[11px] leading-5 text-zinc-500 dark:text-zinc-400">{{ $action['description'] }}</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
