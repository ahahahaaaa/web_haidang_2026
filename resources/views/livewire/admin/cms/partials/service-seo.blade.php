<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">SEO & Metadata</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Thiết lập riêng cho service detail public và social sharing.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ([
            'cover_alt' => 'Alt text cover',
            'meta_title' => 'Meta title',
            'og_title' => 'OG title',
            'canonical_url' => 'Canonical URL',
            'robots_directive' => 'Robots',
        ] as $field => $label)
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $label }}</label>
                <input type="text" wire:model.defer="form.{{ $field }}" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>
        @endforeach

        <div class="space-y-2 md:col-span-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Meta description</label>
            <x-admin.quill-editor wire:key="service-meta-description-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['meta_description'] ?? '')) }}" model="form.meta_description" :value="$form['meta_description'] ?? ''" rows="3" placeholder="Mô tả SEO ngắn gọn, không cần HTML." />
        </div>

        <div class="space-y-2 md:col-span-2">
            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">OG description</label>
            <x-admin.quill-editor wire:key="service-og-description-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['og_description'] ?? '')) }}" model="form.og_description" :value="$form['og_description'] ?? ''" rows="3" placeholder="Mô tả Open Graph ngắn gọn." />
        </div>
    </div>
</section>
