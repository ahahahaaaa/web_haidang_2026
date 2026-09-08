@php
    $items = [
        [
            'label' => 'Danh sách dự án',
            'description' => 'Xem dự án, lọc theo tên và vị trí.',
            'route' => 'admin.projects',
            'active' => request()->routeIs('admin.projects'),
        ],
        [
            'label' => 'Biên tập dự án',
            'description' => 'Tạo mới hoặc cập nhật chi tiết dự án.',
            'route' => 'admin.projects.create',
            'active' => request()->routeIs('admin.projects.create', 'admin.projects.edit'),
        ],
        [
            'label' => 'Danh mục dự án',
            'description' => 'Quản lý nhóm danh mục cho dự án.',
            'route' => 'admin.projects.categories',
            'active' => request()->routeIs('admin.projects.categories'),
        ],
        [
            'label' => 'Biên tập danh mục',
            'description' => 'Tạo mới hoặc cập nhật danh mục dự án.',
            'route' => 'admin.projects.categories.create',
            'active' => request()->routeIs('admin.projects.categories.create', 'admin.projects.categories.edit'),
        ],
        [
            'label' => 'Loại dự án',
            'description' => 'Quản lý loại để lọc và phân tầng nội dung.',
            'route' => 'admin.projects.types',
            'active' => request()->routeIs('admin.projects.types'),
        ],
        [
            'label' => 'Biên tập loại',
            'description' => 'Tạo mới hoặc cập nhật loại dự án.',
            'route' => 'admin.projects.types.create',
            'active' => request()->routeIs('admin.projects.types.create', 'admin.projects.types.edit'),
        ],
    ];
@endphp

<section class="rounded-3xl bg-zinc-50 p-3 dark:bg-zinc-900/60">
    <div class="grid gap-3 xl:grid-cols-3">
        @foreach ($items as $item)
            <a
                href="{{ route($item['route']) }}"
                wire:navigate
                class="{{ $item['active'] ? 'bg-white text-red-700 ring-1 ring-red-200 dark:bg-zinc-900 dark:text-red-300 dark:ring-red-500/30' : 'bg-white/70 text-zinc-700 ring-1 ring-zinc-200 transition hover:text-red-600 hover:ring-red-200 dark:bg-zinc-950/40 dark:text-zinc-200 dark:ring-zinc-800 dark:hover:text-red-300 dark:hover:ring-red-500/20' }} rounded-2xl px-4 py-3"
            >
                <span class="block text-sm font-semibold">{{ $item['label'] }}</span>
                <span class="mt-1 block text-xs leading-5 text-zinc-500 dark:text-zinc-400">{{ $item['description'] }}</span>
            </a>
        @endforeach
    </div>
</section>
