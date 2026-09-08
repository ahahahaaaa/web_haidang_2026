<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Quản lý dự án</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý dự án, danh mục, loại dự án và các trường lọc theo diện tích, vị trí.</p>
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
                <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Danh sách dự án</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Tìm theo tên và vị trí.</p>
                    </div>

                    <button type="button" wire:click="createProject" class="rounded-2xl bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">Dự án mới</button>
                </div>

                <div class="mb-5 grid gap-3 md:grid-cols-2">
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <input type="text" wire:model.live.debounce.300ms="locationFilter" placeholder="Tìm theo vị trí..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                </div>

                <div class="space-y-3">
                    @foreach ($projects as $project)
                        <div wire:key="project-{{ $project->id }}" class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 transition hover:border-red-300 dark:border-zinc-700 dark:bg-zinc-800/70">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <button type="button" wire:click="editProject({{ $project->id }})" class="text-left text-base font-semibold text-zinc-900 transition hover:text-red-600 dark:text-white">
                                        {{ $project->title }}
                                    </button>
                                    <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                        @if ($project->category)
                                            <span class="rounded-full bg-red-50 px-3 py-1 font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">{{ $project->category->name }}</span>
                                        @endif
                                        @if ($project->type)
                                            <span class="rounded-full bg-white px-3 py-1 font-semibold text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">{{ $project->type->name }}</span>
                                        @endif
                                        @if ($project->location)
                                            <span class="text-zinc-500 dark:text-zinc-400">{{ $project->location }}</span>
                                        @endif
                                    </div>
                                </div>

                                <button type="button" wire:click="deleteProject({{ $project->id }})" class="rounded-2xl border border-red-200 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10">
                                    Xóa
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5">
                    {{ $projects->links() }}
                </div>
            </section>

            <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-5 grid gap-6 lg:grid-cols-2">
                    <div class="space-y-3">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Danh mục dự án</h2>
                        @foreach ($categories as $category)
                            <div wire:key="project-category-{{ $category->id }}" class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/70">
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $category->name }}</p>
                                    <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $category->slug }}</p>
                                </div>

                                <div class="flex gap-2">
                                    <button type="button" wire:click="editCategory({{ $category->id }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">Sửa</button>
                                    <button type="button" wire:click="deleteCategory({{ $category->id }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">Xóa</button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-3">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Loại dự án</h2>
                        @foreach ($types as $type)
                            <div wire:key="project-type-{{ $type->id }}" class="flex items-center justify-between rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/70">
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $type->name }}</p>
                                    <p class="text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">{{ $type->slug }}</p>
                                </div>

                                <div class="flex gap-2">
                                    <button type="button" wire:click="editType({{ $type->id }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">Sửa</button>
                                    <button type="button" wire:click="deleteType({{ $type->id }})" class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">Xóa</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <form wire:submit="saveCategory" class="space-y-4 rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Form danh mục</h3>
                        <input type="text" wire:model.defer="categoryForm.name" placeholder="Tên danh mục" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="categoryForm.slug" placeholder="Slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <x-admin.quill-editor wire:key="project-category-description-{{ $editingCategoryId ?? 'new' }}-{{ md5((string) ($categoryForm['description'] ?? '')) }}" model="categoryForm.description" :value="$categoryForm['description'] ?? ''" rows="3" placeholder="Mô tả" />
                        <input type="number" wire:model.defer="categoryForm.sort_order" placeholder="Thứ tự" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <button type="submit" class="rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">{{ $editingCategoryId ? 'Cập nhật danh mục' : 'Thêm danh mục' }}</button>
                    </form>

                    <form wire:submit="saveType" class="space-y-4 rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">Form loại dự án</h3>
                        <input type="text" wire:model.defer="typeForm.name" placeholder="Tên loại" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <input type="text" wire:model.defer="typeForm.slug" placeholder="Slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <x-admin.quill-editor wire:key="project-type-description-{{ $editingTypeId ?? 'new' }}-{{ md5((string) ($typeForm['description'] ?? '')) }}" model="typeForm.description" :value="$typeForm['description'] ?? ''" rows="3" placeholder="Mô tả" />
                        <input type="number" wire:model.defer="typeForm.sort_order" placeholder="Thứ tự" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <button type="submit" class="rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">{{ $editingTypeId ? 'Cập nhật loại' : 'Thêm loại' }}</button>
                    </form>
                </div>
            </section>
        </div>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="mb-5 text-lg font-semibold text-zinc-900 dark:text-white">Biên tập dự án</h2>

            <form wire:submit="saveProject" class="space-y-5">
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

                <x-admin.quill-editor wire:key="project-meta-description-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['meta_description'] ?? '')) }}" model="form.meta_description" :value="$form['meta_description'] ?? ''" rows="3" placeholder="Meta description" />
                <x-admin.quill-editor wire:key="project-og-description-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['og_description'] ?? '')) }}" model="form.og_description" :value="$form['og_description'] ?? ''" rows="3" placeholder="OG description" />

                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Lưu dự án
                </button>
            </form>
        </section>
    </div>
</div>
