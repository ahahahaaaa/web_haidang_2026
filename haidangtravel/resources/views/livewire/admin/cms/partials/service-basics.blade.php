<div class="grid gap-4 md:grid-cols-2">
    <div class="space-y-2 md:col-span-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tên dịch vụ</label>
        <input type="text" wire:model.defer="form.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug</label>
        <input type="text" wire:model.defer="form.slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Có thể để trống để hệ thống tự sinh slug từ tên dịch vụ.</p>
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Danh mục</label>
        <select wire:model.defer="form.content_category_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
            <option value="">Không chọn</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}">{{ $category->name }}</option>
            @endforeach
        </select>
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
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Icon class</label>
        <input type="text" wire:model.defer="form.icon_class" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
    </div>

    <div class="space-y-2">
        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Price note</label>
        <input type="text" wire:model.defer="form.price_note" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
    </div>

    <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
        <input type="checkbox" wire:model.defer="form.is_featured" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
        Nổi bật
    </label>
</div>

<div class="space-y-2">
    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tóm tắt</label>
    <x-admin.quill-editor wire:key="service-excerpt-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['excerpt'] ?? '')) }}" model="form.excerpt" :value="$form['excerpt'] ?? ''" rows="3" placeholder="Tóm tắt ngắn cho phần hero và SEO." />
</div>

<div class="space-y-2">
    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nội dung</label>
    <x-admin.quill-editor wire:key="service-content-{{ $selectedId ?? 'new' }}-{{ md5((string) ($form['content'] ?? '')) }}" model="form.content" :value="$form['content'] ?? ''" mode="rich" :allow-images="true" rows="12" placeholder="Nội dung hỗ trợ thêm cho service detail." />
</div>

<x-admin.image-dropzone
    label="Cover image"
    model="coverUpload"
    :preview="$coverUpload ? $coverUpload->temporaryUrl() : ($selectedLibraryCoverMedia?->getUrl() ?: $editingService?->getFirstMediaUrl('cover'))"
    hint="Upload ảnh mới hoặc chọn ảnh có sẵn từ Media library."
/>

<div class="flex flex-wrap items-center gap-3">
    <button
        type="button"
        data-admin-media-picker-trigger
        data-livewire-id="{{ $this->getId() }}"
        data-pick-method="selectCoverLibraryMedia"
        data-button-label="Chọn ảnh này làm cover dịch vụ"
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
