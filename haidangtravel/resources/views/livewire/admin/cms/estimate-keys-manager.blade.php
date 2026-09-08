<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Khóa dự toán</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tạo, bật hoặc tắt key cho các cấp dự toán cao hơn.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Generate key</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Key mới sẽ được tạo ngẫu nhiên và gắn với các cấp dự toán bạn chọn.</p>

            <form wire:submit="save" class="mt-5 space-y-4">
                <input type="text" wire:model.defer="form.label" placeholder="Tên key / ghi chú ngắn" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                <div class="space-y-3 rounded-2xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">Áp dụng cho cấp dự toán</p>

                    @forelse ($tierOptions as $option)
                        <label class="flex items-start gap-3 text-sm text-zinc-700 dark:text-zinc-200">
                            <input type="checkbox" wire:model.defer="form.tier_codes" value="{{ $option['code'] }}" class="mt-0.5 size-4 rounded border-zinc-300 text-red-600 focus:ring-red-500">
                            <span>{{ $option['name'] }} <span class="text-zinc-400">({{ $option['code'] }})</span></span>
                        </label>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Chưa có cấp dự toán nào bật yêu cầu key trong landing page.</p>
                    @endforelse
                </div>

                <textarea rows="4" wire:model.defer="form.notes" placeholder="Ghi chú nội bộ" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>

                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600">
                    <i class="fa-solid fa-key"></i>
                    Tạo key mới
                </button>
            </form>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Danh sách key</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Key có thể bật hoặc tắt ngay trong admin. Khi tắt, người dùng sẽ không thể dùng để gửi yêu cầu dự toán.</p>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">
                            <th class="px-3 py-3">Key</th>
                            <th class="px-3 py-3">Áp dụng</th>
                            <th class="px-3 py-3">Trạng thái</th>
                            <th class="px-3 py-3">Đã dùng</th>
                            <th class="px-3 py-3">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($keys as $key)
                            <tr>
                                <td class="px-3 py-4 align-top">
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $key->label }}</p>
                                    <p class="mt-1 font-mono text-xs text-zinc-500 dark:text-zinc-400">{{ $key->code }}</p>
                                    @if ($key->notes)
                                        <p class="mt-2 max-w-xs text-xs leading-6 text-zinc-500 dark:text-zinc-400">{{ $key->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    {{ collect($key->tier_codes ?? [])->join(', ') ?: 'Tất cả' }}
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $key->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                        {{ $key->is_active ? 'Đang bật' : 'Đang tắt' }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 align-top text-zinc-600 dark:text-zinc-300">
                                    <p>{{ $key->used_count }}</p>
                                    <p class="mt-1 text-xs text-zinc-400">{{ $key->last_used_at?->format('d/m/Y H:i') ?: 'Chưa sử dụng' }}</p>
                                </td>
                                <td class="px-3 py-4 align-top">
                                    <button type="button" wire:click="toggle({{ $key->id }})" class="rounded-2xl border px-4 py-2 text-sm font-semibold transition {{ $key->is_active ? 'border-zinc-300 text-zinc-700 hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200' : 'border-emerald-300 text-emerald-700 hover:border-emerald-400 dark:border-emerald-500/30 dark:text-emerald-300' }}">
                                        {{ $key->is_active ? 'Tắt key' : 'Bật key' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có key dự toán nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
