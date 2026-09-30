@php
    $filter = is_array($sitewideTourSearchFilter ?? null) ? $sitewideTourSearchFilter : [];
@endphp

@if (($renderSitewideTourSearchFilter ?? false) && $filter !== [])
    <div class="pointer-events-none absolute inset-x-0 bottom-5 z-30 hidden lg:block" data-sitewide-tour-search-overlay>
        <div class="pointer-events-auto">
            @include('themes.haidangtravel.partials.tour-search-panel', [
                'buttonLabel' => 'Tìm',
                'containerClasses' => 'max-w-7xl',
                'destinationPlaceholder' => 'Bạn muốn đi đâu?',
                'filter' => $filter,
                'sectionClasses' => 'px-4 sm:px-6 lg:px-8',
            ])
        </div>
    </div>
@endif
