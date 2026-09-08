<x-layouts::app :title="__('Dashboard')">
    <div class="space-y-6">
        <div class="rounded-3xl border border-zinc-200 bg-white p-8 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.3em] text-teal-600">Haidang Travel</p>
            <h1 class="text-4xl font-semibold text-zinc-900 dark:text-white">Travel CMS Admin</h1>
            <p class="mt-3 max-w-3xl text-sm text-zinc-500 dark:text-zinc-400">
                Quản lý tours, dịch vụ du lịch, blog, landing pages, lead tư vấn, menu, media và theme settings cho haidangtravel.com.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <a href="{{ route($card['route']) }}" class="group flex h-full flex-col rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-6 inline-flex size-12 items-center justify-center rounded-2xl bg-teal-50 text-teal-600 dark:bg-teal-500/10 dark:text-teal-300">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </div>

                    <div class="flex flex-1 flex-col">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-lg font-semibold text-zinc-900 transition group-hover:text-teal-600 dark:text-white">{{ $card['title'] }}</h2>

                            @if (filled($card['secondary']))
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ $card['secondary'] }}
                                </span>
                            @endif
                        </div>

                        <div class="mt-4 flex items-end gap-2">
                            <span class="text-3xl font-semibold leading-none text-zinc-900 transition group-hover:text-teal-600 dark:text-white">
                                {{ number_format($card['count'], 0, ',', '.') }}
                            </span>
                            <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ $card['count_label'] }}</span>
                        </div>

                        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $card['text'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts::app>
