<div class="space-y-4">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('admin.voucher-campaigns') }}" wire:navigate class="mb-2 inline-flex text-sm font-semibold text-teal-700 dark:text-teal-300">Quay lại Voucher campaigns</a>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Mã voucher đã cấp phát</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Tra cứu mã voucher đã cấp phát trên toàn bộ campaigns.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('warning'))
                <div class="rounded-2xl bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                    {{ session('warning') }}
                </div>
            @endif

            <a href="{{ $exportUrl }}" class="inline-flex items-center gap-2 rounded-2xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white">
                <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                Xuất Excel
            </a>

            @can('admin.voucher-campaigns.index')
                <a href="{{ route('admin.voucher-campaigns') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                    Voucher campaigns
                </a>
            @endcan
        </div>
    </div>

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-950/40">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500 dark:text-zinc-400">Tổng đã cấp</p>
                <p class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format((int) $issuedCodesCount) }}</p>
            </div>
            <div class="rounded-2xl bg-orange-50 p-4 dark:bg-orange-500/10">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-700 dark:text-orange-300">Đã phát</p>
                <p class="mt-2 text-2xl font-semibold text-orange-700 dark:text-orange-200">{{ number_format((int) $claimedCodesCount) }}</p>
            </div>
            <div class="rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-500/10">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-300">Đã sử dụng</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-700 dark:text-emerald-200">{{ number_format((int) $usedCodesCount) }}</p>
            </div>
            <div class="rounded-2xl bg-sky-50 p-4 dark:bg-sky-500/10">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-sky-700 dark:text-sky-300">Campaign có mã</p>
                <p class="mt-2 text-2xl font-semibold text-sky-700 dark:text-sky-200">{{ number_format((int) $campaignsWithIssuedCodesCount) }}</p>
            </div>
        </div>

        <div class="mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm mã, campaign, khách, SĐT, email..." class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white xl:col-span-2">

            <select wire:model.live="statusFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Đã phát + đã sử dụng</option>
                <option value="{{ \Src\Domains\Cms\Models\VoucherCode::STATUS_CLAIMED }}">Đã phát</option>
                <option value="{{ \Src\Domains\Cms\Models\VoucherCode::STATUS_USED }}">Đã sử dụng</option>
            </select>

            <select wire:model.live="leadStatusFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả trạng thái lead</option>
                <option value="new">Lead mới</option>
                <option value="contacted">Đã liên hệ</option>
                <option value="closed">Đã đóng</option>
                <option value="missing">Không còn lead</option>
            </select>

            <label class="flex items-center gap-2 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <span class="shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Từ</span>
                <input type="date" wire:model.live="claimedFrom" aria-label="Lọc mã voucher cấp phát từ ngày" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-zinc-700 outline-none focus:ring-0 dark:text-white">
            </label>

            <label class="flex items-center gap-2 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <span class="shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Đến</span>
                <input type="date" wire:model.live="claimedTo" aria-label="Lọc mã voucher cấp phát đến ngày" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-zinc-700 outline-none focus:ring-0 dark:text-white">
            </label>
        </div>

        @if ($hasFilters)
            <div class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:bg-sky-500/10 dark:text-sky-200">
                <span>Đang lọc danh sách mã đã cấp phát.</span>
                <button type="button" wire:click="clearFilters" class="font-semibold underline underline-offset-4">Xóa lọc</button>
            </div>
        @endif

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">Mã voucher</th>
                        <th class="px-4 py-3">Campaign</th>
                        <th class="px-4 py-3">Khách / lead</th>
                        <th class="px-4 py-3">Ngày cấp phát</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Ghi chú sử dụng</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($codes as $voucherCode)
                        @php
                            $campaign = $voucherCode->campaign;
                            $inquiry = $voucherCode->inquiry;
                            $isUsed = $voucherCode->status === \Src\Domains\Cms\Models\VoucherCode::STATUS_USED;
                            $isCodeUsageOpen = $campaign?->isCodeUsageOpen() ?? false;
                        @endphp
                        <tr wire:key="voucher-campaign-code-{{ $voucherCode->id }}" class="align-top">
                            <td class="px-4 py-4">
                                <p class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $voucherCode->code }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">ID mã: #{{ $voucherCode->id }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                @if ($campaign)
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $campaign->title }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $campaign->slug }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Hạn dùng: {{ $campaign->code_valid_until?->format('d/m/Y') ?: 'Không giới hạn' }}</p>
                                    @can('admin.voucher-campaigns.edit')
                                        <a href="{{ route('admin.voucher-campaigns.edit', ['campaign' => $campaign]) }}" wire:navigate class="mt-2 inline-flex text-xs font-semibold text-teal-700 dark:text-teal-300">Sửa campaign</a>
                                    @endcan
                                @else
                                    <p class="text-zinc-500 dark:text-zinc-400">Campaign không còn tồn tại.</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                @if ($inquiry)
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $inquiry->customer_name ?: 'Khách chưa nhập tên' }}</p>
                                    <p class="mt-1">{{ $inquiry->customer_phone ?: 'Chưa có số điện thoại' }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $inquiry->customer_email ?: 'Chưa có email' }}</p>
                                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Lead #{{ $inquiry->id }} · {{ $inquiry->status }} · {{ $inquiry->context_title ?: 'Yêu cầu voucher' }}</p>
                                @else
                                    <p class="text-zinc-500 dark:text-zinc-400">Không còn lead liên kết.</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                <p>{{ $voucherCode->claimed_at?->format('d/m/Y H:i') ?: '-' }}</p>
                                @if ($inquiry?->created_at)
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Lead: {{ $inquiry->created_at->format('d/m/Y H:i') }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $isUsed ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300' }}">
                                    {{ $voucherCode->statusLabel() }}
                                </span>
                                @if ($voucherCode->used_at)
                                    <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $voucherCode->used_at->format('d/m/Y H:i') }}
                                        @if ($voucherCode->usedBy)
                                            · {{ $voucherCode->usedBy->name ?: $voucherCode->usedBy->email }}
                                        @endif
                                    </p>
                                @endif
                            </td>
                            <td class="min-w-[18rem] px-4 py-4">
                                @can('admin.voucher-campaigns.edit')
                                    <textarea rows="2" wire:model.defer="usageNotes.{{ $voucherCode->id }}" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white" placeholder="Nhập ghi chú khi khách đã sử dụng voucher..."></textarea>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if ($isUsed)
                                            <button type="button" wire:click="saveUsageNote({{ $voucherCode->id }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">Lưu ghi chú</button>
                                            <button type="button" wire:click="markVoucherCodeClaimed({{ $voucherCode->id }})" class="rounded-2xl border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-700">Chuyển về đã phát</button>
                                        @else
                                            @if ($isCodeUsageOpen)
                                                <button type="button" wire:click="markVoucherCodeUsed({{ $voucherCode->id }})" class="rounded-2xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Đánh dấu đã sử dụng</button>
                                            @else
                                                <button type="button" disabled class="cursor-not-allowed rounded-2xl bg-zinc-200 px-3 py-2 text-xs font-semibold text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300">Đã quá hạn sử dụng</button>
                                            @endif
                                        @endif
                                    </div>
                                @else
                                    <p class="text-sm text-zinc-600 dark:text-zinc-300">{{ $voucherCode->used_note ?: '-' }}</p>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $hasFilters ? 'Không có mã voucher nào khớp bộ lọc hiện tại.' : 'Chưa có mã voucher nào được cấp phát.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $codes->links() }}
        </div>
    </section>
</div>
