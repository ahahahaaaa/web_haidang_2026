@extends('themes.haidangtravel.layouts.app')

@section('content')
    @php
        $coverMedia = $articleHeroMedia ?? \App\Support\FrontsiteMedia::responsiveUrls($post, 'cover', 'cover_image_url');
        $cover = $coverMedia[\App\Support\FrontsiteMedia::SIZE_FULL] ?? null;
        $coverMedium = $coverMedia[\App\Support\FrontsiteMedia::SIZE_MEDIUM] ?? $cover;
        $publishedLabel = optional($post->published_at)->format('d/m/Y') ?: optional($post->created_at)->format('d/m/Y');
        $faqItems = collect($post->faq_items ?? [])
            ->map(fn ($item) => [
                'question' => trim((string) data_get($item, 'question')),
                'answer' => trim((string) data_get($item, 'answer')),
            ])
            ->filter(fn (array $item) => $item['question'] !== '' && $item['answer'] !== '')
            ->values();
        $sectionHeadingConfig = data_get($siteSettings->structured_data, \App\Support\FrontsiteSectionHeadings::STRUCTURED_DATA_KEY);
        $tocHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'blog_toc');
        $blogShareHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'blog_social_share');
        $blogInfoHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'blog_info');
        $blogFaqHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'blog_faq');
        $blogRelatedHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'blog_related');
        $blogCtaHeading = \App\Support\FrontsiteSectionHeadings::resolve($sectionHeadingConfig, 'blog_cta');
        $blogGeoLinks = collect([
            $post->countryDestination ? [
                'label' => 'Quốc gia',
                'name' => $post->countryDestination->name,
                'url' => route('countries.show', ['slug' => $post->countryDestination->slug]),
            ] : null,
            $post->destination ? [
                'label' => 'Điểm đến',
                'name' => $post->destination->name,
                'url' => route('destinations.show', $post->destination),
            ] : null,
        ])->filter()->values();
    @endphp

    <section class="relative overflow-hidden bg-secondary">
        @if ($cover)
            <picture class="absolute inset-0 block h-full w-full">
                @if ($coverMedium)
                    <source media="(max-width: 767px)" srcset="{{ $coverMedium }}">
                @endif
                <img
                    src="{{ $cover }}"
                    alt="{{ $post->cover_alt ?: $post->title }}"
                    class="absolute inset-0 h-full w-full object-cover"
                    width="1600"
                    height="900"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                >
            </picture>
            <div class="absolute inset-0 bg-slate-950/70"></div>
        @else
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(255,106,0,0.18),_transparent_22%),linear-gradient(135deg,_#004A99_0%,_#0c3569_50%,_#002d5f_100%)]"></div>
        @endif

        <div class="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
            <div class="max-w-4xl space-y-6">
                @include('themes.haidangtravel.partials.breadcrumbs', [
                    'items' => array_merge([
                        ['label' => 'Trang chủ', 'url' => route('home')],
                        ['label' => 'Blog', 'url' => route('blog.index')],
                    ], $blogCategoryTrail->map(fn ($category) => [
                        'label' => $category->name,
                        'url' => route('blog-categories.show', ['slug' => $category->slug]),
                    ])->all(), [
                        ['label' => $post->title],
                    ]),
                ])

                <div class="frontsite-text-reveal flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-[0.3em] text-orange-100" data-reveal="meta">
                    @foreach ($blogCategoryTrail as $category)
                        <a href="{{ route('blog-categories.show', ['slug' => $category->slug]) }}" class="rounded-sm border border-white/15 bg-white/10 px-4 py-2 transition hover:border-orange-200 hover:text-white">
                            {{ $category->name }}
                        </a>
                    @endforeach
                    @foreach ($blogGeoLinks as $geoLink)
                        <a href="{{ $geoLink['url'] }}" class="rounded-sm border border-white/15 bg-white/10 px-4 py-2 transition hover:border-orange-200 hover:text-white">
                            {{ $geoLink['name'] }}
                        </a>
                    @endforeach
                    <span>{{ $publishedLabel }}</span>
                    <span>{{ $post->author_name ?: $siteSettings->site_name }}</span>
                </div>

                <h1 class="frontsite-text-reveal font-heading text-4xl font-extrabold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl" data-reveal="title">
                    {{ $post->title }}
                </h1>
                <p class="frontsite-text-reveal max-w-3xl text-lg leading-8 text-slate-200" data-reveal="body">
                    {{ $post->excerpt ?: $siteSettings->site_description }}
                </p>
            </div>
        </div>
    </section>

    @include('themes.haidangtravel.partials.geo-answer-panel', [
        'geo' => $geo ?? [],
        'sectionClasses' => 'px-4 py-8 sm:px-6 lg:px-8',
    ])

    <section class="px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">
            <div class="theme-panel frontsite-text-reveal p-6 sm:p-8" data-reveal="panel">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    @if ($tocHeading['is_visible'] && ($tocHeading['title'] !== '' || $tocHeading['description'] !== ''))
                        <div class="space-y-2">
                            @if ($tocHeading['title'] !== '')
                                <h2 class="frontsite-h2-compact">{{ $tocHeading['title'] }}</h2>
                            @endif
                            @if ($tocHeading['description'] !== '')
                                <p class="text-sm leading-7 text-slate-600">{{ $tocHeading['description'] }}</p>
                            @endif
                        </div>
                    @endif
                    <a href="#blog-detail-content" class="inline-flex items-center gap-2 text-sm font-semibold text-primary">
                        Đi thẳng vào nội dung
                        <i class="fa-solid fa-arrow-down"></i>
                    </a>
                </div>

                @if ($tocItems !== [])
                    <nav class="toc-container mt-6" aria-label="Mục lục bài viết">
                        <ol class="space-y-3">
                            @foreach ($tocItems as $tocItem)
                                <li>
                                    <a href="#{{ $tocItem['id'] }}" class="group flex items-start gap-3 rounded-sm bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-[color:var(--color-primary-soft)] hover:text-primary">
                                        <span class="mt-0.5 inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-slate-500 shadow-sm transition group-hover:text-primary">
                                            {{ str_pad((string) ($loop->iteration), 2, '0', STR_PAD_LEFT) }}
                                        </span>
                                        <span class="leading-6">{{ $tocItem['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </nav>
                @else
                    <p class="mt-4 text-sm leading-7 text-slate-600">
                        Bài viết này chưa có các đề mục H2 để tạo mục lục tự động.
                    </p>
                @endif
            </div>

            <article id="blog-detail-content" class="theme-panel frontsite-text-reveal p-8 sm:p-10 lg:p-12" data-reveal="copy">
                <div class="theme-copy text-base leading-8 text-slate-600">
                    @if (filled((string) $renderedContent))
                        {!! $renderedContent !!}
                    @else
                        <p>Nội dung đang được cập nhật trong CMS.</p>
                    @endif
                </div>
            </article>

            @include('themes.haidangtravel.partials.social-share', [
                'description' => $post->excerpt ?: $siteSettings->seo_description,
                'heading' => $blogShareHeading['is_visible'] ? $blogShareHeading['title'] : '',
                'intro' => $blogShareHeading['is_visible'] ? $blogShareHeading['description'] : '',
                'title' => $post->title,
                'url' => $articleUrl,
            ])

            @if ($faqItems->isNotEmpty())
                <section class="space-y-5">
                    @include('themes.haidangtravel.partials.faq-block', [
                        'accordionId' => 'blog-detail-faq',
                        'headingWidth' => 'max-w-none',
                        'items' => $faqItems,
                        'title' => $blogFaqHeading['is_visible'] ? $blogFaqHeading['title'] : '',
                    ])
                </section>
            @endif

            <div class="theme-panel frontsite-text-reveal p-6 sm:p-8" data-reveal="panel" data-reveal-delay="0.08">
                @if ($blogInfoHeading['is_visible'] && $blogInfoHeading['title'] !== '')
                    <h2 class="frontsite-h2-compact">{{ $blogInfoHeading['title'] }}</h2>
                @endif
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-sm bg-slate-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Tác giả</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $post->author_name ?: $siteSettings->site_name }}</p>
                    </div>
                    <div class="rounded-sm bg-slate-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Danh mục</p>
                        @if ($blogCategoryTrail->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($blogCategoryTrail as $category)
                                    <a href="{{ route('blog-categories.show', ['slug' => $category->slug]) }}" class="inline-flex items-center rounded-sm bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:text-primary">
                                        {{ $category->name }}
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 font-semibold text-slate-900">Blog</p>
                        @endif
                    </div>
                    <div class="rounded-sm bg-slate-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Ngày xuất bản</p>
                        <p class="mt-2 font-semibold text-slate-900">{{ $publishedLabel }}</p>
                    </div>
                    <div class="rounded-sm bg-slate-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Địa lý</p>
                        @if ($blogGeoLinks->isNotEmpty())
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($blogGeoLinks as $geoLink)
                                    <a href="{{ $geoLink['url'] }}" class="inline-flex items-center rounded-sm bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:text-primary">
                                        {{ $geoLink['label'] }}: {{ $geoLink['name'] }}
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 font-semibold text-slate-900">Chưa gán</p>
                        @endif
                    </div>
                    <div class="rounded-sm bg-slate-50 px-5 py-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-500">Liên kết nhanh</p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <a href="{{ route('services.index') }}" class="inline-flex items-center gap-2 rounded-sm bg-[color:var(--color-primary-soft)] px-4 py-2 text-sm font-semibold text-primary">
                                <i class="fa-solid fa-link"></i>
                                Dịch vụ
                            </a>
                            <a href="{{ route('tours.international') }}" class="inline-flex items-center gap-2 rounded-sm bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700">
                                Tour nước ngoài
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-10">
            @include('themes.haidangtravel.partials.section-heading', [
                'title' => $blogRelatedHeading['is_visible'] ? $blogRelatedHeading['title'] : '',
            ])

            <div class="{{ \App\Support\FrontsiteCardGrid::classes() }}">
                @forelse ($relatedPosts as $relatedPost)
                    @include('themes.haidangtravel.partials.article-card', ['post' => $relatedPost, 'revealDelay' => number_format(($loop->index % 4) * 0.08, 2, '.', '')])
                @empty
                    <div class="theme-panel frontsite-text-reveal col-span-full p-10 text-center text-slate-500" data-reveal="panel">
                        Chưa có bài viết liên quan.
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    @include('themes.haidangtravel.partials.cta-banner', [
        'description' => $blogCtaHeading['is_visible'] ? $blogCtaHeading['description'] : '',
        'primaryLabel' => 'Xem thêm bài viết',
        'primaryUrl' => route('blog.index'),
        'secondaryLabel' => 'Xem tour',
        'secondaryUrl' => route('tours.domestic'),
        'title' => $blogCtaHeading['is_visible'] ? $blogCtaHeading['title'] : '',
    ])
@endsection
