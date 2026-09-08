@php
    $action = $action ?? url()->current();
    $buttonLabel = $buttonLabel ?? 'Tìm';
    $containerClasses = $containerClasses ?? 'max-w-7xl';
    $hiddenInputs = collect($hiddenInputs ?? [])->filter(fn ($value) => filled($value));
    $inputName = $inputName ?? 'q';
    $inputValue = $inputValue ?? '';
    $placeholder = $placeholder ?? 'Bạn muốn đi đâu?';
    $sectionClasses = $sectionClasses ?? 'px-4 py-8 sm:px-6 lg:px-8';
    $selectFields = collect($selectFields ?? [])
        ->filter(fn ($field) => is_array($field) && filled($field['name'] ?? null))
        ->values();
@endphp

<section class="{{ $sectionClasses }}">
    <div class="mx-auto {{ $containerClasses }}">
        <div class="frontsite-text-reveal rounded-[1.75rem] border border-slate-200/80 bg-white p-3 shadow-[0_26px_80px_-48px_rgba(15,23,42,0.28)]" data-reveal="panel">
            <form action="{{ $action }}" method="GET" class="flex flex-col gap-3 md:flex-row md:items-center">
                @foreach ($hiddenInputs as $name => $value)
                    <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                @endforeach

                <label class="flex min-h-16 flex-1 items-center gap-4 rounded-[1.35rem] bg-slate-50 px-5 text-slate-900">
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-secondary shadow-[0_12px_28px_-18px_rgba(15,23,42,0.4)]">
                        <i class="fa-solid fa-magnifying-glass text-[1.35rem]"></i>
                    </span>
                    <input
                        type="text"
                        name="{{ $inputName }}"
                        value="{{ $inputValue }}"
                        placeholder="{{ $placeholder }}"
                        autocomplete="off"
                        class="w-full border-0 bg-transparent px-0 text-[1.1rem] font-medium text-slate-900 outline-none placeholder:text-slate-400"
                    >
                </label>

                @foreach ($selectFields as $field)
                    @php
                        $fieldName = (string) $field['name'];
                        $fieldValue = (string) ($field['value'] ?? '');
                        $fieldIcon = trim((string) ($field['icon'] ?? 'fa-solid fa-sliders'));
                        $fieldOptions = collect($field['options'] ?? [])
                            ->filter(fn ($label, $value) => filled((string) $value) && filled((string) $label));
                        $fieldPlaceholder = trim((string) ($field['placeholder'] ?? 'Tất cả'));
                        $fieldSearchable = (bool) ($field['searchable'] ?? false);
                    @endphp

                    <label class="flex min-h-16 w-full items-center gap-3 rounded-[1.35rem] bg-slate-50 px-4 text-slate-900 md:w-auto md:min-w-[18rem] md:flex-none md:self-stretch">
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-full bg-white text-secondary shadow-[0_12px_28px_-18px_rgba(15,23,42,0.4)]">
                            <i class="{{ $fieldIcon }} text-base"></i>
                        </span>

                        <span class="sr-only">{{ $field['label'] ?? $fieldPlaceholder }}</span>

                        <select
                            name="{{ $fieldName }}"
                            class="w-full min-w-0 bg-transparent text-[0.98rem] font-medium text-slate-900 outline-none"
                            data-frontsite-select
                            data-frontsite-select-max-width="21rem"
                            data-frontsite-select-search="{{ $fieldSearchable ? 'true' : 'false' }}"
                            data-frontsite-select-variant="ghost"
                        >
                            <option value="">{{ $fieldPlaceholder }}</option>
                            @foreach ($fieldOptions as $value => $label)
                                <option value="{{ $value }}" @selected($fieldValue === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach

                <button type="submit" class="inline-flex min-h-16 items-center justify-center gap-2 rounded-[1.2rem] bg-primary px-6 text-sm font-bold text-white transition hover:bg-primary-hover hover:text-white md:min-w-28">
                    <i class="fa-solid fa-arrow-right"></i>
                    {{ $buttonLabel }}
                </button>
            </form>
        </div>
    </div>
</section>
