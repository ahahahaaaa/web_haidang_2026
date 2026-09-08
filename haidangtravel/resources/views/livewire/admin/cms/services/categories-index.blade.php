<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Danh mục dịch vụ</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Taxonomy dịch vụ được tách thành page riêng để dễ quản trị cấu trúc hiển thị frontsite.</p>
        </div>

        <a href="{{ route('admin.services.categories.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
            <i class="fa-solid fa-folder-plus"></i>
            Danh mục mới
        </a>
    </div>

    @include('livewire.admin.cms.partials.services-submenu')

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4">
            <input type="text" wire:model.live.debounce.300ms="categorySearch" placeholder="Tìm theo tên hoặc slug danh mục..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
        </div>

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">Danh mục</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Mô tả</th>
                        <th class="px-4 py-3">Dịch vụ</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($categoryRows as $category)
                        <tr wire:key="service-category-{{ $category->id }}" class="align-top">
                            <td class="px-4 py-4 font-semibold text-zinc-900 dark:text-white">{{ $category->name }}</td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">{{ $category->slug }}</td>
                            <td class="px-4 py-4 text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit(strip_tags((string) $category->description), 120) ?: 'Chưa có mô tả' }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $category->services_count }}</td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.services.categories.edit', $category) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                        Sửa
                                    </a>
                                    <button type="button" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Bạn có chắc chắn muốn xóa danh mục dịch vụ này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có danh mục dịch vụ nào.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $categoryRows->links() }}
        </div>
    </section>
</div>
