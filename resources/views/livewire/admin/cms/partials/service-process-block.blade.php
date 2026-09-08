<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Block Quy trình thực hiện</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Mobile hiển thị dạng slider, desktop chỉ chuyển slider khi số card lớn hơn 5.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Eyebrow</label>
            <input type="text" wire:model.defer="form.detail_config.process.eyebrow" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
            <input type="text" wire:model.defer="form.detail_config.process.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
        </div>

        <div class="space-y-2 md:col-span-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
            <textarea rows="3" wire:model.defer="form.detail_config.process.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
        </div>
    </div>

    <div class="space-y-4">
        @foreach (($form['detail_config']['process']['cards'] ?? []) as $index => $card)
            <div wire:key="service-process-card-{{ $index }}" class="grid gap-3 rounded-3xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 md:grid-cols-[0.38fr_0.62fr]">
                <input type="text" wire:model.defer="form.detail_config.process.cards.{{ $index }}.icon_class" placeholder="fa-solid fa-house" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.detail_config.process.cards.{{ $index }}.title" placeholder="Tiêu đề card" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <textarea rows="3" wire:model.defer="form.detail_config.process.cards.{{ $index }}.description" placeholder="Mô tả card" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2"></textarea>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 md:col-span-2">
                    Nhập class Font Awesome, ví dụ: <code>fa-solid fa-key</code>, <code>fa-solid fa-file-lines</code>, <code>fa-solid fa-paint-roller</code>.
                </p>
                @if (count($form['detail_config']['process']['cards'] ?? []) > 1)
                    <button type="button" wire:click="removeProcessCard({{ $index }})" class="justify-self-start rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300 md:col-span-2">
                        Xóa
                    </button>
                @endif
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap justify-end">
        <button type="button" wire:click="addProcessCard" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm card
        </button>
    </div>
</section>
