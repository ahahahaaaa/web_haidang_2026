@php
    $items = collect($items ?? [])
        ->map(fn ($item) => [
            'question' => trim((string) data_get($item, 'question')),
            'answer' => trim((string) data_get($item, 'answer')),
        ])
        ->filter(fn (array $item) => $item['question'] !== '' && $item['answer'] !== '')
        ->values();

    $accordionId = ($accordionId ?? 'faq').'-'.\Illuminate\Support\Str::lower((string) \Illuminate\Support\Str::uuid());
    $containerClass = $containerClass ?? 'space-y-2';
    $itemClass = $itemClass ?? 'theme-panel frontsite-text-reveal overflow-hidden rounded-[1.75rem] border border-slate-200 bg-[linear-gradient(180deg,_#ffffff_0%,_#fffaf6_100%)] shadow-[0_24px_60px_-50px_rgba(15,23,42,0.35)] transition';
    $triggerClass = $triggerClass ?? 'flex w-full items-start justify-between gap-3 px-3 py-2.5 text-left sm:px-4 sm:py-3';
    $iconClass = $iconClass ?? 'mt-0.5 inline-flex size-10 shrink-0 items-center justify-center rounded-full border border-orange-100 bg-[color:var(--color-primary-soft)] text-primary transition';
    $panelClass = $panelClass ?? 'border-t border-slate-200/60 px-3 pt-2.5 pb-2.5 sm:px-4 sm:pt-3 sm:pb-3';
@endphp

@if ($items->isNotEmpty())
    <div class="{{ $containerClass }}" data-faq-accordion data-faq-single="true">
        @foreach ($items as $item)
            @php
                $isOpen = $loop->first;
                $panelId = $accordionId.'-panel-'.$loop->iteration;
                $triggerId = $accordionId.'-trigger-'.$loop->iteration;
            @endphp

            <article
                class="{{ $itemClass }}"
                data-faq-item
                data-reveal="card"
                data-reveal-delay="{{ number_format($loop->index * 0.08, 2, '.', '') }}"
            >
                <h3 class="w-full">
                    <button
                        type="button"
                        id="{{ $triggerId }}"
                        class="{{ $triggerClass }}"
                        aria-controls="{{ $panelId }}"
                        aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        data-faq-trigger
                    >
                        <span class="min-w-0 pr-2 font-heading text-lg font-bold leading-7 text-slate-950 sm:text-xl">
                            {{ $item['question'] }}
                        </span>

                        <span class="{{ $iconClass }}" aria-hidden="true">
                            <i class="fa-solid {{ $isOpen ? 'fa-minus' : 'fa-plus' }} text-sm" data-faq-icon></i>
                        </span>
                    </button>
                </h3>

                <div
                    id="{{ $panelId }}"
                    class="{{ $panelClass }}"
                    role="region"
                    aria-labelledby="{{ $triggerId }}"
                    data-faq-panel
                    @if (! $isOpen) hidden @endif
                >
                    <div class="theme-copy text-sm leading-7 text-slate-600 sm:text-base">
                        {!! \App\Support\RichText::render($item['answer']) !!}
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif
