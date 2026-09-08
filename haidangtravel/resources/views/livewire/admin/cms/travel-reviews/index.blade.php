<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Quản lý đánh giá</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Danh sách đánh giá của {{ mb_strtolower($ownerConfig['owner_type_label']) }}:
                <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ data_get($owner, $ownerConfig['owner_field']) }}</span>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route($ownerConfig['edit_route'], [$ownerConfig['owner_param'] => $owner]) }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <i class="fa-solid fa-arrow-left"></i>
                Về trang {{ $ownerConfig['owner_label'] }}
            </a>

            <a href="{{ route($ownerConfig['reviews_create_route'], [$ownerConfig['owner_param'] => $owner]) }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
                <i class="fa-solid fa-plus"></i>
                Thêm đánh giá
            </a>
        </div>
    </div>

    @include('livewire.admin.cms.partials.tours-submenu')

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 grid gap-3 lg:grid-cols-4">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tiêu đề, tác giả, nội dung..." class="lg:col-span-3 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

            <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả trạng thái</option>
                <option value="draft">draft</option>
                <option value="published">published</option>
                <option value="archived">archived</option>
            </select>
        </div>

        <div class="mb-4 flex flex-wrap gap-3 text-sm">
            <span class="inline-flex items-center gap-2 rounded-2xl bg-zinc-100 px-4 py-2 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                Tổng đánh giá: {{ $reviewStats['all'] }}
            </span>
            <span class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-2 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                Đang published: {{ $reviewStats['published'] }}
            </span>
        </div>

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">Đánh giá</th>
                        <th class="px-4 py-3">Tác giả</th>
                        <th class="px-4 py-3">Điểm</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Ngày hiển thị</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($reviews as $review)
                        <tr wire:key="travel-review-{{ $review->id }}" class="align-top">
                            <td class="px-4 py-4">
                                <a href="{{ route($ownerConfig['reviews_edit_route'], array_merge([$ownerConfig['owner_param'] => $owner], ['review' => $review])) }}" wire:navigate class="font-semibold text-zinc-900 transition hover:text-red-600 dark:text-white">
                                    {{ $review->title ?: 'Đánh giá #'.$review->id }}
                                </a>
                                <p class="mt-1 max-w-xl text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit($review->content, 120) }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                <p>{{ $review->author_name }}</p>
                                @if ($review->author_title)
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $review->author_title }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ number_format((float) $review->rating_value, 1, ',', '.') }}/5</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $review->status === 'published' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : ($review->status === 'archived' ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300') }}">
                                    {{ $review->status }}
                                </span>
                                @if ($review->is_featured)
                                    <p class="mt-2 text-xs font-semibold text-red-600 dark:text-red-300">Nổi bật</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">{{ optional($review->published_at)->format('d/m/Y') ?: 'Chưa đặt' }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route($ownerConfig['reviews_edit_route'], array_merge([$ownerConfig['owner_param'] => $owner], ['review' => $review])) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                        Sửa
                                    </a>
                                    <button type="button" wire:click="deleteReview({{ $review->id }})" wire:confirm="Bạn có chắc chắn muốn xóa đánh giá này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có đánh giá nào khớp bộ lọc hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $reviews->links() }}
        </div>
    </section>
</div>
