<div class="space-y-4" wire:poll.10s.visible>
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Hàng chờ đồng bộ API Master Data DashBoard</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Theo dõi job gửi tour CMS sang API Master Data DashBoard, kiểm tra lỗi và chạy ngay các hàng chờ hiện có.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                type="button"
                wire:click="deleteAllFinished"
                wire:confirm="Bạn có chắc chắn muốn xóa tất cả hàng chờ đã kết thúc khỏi database? Hành động này không thể hoàn tác."
                wire:loading.attr="disabled"
                wire:target="deleteAllFinished"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10"
            >
                <i class="fa-solid fa-trash"></i>
                <span wire:loading.remove wire:target="deleteAllFinished">Xóa tất cả đã kết thúc</span>
                <span wire:loading wire:target="deleteAllFinished">Đang xóa...</span>
            </button>

            <button
                type="button"
                wire:click="unlockStaleRunning"
                wire:confirm="Chuyển các hàng chờ đang chạy quá 15 phút sang trạng thái lỗi để có thể chạy lại?"
                wire:loading.attr="disabled"
                wire:target="unlockStaleRunning"
                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-amber-200 px-4 py-3 text-sm font-semibold text-amber-700 transition hover:bg-amber-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-amber-500/30 dark:text-amber-300 dark:hover:bg-amber-500/10"
            >
                <i class="fa-solid fa-unlock"></i>
                <span wire:loading.remove wire:target="unlockStaleRunning">Mở khóa đang chạy kẹt</span>
                <span wire:loading wire:target="unlockStaleRunning">Đang mở khóa...</span>
            </button>

            <button
                type="button"
                wire:click="runAllExistingNow"
                wire:loading.attr="disabled"
                wire:target="runAllExistingNow"
                class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <i class="fa-solid fa-play"></i>
                <span wire:loading.remove wire:target="runAllExistingNow">Chạy ngay tất cả hiện có</span>
                <span wire:loading wire:target="runAllExistingNow">Đang xử lý...</span>
            </button>
        </div>
    </div>

    @include('livewire.admin.cms.partials.tours-submenu')

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="grid gap-3 md:grid-cols-5">
        @foreach ($statusOptions as $status => $label)
            <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format($stats[$status] ?? 0) }}</p>
            </div>
        @endforeach
    </section>

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 grid gap-3 lg:grid-cols-[1fr_180px_180px]">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Tìm theo ID, tour, mã API Master Data DashBoard..."
                class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
            >
            <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả trạng thái</option>
                @foreach ($statusOptions as $status => $label)
                    <option value="{{ $status }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="triggerFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả nguồn tạo</option>
                @foreach ($triggerOptions as $trigger => $label)
                    <option value="{{ $trigger }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Tour CMS</th>
                        <th class="px-4 py-3">API Master Data DashBoard</th>
                        <th class="px-4 py-3">Nguồn tạo</th>
                        <th class="px-4 py-3">Thời gian</th>
                        <th class="px-4 py-3">Kết quả</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($runs as $run)
                        <tr wire:key="agency-sync-run-{{ $run->id }}" class="align-top">
                            <td class="px-4 py-4">
                                <span class="{{ $this->statusBadgeClass($run->status) }} inline-flex rounded-full px-3 py-1 text-xs font-semibold">
                                    {{ $this->statusLabel($run->status) }}
                                </span>
                                <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">#{{ $run->id }} · Lần chạy: {{ $run->attempts }}</p>
                            </td>
                            <td class="px-4 py-4">
                                @if ($run->tour)
                                    <a href="{{ route('admin.tours.edit', $run->tour) }}" wire:navigate class="font-semibold text-zinc-900 transition hover:text-red-600 dark:text-white">
                                        {{ $run->tour_title ?: $run->tour->title }}
                                    </a>
                                @else
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $run->tour_title ?: 'Tour đã bị xóa' }}</p>
                                @endif
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">CMS ID: {{ $run->tour_id ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                <p>Tour ID: {{ $run->source_tour_id ?: '-' }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Mã: {{ $run->tour_code ?: '-' }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                <p>{{ $this->triggerLabel($run->trigger) }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Queue: {{ $run->queue_name }}</p>
                                @if ($run->creator)
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Người tạo: {{ $run->creator->name }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-xs text-zinc-500 dark:text-zinc-400">
                                <p>Chờ: {{ optional($run->queued_at)->format('d/m/Y H:i') ?: '-' }}</p>
                                <p class="mt-1">Chạy: {{ optional($run->started_at)->format('d/m/Y H:i') ?: '-' }}</p>
                                <p class="mt-1">Xong: {{ optional($run->finished_at)->format('d/m/Y H:i') ?: '-' }}</p>
                            </td>
                            <td class="max-w-sm px-4 py-4 text-xs text-zinc-500 dark:text-zinc-400">
                                @if ($run->last_error)
                                    <p class="rounded-2xl bg-red-50 px-3 py-2 text-red-700 dark:bg-red-500/10 dark:text-red-300">{{ $run->last_error }}</p>
                                @elseif ($run->summary)
                                    <p>Departure: {{ data_get($run->summary, 'departures', 0) }}</p>
                                    <p class="mt-1">Đã xóa: {{ data_get($run->summary, 'deleted_departures', 0) }}</p>
                                    @if (data_get($run->summary, 'reason'))
                                        <p class="mt-1">Lý do: {{ data_get($run->summary, 'reason') }}</p>
                                    @endif
                                @else
                                    <p>Chưa có kết quả.</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex flex-wrap justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="runNow({{ $run->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="runNow({{ $run->id }})"
                                        @disabled($run->status === \Src\Domains\Cms\Models\TourAgencyPushSyncRun::STATUS_RUNNING)
                                        class="rounded-2xl border border-emerald-200 px-3 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-emerald-500/30 dark:text-emerald-300 dark:hover:bg-emerald-500/10"
                                    >
                                        <span wire:loading.remove wire:target="runNow({{ $run->id }})">Chạy ngay</span>
                                        <span wire:loading wire:target="runNow({{ $run->id }})">Đang chạy...</span>
                                    </button>

                                    @if ($this->canDeleteRun($run->status))
                                        <button
                                            type="button"
                                            wire:click="deleteFinished({{ $run->id }})"
                                            wire:confirm="Bạn có chắc chắn muốn xóa hàng chờ đã kết thúc này khỏi database? Hành động này không thể hoàn tác."
                                            wire:loading.attr="disabled"
                                            wire:target="deleteFinished({{ $run->id }})"
                                            class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10"
                                        >
                                            <span wire:loading.remove wire:target="deleteFinished({{ $run->id }})">Xóa</span>
                                            <span wire:loading wire:target="deleteFinished({{ $run->id }})">Đang xóa...</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                Chưa có hàng chờ đồng bộ API Master Data DashBoard nào khớp bộ lọc hiện tại.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $runs->links() }}
        </div>
    </section>
</div>
