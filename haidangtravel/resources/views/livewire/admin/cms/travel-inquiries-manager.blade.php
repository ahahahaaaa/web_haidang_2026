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
        <div class="mb-4 grid gap-3 xl:grid-cols-3">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo khách, điện thoại, email..." class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

            <select wire:model.live="source" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả nguồn</option>
                <option value="tour">tour</option>
                <option value="service">service</option>
                <option value="general">general</option>
            </select>

            <select wire:model.live="status" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả trạng thái</option>
                <option value="new">new</option>
                <option value="contacted">contacted</option>
                <option value="closed">closed</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">Khách</th>
                        <th class="px-4 py-3">Ngữ cảnh</th>
                        <th class="px-4 py-3">Liên hệ</th>
                        <th class="px-4 py-3">Ngày đi / số khách</th>
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
                        @endphp
                        <tr wire:key="travel-inquiry-{{ $inquiry->id }}" class="align-top">
                            <td class="px-4 py-4">
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ $inquiry->customer_name }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $inquiry->page_url ?: 'Chưa có page URL' }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                <p>{{ $inquiry->source->label() }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $inquiry->context_title ?: 'Yêu cầu chung' }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                <p>{{ $inquiry->customer_phone }}</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $inquiry->customer_email ?: 'Chưa có email' }}</p>
                            </td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">
                                <p>{{ optional($inquiry->travel_date)->format('d/m/Y') ?: 'Chưa có' }}</p>
                                <p class="mt-1 text-xs">Người lớn: {{ filled($adultGuestCount) ? $adultGuestCount : 'Chưa có' }}</p>
                                <p class="mt-1 text-xs">Trẻ em: {{ filled($childGuestCount) ? $childGuestCount : 'Chưa có' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $inquiry->status === 'closed' ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' : ($inquiry->status === 'contacted' ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300') }}">
                                    {{ $inquiry->status }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end">
                                    <button type="button" wire:click="selectInquiry({{ $inquiry->id }})" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                        Xem chi tiết
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có yêu cầu nào khớp bộ lọc hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $inquiries->links() }}
        </div>
    </section>

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        @if ($selectedInquiry)
            @php
                $selectedLegacyAdultGuestCount = data_get($selectedInquiry->meta, 'company_name');
                $selectedAdultGuestCount = data_get($selectedInquiry->meta, 'adult_guest_count', is_numeric($selectedLegacyAdultGuestCount) ? $selectedLegacyAdultGuestCount : null);
                $selectedChildGuestCount = $selectedInquiry->party_size;
            @endphp
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $selectedInquiry->customer_name }}</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $selectedInquiry->source->label() }} · {{ $selectedInquiry->context_title ?: 'Yêu cầu chung' }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="markStatus({{ $selectedInquiry->id }}, 'contacted')" class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700 dark:text-zinc-200">Đánh dấu đã liên hệ</button>
                        <button type="button" wire:click="markStatus({{ $selectedInquiry->id }}, 'closed')" class="rounded-2xl bg-teal-600 px-3 py-2 text-sm font-semibold text-white">Đóng yêu cầu</button>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Điện thoại</p>
                        <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ $selectedInquiry->customer_phone }}</p>
                    </div>
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Email</p>
                        <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ $selectedInquiry->customer_email ?: 'Chưa cung cấp' }}</p>
                    </div>
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Ngày đi dự kiến</p>
                        <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ optional($selectedInquiry->travel_date)->format('d/m/Y') ?: 'Chưa có' }}</p>
                    </div>
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Số khách người lớn</p>
                        <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ filled($selectedAdultGuestCount) ? $selectedAdultGuestCount : 'Chưa có' }}</p>
                    </div>
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Số trẻ em</p>
                        <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ filled($selectedChildGuestCount) ? $selectedChildGuestCount : 'Chưa có' }}</p>
                    </div>
                </div>

                @if ($selectedInquiry->message)
                    <div class="rounded-3xl bg-zinc-50 p-5 dark:bg-zinc-800/70">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Nội dung yêu cầu</p>
                        <div class="mt-3 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $selectedInquiry->message }}</div>
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Mail status</p>
                        <p class="mt-2 font-semibold text-zinc-900 dark:text-white">{{ $selectedInquiry->mail_status ?: 'pending' }}</p>
                    </div>
                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/70">
                        <p class="text-xs uppercase tracking-[0.2em] text-zinc-500">Trang gửi</p>
                        <p class="mt-2 break-all text-sm text-zinc-700 dark:text-zinc-200">{{ $selectedInquiry->page_url ?: 'Chưa có' }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-3xl bg-zinc-50 p-6 text-sm text-zinc-500 dark:bg-zinc-800/70 dark:text-zinc-300">
                Chọn một yêu cầu trong bảng để xem chi tiết.
            </div>
        @endif
    </section>
</div>
