<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Danh mục dự án</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tách riêng màn danh mục dự án để quản trị taxonomy rõ ràng hơn.</p>
        </div>

        <a href="{{ route('admin.projects.categories.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
            <i class="fa-solid fa-folder-plus"></i>
            Danh mục mới
        </a>
    </div>

    @include('livewire.admin.cms.partials.projects-submenu')

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="space-y-3">
            @forelse ($categories as $category)
                <div wire:key="project-category-{{ $category->id }}" class="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-800/70 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-white">{{ $category->name }}</p>
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $category->slug }}</p>
                        @if ($category->description)
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $category->description }}</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.projects.categories.edit', $category) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                            Sửa
                        </a>
                        <button type="button" wire:click="deleteCategory({{ $category->id }})" wire:confirm="Bạn có chắc chắn muốn xóa danh mục dự án này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-4 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa
                        </button>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Chưa có danh mục dự án nào.
                </div>
            @endforelse
        </div>
    </section>
</div>
