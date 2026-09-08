@include('themes.haidangtravel.partials.taxonomy-card-carousel', [
    'cardCtaLabel' => $cardCtaLabel ?? null,
    'description' => $description ?? null,
    'items' => $destinations ?? [],
    'sectionId' => 'home-destination-slider',
    'taxonomyType' => 'destination',
    'title' => $title ?? null,
    'visualStyle' => 'destination',
])
