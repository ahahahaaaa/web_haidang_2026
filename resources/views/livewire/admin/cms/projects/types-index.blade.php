<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Loại dự án</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tách riêng page loại dự án để quản lý filter và phân loại nội dung rõ ràng hơn.</p>
        </div>

        <a href="{{ route('admin.projects.types.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
            <i class="fa-solid fa-layer-group"></i>
            Loại mới
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
            @forelse ($types as $type)
                <div wire:key="project-type-{{ $type->id }}" class="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4 dark:border-zinc-700 dark:bg-zinc-800/70 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="font-semibold text-zinc-900 dark:text-white">{{ $type->name }}</p>
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $type->slug }}</p>
                        @if ($type->description)
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $type->description }}</p>
                        @endif
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.projects.types.edit', $type) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                            Sửa
                        </a>
                        <button type="button" wire:click="deleteType({{ $type->id }})" wire:confirm="Bạn có chắc chắn muốn xóa loại dự án này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-4 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa
                        </button>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Chưa có loại dự án nào.
                </div>
            @endforelse
        </div>
    </section>
</div>
