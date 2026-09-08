<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Thiết lập chung</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Đặt tiêu đề, slug, trạng thái hiển thị và nội dung text chính của landing page.</p>
        </div>

        <label class="inline-flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
            <input type="checkbox" wire:model.defer="form.is_active" class="rounded border-zinc-300 text-teal-600 focus:ring-teal-500">
            Đang kích hoạt landing page
        </label>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.page_key" placeholder="Page key" readonly class="rounded-2xl border border-zinc-200 bg-zinc-100 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
        <input type="text" wire:model.defer="form.title" placeholder="Tiêu đề quản trị" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.slug" placeholder="Slug" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.hero_badge" placeholder="Hero badge" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.hero_title" placeholder="Hero title" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2">
        <textarea rows="3" wire:model.defer="form.hero_excerpt" placeholder="Hero excerpt" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <input type="text" wire:model.defer="form.intro_title" placeholder="Intro title" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.intro_excerpt" placeholder="Intro excerpt" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
    </div>

    <x-admin.quill-editor wire:key="landing-body-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['body'] ?? '')) }}" model="form.body" :value="$form['body'] ?? ''" mode="rich" :allow-images="true" rows="8" placeholder="Nội dung landing page chi tiết." />

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.cta_title" placeholder="CTA title" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.cta_excerpt" placeholder="CTA excerpt" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.cta_primary_label" placeholder="CTA primary label" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.cta_primary_url" placeholder="CTA primary url" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.cta_secondary_label" placeholder="CTA secondary label" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.cta_secondary_url" placeholder="CTA secondary url" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.meta_title" placeholder="Meta title" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.og_title" placeholder="OG title" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="url" wire:model.defer="form.canonical_url" placeholder="Canonical URL" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.robots_directive" placeholder="Robots" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.meta_description" placeholder="Meta description" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <textarea rows="3" wire:model.defer="form.og_description" placeholder="OG description" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-teal-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
    </div>
</section>
