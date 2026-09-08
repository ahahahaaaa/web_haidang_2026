<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Quản lý dịch vụ</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Bộ dịch vụ travel gồm visa, vé máy bay, sim du lịch và tư vấn du học.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <div class="space-y-6">
            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Danh sách dịch vụ</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Tìm nhanh và mở form biên tập.</p>
                    </div>

                    <button type="button" wire:click="createService" class="rounded-2xl bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
                        Dịch vụ mới
                    </button>
                </div>

                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên dịch vụ..." class="mb-5 w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                <div class="space-y-3">
                    @foreach ($services as $service)
                        <div wire:key="service-{{ $service->id }}" class="rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/70">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <button type="button" wire:click="editService({{ $service->id }})" class="text-left text-base font-semibold text-zinc-900 hover:text-teal-600 dark:text-white">
                                        {{ $service->title }}
                                    </button>
                                    <div class="mt-2 flex flex-wrap gap-2 text-xs">
                                        <span class="rounded-full bg-white px-3 py-1 font-semibold text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">{{ $service->status }}</span>
                                        @if ($service->category)
                                            <span class="rounded-full bg-teal-50 px-3 py-1 font-semibold text-teal-700 dark:bg-teal-500/10 dark:text-teal-300">{{ $service->category->name }}</span>
                                        @endif
                                    </div>
                                </div>

                                <button type="button" wire:click="deleteService({{ $service->id }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm font-medium text-rose-600 dark:border-rose-500/30 dark:text-rose-300">
                                    Xóa
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5">
                    {{ $services->links() }}
                </div>
            </section>

            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Danh mục dịch vụ</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý taxonomy cho nhóm dịch vụ travel.</p>
                </div>

                <div class="mb-4 space-y-3">
                    @foreach ($categories as $category)
                        <div class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/70">
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ $category->name }}</p>
                                <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $category->slug }}</p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="editCategory({{ $category->id }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:text-zinc-200">Sửa</button>
                                <button type="button" wire:click="deleteCategory({{ $category->id }})" class="rounded-2xl border border-rose-200 px-3 py-2 text-sm text-rose-600 dark:border-rose-500/30 dark:text-rose-300">Xóa</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <form wire:submit="saveCategory" class="grid gap-4 rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70 md:grid-cols-2">
                    <input type="text" wire:model.defer="categoryForm.name" placeholder="Tên danh mục" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <input type="text" wire:model.defer="categoryForm.slug" placeholder="Slug" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <textarea rows="3" wire:model.defer="categoryForm.description" placeholder="Mô tả" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white md:col-span-2"></textarea>
                    <input type="number" wire:model.defer="categoryForm.sort_order" placeholder="Thứ tự" class="rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    <div class="md:col-span-2">
                        <button type="submit" class="rounded-2xl bg-teal-600 px-5 py-3 text-sm font-semibold text-white">{{ $editingCategoryId ? 'Cập nhật danh mục' : 'Thêm danh mục' }}</button>
                    </div>
                </form>
            </section>
        </div>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="mb-5 text-lg font-semibold text-zinc-900 dark:text-white">Biên tập dịch vụ</h2>

            <form wire:submit="saveService" class="space-y-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" wire:model.defer="form.title" placeholder="Tên dịch vụ" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">
                    <input type="text" wire:model.defer="form.slug" placeholder="Slug" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <select wire:model.defer="form.status" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="draft">draft</option>
                        <option value="published">published</option>
                        <option value="archived">archived</option>
                    </select>
                    <select wire:model.defer="form.content_category_id" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Danh mục</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" wire:model.defer="form.icon_class" placeholder="Icon class" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.price_note" placeholder="Ghi chú giá / phạm vi" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">
                    <input type="url" wire:model.defer="form.cover_image_url" placeholder="Cover image URL" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">
                    <input type="text" wire:model.defer="form.cover_alt" placeholder="Alt text cover" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">
                    <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 md:col-span-2">
                        <input type="checkbox" wire:model.defer="form.is_featured" class="rounded border-zinc-300 text-teal-600">
                        Nổi bật
                    </label>
                </div>

                <x-admin.quill-editor wire:key="service-excerpt-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['excerpt'] ?? '')) }}" model="form.excerpt" :value="$form['excerpt'] ?? ''" rows="3" placeholder="Tóm tắt dịch vụ" />
                <x-admin.quill-editor wire:key="service-content-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['content'] ?? '')) }}" model="form.content" :value="$form['content'] ?? ''" mode="rich" :allow-images="true" rows="10" placeholder="Nội dung chi tiết" />

                <textarea rows="4" wire:model.defer="form.detail_highlights_text" placeholder="Highlights, mỗi dòng một ý" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                <textarea rows="4" wire:model.defer="form.faq_text" placeholder="FAQ, mỗi dòng: Câu hỏi | Trả lời" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                <textarea rows="3" wire:model.defer="form.related_questions_text" placeholder="Câu hỏi liên quan, mỗi dòng một câu" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>

                <div class="grid gap-4 md:grid-cols-2">
                    <input type="text" wire:model.defer="form.meta_title" placeholder="Meta title" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.og_title" placeholder="OG title" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="url" wire:model.defer="form.canonical_url" placeholder="Canonical URL" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.defer="form.robots_directive" placeholder="Robots" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>

                <textarea rows="3" wire:model.defer="form.meta_description" placeholder="Meta description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                <textarea rows="3" wire:model.defer="form.og_description" placeholder="OG description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>

                <button type="submit" class="rounded-2xl bg-gradient-to-r from-teal-600 to-cyan-600 px-5 py-3 text-sm font-semibold text-white">
                    Lưu dịch vụ
                </button>
            </form>
        </section>
    </div>
</div>
