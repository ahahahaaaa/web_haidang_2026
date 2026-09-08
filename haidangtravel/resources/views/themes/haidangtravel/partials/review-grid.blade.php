@php
    $items = collect($items ?? [])
        ->filter(fn ($item) => is_array($item))
        ->values();
    $summary = $summary ?? null;
    $sectionId = $sectionId ?? null;
    $title = trim((string) ($title ?? 'Đánh giá nổi bật'));
    $description = trim((string) ($description ?? ''));
    $averageLabel = filled(data_get($summary, 'average_value'))
        ? number_format((float) data_get($summary, 'average_value'), 1, ',', '.').'/5'
        : null;
    $reviewCount = data_get($summary, 'count');
@endphp

@if ($items->isNotEmpty())
    <section @if ($sectionId) id="{{ $sectionId }}" @endif class="space-y-5">
        @include('themes.haidangtravel.partials.section-heading', [
            'description' => $description,
            'title' => $title,
            'width' => 'max-w-none',
        ])

        <div class="grid gap-4 xl:grid-cols-[18rem,1fr]">
            @if ($averageLabel)
                <div class="overflow-hidden rounded-[1.85rem] bg-[linear-gradient(180deg,#0d1730_0%,#10254a_100%)] p-6 text-white shadow-[0_34px_90px_-48px_rgba(15,23,42,0.72)]">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-orange-100">Điểm đánh giá</p>
                    <p class="mt-4 font-heading text-5xl font-bold leading-none">{{ $averageLabel }}</p>
                    <p class="mt-4 text-sm leading-7 text-slate-200">
                        @if (is_numeric($reviewCount) && (int) $reviewCount > 0)
                            {{ (int) $reviewCount }} đánh giá đang hiển thị trực tiếp trên trang này.
                        @else
                            Khối đánh giá này được hiển thị trực tiếp trên trang để người xem đọc nhanh trước khi gửi yêu cầu.
                        @endif
                    </p>
                </div>
            @endif

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($items as $index => $item)
                    @php
                        $reviewDate = trim((string) data_get($item, 'published_at'));
                        $reviewRating = filled(data_get($item, 'rating_value'))
                            ? number_format((float) data_get($item, 'rating_value'), 1, ',', '.')
                            : null;
                    @endphp

                    <article class="theme-panel frontsite-text-reveal flex h-full flex-col gap-4 p-5" data-reveal="card" data-reveal-delay="{{ number_format(($index % 3) * 0.06, 2, '.', '') }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="space-y-2">
                                <h3 class="font-heading text-lg font-bold leading-tight text-slate-950">
                                    {{ trim((string) data_get($item, 'title')) !== '' ? data_get($item, 'title') : 'Nhận xét nổi bật' }}
                                </h3>
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-slate-900">{{ data_get($item, 'author_name') }}</p>
                                    @if (trim((string) data_get($item, 'author_title')) !== '')
                                        <p class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ data_get($item, 'author_title') }}</p>
                                    @endif
                                </div>
                            </div>

                            @if ($reviewRating)
                                <span class="inline-flex shrink-0 items-center gap-2 rounded-full bg-[color:var(--color-primary-soft)] px-3 py-1 text-sm font-semibold text-primary">
                                    <i class="fa-solid fa-star text-[color:var(--color-warning)]"></i>
                                    {{ $reviewRating }}
                                </span>
                            @endif
                        </div>

                        <div class="text-sm leading-7 text-slate-600">
                            {!! nl2br(e((string) data_get($item, 'content')), false) !!}
                        </div>

                        @if ($reviewDate !== '')
                            <p class="mt-auto text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                                {{ \Illuminate\Support\Carbon::parse($reviewDate)->format('d/m/Y') }}
                            </p>
                        @endif
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
