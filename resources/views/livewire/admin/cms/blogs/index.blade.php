<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Danh sách bài viết blog</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tách riêng trang danh sách để duyệt, lọc và đi vào biên tập nhanh hơn.</p>
        </div>

        <a href="{{ route('admin.blogs.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
            <i class="fa-solid fa-plus"></i>
            Bài viết mới
        </a>
    </div>

    @include('livewire.admin.cms.partials.blogs-submenu')

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 grid gap-3 xl:grid-cols-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên bài viết..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

            <select wire:model.live="categoryFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ str_repeat('— ', max(0, (int) ($category->tree_depth ?? 0))) }}{{ $category->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="status" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
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
                        <th class="px-4 py-3">Bài viết</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Danh mục</th>
                        <th class="px-4 py-3">Ngữ cảnh</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Tác giả</th>
                        <th class="px-4 py-3">Cập nhật</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($posts as $post)
                        <tr wire:key="blog-post-{{ $post->id }}" class="align-top">
                            <td class="px-4 py-4">
                                <a href="{{ route('admin.blogs.edit', $post) }}" wire:navigate class="font-semibold text-zinc-900 transition hover:text-red-600 dark:text-white">
                                    {{ $post->title }}
                                </a>
                                @if ($post->excerpt)
                                    <p class="mt-1 max-w-xl text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit(strip_tags((string) $post->excerpt), 110) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="block max-w-52 break-all font-mono text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $post->slug ?: 'Chưa có' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $post->category ? \App\Support\ContentCategoryTree::pathLabel($post->category) : 'Chưa gán' }}</td>
                            <td class="px-4 py-4 text-xs leading-5 text-zinc-500 dark:text-zinc-400">
                                @if ($post->countryDestination)
                                    <p>Quốc gia: {{ $post->countryDestination->name }}</p>
                                @endif
                                @if ($post->destination)
                                    <p>Điểm đến: {{ $post->destination->name }}</p>
                                @endif
                                @if (! $post->countryDestination && ! $post->destination)
                                    <span>Chưa gán</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $post->status === 'published' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : ($post->status === 'archived' ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300') }}">
                                    {{ $post->status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $post->author_name ?: 'Chưa có' }}</td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">{{ optional($post->updated_at)->format('d/m/Y H:i') ?: '-' }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.blogs.edit', $post) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300">
                                        Sửa
                                    </a>
                                    <button type="button" wire:click="deletePost({{ $post->id }})" wire:confirm="Bạn có chắc chắn muốn xóa bài viết này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có bài viết nào khớp bộ lọc hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $posts?->links() }}
        </div>
    </section>
</div>
