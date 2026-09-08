<div class="space-y-4">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Voucher campaigns</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tạo chiến dịch voucher theo thời gian nhận mã, hạn sử dụng mã, số lượng mã và frame hình chung.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if (session('status'))
                <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                    {{ session('status') }}
                </div>
            @endif

            @can('admin.voucher-campaigns.edit')
                <a href="{{ route('admin.voucher-campaigns.create') }}" wire:navigate class="rounded-2xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white">
                    Tạo campaign
                </a>
            @endcan
        </div>
    </div>

    <section class="{{ $isEditor ? 'max-w-4xl' : '' }}">
        @if (! $isEditor)
        <div class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 grid gap-3 md:grid-cols-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên hoặc slug..." class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                <select wire:model.live="statusFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active">Đang nhận mã</option>
                    <option value="inactive">Đang tắt</option>
                </select>
            </div>

            <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                    <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                        <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                            <th class="px-4 py-3">Campaign</th>
                            <th class="px-4 py-3">Thời gian</th>
                            <th class="px-4 py-3">Mã</th>
                            <th class="px-4 py-3 text-right">Hành động</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                        @forelse ($campaigns as $campaign)
                            <tr wire:key="voucher-campaign-{{ $campaign->id }}" class="align-top">
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ $campaign->title }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $campaign->slug }}</p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $campaign->landingPage?->title ?: 'Chưa gắn landing page' }}</p>
                                </td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                    <p>Nhận mã từ: {{ $campaign->starts_at?->format('d/m/Y') ?: 'Không giới hạn' }}</p>
                                    <p class="mt-1">Nhận mã đến hết ngày: {{ $campaign->ends_at?->format('d/m/Y') ?: 'Không giới hạn' }}</p>
                                    <p class="mt-1">Hạn dùng mã: {{ $campaign->code_valid_until?->format('d/m/Y') ?: 'Không giới hạn' }}</p>
                                    <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $campaign->isCurrentlyRedeemable() ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' }}">
                                        {{ $campaign->isCurrentlyRedeemable() ? 'Đang nhận mã' : 'Không nhận mã' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                    <p>Tổng: {{ $campaign->codes_count }}</p>
                                    <p class="mt-1 text-xs">Đã phát: {{ $campaign->claimed_codes_count }}</p>
                                    <p class="mt-1 text-xs">Đã dùng: {{ $campaign->used_codes_count }}</p>
                                    <p class="mt-1 text-xs">Còn lại: {{ $campaign->available_codes_count }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.voucher-codes') }}" wire:navigate class="rounded-2xl border border-teal-200 bg-teal-50 px-3 py-2 text-sm font-medium text-teal-700 dark:border-teal-500/30 dark:bg-teal-500/10 dark:text-teal-200">
                                            Mã đã cấp
                                        </a>
                                        @can('admin.voucher-campaigns.edit')
                                            <a href="{{ route('admin.voucher-campaigns.edit', ['campaign' => $campaign]) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                                Sửa
                                            </a>
                                            <button
                                                type="button"
                                                wire:click="deleteCampaign({{ $campaign->id }})"
                                                wire:confirm="Bạn có chắc chắn muốn xóa campaign voucher này? Toàn bộ mã voucher thuộc campaign cũng sẽ bị xóa. Hành động này không thể hoàn tác."
                                                class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10"
                                            >
                                                Xóa
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có campaign voucher nào.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $campaigns->links() }}
            </div>
        </div>
        @endif

        @if ($isEditor)
        <form wire:submit="save" class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4">
                <a href="{{ route('admin.voucher-campaigns') }}" wire:navigate class="mb-3 inline-flex text-sm font-semibold text-teal-700 dark:text-teal-300">Quay lại danh sách</a>
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $selectedId ? 'Cập nhật campaign' : 'Tạo campaign mới' }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Khi bấm đổi mới bộ mã, cookie mã cũ của khách sẽ tự mất hiệu lực.</p>
            </div>

            <div class="space-y-4">
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Tên campaign</span>
                    <input type="text" wire:model.live.debounce.300ms="form.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.title') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Slug campaign</span>
                    <input type="text" wire:model="form.slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('form.slug') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Landing page áp dụng</span>
                    <select wire:model="form.landing_page_id" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Không gắn landing page</option>
                        @foreach ($landingPages as $landingPage)
                            @php
                                $landingPageUsedByAnotherCampaign = in_array((int) $landingPage->id, $usedVoucherLandingPageIds ?? [], true);
                            @endphp
                            <option value="{{ $landingPage->id }}" @if ($landingPageUsedByAnotherCampaign) disabled @endif>
                                {{ $landingPage->title }} /{{ $landingPage->slug }}{{ $landingPageUsedByAnotherCampaign ? ' — đã có campaign' : '' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Quy ước hiện tại: một landing page voucher chỉ gắn với một campaign mã voucher. Nếu landing page đã có campaign, hãy mở campaign đó để sửa mã, thời hạn hoặc frame.</p>
                    @error('form.landing_page_id') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </label>

                <div class="grid gap-3 md:grid-cols-3">
                    <label class="block space-y-2">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Nhận mã từ ngày</span>
                        <input type="text" inputmode="numeric" wire:model="form.starts_at" placeholder="dd/mm/yyyy" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.starts_at') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Nhận mã đến hết ngày</span>
                        <input type="text" inputmode="numeric" wire:model="form.ends_at" placeholder="dd/mm/yyyy" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.ends_at') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Sử dụng voucher đến hết ngày</span>
                        <input type="text" inputmode="numeric" wire:model="form.code_valid_until" placeholder="dd/mm/yyyy" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.code_valid_until') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </label>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Thời gian nhận mã quyết định khi khách có thể lấy voucher trên frontsite. Hạn sử dụng voucher là ngày cuối nhân viên cho phép khách dùng mã sau khi đã nhận.</p>

                <div class="grid gap-3 md:grid-cols-2">
                    <label class="block space-y-2">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Prefix mã</span>
                        <input type="text" wire:model="form.code_prefix" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.code_prefix') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </label>

                    <label class="block space-y-2">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Số lượng mã</span>
                        <input type="number" min="1" max="5000" wire:model="form.code_quantity" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        @error('form.code_quantity') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                    </label>
                </div>

                <div class="space-y-3 rounded-3xl border border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Frame hình chung</span>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Ảnh này hiển thị trong popup mã voucher sau khi khách submit form nhận voucher.</p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                data-admin-media-picker-trigger
                                data-livewire-id="{{ $this->getId() }}"
                                data-pick-method="selectFrameImageFromLibrary"
                                data-button-label="Chọn ảnh làm frame voucher"
                                class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                            >
                                <i class="fa-regular fa-images"></i>
                                Chọn từ Media popup
                            </button>

                            @if (filled($form['frame_image_url'] ?? null))
                                <button type="button" wire:click="clearFrameImage" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-600 transition hover:border-zinc-300 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:text-white">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    Bỏ frame
                                </button>
                            @endif
                        </div>
                    </div>

                    @if (filled($form['frame_image_url'] ?? null))
                        <div class="overflow-hidden rounded-3xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                            <img src="{{ $form['frame_image_url'] }}" alt="Frame hình chung voucher" class="h-56 w-full object-cover">
                        </div>
                    @else
                        <div class="flex h-56 items-center justify-center rounded-3xl border border-dashed border-zinc-300 bg-white text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                            Chưa chọn frame hình chung.
                        </div>
                    @endif

                    <label class="block space-y-2">
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">URL frame đang lưu</span>
                        <input type="text" wire:model="form.frame_image_url" placeholder="https://... hoặc /storage/..." class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                    </label>
                    @error('form.frame_image_url') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <label class="block space-y-2">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Mô tả hiển thị trong popup</span>
                    <textarea rows="3" wire:model="form.description" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"></textarea>
                    @error('form.description') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="inline-flex items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                    <input type="checkbox" wire:model="form.is_active" class="rounded border-zinc-300 text-teal-600">
                    Đang bật campaign
                </label>

                <div class="flex flex-wrap gap-2 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                    @can('admin.voucher-campaigns.edit')
                        <button type="submit" class="rounded-2xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white">
                            Lưu campaign
                        </button>

                        @if ($selectedId)
                            <button type="button" wire:click="refreshCodes" class="rounded-2xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm font-semibold text-orange-700">
                                Đổi mới bộ mã
                            </button>

                            <a href="{{ route('admin.voucher-codes') }}" wire:navigate class="rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-700">
                                Quản lý mã đã cấp
                            </a>

                            <button
                                type="button"
                                wire:click="deleteCampaign({{ $selectedId }})"
                                wire:confirm="Bạn có chắc chắn muốn xóa campaign voucher này? Toàn bộ mã voucher thuộc campaign cũng sẽ bị xóa. Hành động này không thể hoàn tác."
                                class="rounded-2xl border border-red-200 px-4 py-3 text-sm font-semibold text-red-600 transition hover:bg-red-50 dark:border-red-500/30 dark:text-red-300 dark:hover:bg-red-500/10"
                            >
                                Xóa campaign
                            </button>
                        @endif
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Bạn chỉ có quyền xem danh sách campaign.</p>
                    @endcan
                </div>
            </div>
        </form>

        @if (false && $selectedCampaign)
            <section class="mt-4 rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Mã voucher đã cấp phát</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            Các mã đã được khách nhận qua form voucher, kèm thông tin lead nếu có.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2 text-sm">
                        <span class="rounded-2xl bg-zinc-100 px-3 py-2 font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            Tổng: {{ number_format((int) $selectedCampaign->codes_count) }}
                        </span>
                        <span class="rounded-2xl bg-orange-50 px-3 py-2 font-semibold text-orange-700 dark:bg-orange-500/10 dark:text-orange-300">
                            Đã phát: {{ number_format((int) $selectedCampaign->claimed_codes_count) }}
                        </span>
                        <span class="rounded-2xl bg-emerald-50 px-3 py-2 font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            Còn lại: {{ number_format((int) $selectedCampaign->available_codes_count) }}
                        </span>
                    </div>
                </div>

                <div class="mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <input type="text" wire:model.live.debounce.300ms="claimedVoucherSearch" placeholder="Tìm mã, khách, SĐT, email..." class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

                    <select wire:model.live="claimedVoucherLeadStatusFilter" class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Tất cả trạng thái lead</option>
                        <option value="new">Lead mới</option>
                        <option value="contacted">Đã liên hệ</option>
                        <option value="closed">Đã đóng</option>
                        <option value="missing">Không còn lead</option>
                    </select>

                    <label class="flex items-center gap-2 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <span class="shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Từ</span>
                        <input type="date" wire:model.live="claimedVoucherClaimedFrom" aria-label="Lọc mã voucher cấp phát từ ngày" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-zinc-700 outline-none focus:ring-0 dark:text-white">
                    </label>

                    <label class="flex items-center gap-2 rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <span class="shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-zinc-500 dark:text-zinc-400">Đến</span>
                        <input type="date" wire:model.live="claimedVoucherClaimedTo" aria-label="Lọc mã voucher cấp phát đến ngày" class="min-w-0 flex-1 border-0 bg-transparent p-0 text-sm text-zinc-700 outline-none focus:ring-0 dark:text-white">
                    </label>

                    @if ($hasClaimedVoucherFilters)
                        <button type="button" wire:click="clearClaimedVoucherFilters" class="rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:text-zinc-200">
                            Xóa lọc
                        </button>
                    @endif
                </div>

                @if ($hasClaimedVoucherFilters)
                    <p class="mb-3 rounded-2xl bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:bg-sky-500/10 dark:text-sky-200">
                        Tìm thấy {{ number_format((int) $filteredClaimedVoucherCodesCount) }} mã phù hợp trên tổng {{ number_format((int) $selectedCampaign->claimed_codes_count) }} mã đã phát.
                    </p>
                @endif

                @if ((int) $filteredClaimedVoucherCodesCount > $claimedVoucherCodes->count())
                    <p class="mb-3 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-200">
                        Đang hiển thị {{ number_format($claimedVoucherCodes->count()) }} mã được cấp phát gần nhất trên tổng {{ number_format((int) $filteredClaimedVoucherCodesCount) }} mã phù hợp.
                    </p>
                @endif

                <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                        <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                            <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                                <th class="px-4 py-3">Mã voucher</th>
                                <th class="px-4 py-3">Khách / lead</th>
                                <th class="px-4 py-3">Ngữ cảnh</th>
                                <th class="px-4 py-3">Ngày cấp phát</th>
                                <th class="px-4 py-3">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                            @forelse ($claimedVoucherCodes as $voucherCode)
                                @php
                                    $inquiry = $voucherCode->inquiry;
                                @endphp
                                <tr wire:key="claimed-voucher-code-{{ $voucherCode->id }}" class="align-top">
                                    <td class="px-4 py-4">
                                        <p class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">{{ $voucherCode->code }}</p>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">ID mã: #{{ $voucherCode->id }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                        @if ($inquiry)
                                            <p class="font-semibold text-zinc-900 dark:text-white">{{ $inquiry->customer_name ?: 'Khách chưa nhập tên' }}</p>
                                            <p class="mt-1">{{ $inquiry->customer_phone ?: 'Chưa có số điện thoại' }}</p>
                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $inquiry->customer_email ?: 'Chưa có email' }}</p>
                                        @else
                                            <p class="text-zinc-500 dark:text-zinc-400">Không còn lead liên kết.</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                        @if ($inquiry)
                                            <p>{{ $inquiry->context_title ?: 'Yêu cầu voucher' }}</p>
                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Lead #{{ $inquiry->id }} · {{ $inquiry->status }}</p>
                                        @else
                                            <p>-</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">
                                        <p>{{ $voucherCode->claimed_at?->format('d/m/Y H:i') ?: '-' }}</p>
                                        @if ($inquiry?->created_at)
                                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Lead: {{ $inquiry->created_at->format('d/m/Y H:i') }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700 dark:bg-orange-500/10 dark:text-orange-300">
                                            Đã phát
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $hasClaimedVoucherFilters ? 'Không có mã voucher nào khớp bộ lọc hiện tại.' : 'Chưa có mã voucher nào được cấp phát trong campaign này.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @can('admin.travel-inquiries.index')
                    <div class="mt-4">
                        <a href="{{ route('admin.travel-inquiries') }}" wire:navigate class="inline-flex rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-teal-300 hover:text-teal-700 dark:border-zinc-700 dark:text-zinc-200">
                            Mở danh sách yêu cầu tư vấn
                        </a>
                    </div>
                @endcan
            </section>
        @endif
        @endif
    </section>
</div>
