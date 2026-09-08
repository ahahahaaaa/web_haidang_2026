@include('themes.haidangtravel.partials.taxonomy-card-carousel', [
    'cardCtaLabel' => $cardCtaLabel ?? null,
    'description' => $description ?? null,
    'items' => $tourCategories ?? [],
    'sectionId' => 'home-tour-topics',
    'taxonomyType' => 'tour_category',
    'title' => $title ?? null,
    'visualStyle' => 'topic',
])
