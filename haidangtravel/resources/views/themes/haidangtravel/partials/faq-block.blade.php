@php
    $normalizedItems = collect($items ?? [])
        ->filter(fn ($item) => filled(data_get($item, 'question')) && filled(data_get($item, 'answer')))
        ->values();
    $blockClass = $blockClass ?? 'space-y-5';
    $headingAlign = $headingAlign ?? 'left';
    $headingWidth = $headingWidth ?? 'max-w-3xl';
    $accordionWrapperClass = trim((string) ($accordionWrapperClass ?? ''));
@endphp

@if ($normalizedItems->isNotEmpty())
    <div class="{{ $blockClass }}">
        @include('themes.haidangtravel.partials.section-heading', [
            'align' => $headingAlign,
            'title' => $title ?? 'Câu hỏi thường gặp',
            'width' => $headingWidth,
        ])

        @if ($accordionWrapperClass !== '')
            <div class="{{ $accordionWrapperClass }}">
        @endif

        @include('themes.haidangtravel.partials.faq-accordion', [
            'accordionId' => $accordionId ?? 'faq',
            'containerClass' => $accordionContainerClass ?? null,
            'iconClass' => $accordionIconClass ?? null,
            'itemClass' => $accordionItemClass ?? null,
            'items' => $normalizedItems,
            'panelClass' => $accordionPanelClass ?? null,
            'triggerClass' => $accordionTriggerClass ?? null,
        ])

        @if ($accordionWrapperClass !== '')
            </div>
        @endif
    </div>
@endif
