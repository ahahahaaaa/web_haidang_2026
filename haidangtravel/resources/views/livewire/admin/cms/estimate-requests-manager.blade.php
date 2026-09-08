<div class="space-y-6">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Phiếu yêu cầu dự toán</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Theo dõi các yêu cầu dự toán đã lưu, trạng thái gửi mail admin và mail khách hàng.</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
        <section class="space-y-5 rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-4 md:grid-cols-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên, email, số điện thoại" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white md:col-span-2">

                <select wire:model.live="tier" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Tất cả cấp dự toán</option>
                    @foreach ($tierOptions as $option)
                        <option value="{{ $option->tier_code }}">{{ $option->tier_name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="adminMailStatus" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Mail admin: tất cả</option>
                    @foreach (['pending' => 'Chờ gửi', 'sent' => 'Đã gửi', 'failed' => 'Lỗi gửi', 'skipped' => 'Bỏ qua'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="customerMailStatus" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Mail khách: tất cả</option>
                    @foreach (['pending' => 'Chờ gửi', 'sent' => 'Đã gửi', 'failed' => 'Lỗi gửi', 'skipped' => 'Bỏ qua'] as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-3">
                @forelse ($requests as $request)
                    <button
                        type="button"
                        wire:click="selectRequest({{ $request->id }})"
                        class="flex w-full flex-col gap-3 rounded-3xl border px-4 py-4 text-left transition {{ $selectedId === $request->id ? 'border-red-400 bg-red-50 dark:border-red-500/50 dark:bg-red-500/10' : 'border-zinc-200 bg-zinc-50 hover:border-zinc-300 dark:border-zinc-800 dark:bg-zinc-950/40' }}"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-white">{{ $request->customer_name }}</p>
                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-zinc-500 dark:text-zinc-400">{{ $request->tier_name }}</p>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $request->created_at?->format('d/m/Y H:i') }}</p>
                        </div>

                        <div class="grid gap-2 text-sm text-zinc-600 dark:text-zinc-300 md:grid-cols-2">
                            <p>{{ $request->customer_email }}</p>
                            <p>{{ $request->customer_phone }}</p>
                        </div>

                        <div class="flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-full px-3 py-1 {{ $request->mail_status === 'sent' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : ($request->mail_status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200') }}">
                                Admin: {{ $request->mail_status }}
                            </span>
                            <span class="rounded-full px-3 py-1 {{ $request->customer_mail_status === 'sent' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : ($request->customer_mail_status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200') }}">
                                Khách: {{ $request->customer_mail_status }}
                            </span>
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">
                                {{ $request->delivery_channel }}
                            </span>
                        </div>
                    </button>
                @empty
                    <div class="rounded-3xl border border-dashed border-zinc-300 px-4 py-8 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                        Chưa có yêu cầu dự toán nào phù hợp với bộ lọc hiện tại.
                    </div>
                @endforelse
            </div>

            <div>
                {{ $requests->links() }}
            </div>
        </section>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            @if ($selectedRequest)
                @php
                    $storedInputPayload = $selectedRequest->input_payload ?? [];
                    $flatInputPayload = data_get($storedInputPayload, 'flat', is_array($storedInputPayload) ? $storedInputPayload : []);
                    $roomProgram = data_get($storedInputPayload, 'room_program', []);
                    $designOptions = data_get($storedInputPayload, 'design_options', []);
                    $specialAddons = data_get($storedInputPayload, 'special_addons', []);
                    $estimatePayload = data_get($selectedRequest->result_snapshot, 'estimate', []);
                    $pricingPackages = data_get($estimatePayload, 'pricing_summary.packages', data_get($estimatePayload, 'pricing.packages', []));
                    $componentTree = data_get($selectedRequest->result_snapshot, 'component_tree', data_get($estimatePayload, 'component_tree_breakdown', []));
                    $roomBreakdown = data_get($estimatePayload, 'room_breakdown', []);
                @endphp
                <div class="space-y-6">
                    <div class="flex flex-col gap-3 border-b border-zinc-200 pb-5 dark:border-zinc-800">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-red-600">Chi tiết yêu cầu</p>
                                <h2 class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $selectedRequest->customer_name }}</h2>
                                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $selectedRequest->tier_name }} · {{ $selectedRequest->created_at?->format('d/m/Y H:i') }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs font-semibold">
                                <span class="rounded-full bg-zinc-200 px-3 py-1 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">{{ $selectedRequest->status }}</span>
                                @if ($selectedRequest->requires_key)
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        Key: {{ $selectedRequest->estimate_key_code ?: 'Đã dùng' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Liên hệ</p>
                                <dl class="mt-3 space-y-2 text-sm text-zinc-700 dark:text-zinc-200">
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="font-medium">Email</dt>
                                        <dd class="text-right">{{ $selectedRequest->customer_email }}</dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="font-medium">Điện thoại</dt>
                                        <dd class="text-right">{{ $selectedRequest->customer_phone }}</dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="font-medium">Công ty</dt>
                                        <dd class="text-right">{{ $selectedRequest->company_name ?: 'Không cung cấp' }}</dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="font-medium">Khu vực</dt>
                                        <dd class="text-right">{{ $selectedRequest->project_location ?: 'Không cung cấp' }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Trạng thái gửi mail</p>
                                <dl class="mt-3 space-y-2 text-sm text-zinc-700 dark:text-zinc-200">
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="font-medium">Admin</dt>
                                        <dd class="text-right">{{ $selectedRequest->mail_status }}{{ $selectedRequest->mailed_to ? ' · '.$selectedRequest->mailed_to : '' }}</dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="font-medium">Thời gian gửi admin</dt>
                                        <dd class="text-right">{{ $selectedRequest->mailed_at?->format('d/m/Y H:i') ?: 'Chưa gửi' }}</dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="font-medium">Khách hàng</dt>
                                        <dd class="text-right">{{ $selectedRequest->customer_mail_status }}{{ $selectedRequest->customer_mailed_to ? ' · '.$selectedRequest->customer_mailed_to : '' }}</dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-3">
                                        <dt class="font-medium">Thời gian gửi khách</dt>
                                        <dd class="text-right">{{ $selectedRequest->customer_mailed_at?->format('d/m/Y H:i') ?: 'Chưa gửi' }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-zinc-200 bg-zinc-50 px-5 py-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Mô tả nhu cầu</p>
                        <p class="mt-3 whitespace-pre-line text-sm leading-7 text-zinc-700 dark:text-zinc-200">{{ $selectedRequest->project_overview }}</p>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                            <div class="rounded-3xl border border-orange-200 bg-orange-50 px-5 py-4 dark:border-orange-500/20 dark:bg-orange-500/5">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-700 dark:text-orange-300">Thông số đầu vào</p>
                                <ul class="mt-4 space-y-3 text-sm leading-7 text-orange-900 dark:text-orange-100">
                                @forelse ($flatInputPayload as $item)
                                    <li class="flex items-start justify-between gap-4 border-b border-orange-200/60 pb-3 last:border-none last:pb-0 dark:border-orange-400/10">
                                        <span class="font-semibold">{{ $item['label'] ?? $item['slug'] }}</span>
                                        <span class="text-right">{{ $item['display_value'] ?? $item['value'] }}</span>
                                    </li>
                                @empty
                                    <li>Không có dữ liệu đầu vào.</li>
                                @endforelse
                                </ul>

                                @if ($roomProgram !== [])
                                    <div class="mt-4 rounded-2xl bg-white/80 px-4 py-3 text-sm leading-7 text-orange-900 dark:bg-zinc-900/50 dark:text-orange-100">
                                        <p class="font-semibold">Công năng & nội thất</p>
                                        <ul class="mt-2 space-y-2">
                                            @foreach ($roomProgram as $room)
                                                <li>
                                                    {{ $room['room_type_label'] ?? $room['room_type'] }} · {{ $room['count'] ?? 0 }} phòng
                                                    @if (filled($room['template_label'] ?? null))
                                                        · {{ $room['template_label'] }}
                                                    @endif
                                                    @if (filled($room['extra_items'] ?? []))
                                                        · Add thêm: {{ collect($room['extra_items'])->pluck('label')->implode(', ') }}
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($designOptions !== [] || $specialAddons !== [])
                                    <div class="mt-4 rounded-2xl bg-white/80 px-4 py-3 text-sm leading-7 text-orange-900 dark:bg-zinc-900/50 dark:text-orange-100">
                                        @if ($designOptions !== [])
                                            <p><span class="font-semibold">Thiết kế:</span> {{ collect($designOptions)->pluck('label')->implode(', ') }}</p>
                                        @endif
                                        @if ($specialAddons !== [])
                                            <p class="{{ $designOptions !== [] ? 'mt-2' : '' }}"><span class="font-semibold">Add-on:</span> {{ collect($specialAddons)->pluck('label')->implode(', ') }}</p>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="rounded-3xl border border-blue-200 bg-blue-50 px-5 py-4 dark:border-blue-500/20 dark:bg-blue-500/5">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700 dark:text-blue-300">Kết quả dự toán</p>
                            <p class="mt-4 text-lg font-semibold text-blue-900 dark:text-blue-100">{{ data_get($selectedRequest->result_snapshot, 'title', 'Chưa có tiêu đề') }}</p>
                            <p class="mt-3 text-sm leading-7 text-blue-800 dark:text-blue-100">{{ data_get($selectedRequest->result_snapshot, 'summary', 'Chưa có mô tả kết quả.') }}</p>
                            <ul class="mt-4 space-y-3 text-sm leading-7 text-blue-900 dark:text-blue-100">
                                @forelse (data_get($selectedRequest->result_snapshot, 'bullets', []) as $bullet)
                                    <li class="flex items-start gap-3">
                                        <span class="mt-2 size-2 rounded-full bg-blue-600"></span>
                                        <span>{{ $bullet }}</span>
                                    </li>
                                @empty
                                    <li>Không có bullet kết quả.</li>
                                @endforelse
                            </ul>
                            @if (filled(data_get($selectedRequest->result_snapshot, 'delivery_text')))
                                <div class="mt-4 rounded-2xl bg-white/80 px-4 py-3 text-sm leading-7 text-blue-800 dark:bg-zinc-900/50 dark:text-blue-100">
                                    {{ data_get($selectedRequest->result_snapshot, 'delivery_text') }}
                                </div>
                            @endif

                            @if (filled($estimatePayload))
                                <div class="mt-4 grid gap-3 md:grid-cols-3">
                                    <div class="rounded-2xl bg-white/80 px-4 py-3 dark:bg-zinc-900/50">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Level</p>
                                        <p class="mt-2 font-semibold text-blue-900 dark:text-blue-100">{{ data_get($estimatePayload, 'level', data_get($selectedRequest->result_snapshot, 'level', 'N/A')) }}</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/80 px-4 py-3 dark:bg-zinc-900/50">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Footprint (m2)</p>
                                        <p class="mt-2 font-semibold text-blue-900 dark:text-blue-100">{{ number_format((float) data_get($estimatePayload, 'derived.base_area', 0), 2, ',', '.') }} m2</p>
                                    </div>
                                    <div class="rounded-2xl bg-white/80 px-4 py-3 dark:bg-zinc-900/50">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-blue-700 dark:text-blue-300">Quy đổi (m2)</p>
                                        <p class="mt-2 font-semibold text-blue-900 dark:text-blue-100">{{ number_format((float) data_get($estimatePayload, 'totals.converted_area', 0), 2, ',', '.') }} m2</p>
                                    </div>
                                </div>

                                @if (filled($pricingPackages))
                                    <div class="mt-4 space-y-3">
                                        @foreach ($pricingPackages as $package)
                                            <div class="flex items-start justify-between gap-4 rounded-2xl bg-white/80 px-4 py-3 text-sm leading-7 text-blue-900 dark:bg-zinc-900/50 dark:text-blue-100">
                                                <div>
                                                    <p class="font-semibold">{{ $package['label'] ?? $package['key'] ?? 'Gói' }}</p>
                                                    <p class="text-blue-700 dark:text-blue-300">{{ number_format((float) ($package['unit_price'] ?? 0), 0, ',', '.') }} đ/m2</p>
                                                </div>
                                                <p class="text-right font-semibold">{{ number_format((float) ($package['amount'] ?? 0), 0, ',', '.') }} đ</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($componentTree !== [])
                                    <div class="mt-4 rounded-2xl bg-white/80 px-4 py-3 dark:bg-zinc-900/50">
                                        <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">Component tree breakdown</p>
                                        <ul class="mt-2 space-y-2 text-sm leading-7 text-blue-900 dark:text-blue-100">
                                            @foreach ($componentTree as $component)
                                                <li>{{ $component['node_name'] ?? $component['label'] ?? $component['code'] }}: {{ number_format((float) ($component['amount'] ?? 0), 0, ',', '.') }} đ</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if ($roomBreakdown !== [])
                                    <div class="mt-4 rounded-2xl bg-white/80 px-4 py-3 dark:bg-zinc-900/50">
                                        <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">Room breakdown</p>
                                        <ul class="mt-2 space-y-2 text-sm leading-7 text-blue-900 dark:text-blue-100">
                                            @foreach ($roomBreakdown as $room)
                                                <li>{{ $room['room_type_label'] ?? $room['room_type'] }}: {{ number_format((float) ($room['amount'] ?? 0), 0, ',', '.') }} đ</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-3xl border border-dashed border-zinc-300 px-4 py-12 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Chọn một yêu cầu dự toán ở cột bên trái để xem chi tiết.
                </div>
            @endif
        </section>
    </div>
</div>
