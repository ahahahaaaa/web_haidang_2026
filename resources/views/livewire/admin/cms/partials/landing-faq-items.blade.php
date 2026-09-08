@php
    $pageKey = (string) ($form['page_key'] ?? '');
    $faqTitle = match ($pageKey) {
        'services' => 'FAQ landing dịch vụ',
        'domestic_tours' => 'FAQ tour trong nước',
        'international_tours' => 'FAQ tour nước ngoài',
        'group_tours' => 'FAQ tour đoàn',
        'blog' => 'FAQ landing blog',
        default => 'FAQ landing page',
    };
    $faqDescription = match ($pageKey) {
        'services' => 'Các câu hỏi này sẽ hiển thị ở landing dịch vụ để chốt intent trước khi người dùng chuyển sang service detail hoặc form liên hệ.',
        'domestic_tours' => 'Ưu tiên các câu hỏi về thời gian đi, loại khách phù hợp, mức giá và cách chọn hành trình trong nước.',
        'international_tours' => 'Ưu tiên các câu hỏi về hồ sơ, lịch khởi hành, chi phí và các lưu ý trước khi đi tour nước ngoài.',
        'group_tours' => 'Nên tập trung vào các câu hỏi về quy mô đoàn, cách nhận brief, lịch trình riêng và dịch vụ hỗ trợ đi kèm.',
        'blog' => 'Các câu hỏi này giúp người đọc blog hiểu cách chuyển từ nội dung tham khảo sang hành động đặt tour hoặc gửi yêu cầu tư vấn.',
        default => 'Các câu hỏi này sẽ hiển thị ở landing page tương ứng để giảm bớt băn khoăn trước khi khách liên hệ.',
    };
@endphp

<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $faqTitle }}</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $faqDescription }}</p>
        </div>

        <button type="button" wire:click="addFaqItem" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            Thêm FAQ
        </button>
    </div>

    <div class="space-y-4">
        @foreach (($form['faq_items'] ?? []) as $index => $item)
            <div wire:key="landing-faq-item-{{ $pageKey }}-{{ $index }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">FAQ {{ $loop->iteration }}</p>

                    @if (count($form['faq_items'] ?? []) > 1)
                        <button type="button" wire:click="removeFaqItem({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa
                        </button>
                    @endif
                </div>

                <div class="grid gap-4">
                    <input type="text" wire:model.defer="form.faq_items.{{ $index }}.question" placeholder="Câu hỏi" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <textarea rows="4" wire:model.defer="form.faq_items.{{ $index }}.answer" placeholder="Câu trả lời" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                </div>
            </div>
        @endforeach
    </div>
</section>
