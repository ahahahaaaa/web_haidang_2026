<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ ($editingTaxonomyModel?->id ?? null) ? 'Biên tập ' . $taxonomyConfig['singular_label'] : 'Tạo ' . $taxonomyConfig['singular_label'] }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Trang detail riêng để quản lý content landing, avatar và gallery của taxonomy tour.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route($taxonomyConfig['index_route']) }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <i class="fa-solid fa-arrow-left"></i>
                Về {{ mb_strtolower($taxonomyConfig['plural_label']) }}
            </a>
            @if ($editingTaxonomyModel && filled($taxonomyConfig['reviews_index_route'] ?? null))
                <a href="{{ route($taxonomyConfig['reviews_index_route'], [$taxonomyConfig['type'] => $editingTaxonomyModel]) }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-amber-200 px-4 py-3 text-sm font-medium text-amber-700 dark:border-amber-500/30 dark:text-amber-300">
                    <i class="fa-solid fa-star"></i>
                    Quản lý đánh giá
                </a>
            @endif

            @if ($editingTaxonomyModel)
                <button type="button" wire:click="{{ $taxonomyConfig['delete_action'] }}({{ $editingTaxonomyModel->id }})" wire:confirm="Bạn có chắc chắn muốn xóa {{ $taxonomyConfig['singular_label'] }} này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                    <i class="fa-solid fa-trash"></i>
                    Xóa
                </button>
            @endif
        </div>
    </div>

    @include('livewire.admin.cms.partials.tours-submenu')

    <x-admin.form-feedback />

    @php
        $avatarPreview = $avatarUpload
            ? $avatarUpload->temporaryUrl()
            : ($selectedLibraryAvatarMedia?->getUrl() ?: ($editingTaxonomyModel?->getFirstMediaUrl('avatar') ?: ($taxonomyForm['cover_image_url'] ?? null)));
        $formPath = match ($taxonomyConfig['type']) {
            'category' => 'categoryForm',
            'destination' => 'destinationForm',
            'region' => 'regionForm',
            default => 'categoryForm',
        };
    @endphp

    <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit.prevent="{{ $taxonomyConfig['save_action'] }}" class="space-y-5 pb-32 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu {{ $taxonomyConfig['singular_label'] }}...">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <input type="text" wire:model.defer="{{ $formPath }}.name" placeholder="Tên {{ $taxonomyConfig['singular_label'] }}" class="md:col-span-2 xl:col-span-4 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <div class="space-y-2">
                    <input type="text" wire:model.defer="{{ $formPath }}.slug" placeholder="Slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Có thể để trống để hệ thống tự sinh slug từ tên {{ mb_strtolower($taxonomyConfig['singular_label']) }}.</p>
                </div>
                <select wire:model.defer="{{ $formPath }}.status" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="draft">draft</option>
                    <option value="published">published</option>
                    <option value="archived">archived</option>
                </select>

                @if (in_array($taxonomyConfig['type'], ['category', 'destination', 'region'], true))
                    <select wire:model.defer="{{ $formPath }}.scope" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Tất cả phạm vi</option>
                        @foreach ($scopeOptions as $scopeOption)
                            <option value="{{ $scopeOption->value }}">{{ $scopeOption->label() }}</option>
                        @endforeach
                    </select>
                @endif

                <input type="datetime-local" wire:model.defer="{{ $formPath }}.published_at" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                @if ($taxonomyConfig['type'] === 'destination')
                    <select wire:model.defer="{{ $formPath }}.region_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Vùng miền</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}">{{ $region->name }}</option>
                        @endforeach
                    </select>
                @endif

                <input type="number" wire:model.defer="{{ $formPath }}.sort_order" placeholder="Thứ tự" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                <label class="md:col-span-2 xl:col-span-4 flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <input type="checkbox" wire:model.defer="{{ $formPath }}.is_featured" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                    Mục nổi bật
                </label>
            </div>

            <x-admin.quill-editor wire:key="{{ $taxonomyConfig['type'] }}-excerpt-{{ $editingTaxonomyModel?->id ?? 'new' }}-{{ md5((string) ($taxonomyForm['excerpt'] ?? '')) }}" model="{{ $formPath }}.excerpt" :value="$taxonomyForm['excerpt'] ?? ''" rows="3" placeholder="Tóm tắt ngắn" />
            <x-admin.quill-editor wire:key="{{ $taxonomyConfig['type'] }}-content-{{ $editingTaxonomyModel?->id ?? 'new' }}-{{ md5((string) ($taxonomyForm['content'] ?? '')) }}" model="{{ $formPath }}.content" :value="$taxonomyForm['content'] ?? ''" mode="rich" :allow-images="true" rows="12" placeholder="Nội dung landing" />

            <x-admin.image-dropzone
                label="Avatar"
                model="avatarUpload"
                :preview="$avatarPreview"
                hint="Upload ảnh đại diện, chọn từ Media popup hoặc dùng URL ảnh ngoài."
            />

            <div class="grid gap-4 md:grid-cols-2">
                <input type="text" wire:model.defer="{{ $formPath }}.cover_alt" placeholder="Alt text avatar" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="url" wire:model.defer="{{ $formPath }}.cover_image_url" placeholder="URL ảnh ngoài cho avatar" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    data-admin-media-picker-trigger
                    data-livewire-id="{{ $this->getId() }}"
                    data-pick-method="selectAvatarLibraryMedia"
                    data-button-label="Chọn ảnh này làm avatar"
                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                >
                    <i class="fa-regular fa-images"></i>
                    Chọn từ Media popup
                </button>

                @if ($selectedLibraryAvatarMedia)
                    <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <i class="fa-solid fa-circle-check"></i>
                        Đã chọn ảnh từ thư viện: {{ $selectedLibraryAvatarMedia->name }}
                    </div>

                    <button type="button" wire:click="clearAvatarLibraryMediaSelection" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                        <i class="fa-solid fa-rotate-left"></i>
                        Bỏ chọn ảnh thư viện
                    </button>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <input type="text" wire:model.defer="{{ $formPath }}.meta_title" placeholder="Meta title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="{{ $formPath }}.og_title" placeholder="OG title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="{{ $formPath }}.canonical_url" placeholder="Canonical URL" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="{{ $formPath }}.robots_directive" placeholder="Robots directive" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                @if (in_array($taxonomyConfig['type'], ['category', 'destination'], true))
                    <input type="number" step="0.1" wire:model.defer="{{ $formPath }}.rating_average" placeholder="Điểm tổng đánh giá ảo" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="number" wire:model.defer="{{ $formPath }}.rating_count" placeholder="Tổng lượt đánh giá ảo" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                @endif
            </div>

            @if (in_array($taxonomyConfig['type'], ['category', 'destination'], true))
                <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                    Tổng điểm và tổng lượt đánh giá ở đây dùng cho aggregate rating ảo của trang landing. Review item chi tiết được quản lý ở màn <span class="font-semibold text-zinc-900 dark:text-white">Quản lý đánh giá</span>.
                </div>
            @endif

            <x-admin.quill-editor wire:key="{{ $taxonomyConfig['type'] }}-meta-description-{{ $editingTaxonomyModel?->id ?? 'new' }}-{{ md5((string) ($taxonomyForm['meta_description'] ?? '')) }}" model="{{ $formPath }}.meta_description" :value="$taxonomyForm['meta_description'] ?? ''" rows="3" placeholder="Meta description" />
            <x-admin.quill-editor wire:key="{{ $taxonomyConfig['type'] }}-og-description-{{ $editingTaxonomyModel?->id ?? 'new' }}-{{ md5((string) ($taxonomyForm['og_description'] ?? '')) }}" model="{{ $formPath }}.og_description" :value="$taxonomyForm['og_description'] ?? ''" rows="3" placeholder="OG description" />

            @include('livewire.admin.cms.partials.tour-gallery', [
                'galleryItems' => $taxonomyForm['gallery'] ?? [],
                'galleryPathPrefix' => $formPath . '.gallery',
                'galleryContext' => $taxonomyConfig['type'],
                'editingModel' => $editingTaxonomyModel,
                'galleryTitle' => 'Gallery nội dung',
                'galleryDescription' => 'Dùng cho section media trên trang landing của taxonomy.',
            ])

            @if (in_array($taxonomyConfig['type'], ['category', 'destination'], true))
                @include('livewire.admin.cms.partials.faq-related-fields', [
                    'faqTitle' => $taxonomyConfig['type'] === 'category' ? 'FAQ danh mục tour' : 'FAQ điểm đến',
                    'faqDescription' => $taxonomyConfig['type'] === 'category'
                        ? 'Các câu hỏi này sẽ hiển thị trên trang danh mục tour trước CTA cuối trang.'
                        : 'Các câu hỏi này sẽ hiển thị trên trang điểm đến trước CTA cuối trang.',
                    'faqItems' => $taxonomyForm['faq_items'] ?? [],
                    'faqWireKeyPrefix' => $taxonomyConfig['type'].'-faq-item',
                    'faqQuestionPathPrefix' => $formPath.'.faq_items',
                    'faqAnswerPathPrefix' => $formPath.'.faq_items',
                    'showRelatedQuestions' => false,
                ])
            @endif

            <x-admin.form-action-bar>
                <button type="submit" wire:loading.attr="disabled" wire:target="{{ $taxonomyConfig['save_action'] }}" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-zinc-900 px-5 py-3 text-sm font-semibold text-white transition disabled:cursor-not-allowed disabled:opacity-70 dark:bg-white dark:text-zinc-900">
                    <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="{{ $taxonomyConfig['save_action'] }}"></i>
                    <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="{{ $taxonomyConfig['save_action'] }}"></i>
                    Lưu
                </button>
            </x-admin.form-action-bar>
        </form>
    </section>
</div>
