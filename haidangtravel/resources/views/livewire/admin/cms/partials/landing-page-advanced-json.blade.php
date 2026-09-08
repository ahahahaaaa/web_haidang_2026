<details class="rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <summary class="cursor-pointer list-none text-lg font-semibold text-zinc-900 dark:text-white">Advanced JSON config</summary>
    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Giữ các config cũ dưới dạng JSON để không mất dữ liệu đang dùng ở runtime khác hoặc migration trước đó.</p>

    <div class="mt-5 grid gap-4">
        <textarea rows="8" wire:model.defer="form.home_config_json" placeholder="Home config JSON" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
        <textarea rows="8" wire:model.defer="form.service_detail_config_json" placeholder="Service detail config JSON" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
        <textarea rows="8" wire:model.defer="form.estimate_config_json" placeholder="Estimate config JSON" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
        <textarea rows="8" wire:model.defer="form.schema_json" placeholder="Schema JSON (optional)" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 font-mono text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
    </div>
</details>
