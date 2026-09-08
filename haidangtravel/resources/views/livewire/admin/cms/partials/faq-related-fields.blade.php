@php
    $faqTitle = $faqTitle ?? 'FAQ';
    $faqDescription = $faqDescription ?? 'Quản lý các câu hỏi thường gặp sẽ hiển thị ở cuối trang chi tiết.';
    $relatedTitle = $relatedTitle ?? 'Câu hỏi liên quan';
    $relatedDescription = $relatedDescription ?? 'Các câu hỏi ngắn giúp mở rộng intent tìm kiếm và gợi ý nội dung liên quan cho người đọc.';
    $faqItems = $faqItems ?? ($form['faq_items'] ?? []);
    $faqWireKeyPrefix = $faqWireKeyPrefix ?? 'faq-item';
    $faqQuestionPathPrefix = $faqQuestionPathPrefix ?? 'form.faq_items';
    $faqAnswerPathPrefix = $faqAnswerPathPrefix ?? 'form.faq_items';
    $addFaqAction = $addFaqAction ?? 'addFaqItem';
    $removeFaqAction = $removeFaqAction ?? 'removeFaqItem';
    $faqItemLabel = $faqItemLabel ?? 'FAQ';
    $addFaqLabel = $addFaqLabel ?? 'Thêm FAQ';
    $faqQuestionPlaceholder = $faqQuestionPlaceholder ?? 'Câu hỏi';
    $faqAnswerLabel = $faqAnswerLabel ?? 'Câu trả lời';
    $faqAnswerHint = $faqAnswerHint ?? 'Có thể bôi đen nội dung để chèn link liên kết.';
    $faqAnswerPlaceholder = $faqAnswerPlaceholder ?? 'Nhập câu trả lời và chèn link khi cần.';
    $showRelatedQuestions = $showRelatedQuestions ?? true;
    $relatedQuestions = $relatedQuestions ?? ($form['related_questions'] ?? []);
    $relatedWireKeyPrefix = $relatedWireKeyPrefix ?? 'related-question';
    $relatedQuestionPathPrefix = $relatedQuestionPathPrefix ?? 'form.related_questions';
    $addRelatedAction = $addRelatedAction ?? 'addRelatedQuestion';
    $removeRelatedAction = $removeRelatedAction ?? 'removeRelatedQuestion';
@endphp

<section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
    <div>
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $faqTitle }}</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $faqDescription }}</p>
    </div>

    <div class="space-y-4">
        @foreach ($faqItems as $index => $item)
            @php
                $faqQuestionModel = $faqQuestionPathPrefix.'.'.$index.'.question';
                $faqAnswerModel = $faqAnswerPathPrefix.'.'.$index.'.answer';
            @endphp

            <div wire:key="{{ $faqWireKeyPrefix }}-{{ $index }}" class="rounded-3xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">{{ $faqItemLabel }} {{ $loop->iteration }}</p>

                    @if (count($faqItems) > 1)
                        <button type="button" wire:click="{{ $removeFaqAction }}({{ $index }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa
                        </button>
                    @endif
                </div>

                <div class="grid gap-4">
                    <div class="space-y-2">
                        <input type="text" wire:model.defer="{{ $faqQuestionModel }}" placeholder="{{ $faqQuestionPlaceholder }}" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error($faqQuestionModel) <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ $faqAnswerLabel }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $faqAnswerHint }}</p>
                        </div>

                        <x-admin.quill-editor
                            wire:key="{{ $faqWireKeyPrefix }}-answer-{{ $index }}"
                            model="{{ $faqAnswerModel }}"
                            :value="$item['answer'] ?? ''"
                            mode="rich"
                            rows="5"
                            placeholder="{{ $faqAnswerPlaceholder }}"
                        />
                        @error($faqAnswerModel) <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex flex-wrap justify-end">
        <button type="button" wire:click="{{ $addFaqAction }}" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
            {{ $addFaqLabel }}
        </button>
    </div>
</section>

@if ($showRelatedQuestions)
    <section class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40">
        <div>
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $relatedTitle }}</h3>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $relatedDescription }}</p>
        </div>

        <div class="space-y-3">
            @foreach ($relatedQuestions as $index => $question)
                <div wire:key="{{ $relatedWireKeyPrefix }}-{{ $index }}" class="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm md:flex-row md:items-center dark:border-zinc-800 dark:bg-zinc-900">
                    <input type="text" wire:model.defer="{{ $relatedQuestionPathPrefix }}.{{ $index }}" placeholder="Ví dụ: Làm sao kiểm soát phát sinh cho hạng mục này?" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                    @if (count($relatedQuestions) > 1)
                        <button type="button" wire:click="{{ $removeRelatedAction }}({{ $index }})" class="inline-flex items-center justify-center rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                            Xóa
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap justify-end">
            <button type="button" wire:click="{{ $addRelatedAction }}" class="rounded-2xl border border-zinc-300 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200">
                Thêm câu hỏi
            </button>
        </div>
    </section>
@endif
