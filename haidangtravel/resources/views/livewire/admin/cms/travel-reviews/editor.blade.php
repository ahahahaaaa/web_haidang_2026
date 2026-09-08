<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $selectedId ? 'Biên tập đánh giá' : 'Tạo đánh giá' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                Đánh giá thuộc {{ mb_strtolower($ownerConfig['owner_type_label']) }}:
                <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ data_get($owner, $ownerConfig['owner_field']) }}</span>
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route($ownerConfig['reviews_index_route'], [$ownerConfig['owner_param'] => $owner]) }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <i class="fa-solid fa-arrow-left"></i>
                Về danh sách đánh giá
            </a>

            @if ($selectedId)
                <button type="button" wire:click="deleteReview({{ $selectedId }})" wire:confirm="Bạn có chắc chắn muốn xóa đánh giá này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                    <i class="fa-solid fa-trash"></i>
                    Xóa đánh giá
                </button>
            @endif
        </div>
    </div>

    @include('livewire.admin.cms.partials.tours-submenu')

    <x-admin.form-feedback />

    <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit.prevent="saveReview" class="space-y-5 pb-32 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu đánh giá...">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <input type="text" wire:model.defer="form.title" placeholder="Tiêu đề review" class="md:col-span-2 xl:col-span-4 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.author_name" placeholder="Tên người đánh giá" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.author_title" placeholder="Vai trò / đoàn / ngữ cảnh" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="number" min="1" max="5" step="0.1" wire:model.defer="form.rating_value" placeholder="Điểm đánh giá" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <select wire:model.defer="form.status" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="draft">draft</option>
                    <option value="published">published</option>
                    <option value="archived">archived</option>
                </select>
                <input type="date" wire:model.defer="form.published_at" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="number" wire:model.defer="form.sort_order" placeholder="Thứ tự" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                <label class="md:col-span-2 xl:col-span-4 flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <input type="checkbox" wire:model.defer="form.is_featured" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                    Đánh giá nổi bật
                </label>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-semibold text-zinc-900 dark:text-white">Nội dung đánh giá</label>
                <textarea rows="7" wire:model.defer="form.content" placeholder="Nội dung đánh giá sẽ hiển thị ngoài frontsite và dùng cho schema review." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
            </div>

            <x-admin.form-action-bar>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveReview" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600 disabled:cursor-not-allowed disabled:opacity-70">
                    <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="saveReview"></i>
                    <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="saveReview"></i>
                    Lưu đánh giá
                </button>
            </x-admin.form-action-bar>
        </form>
    </section>
</div>
