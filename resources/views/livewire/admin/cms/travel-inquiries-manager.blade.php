<div class="space-y-4">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Yêu cầu tư vấn du lịch</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Lead từ tour, dịch vụ và liên hệ chung đều đi qua một pipeline.</p>
        </div>

        @if (session('status'))
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif
    </div>

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo khách, điện thoại, email..." class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

            <select wire:model.live="source" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả nguồn</option>
                <option value="tour">tour</option>
                <option value="service">service</option>
                <option value="general">general</option>
            </select>

            <select wire:model.live="voucherCampaign" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Không lọc voucher campaign</option>
                <option value="__any">Tất cả lead voucher</option>
                @foreach (($voucherCampaigns ?? []) as $campaign)
                    <option value="{{ $campaign->slug }}">{{ $campaign->title }} /{{ $campaign->slug }}</option>
                @endforeach
            </select>

            <select wire:model.live="status" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả trạng thái</option>
                <option value="new">new</option>
                <option value="contacted">contacted</option>
                <option value="closed">closed</option>
            </select>

            <label class="flex items-center gap-2 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <span class="shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Từ</span>
                <input type="date" wire:model.live="createdFrom" aria-label="Lọc từ ngày tạo" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-zinc-700 outline-none focus:ring-0 dark:text-white">
            </label>

            <label class="flex items-center gap-2 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <span class="shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Đến</span>
                <input type="date" wire:model.live="createdTo" aria-label="Lọc đến ngày tạo" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-zinc-700 outline-none focus:ring-0 dark:text-white">
            </label>
        </div>

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">Khách</th>
                        <th class="px-4 py-3">Ngữ cảnh</th>
                        <th class="px-4 py-3">Liên hệ</th>
                        <th class="px-4 py-3">Ngày đi / số khách</th>
                        <th class="px-4 py-3">Ngày tạo</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($inquiries as $inquiry)
                        @php
                            $legacyAdultGuestCount = data_get($inquiry->meta, 'company_name');
                            $adultGuestCount = data_get($inquiry->meta, 'adult_guest_count', is_numeric($legacyAdultGuestCount) ? $legacyAdultGuestCount : null);
                            $childGuestCount = $inquiry->party_size;
                            $voucherCode = data_get($inquiry->meta, 'voucher.code');
                            $voucherVariant = data_get($inquiry->meta, 'voucher_variant', data_get($inquiry->meta, 'ab_variant'));
                            $expectedDestination = data_get($inquiry->meta, 'expected_destination');
                            $expectedTime = data_get($inquiry->meta, 'expected_time');
                        @endphp
                        <tr wire:key="travel-inquiry-{{ $inquiry->id }}" class="align-top {{ $selectedInquiry?->id === $inquiry->id ? 'bg-teal-50/40 dark:bg-teal-500/5' : '' }}">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ $inquiry->customer_name }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $inquiry->page_url ?: 'Chưa có page URL' }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                <p>{{ $inquiry->source->label() }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $inquiry->context_title ?: 'Yêu cầu chung' }}</p>
                                @if ($voucherCode)
                                    <p class="mt-2 inline-flex rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700 dark:bg-orange-500/10 dark:text-orange-300">Voucher: {{ $voucherCode }}</p>
                                @endif
                                @if ($voucherVariant)
                                    <p class="mt-2 inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 dark:bg-sky-500/10 dark:text-sky-300">Biến thể: {{ $voucherVariant }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                <p>{{ $inquiry->customer_phone }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $inquiry->customer_email ?: 'Chưa có email' }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">
                                @if ($expectedDestination)
                                    <p>Đi: {{ $expectedDestination }}</p>
                                @endif
                                <p>{{ $expectedTime ?: (optional($inquiry->travel_date)->format('d/m/Y') ?: 'Chưa có') }}</p>
                                <p class="mt-1 text-xs">Người lớn: {{ filled($adultGuestCount) ? $adultGuestCount : 'Chưa có' }}</p>
                                <p class="mt-1 text-xs">Trẻ em: {{ filled($childGuestCount) ? $childGuestCount : 'Chưa có' }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                <p class="whitespace-nowrap">{{ optional($inquiry->created_at)->format('d/m/Y H:i:s') ?: 'Chưa có' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $inquiry->status === 'closed' ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' : ($inquiry->status === 'contacted' ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300') }}">
                                    {{ $inquiry->status }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end">
                                    <button type="button" wire:click="selectInquiry({{ $inquiry->id }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                        {{ $selectedInquiry?->id === $inquiry->id ? 'Ẩn chi tiết' : 'Xem chi tiết' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @if ($selectedInquiry?->id === $inquiry->id)
                            <tr wire:key="travel-inquiry-details-{{ $inquiry->id }}">
                                <td colspan="7" class="bg-zinc-50/80 px-4 py-4 dark:bg-zinc-950/30">
                                    @include('livewire.admin.cms.partials.travel-inquiry-detail-panel', ['selectedInquiry' => $selectedInquiry])
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có yêu cầu nào khớp bộ lọc hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $inquiries->links() }}
        </div>
    </section>
</div>
