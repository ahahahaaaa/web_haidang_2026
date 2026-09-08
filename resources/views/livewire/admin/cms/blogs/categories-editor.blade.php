<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $editingCategoryId ? 'Biên tập danh mục blog' : 'Tạo danh mục blog' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Mỗi danh mục có trang detail riêng để chỉnh slug, mô tả và thứ tự.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.blogs.categories') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <i class="fa-solid fa-arrow-left"></i>
                Về danh mục
            </a>
            @if ($editingCategoryId)
                <button type="button" wire:click="deleteCategory({{ $editingCategoryId }})" wire:confirm="Bạn có chắc chắn muốn xóa danh mục blog này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                    <i class="fa-solid fa-trash"></i>
                    Xóa danh mục
                </button>
            @endif
        </div>
    </div>

    @include('livewire.admin.cms.partials.blogs-submenu')

    <x-admin.form-feedback />

    @php
        $avatarPreview = $avatarUpload
            ? $avatarUpload->temporaryUrl()
            : ($selectedLibraryAvatarMedia?->getUrl() ?: ($editingCategoryModel?->getFirstMediaUrl('avatar') ?: null));
    @endphp

    <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit="saveCategory" class="grid gap-4 pb-32 md:grid-cols-2 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu danh mục blog...">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tên danh mục</label>
                <input type="text" wire:model.defer="categoryForm.name" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug</label>
                <input type="text" wire:model.defer="categoryForm.slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Có thể để trống để hệ thống tự sinh slug từ tên danh mục.</p>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Danh mục cha</label>
                <select wire:model.defer="categoryForm.parent_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Danh mục gốc</option>
                    @foreach ($categoryParentOptions as $categoryOption)
                        <option value="{{ $categoryOption->id }}">{{ str_repeat('— ', max(0, (int) ($categoryOption->tree_depth ?? 0))) }}{{ $categoryOption->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs leading-5 text-zinc-500 dark:text-zinc-400">Hiện hỗ trợ tối đa 2 cấp để frontsite lọc theo nhánh rõ ràng và dễ scan.</p>
            </div>

            <div class="space-y-2 md:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả</label>
                <x-admin.quill-editor wire:key="blog-category-description-{{ $editingCategoryId ?? 'new' }}-{{ md5((string) ($categoryForm['description'] ?? '')) }}" model="categoryForm.description" :value="$categoryForm['description'] ?? ''" rows="3" />
            </div>

            <div class="space-y-4 md:col-span-2">
                <x-admin.image-dropzone
                    label="Avatar danh mục"
                    model="avatarUpload"
                    :preview="$avatarPreview"
                    hint="Upload ảnh mới hoặc chọn ảnh có sẵn từ Media library."
                />

                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        data-admin-media-picker-trigger
                        data-livewire-id="{{ $this->getId() }}"
                        data-pick-method="selectAvatarLibraryMedia"
                        data-button-label="Chọn ảnh này làm avatar danh mục blog"
                        class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                    >
                        <i class="fa-regular fa-images"></i>
                        Chọn từ Media popup
                    </button>

                    @if ($selectedLibraryAvatarMedia)
                        <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check"></i>
                            Đã chọn avatar từ thư viện: {{ $selectedLibraryAvatarMedia->name }}
                        </div>

                        <button type="button" wire:click="clearAvatarLibraryMediaSelection" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                            <i class="fa-solid fa-rotate-left"></i>
                            Bỏ chọn ảnh thư viện
                        </button>
                    @endif
                </div>
            </div>

            <div class="md:col-span-2">
                @include('livewire.admin.cms.partials.faq-related-fields', [
                    'faqTitle' => 'FAQ danh mục blog',
                    'faqDescription' => 'Các câu hỏi này sẽ hiển thị khi người dùng đang xem blog theo danh mục tương ứng.',
                    'faqItems' => $categoryForm['faq_items'] ?? [],
                    'faqWireKeyPrefix' => 'blog-category-faq-item',
                    'faqQuestionPathPrefix' => 'categoryForm.faq_items',
                    'faqAnswerPathPrefix' => 'categoryForm.faq_items',
                    'showRelatedQuestions' => false,
                ])
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Thứ tự</label>
                <input type="number" wire:model.defer="categoryForm.sort_order" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>

            <div class="md:col-span-2">
                @include('livewire.admin.cms.partials.geo-config-fields', [
                    'path' => 'categoryForm.geo_config',
                ])
            </div>

            <div class="md:col-span-2">
                <x-admin.form-action-bar>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveCategory" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600 disabled:cursor-not-allowed disabled:opacity-70">
                        <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="saveCategory"></i>
                        <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="saveCategory"></i>
                        Lưu
                    </button>
                </x-admin.form-action-bar>
            </div>
        </form>
    </section>
</div>
