@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $renderedLandingBody = filled($landing?->body) ? \App\Support\RichText::render($landing->body) : null;
        $landingBodyText = \Illuminate\Support\Str::of(strip_tags((string) $renderedLandingBody))->squish()->lower()->value();
        $hideLegacyLandingBody = $landingBodyText === 'blog travel thay cho toàn bộ blog xây dựng cũ.';
        $selectedBlogCategoryFaqItems = collect($selectedBlogCategory?->faq_items ?? [])
            ->map(fn ($item) => [
                'question' => trim((string) data_get($item, 'question')),
                'answer' => trim((string) data_get($item, 'answer')),
            ])
            ->filter(fn (array $item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values();
        $selectedCategoryPathLabel = $selectedBlogCategory ? \App\Support\ContentCategoryTree::pathLabel($selectedBlogCategory) : null;
        $blogBreadcrumbItems = collect([
            ['label' => 'Trang chủ', 'url' => route('home')],
            ['label' => 'Blog', 'url' => route('blog.index')],
        ])->when($selectedBlogCategoryTrail->isNotEmpty(), fn ($items) => $items->concat(
            $selectedBlogCategoryTrail->map(fn ($category) => [
                'label' => $category->name,
                'url' => route('blog-categories.show', ['slug' => $category->slug]),
            ])
        ))->all();
        $blogIndexFaqHeading = \App\Support\FrontsiteSectionHeadings::resolve(
            data_get($siteSettings->structured_data, \App\Support\FrontsiteSectionHeadings::STRUCTURED_DATA_KEY),
            'blog_index_faq',
            ['title' => $selectedBlogCategory?->name ?: 'chủ đề này'],
        );
    @endphp

    @include('themes.haidangtravel.partials.landing-hero', [
        'breadcrumbItems' => $blogBreadcrumbItems,
        'fallbackDescription' => $selectedBlogCategory?->description ?: ($landing?->hero_excerpt ?: $landing?->intro_excerpt ?: 'Nội dung travel tập trung vào quyết định đặt tour, chuẩn bị hồ sơ và kinh nghiệm điểm đến.'),
        'fallbackEyebrow' => $landing?->hero_badge ?: 'Cẩm nang du lịch',
        'fallbackPrimaryLabel' => $landing?->cta_primary_label ?: 'Gửi yêu cầu',
        'fallbackPrimaryUrl' => $landing?->cta_primary_url ?: route('contact'),
        'fallbackSecondaryLabel' => $landing?->cta_secondary_label ?: 'Xem tour nước ngoài',
        'fallbackSecondaryUrl' => $landing?->cta_secondary_url ?: route('tours.international'),
        'fallbackTitle' => $selectedBlogCategory?->name ?: ($landing?->hero_title ?: $landing?->title ?: 'Blog du lịch và visa'),
        'hero' => $landingHero ?? [],
        'landing' => $landing,
    ])

    @include('themes.haidangtravel.partials.landing-gallery', ['gallery' => $landingGallery ?? []])

    @include('themes.haidangtravel.partials.landing-content-blocks', [
        'blocks' => $landingHtmlWidgetBlocks ?? [],
    ])

    @include('themes.haidangtravel.partials.shared-search-bar', [
        'action' => $blogSearchAction ?? route('blog.index'),
        'hiddenInputs' => collect(request()->except(['page', 'q'])),
        'inputValue' => request('q'),
        'placeholder' => 'Tìm bài viết, visa hoặc điểm đến...',
    ])

    @include('themes.haidangtravel.partials.geo-answer-panel', [
        'geo' => $geo ?? [],
        'sectionClasses' => 'px-4 pb-8 sm:px-6 lg:px-8',
    ])

    <section class="px-4 pb-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-8">
            @php
                $rootCategories = collect($categoryTree)->values();
                $rootCategoryCount = $rootCategories->count();
                $rootCategoryDesktopSlider = $rootCategoryCount > 6;
                $rootCategoryMobileSlider = $rootCategoryCount > 2;
                $rootCategoriesForRail = $rootCategories->map(function ($category) use ($selectedBlogCategoryRoot) {
                    $category->setAttribute('is_active', (int) ($selectedBlogCategoryRoot?->id ?? 0) === (int) $category->id);

                    return $category;
                });
            @endphp

            <div class="space-y-6">
                @include('themes.haidangtravel.partials.section-heading', [
                    'eyebrow' => $landing?->intro_title ?: 'Travel content',
                    'title' => $landing?->intro_title ?: ($landing?->title ?: 'Cẩm Nang & Sự Kiện Nổi Bật'),
                    'description' => $selectedCategoryPathLabel
                        ? 'Bạn đang xem '.$selectedCategoryPathLabel.'. Haidangtravel chia sẻ các thông tin hữu ích mới nhất hằng ngày, bạn xem các bài viết bên dưới.'
                        : ($landing?->intro_excerpt ?: 'Tổng hợp kinh nghiệm du lịch thực tế, thông tin visa mới nhất và các sự kiện đặc sắc trong năm.'),
                ])

                <div
                    class="frontsite-text-reveal space-y-5"
                    data-reveal="panel"
                >
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div class="flex flex-wrap items-center gap-3">
                            <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 rounded-sm border px-4 py-2 text-sm font-semibold transition {{ $selectedBlogCategory ? 'border-slate-200 bg-white text-slate-700 hover:border-orange-200 hover:text-primary' : 'border-orange-200 bg-[color:var(--color-primary-soft)] text-primary' }}">
                                Tất cả bài viết
                            </a>

                            @if ($selectedCategoryPathLabel)
                                <span class="inline-flex items-center rounded-sm bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                                    Đang xem: {{ $selectedCategoryPathLabel }}
                                </span>
                            @endif
                        </div>

                    </div>

                    @if ($rootCategoryMobileSlider)
                        <div class="lg:hidden">
                            @include('themes.haidangtravel.partials.taxonomy-card-carousel', [
                                'containerClass' => '',
                                'desktopCardWidth' => 'calc((100% - 1rem) / 2)',
                                'desktopSlider' => true,
                                'items' => $rootCategoriesForRail,
                                'itemLimit' => $rootCategoryCount,
                                'mobileCardWidth' => 'calc((100% - 1rem) / 2)',
                                'showNavigator' => false,
                                'tabletCardWidth' => 'calc((100% - 1rem) / 2)',
                                'taxonomyType' => 'blog_category',
                                'useDefaultCopy' => false,
                                'visualStyle' => 'topic',
                                'wrapInSection' => false,
                            ])
                        </div>
                    @else
                        <div class="{{ $rootCategoryCount === 1 ? 'grid grid-cols-1 justify-center' : 'grid grid-cols-2' }} gap-4 lg:hidden">
                            @foreach ($rootCategories as $rootCategory)
                                <div @if ($rootCategoryCount === 1) class="mx-auto w-full max-w-[11rem]" @endif>
                                    @include('themes.haidangtravel.partials.blog-category-entry', [
                                        'category' => $rootCategory,
                                        'active' => (int) ($selectedBlogCategoryRoot?->id ?? 0) === (int) $rootCategory->id,
                                        'revealDelay' => number_format(($loop->index % 2) * 0.05, 2, '.', ''),
                                    ])
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($rootCategoryDesktopSlider)
                        <div class="hidden lg:block">
                            @include('themes.haidangtravel.partials.taxonomy-card-carousel', [
                                'containerClass' => '',
                                'desktopCardWidth' => 'calc((100% - 5rem) / 6)',
                                'desktopSlider' => true,
                                'items' => $rootCategoriesForRail,
                                'itemLimit' => $rootCategoryCount,
                                'showNavigator' => true,
                                'taxonomyType' => 'blog_category',
                                'useDefaultCopy' => false,
                                'visualStyle' => 'topic',
                                'wrapInSection' => false,
                            ])
                        </div>
                    @else
                        <div class="hidden gap-4 lg:grid lg:grid-flow-col lg:auto-cols-[minmax(0,11rem)] lg:justify-center">
                            @foreach ($rootCategories as $rootCategory)
                                @include('themes.haidangtravel.partials.blog-category-entry', [
                                    'category' => $rootCategory,
                                    'active' => (int) ($selectedBlogCategoryRoot?->id ?? 0) === (int) $rootCategory->id,
                                    'revealDelay' => number_format(($loop->index % 6) * 0.05, 2, '.', ''),
                                ])
                            @endforeach
                        </div>
                    @endif
                </div>

                @if (filled($renderedLandingBody) && ! $hideLegacyLandingBody)
                    <div class="theme-panel frontsite-text-reveal p-6" data-reveal="copy">
                        <div class="theme-copy text-sm leading-7 text-slate-600">
                            {!! $renderedLandingBody !!}
                        </div>
                    </div>
                @endif
            </div>

            <div class="{{ \App\Support\FrontsiteCardGrid::classes() }}">
                @forelse ($posts as $post)
                    @include('themes.haidangtravel.partials.article-card', ['post' => $post, 'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', '')])
                @empty
                    <div class="theme-panel frontsite-text-reveal col-span-full p-10 text-center text-slate-500" data-reveal="panel">
                        Chưa có bài viết phù hợp bộ lọc hiện tại.
                    </div>
                @endforelse
            </div>

            @if ($posts->hasPages())
                <div class="theme-panel frontsite-text-reveal p-4" data-reveal="panel">
                    {{ $posts->links() }}
                </div>
            @endif

            @if ($selectedBlogCategoryFaqItems->isNotEmpty())
                <section class="space-y-5">
                    @include('themes.haidangtravel.partials.faq-block', [
                        'accordionId' => 'blog-category-faq',
                        'headingWidth' => 'max-w-5xl',
                        'items' => $selectedBlogCategoryFaqItems,
                        'title' => $blogIndexFaqHeading['is_visible'] ? $blogIndexFaqHeading['title'] : '',
                    ])
                </section>
            @endif
        </div>
    </section>

    @include('themes.haidangtravel.partials.cta-banner', [
        'description' => $landing?->cta_excerpt ?: 'Cần tư vấn theo bài đang đọc, theo điểm đến hoặc theo thủ tục visa cụ thể?',
        'primaryLabel' => $landing?->cta_primary_label ?: 'Gửi yêu cầu',
        'primaryUrl' => $landing?->cta_primary_url ?: route('contact'),
        'secondaryLabel' => $landing?->cta_secondary_label ?: 'Xem tour nước ngoài',
        'secondaryUrl' => $landing?->cta_secondary_url ?: route('tours.international'),
        'title' => $landing?->cta_title ?: 'Muốn biến nội dung đang đọc thành hành trình phù hợp với nhu cầu thực tế?',
    ])
@endsection
