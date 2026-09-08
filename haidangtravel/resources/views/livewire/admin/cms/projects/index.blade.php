<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Danh sách dự án</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Danh sách dự án được tách riêng khỏi form chi tiết để việc duyệt và tìm kiếm nhanh hơn.</p>
        </div>

        <a href="{{ route('admin.projects.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
            <i class="fa-solid fa-plus"></i>
            Dự án mới
        </a>
    </div>

    @include('livewire.admin.cms.partials.projects-submenu')

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-5 grid gap-3 md:grid-cols-2">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            <input type="text" wire:model.live.debounce.300ms="locationFilter" placeholder="Tìm theo vị trí..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
        </div>

        <div class="space-y-3">
            @forelse ($projects as $project)
                <div wire:key="project-{{ $project->id }}" class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 transition hover:border-red-300 dark:border-zinc-700 dark:bg-zinc-800/70">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate class="text-left text-base font-semibold text-zinc-900 transition hover:text-red-600 dark:text-white">
                                {{ $project->title }}
                            </a>
                            <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                @if ($project->category)
                                    <span class="rounded-full bg-red-50 px-3 py-1 font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">{{ $project->category->name }}</span>
                                @endif
                                @if ($project->type)
                                    <span class="rounded-full bg-white px-3 py-1 font-semibold text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">{{ $project->type->name }}</span>
                                @endif
                                @if ($project->location)
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ $project->location }}</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.projects.edit', $project) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                Sửa
                            </a>
                            <button type="button" wire:click="deleteProject({{ $project->id }})" wire:confirm="Bạn có chắc chắn muốn xóa dự án này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-4 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                Xóa
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Chưa có dự án nào khớp bộ lọc hiện tại.
                </div>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $projects?->links() }}
        </div>
    </section>
</div>
