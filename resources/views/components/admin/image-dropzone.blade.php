@props([
    'label',
    'model',
    'preview' => null,
    'hint' => null,
])

<div
    x-data="{
        dragDepth: 0,
        isDragging: false,
        model: @js($model),
        dropImage(event) {
            const droppedFiles = Array.from(event.dataTransfer?.files ?? []);
            const image = droppedFiles.find((file) => file.type.startsWith('image/'));

            this.dragDepth = 0;
            this.isDragging = false;

            if (!image) {
                return;
            }

            if (this.$wire?.$upload) {
                this.$wire.$upload(this.model, image);

                return;
            }

            if (typeof DataTransfer === 'undefined') {
                return;
            }

            const transfer = new DataTransfer();
            transfer.items.add(image);

            this.$refs.input.files = transfer.files;
            this.$refs.input.dispatchEvent(new Event('change', { bubbles: true }));
            this.$refs.input.dispatchEvent(new Event('input', { bubbles: true }));
        },
        enterDropzone() {
            this.dragDepth += 1;
            this.isDragging = true;
        },
        leaveDropzone() {
            this.dragDepth = Math.max(0, this.dragDepth - 1);

            if (this.dragDepth === 0) {
                this.isDragging = false;
            }
        },
    }"
    class="space-y-3"
>
    <div class="flex items-center justify-between gap-3">
        <label class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $label }}</label>
        @if ($hint)
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
        @endif
    </div>

    <label
        x-on:dragenter.prevent.stop="enterDropzone()"
        x-on:dragover.prevent.stop="isDragging = true"
        x-on:dragleave.prevent.stop="leaveDropzone()"
        x-on:drop.prevent.stop="dropImage($event)"
        x-bind:class="isDragging ? 'border-red-500 bg-red-50/80 ring-2 ring-red-200 dark:border-red-500/80 dark:bg-zinc-900 dark:ring-red-500/20' : ''"
        class="group flex cursor-pointer flex-col items-center justify-center gap-3 rounded-3xl border border-dashed border-zinc-300 bg-zinc-50 px-6 py-8 text-center transition hover:border-red-400 hover:bg-red-50/60 dark:border-zinc-700 dark:bg-zinc-900/70 dark:hover:border-red-500/60 dark:hover:bg-zinc-900"
    >
        <input x-ref="input" type="file" accept="image/*" class="hidden" wire:model.live="{{ $model }}">

        <div class="pointer-events-none flex size-14 items-center justify-center rounded-2xl bg-white text-zinc-700 shadow-sm ring-1 ring-zinc-200 transition group-hover:text-red-600 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-700">
            <i class="fa-solid fa-cloud-arrow-up text-xl"></i>
        </div>

        <div class="pointer-events-none space-y-1">
            <p class="text-sm font-semibold text-zinc-900 dark:text-white">Kéo thả ảnh vào đây hoặc bấm để chọn</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">PNG, JPG, WEBP. Nên dùng ảnh ngang chất lượng tốt.</p>
        </div>

        @if ($preview)
            <img src="{{ $preview }}" alt="{{ $label }}" class="pointer-events-none mt-2 h-44 w-full rounded-2xl object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
        @else
            <div class="pointer-events-none mt-2 flex h-44 w-full items-center justify-center rounded-2xl bg-white/80 text-zinc-400 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:ring-zinc-700">
                <i class="fa-regular fa-image text-4xl"></i>
            </div>
        @endif

        <p class="pointer-events-none text-xs text-zinc-500" wire:loading wire:target="{{ $model }}">Đang tải ảnh lên...</p>
    </label>

    @error($model)
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>
