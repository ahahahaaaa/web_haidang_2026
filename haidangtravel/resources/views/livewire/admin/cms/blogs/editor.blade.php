<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">{{ $selectedId ? 'Biên tập bài viết blog' : 'Tạo bài viết blog' }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Trang chi tiết tập trung vào nội dung, SEO và cover thay vì trộn chung với danh sách.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.blogs') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                <i class="fa-solid fa-arrow-left"></i>
                Về danh sách
            </a>
            @if ($selectedId)
                <button type="button" wire:click="deletePost({{ $selectedId }})" wire:confirm="Bạn có chắc chắn muốn xóa bài viết này? Hành động này không thể hoàn tác." class="inline-flex items-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                    <i class="fa-solid fa-trash"></i>
                    Xóa bài viết
                </button>
            @endif
        </div>
    </div>

    @include('livewire.admin.cms.partials.blogs-submenu')

    <x-admin.form-feedback />

    <section class="rounded-[28px] border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit="savePost" class="space-y-5 pb-32 md:pb-6" data-admin-feedback-form data-admin-loading-text="Đang lưu bài viết blog...">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề</label>
                    <input type="text" wire:model.defer="form.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug</label>
                    <input type="text" wire:model.defer="form.slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Có thể để trống để hệ thống tự sinh slug từ tiêu đề bài viết.</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Danh mục</label>
                    <select wire:model.defer="form.content_category_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Không chọn</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ str_repeat('— ', max(0, (int) ($category->tree_depth ?? 0))) }}{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs leading-5 text-zinc-500 dark:text-zinc-400">Nếu chọn danh mục cha trên frontsite, bài viết trong các danh mục con vẫn được gom chung theo nhánh.</p>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tác giả</label>
                    <input type="text" wire:model.defer="form.author_name" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Ngày xuất bản</label>
                    <input type="datetime-local" wire:model.defer="form.published_at" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Trạng thái</label>
                    <select wire:model.defer="form.status" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="draft">draft</option>
                        <option value="published">published</option>
                        <option value="archived">archived</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Thứ tự</label>
                    <input type="number" wire:model.defer="form.sort_order" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                    <input type="checkbox" wire:model.defer="form.is_featured" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                    Nổi bật
                </label>
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tóm tắt</label>
                <x-admin.quill-editor wire:key="blog-excerpt-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['excerpt'] ?? '')) }}" model="form.excerpt" :value="$form['excerpt'] ?? ''" rows="3" placeholder="Đoạn mở ngắn cho hero, card và SEO." />
            </div>

            <div class="space-y-2">
                <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nội dung</label>
                <x-admin.quill-editor wire:key="blog-content-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['content'] ?? '')) }}" model="form.content" :value="$form['content'] ?? ''" mode="rich" :allow-images="true" rows="12" placeholder="Nội dung bài viết, hình ảnh, CTA và block trích dẫn." />
            </div>

            <x-admin.image-dropzone
                label="Cover image"
                model="coverUpload"
                :preview="$coverUpload ? $coverUpload->temporaryUrl() : ($selectedLibraryCoverMedia?->getUrl() ?: $editingPost?->getFirstMediaUrl('cover'))"
                hint="Upload ảnh mới hoặc chọn ảnh có sẵn từ Media library."
            />

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    data-admin-media-picker-trigger
                    data-livewire-id="{{ $this->getId() }}"
                    data-pick-method="selectCoverLibraryMedia"
                    data-button-label="Chọn ảnh này làm cover blog"
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
                    <x-admin.quill-editor wire:key="blog-meta-description-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['meta_description'] ?? '')) }}" model="form.meta_description" :value="$form['meta_description'] ?? ''" rows="3" placeholder="Mô tả SEO ngắn gọn." />
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">OG description</label>
                    <x-admin.quill-editor wire:key="blog-og-description-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['og_description'] ?? '')) }}" model="form.og_description" :value="$form['og_description'] ?? ''" rows="3" placeholder="Mô tả Open Graph ngắn gọn." />
                </div>
            </div>

            @include('livewire.admin.cms.partials.faq-related-fields', [
                'faqTitle' => 'FAQ bài viết',
                'faqDescription' => 'Các câu hỏi này sẽ hiển thị ở cuối blog detail và hỗ trợ FAQ schema ngoài frontsite.',
                'faqItems' => $form['faq_items'] ?? [],
                'faqWireKeyPrefix' => 'blog-faq-item',
                'faqQuestionPathPrefix' => 'form.faq_items',
                'faqAnswerPathPrefix' => 'form.faq_items',
                'showRelatedQuestions' => false,
            ])

            <x-admin.form-action-bar>
                <button type="submit" wire:loading.attr="disabled" wire:target="savePost" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600 disabled:cursor-not-allowed disabled:opacity-70">
                    <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="savePost"></i>
                    <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="savePost"></i>
                    Lưu
                </button>
            </x-admin.form-action-bar>
        </form>
    </section>
</div>
