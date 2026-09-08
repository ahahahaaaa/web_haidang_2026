<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $editingCategoryId ? 'Biên tập danh mục dịch vụ' : 'Tạo danh mục dịch vụ' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Mỗi danh mục dịch vụ có page detail riêng để chỉnh slug, mô tả, nội dung trang và thứ tự.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.services.categories') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <i class="fa-solid fa-arrow-left"></i>
                Về danh mục
            </a>
            @if ($editingCategoryId)
                <button type="button" wire:click="deleteCategory({{ $editingCategoryId }})" wire:confirm="Bạn có chắc chắn muốn xóa danh mục dịch vụ này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                    <i class="fa-solid fa-trash"></i>
                    Xóa danh mục
                </button>
            @endif
        </div>
    </div>

    @include('livewire.admin.cms.partials.services-submenu')

    <x-admin.form-feedback />

    @php
        $avatarPreview = $avatarUpload
            ? $avatarUpload->temporaryUrl()
            : ($selectedLibraryAvatarMedia?->getUrl() ?: ($editingCategoryModel?->getFirstMediaUrl('avatar') ?: null));
    @endphp

    <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit="saveCategory" class="grid gap-4 pb-32 md:grid-cols-2 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu danh mục dịch vụ...">
            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tên danh mục</label>
                <input type="text" wire:model.defer="categoryForm.name" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug</label>
                <input type="text" wire:model.defer="categoryForm.slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Có thể để trống để hệ thống tự sinh slug từ tên danh mục.</p>
            </div>

            <div class="space-y-2 md:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Mô tả ngắn</label>
                <x-admin.quill-editor wire:key="service-category-description-{{ $editingCategoryId ?? 'new' }}-{{ md5((string) ($categoryForm['description'] ?? '')) }}" model="categoryForm.description" :value="$categoryForm['description'] ?? ''" rows="3" />
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Dùng cho hero, mô tả SEO và summary ngắn ngoài frontsite.</p>
            </div>

            <div class="space-y-2 md:col-span-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nội dung trang danh mục</label>
                <x-admin.quill-editor
                    wire:key="service-category-content-{{ $editingCategoryId ?? 'new' }}-{{ md5((string) ($categoryForm['content'] ?? '')) }}"
                    model="categoryForm.content"
                    :value="$categoryForm['content'] ?? ''"
                    mode="rich"
                    :allow-images="true"
                    rows="8"
                    placeholder="Viết nội dung giới thiệu, phạm vi hỗ trợ, lưu ý chuẩn bị hoặc CTA cho trang danh mục dịch vụ..."
                />
            </div>

            <div class="space-y-4 md:col-span-2">
                <x-admin.image-dropzone
                    label="Avatar danh mục"
                    model="avatarUpload"
                    :preview="$avatarPreview"
                    hint="Upload ảnh mới hoặc chọn ảnh có sẵn từ Media library."
                />

                <div class="grid gap-3 md:grid-cols-[1fr_auto] md:items-end">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt text avatar</label>
                        <input type="text" wire:model.defer="categoryForm.avatar_alt" placeholder="Mô tả ngắn cho ảnh danh mục dịch vụ" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    </div>

                    <button
                        type="button"
                        data-admin-media-picker-trigger
                        data-livewire-id="{{ $this->getId() }}"
                        data-pick-method="selectAvatarLibraryMedia"
                        data-button-label="Chọn ảnh này làm avatar danh mục dịch vụ"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                    >
                        <i class="fa-regular fa-images"></i>
                        Chọn từ Media popup
                    </button>
                </div>

                @if ($selectedLibraryAvatarMedia)
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            <i class="fa-solid fa-circle-check"></i>
                            Đã chọn avatar từ thư viện: {{ $selectedLibraryAvatarMedia->name }}
                        </div>

                        <button type="button" wire:click="clearAvatarLibraryMediaSelection" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                            <i class="fa-solid fa-rotate-left"></i>
                            Bỏ chọn ảnh thư viện
                        </button>
                    </div>
                @endif
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
