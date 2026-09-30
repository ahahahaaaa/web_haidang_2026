@include('themes.haidangtravel.partials.taxonomy-card-carousel', [
    'cardCtaLabel' => $cardCtaLabel ?? null,
    'description' => $description ?? null,
    'eyebrow' => $eyebrow ?? null,
    'items' => $tourCategories ?? [],
    'sectionId' => 'home-tour-topics',
    'showCardTitles' => $showCardTitles ?? true,
    'taxonomyType' => 'tour_category',
    'title' => $title ?? null,
    'visualStyle' => 'topic',
])
