@php
    /** @var \Src\Domains\Cms\Models\ContentCategory $category */
    $active = ($active ?? false) === true;
    $revealDelay = trim((string) ($revealDelay ?? '0'));
    $categoryAvatar = \App\Support\FrontsiteMedia::taxonomyAvatarUrl(
        $category,
        \App\Support\FrontsiteMedia::SIZE_SMALL,
        'cover_image_url',
        false,
    );
    $categoryInitials = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($category->name, 0, 2));
@endphp

<a
    href="{{ route('blog-categories.show', ['slug' => $category->slug]) }}"
    class="frontsite-text-reveal group flex h-full flex-col items-stretch gap-2.5 rounded-[1.45rem] bg-[linear-gradient(180deg,_rgba(255,255,255,0.98),_rgba(255,246,238,0.95))] p-3 text-center shadow-[0_18px_42px_-34px_rgba(15,23,42,0.16)] transition hover:-translate-y-1 hover:shadow-[0_24px_56px_-38px_rgba(255,106,0,0.2)] {{ $active ? 'ring-2 ring-orange-200 bg-[color:var(--color-primary-soft)]/65' : '' }}"
    data-reveal="card"
    data-reveal-delay="{{ $revealDelay }}"
    @if ($active) aria-current="page" @endif
>
    <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-[1.45rem] bg-[linear-gradient(135deg,_rgba(255,106,0,0.16),_rgba(255,140,0,0.22))] shadow-[0_16px_30px_-24px_rgba(15,23,42,0.22)]">
        @if ($categoryAvatar)
            <img src="{{ $categoryAvatar }}" alt="{{ $category->name }}" class="h-full w-full object-cover" loading="lazy" decoding="async">
        @else
            <div class="flex h-full w-full items-center justify-center bg-[radial-gradient(circle_at_top,_rgba(255,140,0,0.24),_transparent_38%),linear-gradient(135deg,_rgba(255,106,0,0.2),_rgba(255,106,0,0.08))]">
                <span class="font-heading text-3xl font-black uppercase tracking-[0.18em] text-primary">
                    {{ $categoryInitials }}
                </span>
            </div>
        @endif
    </div>

    <div class="space-y-1 px-1 pb-1">
        <h3 class="line-clamp-2 text-center font-heading text-[0.85rem] font-extrabold leading-[1.15rem] text-primary transition group-hover:text-primary-hover sm:text-[0.9rem] xl:text-[0.95rem]">
            {{ $category->name }}
        </h3>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">
            {{ (int) ($category->branch_blog_posts_count ?? 0) }} bài viết
        </p>
    </div>
</a>
