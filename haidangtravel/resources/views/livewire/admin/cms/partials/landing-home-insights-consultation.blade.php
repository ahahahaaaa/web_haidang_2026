<datalist id="landing-home-blog-options">
    @foreach ($blogOptions as $post)
        <option value="{{ $post->id }} - {{ $post->title }}"></option>
    @endforeach
</datalist>

<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Góc nhìn</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Nhập ID bài viết hoặc chọn nhanh theo cú pháp “ID - tiêu đề”.</p>
        </div>

        @if (count($form['home_config']['insights']['items'] ?? []) < 12)
            <button type="button" wire:click="addHomeInsightItem" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                Thêm bài viết
            </button>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.home_config.insights.eyebrow" placeholder="Eyebrow section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.insights.title" placeholder="Tiêu đề section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <textarea rows="3" wire:model.defer="form.home_config.insights.description" placeholder="Mô tả section" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <input type="text" wire:model.defer="form.home_config.insights.cta_label" placeholder="Label CTA" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.insights.cta_url" placeholder="URL CTA" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach (($form['home_config']['insights']['items'] ?? []) as $index => $item)
            @php
                $selectedBlogId = \App\Support\HomePageContent::extractReferenceId($item['blog_ref'] ?? '');
                $selectedBlog = $selectedBlogId ? $blogOptions->firstWhere('id', $selectedBlogId) : null;
            @endphp

            <div wire:key="landing-home-insight-item-{{ $item['uuid'] }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Bài viết {{ $loop->iteration }}</p>

                    @if (count($form['home_config']['insights']['items'] ?? []) > 1)
                        <button type="button" wire:click="removeHomeInsightItem({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa
                        </button>
                    @endif
                </div>

                <div class="grid gap-3">
                    <input type="text" list="landing-home-blog-options" wire:model.defer="form.home_config.insights.items.{{ $index }}.blog_ref" placeholder="Ví dụ 12 hoặc 12 - Tiêu đề bài viết" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <div class="rounded-2xl bg-zinc-50 px-4 py-3 text-xs text-zinc-500 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ $selectedBlog ? 'Đang chọn: '.$selectedBlog->title : 'Chưa map được bài viết từ giá trị đang nhập.' }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">CTA cuối trang và popup tư vấn dùng chung</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Popup này sẽ được dùng chung trên toàn frontsite, gồm cả CTA ở header và homepage.</p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.home_config.final_cta.title" placeholder="Tiêu đề CTA cuối trang" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2">
        <textarea rows="3" wire:model.defer="form.home_config.final_cta.description" placeholder="Mô tả CTA cuối trang" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <input type="text" wire:model.defer="form.home_config.final_cta.primary_label" placeholder="Label nút popup" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.final_cta.secondary_label" placeholder="Label nút phụ" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.final_cta.secondary_url" placeholder="URL nút phụ" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2">
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <input type="text" wire:model.defer="form.home_config.consultation.eyebrow" placeholder="Eyebrow popup" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.consultation.button_label" placeholder="Label nút gửi popup" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
        <input type="text" wire:model.defer="form.home_config.consultation.title" placeholder="Tiêu đề popup" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2">
        <textarea rows="3" wire:model.defer="form.home_config.consultation.description" placeholder="Mô tả popup" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
        <input type="text" wire:model.defer="form.home_config.consultation.success_message" placeholder="Thông báo thành công" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2">
    </div>
</section>
