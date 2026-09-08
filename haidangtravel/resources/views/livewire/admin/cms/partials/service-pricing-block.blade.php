<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Bảng giá tham khảo</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Cho phép thay đổi toàn bộ text của heading, cột, từng dòng và ghi chú cuối bảng.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Eyebrow</label>
            <input type="text" wire:model.defer="form.detail_config.pricing.eyebrow" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
            <input type="text" wire:model.defer="form.detail_config.pricing.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
        </div>

        <div class="space-y-2 md:col-span-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
            <textarea rows="3" wire:model.defer="form.detail_config.pricing.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-4">
        @foreach ([
            'label' => 'Tên cột 1',
            'size' => 'Tên cột 2',
            'price' => 'Tên cột 3',
            'note' => 'Tên cột 4',
        ] as $column => $columnLabel)
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $columnLabel }}</label>
                <input type="text" wire:model.defer="form.detail_config.pricing.columns.{{ $column }}" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>
        @endforeach
    </div>

    <div class="space-y-4">
        @foreach (($form['detail_config']['pricing']['rows'] ?? []) as $index => $row)
            <div wire:key="service-pricing-row-{{ $index }}" class="grid gap-3 rounded-3xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 md:grid-cols-4">
                <input type="text" wire:model.defer="form.detail_config.pricing.rows.{{ $index }}.label" placeholder="Hạng mục" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.detail_config.pricing.rows.{{ $index }}.size" placeholder="Quy mô" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.detail_config.pricing.rows.{{ $index }}.price" placeholder="Mức giá" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <div class="flex gap-3">
                    <input type="text" wire:model.defer="form.detail_config.pricing.rows.{{ $index }}.note" placeholder="Ghi chú" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @if (count($form['detail_config']['pricing']['rows'] ?? []) > 1)
                        <button type="button" wire:click="removePricingRow({{ $index }})" class="rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Footnote</label>
        <textarea rows="3" wire:model.defer="form.detail_config.pricing.footnote" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
    </div>

    <div class="flex flex-wrap justify-end">
        <button type="button" wire:click="addPricingRow" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm dòng giá
        </button>
    </div>
</section>
