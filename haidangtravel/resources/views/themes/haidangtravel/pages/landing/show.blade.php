@extends('themes.haidangtravel.layouts.app')

@section('content')
    @if (($landingEditorMode ?? \Src\Domains\Cms\Models\LandingPage::EDITOR_MODE_BLOCKS) === \Src\Domains\Cms\Models\LandingPage::EDITOR_MODE_HTML)
        <section class="px-4 pt-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl">
                <div class="theme-panel p-6 sm:p-8">
                    <h1 class="text-3xl font-semibold text-slate-900 sm:text-4xl">{{ $landing->title }}</h1>
                </div>
            </div>
        </section>

        {!! $landingHtmlContent ?? '' !!}
    @else
        @php
            $landingBlockTypes = collect($landingContentBlocks ?? [])->pluck('type');
            $landingHasHeroBlock = $landingBlockTypes->intersect([
                \App\Support\LandingPageBlocks::TYPE_HERO_SLIDER,
                \App\Support\LandingPageBlocks::TYPE_HERO_MEDIA,
                \App\Support\LandingPageBlocks::TYPE_HERO_DEMO_LANDINGPAGE,
            ])->isNotEmpty();
            $landingHasGalleryBlock = $landingBlockTypes->intersect([
                \App\Support\LandingPageBlocks::TYPE_GALLERY_SLIDER,
                \App\Support\LandingPageBlocks::TYPE_GALLERY_MEDIA,
            ])->isNotEmpty();
        @endphp

        @if (! $landingHasHeroBlock && (($landingHero['managed'] ?? false) !== true || ($landingHero['enabled'] ?? true)))
            @include('themes.haidangtravel.partials.landing-hero', [
                'breadcrumbItems' => [
                    ['label' => 'Trang chủ', 'url' => route('home')],
                    ['label' => $landing->title],
                ],
                'fallbackDescription' => $landing->hero_excerpt ?: $landing->intro_excerpt,
                'fallbackEyebrow' => $landing->hero_badge ?: $landing->title,
                'fallbackPrimaryLabel' => $landing->cta_primary_label ?: 'Gửi yêu cầu',
                'fallbackPrimaryUrl' => $landing->cta_primary_url ?: route('contact'),
                'fallbackSecondaryLabel' => $landing->cta_secondary_label,
                'fallbackSecondaryUrl' => $landing->cta_secondary_url,
                'fallbackTitle' => $landing->hero_title ?: $landing->title,
                'hero' => $landingHero ?? [],
                'landing' => $landing,
            ])
        @endif

        @if (! $landingHasGalleryBlock)
            @include('themes.haidangtravel.partials.landing-gallery', ['gallery' => $landingGallery ?? []])
        @endif

        @include('themes.haidangtravel.partials.landing-content-blocks', [
            'blocks' => $landingContentBlocks,
            'landing' => $landing,
        ])
    @endif
@endsection
