<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    @php
        $currentRouteName = request()->route()?->getName();
        $adminGroups = \App\Support\Admin\AdminNavigationRegistry::cmsGroups(auth()->user());
        $defaultOpenGroups = collect($adminGroups)
            ->mapWithKeys(fn (array $group) => [
                $group['key'] => \App\Support\Admin\AdminNavigationRegistry::groupIsCurrent($group['key'], $currentRouteName),
            ])
            ->all();
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
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav
                x-data="{
                    storageKey: 'haidangtravel-admin-sidebar-groups',
                    openGroups: {},
                    defaults: {{ \Illuminate\Support\Js::from($defaultOpenGroups) }},
                    init() {
                        this.openGroups = this.readStoredGroups();
                        this.syncWithDefaults();
                    },
                    readStoredGroups() {
                        const stored = localStorage.getItem(this.storageKey);

                        if (!stored) {
                            return {};
                        }

                        try {
                            const parsed = JSON.parse(stored);

                            return parsed && typeof parsed === 'object' && !Array.isArray(parsed)
                                ? parsed
                                : {};
                        } catch (error) {
                            localStorage.removeItem(this.storageKey);

                            return {};
                        }
                    },
                    syncWithDefaults() {
                        const nextState = {};

                        Object.entries(this.defaults).forEach(([groupKey, isCurrent]) => {
                            nextState[groupKey] = Boolean(isCurrent) || Boolean(this.openGroups[groupKey]);
                        });

                        this.openGroups = nextState;
                        this.persist();
                    },
                    isOpen(groupKey) {
                        return Boolean(this.openGroups[groupKey]);
                    },
                    persist() {
                        localStorage.setItem(this.storageKey, JSON.stringify(this.openGroups));
                    },
                    toggle(groupKey) {
                        this.openGroups[groupKey] = !this.isOpen(groupKey);
                        this.persist();
                    }
                }"
            >
                <div class="mt-3 rounded-[28px] border border-zinc-200 bg-white/85 p-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-950/40">
                    <a
                        href="{{ route('dashboard') }}"
                        wire:navigate
                        class="{{ request()->routeIs('dashboard') ? 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800/80' }} flex items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition"
                    >
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            <i class="fa-solid fa-house"></i>
                        </span>
                        <span>{{ __('Dashboard') }}</span>
                    </a>

                    @if ($adminGroups !== [])
                        <div class="mt-2 divide-y divide-zinc-200/70 dark:divide-zinc-800/80">
                            @foreach ($adminGroups as $group)
                                @php($groupIsCurrent = \App\Support\Admin\AdminNavigationRegistry::groupIsCurrent($group['key'], $currentRouteName))
                                <section data-sidebar-group="{{ $group['key'] }}" class="py-1.5 first:pt-0 last:pb-0">
                                    <button
                                        type="button"
                                        x-on:click="toggle('{{ $group['key'] }}')"
                                        x-bind:aria-expanded="isOpen('{{ $group['key'] }}')"
                                        aria-controls="sidebar-group-panel-{{ $group['key'] }}"
                                        class="flex w-full items-center justify-between rounded-2xl px-3 py-2.5 text-left transition {{ $groupIsCurrent ? 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300' : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800/80' }}"
                                    >
                                        <span class="flex min-w-0 items-center gap-3">
                                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                                <i class="{{ $groupIconMap[$group['icon']] ?? 'fa-regular fa-folder' }}"></i>
                                            </span>
                                            <span class="truncate text-sm font-semibold">{{ $group['label'] }}</span>
                                        </span>

                                        <i class="fa-solid fa-chevron-down text-[11px] transition" :class="isOpen('{{ $group['key'] }}') ? 'rotate-180' : ''"></i>
                                    </button>

                                    <div
                                        id="sidebar-group-panel-{{ $group['key'] }}"
                                        data-sidebar-group-panel="{{ $group['key'] }}"
                                        x-show="isOpen('{{ $group['key'] }}')"
                                        x-cloak
                                        @unless($groupIsCurrent) style="display: none;" @endunless
                                        class="mt-1 space-y-1 pr-1"
                                    >
                                        @foreach ($group['actions'] as $action)
                                            @php($actionIsCurrent = \App\Support\Admin\AdminNavigationRegistry::actionIsCurrent($action, $currentRouteName))
                                            <a
                                                href="{{ route($action['route']) }}"
                                                wire:navigate
                                                class="{{ $actionIsCurrent ? 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300' : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800/80 dark:hover:text-white' }} flex items-center rounded-2xl px-3 py-2 text-sm font-medium transition"
                                            >
                                                {{ $action['label'] }}
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    @endif
                </div>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <flux:dropdown position="top" align="end">
                <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />
                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
