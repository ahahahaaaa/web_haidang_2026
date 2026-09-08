<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $selectedId ? 'Biên tập dự án' : 'Tạo dự án' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Trang detail riêng cho dự án để xử lý content, gallery và metadata tập trung hơn.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.projects') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <i class="fa-solid fa-arrow-left"></i>
                Về danh sách
            </a>
            @if ($selectedId)
                <button type="button" wire:click="deleteProject({{ $selectedId }})" wire:confirm="Bạn có chắc chắn muốn xóa dự án này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                    <i class="fa-solid fa-trash"></i>
                    Xóa dự án
                </button>
            @endif
        </div>
    </div>

    @include('livewire.admin.cms.partials.projects-submenu')

    <x-admin.form-feedback />

    <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit.prevent="saveProject" class="space-y-5 pb-32 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu dự án...">
            <div class="grid gap-4 md:grid-cols-2">
                <input type="text" wire:model.defer="form.title" placeholder="Tên dự án" class="md:col-span-2 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.slug" placeholder="Slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <select wire:model.defer="form.status" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="draft">draft</option>
                    <option value="published">published</option>
                    <option value="archived">archived</option>
                </select>
                <select wire:model.defer="form.content_category_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Danh mục</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <select wire:model.defer="form.project_type_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Loại dự án</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
                <input type="text" wire:model.defer="form.location" placeholder="Vị trí" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="number" step="0.01" wire:model.defer="form.area_value" placeholder="Diện tích" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.area_unit" placeholder="Đơn vị" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.timeline" placeholder="Timeline" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="date" wire:model.defer="form.completion_date" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <label class="md:col-span-2 flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <input type="checkbox" wire:model.defer="form.is_featured" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                    Nổi bật
                </label>
            </div>

            <x-admin.quill-editor wire:key="project-excerpt-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['excerpt'] ?? '')) }}" model="form.excerpt" :value="$form['excerpt'] ?? ''" rows="3" placeholder="Tóm tắt dự án" />
            <x-admin.quill-editor wire:key="project-content-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['content'] ?? '')) }}" model="form.content" :value="$form['content'] ?? ''" mode="rich" :allow-images="true" rows="12" placeholder="Nội dung case study, giải pháp, hình ảnh và điểm nhấn kỹ thuật." />

            <x-admin.image-dropzone
                label="Cover image"
                model="coverUpload"
                :preview="$coverUpload ? $coverUpload->temporaryUrl() : ($selectedLibraryCoverMedia?->getUrl() ?: $editingProject?->getFirstMediaUrl('cover'))"
                hint="Upload ảnh mới hoặc chọn ảnh có sẵn từ Media library."
            />

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    data-admin-media-picker-trigger
                    data-livewire-id="{{ $this->getId() }}"
                    data-pick-method="selectCoverLibraryMedia"
                    data-button-label="Chọn ảnh này làm cover dự án"
                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                >
                    <i class="fa-regular fa-images"></i>
                    Chọn từ Media popup
                </button>

                @if ($selectedLibraryCoverMedia)
                    <div class="inline-flex items-center gap-2 rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <i class="fa-solid fa-circle-check"></i>
                        Đã chọn cover từ thư viện: {{ $selectedLibraryCoverMedia->name }}
                    </div>

                    <button type="button" wire:click="clearCoverLibraryMediaSelection" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                        <i class="fa-solid fa-rotate-left"></i>
                        Bỏ chọn ảnh thư viện
                    </button>
                @endif
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <input type="text" wire:model.defer="form.cover_alt" placeholder="Alt text cover" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.meta_title" placeholder="Meta title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.og_title" placeholder="OG title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.canonical_url" placeholder="Canonical URL" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <input type="text" wire:model.defer="form.robots_directive" placeholder="Robots" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            </div>

            @include('livewire.admin.cms.partials.project-gallery')
            @include('livewire.admin.cms.partials.faq-related-fields', [
                'faqTitle' => 'FAQ dự án',
                'faqDescription' => 'Các câu hỏi này sẽ hiển thị ở cuối case study dự án và được dùng cho FAQ schema.',
                'relatedTitle' => 'Câu hỏi liên quan của dự án',
                'relatedDescription' => 'Nhóm câu hỏi ngắn giúp người xem mở rộng góc nhìn trước khi chuyển sang dịch vụ hoặc gửi brief.',
            ])

            <x-admin.quill-editor wire:key="project-meta-description-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['meta_description'] ?? '')) }}" model="form.meta_description" :value="$form['meta_description'] ?? ''" rows="3" placeholder="Meta description" />
            <x-admin.quill-editor wire:key="project-og-description-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['og_description'] ?? '')) }}" model="form.og_description" :value="$form['og_description'] ?? ''" rows="3" placeholder="OG description" />

            <x-admin.form-action-bar>
                <button type="submit" wire:loading.attr="disabled" wire:target="saveProject" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600 disabled:cursor-not-allowed disabled:opacity-70">
                    <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="saveProject"></i>
                    <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="saveProject"></i>
                    Lưu
                </button>
            </x-admin.form-action-bar>
        </form>
    </section>
</div>
