<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $taxonomyConfig['plural_label'] }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Danh sách taxonomy đang cấp dữ liệu thật cho frontsite, filter và internal linking của cụm tour.</p>
        </div>

        <a href="{{ route($taxonomyConfig['create_route']) }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
            <i class="fa-solid fa-plus"></i>
            Tạo {{ $taxonomyConfig['singular_label'] }}
        </a>
    </div>

    @include('livewire.admin.cms.partials.tours-submenu')

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 grid gap-3 lg:grid-cols-2">
            <input type="text" wire:model.live.debounce.300ms="taxonomySearch" placeholder="Tìm theo tên hoặc slug..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            <select wire:model.live="taxonomyStatusFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả trạng thái</option>
                <option value="draft">draft</option>
                <option value="published">published</option>
                <option value="archived">archived</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">{{ $taxonomyConfig['singular_label'] }}</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Ngữ cảnh</th>
                        <th class="px-4 py-3">Tour liên quan</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($taxonomyItems as $item)
                        <tr wire:key="{{ $taxonomyConfig['type'] }}-{{ $item->id }}" class="align-top">
                            <td class="px-4 py-4">
                                <a href="{{ route($taxonomyConfig['edit_route'], [$taxonomyConfig['type'] => $item]) }}" wire:navigate class="font-semibold text-zinc-900 transition hover:text-red-600 dark:text-white">
                                    {{ $item->name }}
                                </a>
                                @if ($item->excerpt)
                                    <p class="mt-1 max-w-xl text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit(strip_tags((string) $item->excerpt), 110) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">{{ $item->slug }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ ($item->status ?? 'draft') === 'published' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : (($item->status ?? 'draft') === 'archived' ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300') }}">
                                    {{ $item->status ?? 'draft' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                                @if ($taxonomyConfig['type'] === 'destination' && ($item->is_country_root ?? false))
                                    <p>Quốc gia root</p>
                                @elseif ($taxonomyConfig['type'] === 'destination' && $item->country)
                                    <p>Quốc gia: {{ $item->country->name }}</p>
                                @endif
                                @if (filled($item->scope ?? null))
                                    <p>Phạm vi: {{ \Src\Domains\Cms\Enums\TourScope::tryFrom($item->scope)?->label() ?? $item->scope }}</p>
                                @endif
                                @if (method_exists($item, 'region') && $item->region)
                                    <p>Vùng: {{ $item->region->name }}</p>
                                @endif
                                @if (! ($taxonomyConfig['type'] === 'destination' && (($item->is_country_root ?? false) || $item->country)) && ! filled($item->scope ?? null) && (! method_exists($item, 'region') || ! $item->region))
                                    <span>Chưa có ngữ cảnh phụ</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $item->tours_count ?? 0 }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    @if (config('travel_reviews.enabled', true) && filled($taxonomyConfig['reviews_index_route'] ?? null))
                                        <a href="{{ route($taxonomyConfig['reviews_index_route'], [$taxonomyConfig['type'] => $item]) }}" wire:navigate class="rounded-2xl border border-amber-200 px-3 py-2 text-sm font-medium text-amber-700 dark:border-amber-500/30 dark:text-amber-300">
                                            Quản lý đánh giá
                                        </a>
                                    @endif
                                    <a href="{{ route($taxonomyConfig['edit_route'], [$taxonomyConfig['type'] => $item]) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                        Sửa
                                    </a>
                                    <button type="button" wire:click="{{ $taxonomyConfig['delete_action'] }}({{ $item->id }})" wire:confirm="Bạn có chắc chắn muốn xóa {{ $taxonomyConfig['singular_label'] }} này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có {{ $taxonomyConfig['singular_label'] }} nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $taxonomyItems->links() }}
        </div>
    </section>
</div>
