<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $editingCategoryId ? 'Biên tập danh mục dự án' : 'Tạo danh mục dự án' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Trang detail riêng để chỉnh slug, mô tả và thứ tự cho danh mục dự án.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.projects.categories') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <i class="fa-solid fa-arrow-left"></i>
                Về danh mục
            </a>
            @if ($editingCategoryId)
                <button type="button" wire:click="deleteCategory({{ $editingCategoryId }})" wire:confirm="Bạn có chắc chắn muốn xóa danh mục dự án này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                    <i class="fa-solid fa-trash"></i>
                    Xóa danh mục
                </button>
            @endif
        </div>
    </div>

    @include('livewire.admin.cms.partials.projects-submenu')

    <x-admin.form-feedback />

    <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit="saveCategory" class="space-y-4 pb-32 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu danh mục dự án...">
            <input type="text" wire:model.defer="categoryForm.name" placeholder="Tên danh mục" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            <input type="text" wire:model.defer="categoryForm.slug" placeholder="Slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            <x-admin.quill-editor wire:key="project-category-description-{{ $editingCategoryId ?? 'new' }}-{{ md5((string) ($categoryForm['description'] ?? '')) }}" model="categoryForm.description" :value="$categoryForm['description'] ?? ''" rows="3" placeholder="Mô tả" />
            <input type="number" wire:model.defer="categoryForm.sort_order" placeholder="Thứ tự" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

            <x-admin.form-action-bar>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveCategory" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-zinc-900 px-5 py-3 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-70 dark:bg-white dark:text-zinc-900">
                    <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="saveCategory"></i>
                    <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="saveCategory"></i>
                    Lưu
                </button>
            </x-admin.form-action-bar>
        </form>
    </section>
</div>
