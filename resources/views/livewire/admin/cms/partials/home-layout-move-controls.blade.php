@if ($canEdit ?? false)
    <div class="flex shrink-0 flex-wrap items-center gap-2">
        <button type="button" wire:click="moveHomeLayoutItemUp('{{ $token }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-600 disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300" @disabled($isFirst ?? false)>
            <i class="fa-solid fa-arrow-up"></i>
            Chuyển lên
        </button>
        <button type="button" wire:click="moveHomeLayoutItemDown('{{ $token }}')" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-600 disabled:opacity-40 dark:border-zinc-700 dark:text-zinc-300" @disabled($isLast ?? false)>
            <i class="fa-solid fa-arrow-down"></i>
            Chuyển xuống
        </button>
    </div>
@endif
