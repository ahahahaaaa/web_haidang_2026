@php
    $taxonomyType = in_array((string) ($taxonomyType ?? 'destination'), ['destination', 'tour_category', 'region', 'blog_category'], true)
        ? (string) $taxonomyType
        : 'destination';
    $visualStyle = in_array((string) ($visualStyle ?? 'destination'), ['destination', 'topic'], true)
        ? (string) $visualStyle
        : 'destination';
    $sectionId = trim((string) ($sectionId ?? ''));
    $style = [
        'destination' => [
            'delay_step' => 0.06,
            'fallback_background' => 'bg-[radial-gradient(circle_at_top,_rgba(255,140,0,0.26),_transparent_38%),linear-gradient(135deg,_rgba(0,74,153,0.16),_rgba(255,106,0,0.08))]',
            'fallback_text' => 'text-secondary',
            'image_background' => 'bg-[linear-gradient(135deg,_rgba(0,74,153,0.12),_rgba(255,106,0,0.18))]',
            'section_classes' => 'bg-[color:var(--color-bg-soft)] px-4 py-8 sm:px-6 lg:px-8 lg:py-10',
            'badge_text' => 'text-secondary',
        ],
        'topic' => [
            'delay_step' => 0.05,
            'fallback_background' => 'bg-[radial-gradient(circle_at_top,_rgba(255,140,0,0.24),_transparent_38%),linear-gradient(135deg,_rgba(255,106,0,0.2),_rgba(255,106,0,0.08))]',
            'fallback_text' => 'text-primary',
            'image_background' => 'bg-[linear-gradient(135deg,_rgba(255,106,0,0.16),_rgba(255,140,0,0.22))]',
            'section_classes' => 'bg-[color:var(--color-bg-soft)] px-4 py-6 sm:px-6 sm:py-7 lg:px-8 lg:py-8',
            'badge_text' => 'text-primary',
        ],
    ][$visualStyle];
    $taxonomyDefaults = match ($taxonomyType) {
        'region' => [
            'card_cta_label' => 'Xem hub vùng miền',
            'description' => 'Lướt nhanh các hub vùng miền đang có tour hoạt động để khoanh vùng khu vực phù hợp trước khi đi sâu vào từng điểm đến cụ thể.',
            'next_label' => 'Xem vùng miền tiếp theo',
            'prev_label' => 'Xem vùng miền trước',
            'summary' => 'Mở nhanh hub vùng miền để xem nhóm tour đang hoạt động, điểm đến nổi bật và các hành trình liên quan.',
            'title' => 'Vùng miền nổi bật',
        ],
        'tour_category' => [
            'card_cta_label' => 'Xem chủ đề tour',
            'description' => 'Lướt nhanh các chủ đề tour đang có hành trình hoạt động để khoanh vùng nhu cầu phù hợp trước khi so sánh điểm đến, ngày đi và mức giá.',
            'next_label' => 'Xem chủ đề tour tiếp theo',
            'prev_label' => 'Xem chủ đề tour trước',
            'summary' => 'Mở nhanh chủ đề tour để xem các hành trình đang hoạt động, mức giá và hướng đi phù hợp trước khi chốt điểm đến.',
            'title' => 'Chủ đề tour nổi bật',
        ],
        'blog_category' => [
            'card_cta_label' => 'Xem danh mục blog',
            'description' => 'Mở nhanh các danh mục blog để lọc đúng nhóm bài viết đang cần theo điểm đến, visa hoặc nhu cầu chuẩn bị chuyến đi.',
            'next_label' => 'Xem danh mục blog tiếp theo',
            'prev_label' => 'Xem danh mục blog trước',
            'summary' => 'Mở nhanh danh mục blog để lọc đúng nhóm bài viết đang cần đọc trước khi đi sâu vào từng bài chi tiết.',
            'title' => 'Danh mục blog',
        ],
        default => [
            'card_cta_label' => 'Xem hub điểm đến',
            'description' => 'Lướt nhanh các hub điểm đến đang có tour hoạt động để chọn hướng đi phù hợp trước khi xem sâu hơn ở phần Điểm đến yêu thích.',
            'next_label' => 'Xem điểm đến tiếp theo',
            'prev_label' => 'Xem điểm đến trước',
            'summary' => 'Mở nhanh hub điểm đến để xem tour đang hoạt động, ngày khởi hành và các hành trình liên quan.',
            'title' => 'Điểm đến nổi bật',
        ],
    };
    $autoplay = ($autoplay ?? true) === true;
    $containerClass = trim((string) ($containerClass ?? 'mx-auto max-w-7xl'));
    $desktopSlider = ($desktopSlider ?? true) === true;
    $displayEyebrow = trim((string) ($eyebrow ?? ''));
    $resolvedSectionClasses = trim((string) ($sectionClassesOverride ?? $style['section_classes']));
    $useDefaultCopy = ($useDefaultCopy ?? true) === true;
    $wrapInSection = ($wrapInSection ?? true) === true;
    $cardCtaLabel = trim((string) ($cardCtaLabel ?? '')) ?: $taxonomyDefaults['card_cta_label'];
    $description = trim((string) ($description ?? ''));
    $isTopicStyle = $visualStyle === 'topic';
    $title = trim((string) ($title ?? ''));
    $displayTitle = $title !== '' ? $title : ($useDefaultCopy ? $taxonomyDefaults['title'] : '');
    $displayDescription = $description !== '' ? $description : ($useDefaultCopy ? $taxonomyDefaults['description'] : '');
    $showNavigator = ($showNavigator ?? true) === true;
    $desktopColumns = max(1, (int) ($desktopColumns ?? 4));
    $desktopCardWidth = trim((string) ($desktopCardWidth ?? ''));
    $mobileCardWidth = trim((string) ($mobileCardWidth ?? ''));
    $tabletCardWidth = trim((string) ($tabletCardWidth ?? ''));
    $itemLimit = isset($itemLimit) ? max(0, (int) $itemLimit) : 8;
    $avatarSize = \App\Support\FrontsiteMedia::SIZE_SMALL;
    $carouselInlineStyle = $isTopicStyle
        ? '--mobile-card-width: calc((100% - 0.95rem) / 2.2); --tablet-card-width: calc((100% - 2rem) / 3); --desktop-card-width: calc((100% - 5rem) / 6);'
        : '--mobile-card-width: calc((100% - 0.85rem) / 1.18); --tablet-card-width: calc((100% - 1rem) / 2.15); --desktop-card-width: calc((100% - 3rem) / 4);';
    $carouselInlineStyle .= ' --desktop-columns: '.$desktopColumns.';';
    if ($mobileCardWidth !== '') {
        $carouselInlineStyle .= ' --mobile-card-width: '.$mobileCardWidth.';';
    }
    if ($tabletCardWidth !== '') {
        $carouselInlineStyle .= ' --tablet-card-width: '.$tabletCardWidth.';';
    }
    if ($desktopCardWidth !== '') {
        $carouselInlineStyle .= ' --desktop-card-width: '.$desktopCardWidth.';';
    }
    $headerClass = $isTopicStyle
        ? 'flex flex-col gap-3 md:flex-row md:items-end md:justify-between'
        : 'flex flex-col gap-4 md:flex-row md:items-end md:justify-between';
    $trackSpacingClass = $isTopicStyle ? 'mt-5' : 'mt-6';
    $normalizedItems = collect($items ?? [])
        ->filter(fn ($item) => filled(data_get($item, 'name')))
        ->when($itemLimit > 0, fn ($collection) => $collection->take($itemLimit))
        ->values()
        ->map(function ($item) use ($avatarSize, $taxonomyDefaults, $taxonomyType): array {
            $label = trim((string) data_get($item, 'name'));
            $avatar = \App\Support\FrontsiteMedia::taxonomyAvatarUrl(
                $item,
                $avatarSize,
                'cover_image_url',
                false,
            ) ?: '';
            $count = match ($taxonomyType) {
                'blog_category' => (int) (data_get($item, 'branch_blog_posts_count') ?: data_get($item, 'blog_posts_count', 0)),
                default => (int) data_get($item, 'tours_count', 0),
            };

            return [
                'alt' => trim((string) (data_get($item, 'cover_alt') ?: $label)),
                'avatar' => $avatar,
                'count_label' => match ($taxonomyType) {
                    'blog_category' => $count > 0 ? $count.' bài viết' : 'Đang cập nhật bài viết',
                    default => $count > 0 ? $count.' tour' : 'Đang cập nhật tour',
                },
                'is_active' => (bool) data_get($item, 'is_active', false),
                'label' => $label,
                'summary' => trim((string) (data_get($item, 'excerpt') ?: $taxonomyDefaults['summary'])),
                'url' => match ($taxonomyType) {
                    'region' => route('regions.show', $item),
                    'tour_category' => route('tour-categories.show', $item),
                    'blog_category' => route('blog-categories.show', ['slug' => data_get($item, 'slug')]),
                    default => route('destinations.show', $item),
                },
            ];
        });
@endphp

@if ($normalizedItems->isNotEmpty())
    @if ($wrapInSection)
        <section class="{{ $resolvedSectionClasses }}" @if ($sectionId !== '') id="{{ $sectionId }}" @endif>
    @endif
        <div @if ($containerClass !== '') class="{{ $containerClass }}" @endif>
            <div
                data-card-carousel
                @if ($autoplay) data-autoplay="true" @endif
                @if ($desktopSlider) data-desktop-slider="true" @endif
                data-interval="4200"
                style="{{ $carouselInlineStyle }}"
            >
                @if ($displayEyebrow !== '' || $displayTitle !== '' || $displayDescription !== '' || ($showNavigator && $normalizedItems->count() > 1))
                    <div class="{{ $headerClass }}">
                        @if ($displayEyebrow !== '' || $displayTitle !== '' || $displayDescription !== '')
                            <div class="max-w-3xl">
                                @if ($displayEyebrow !== '')
                                    <p class="frontsite-text-reveal text-xs font-semibold uppercase tracking-[0.28em] text-primary" data-reveal="eyebrow">
                                        {{ $displayEyebrow }}
                                    </p>
                                @endif
                                @if ($displayTitle !== '')
                                    <h2 class="frontsite-text-reveal frontsite-h2 {{ $displayEyebrow !== '' ? 'mt-3' : '' }}" data-reveal="title">
                                        {{ $displayTitle }}
                                    </h2>
                                @endif
                                @if ($displayDescription !== '')
                                    <p class="frontsite-text-reveal mt-3 text-sm leading-7 text-slate-600 sm:text-base" data-reveal="body">
                                        {{ $displayDescription }}
                                    </p>
                                @endif
                            </div>
                        @endif

                        @if ($showNavigator && $normalizedItems->count() > 1)
                            <div class="frontsite-slider-nav flex items-center gap-3">
                                <button
                                    type="button"
                                    data-card-carousel-prev
                                    class="service-card-carousel-control frontsite-text-reveal"
                                    data-reveal="meta"
                                    aria-label="{{ $taxonomyDefaults['prev_label'] }}"
                                >
                                    <i class="fa-solid fa-arrow-left"></i>
                                </button>
                                <button
                                    type="button"
                                    data-card-carousel-next
                                    class="service-card-carousel-control frontsite-text-reveal"
                                    data-reveal="meta"
                                    aria-label="{{ $taxonomyDefaults['next_label'] }}"
                                >
                                    <i class="fa-solid fa-arrow-right"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="{{ $trackSpacingClass }}">
                    <div class="service-card-carousel-track" data-card-carousel-track>
                        @foreach ($normalizedItems as $item)
                            <div class="service-card-carousel-item" data-card-carousel-item>
                                @if ($isTopicStyle)
                                    <a
                                        href="{{ $item['url'] }}"
                                        class="frontsite-text-reveal group flex h-full flex-col items-stretch gap-2.5 rounded-[1.45rem] bg-[linear-gradient(180deg,_rgba(255,255,255,0.98),_rgba(255,246,238,0.95))] px-2.5 py-3 text-center shadow-[0_18px_42px_-34px_rgba(15,23,42,0.16)] transition hover:-translate-y-1 hover:shadow-[0_24px_56px_-38px_rgba(255,106,0,0.2)] {{ $item['is_active'] ? 'ring-2 ring-orange-200 bg-[color:var(--color-primary-soft)]/65' : '' }}"
                                        data-reveal="card"
                                        data-reveal-delay="{{ number_format(($loop->index % 6) * $style['delay_step'], 2, '.', '') }}"
                                        @if ($item['is_active']) aria-current="page" @endif
                                    >
                                        <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-[1.45rem] {{ $style['image_background'] }} shadow-[0_16px_30px_-24px_rgba(15,23,42,0.22)]">
                                            @if ($item['avatar'] !== '')
                                                <img src="{{ $item['avatar'] }}" alt="{{ $item['alt'] }}" class="h-full w-full object-cover" width="500" height="500" loading="lazy" decoding="async" fetchpriority="low">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center {{ $style['fallback_background'] }}">
                                                    <span class="font-heading text-3xl font-black uppercase tracking-[0.18em] {{ $style['fallback_text'] }}">
                                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item['label'], 0, 2)) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <h3 class="line-clamp-2 text-center font-heading text-[0.8rem] font-extrabold leading-[1.1rem] text-primary transition group-hover:text-primary-hover sm:text-[0.85rem] xl:text-[0.9rem]">
                                            {{ $item['label'] }}
                                        </h3>
                                    </a>
                                @else
                                    <a
                                        href="{{ $item['url'] }}"
                                        class="frontsite-text-reveal group flex h-full flex-col overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-[0_24px_60px_-50px_rgba(15,23,42,0.35)] transition hover:-translate-y-1 hover:border-orange-200 hover:shadow-[0_30px_75px_-48px_rgba(15,23,42,0.42)]"
                                        data-reveal="card"
                                        data-reveal-delay="{{ number_format(($loop->index % 4) * $style['delay_step'], 2, '.', '') }}"
                                    >
                                        <div class="relative aspect-[5/3] overflow-hidden {{ $style['image_background'] }}">
                                            @if ($item['avatar'] !== '')
                                                <img src="{{ $item['avatar'] }}" alt="{{ $item['alt'] }}" class="h-full w-full object-cover" width="500" height="300" loading="lazy" decoding="async" fetchpriority="low">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center {{ $style['fallback_background'] }}">
                                                    <span class="font-heading text-3xl font-black uppercase tracking-[0.2em] {{ $style['fallback_text'] }}">
                                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item['label'], 0, 2)) }}
                                                    </span>
                                                </div>
                                            @endif

                                            <span class="absolute left-4 top-4 inline-flex rounded-full bg-white/92 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] {{ $style['badge_text'] }} shadow-sm">
                                                {{ $item['count_label'] }}
                                            </span>
                                        </div>

                                        <div class="flex flex-1 flex-col p-5">
                                            <h3 class="frontsite-h2-card text-slate-950">
                                                {{ $item['label'] }}
                                            </h3>
                                            <p class="mt-3 flex-1 text-sm leading-7 text-slate-600">
                                                {{ \Illuminate\Support\Str::limit(strip_tags($item['summary']), 130) }}
                                            </p>
                                            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-secondary transition group-hover:text-primary">
                                                {{ $cardCtaLabel }}
                                                <i class="fa-solid fa-arrow-right"></i>
                                            </span>
                                        </div>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @if ($wrapInSection)
        </section>
    @endif
@endif
