<div class="space-y-4">
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">Danh sách tour</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý tour theo scope travel, category, điểm đến và lịch khởi hành thực tế.</p>
        </div>

        <a href="{{ route('admin.tours.create') }}" wire:navigate class="inline-flex items-center gap-2 rounded-2xl bg-zinc-900 px-4 py-3 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
            <i class="fa-solid fa-plus"></i>
            Tour mới
        </a>
    </div>

    @include('livewire.admin.cms.partials.tours-submenu')

    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-[28px] border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 grid gap-3 xl:grid-cols-6">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Tìm theo tên tour..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">

            <select wire:model.live="categoryFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả danh mục</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="destinationFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả điểm đến</option>
                @include('livewire.admin.cms.partials.destination-country-option-groups', ['destinations' => $destinations])
            </select>

            @if (! auth()->user()?->hasRole('sale'))
                <select wire:model.live="managerFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    <option value="">Tất cả người phụ trách</option>
                    <option value="0">Chưa gán phụ trách</option>
                    @foreach ($saleUsers as $saleUser)
                        <option value="{{ $saleUser->id }}">{{ $saleUser->name }}</option>
                    @endforeach
                </select>
            @endif

            <select wire:model.live="scopeFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả phạm vi</option>
                @foreach ($scopeOptions as $scopeOption)
                    <option value="{{ $scopeOption->value }}">{{ $scopeOption->label() }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                <option value="">Tất cả trạng thái</option>
                <option value="draft">draft</option>
                <option value="published">published</option>
                <option value="archived">archived</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-[24px] border border-zinc-200/80 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
                <thead class="bg-zinc-50/80 dark:bg-zinc-950/60">
                    <tr class="text-left text-[11px] uppercase tracking-[0.24em] text-zinc-500 dark:text-zinc-400">
                        <th class="px-4 py-3">Tour</th>
                        <th class="px-4 py-3">Slug</th>
                        <th class="px-4 py-3">Danh mục</th>
                        <th class="px-4 py-3">Điểm đến</th>
                        <th class="px-4 py-3">Phạm vi</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3">Giá / khởi hành</th>
                        <th class="px-4 py-3 text-right">Hành động</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                    @forelse ($tours as $tour)
                        <tr wire:key="tour-{{ $tour->id }}" class="align-top">
                            <td class="px-4 py-4">
                                <a href="{{ route('tours.show', $tour) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 font-semibold text-zinc-900 transition hover:text-red-600 dark:text-white" title="Mở tour trên frontsite">
                                    <span>{{ $tour->title }}</span>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-zinc-400"></i>
                                </a>
                                <div class="mt-1 space-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    <p>Khởi hành mặc định: {{ $tour->departure_location ?: 'Chưa cập nhật' }}</p>
                                    <p>Liên hệ: {{ $tour->contact_phone ?: 'Chưa cập nhật' }}</p>
                                    @if ($tour->transport)
                                        <p>Phương tiện: {{ $tour->transport }}</p>
                                    @endif
                                    @if ($tour->manager)
                                        <p>Sale phụ trách: {{ $tour->manager->name }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="block max-w-52 break-all font-mono text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $tour->slug ?: 'Chưa có' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $tour->primaryCategory?->name ?: 'Chưa gán' }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $tour->destination?->name ?: 'Chưa gán' }}</td>
                            <td class="px-4 py-4 text-zinc-600 dark:text-zinc-300">{{ $tour->scope?->label() ?: 'Chưa gán' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $tour->status === 'published' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' : ($tour->status === 'archived' ? 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300') }}">
                                    {{ $tour->status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-zinc-500 dark:text-zinc-400">
                                <p>{{ $tour->sale_price ? number_format($tour->sale_price, 0, ',', '.') . ' đ' : 'Liên hệ' }}</p>
                                <p class="mt-1 text-xs">Departure: {{ $tour->departures_count }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <button
                                        type="button"
                                        wire:click="openTourSyncPicker({{ $tour->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="openTourSyncPicker({{ $tour->id }})"
                                        class="rounded-2xl border border-sky-200 px-3 py-2 text-sm font-medium text-sky-700 transition hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-sky-500/30 dark:text-sky-300 dark:hover:bg-sky-500/10"
                                    >
                                        <span wire:loading.remove wire:target="openTourSyncPicker({{ $tour->id }})">Chọn tour đồng bộ</span>
                                        <span wire:loading wire:target="openTourSyncPicker({{ $tour->id }})">Đang tải...</span>
                                    </button>
                                    @if (config('travel_reviews.enabled', true))
                                        <a href="{{ route('admin.tours.reviews.index', $tour) }}" wire:navigate class="rounded-2xl border border-amber-200 px-3 py-2 text-sm font-medium text-amber-700 dark:border-amber-500/30 dark:text-amber-300">
                                            Quản lý đánh giá
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.tours.edit', $tour) }}" wire:navigate class="rounded-2xl border border-zinc-200 px-3 py-2 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                                        Sửa
                                    </a>
                                    <button type="button" wire:click="deleteTour({{ $tour->id }})" wire:confirm="Bạn có chắc chắn muốn xóa tour này? Hành động này không thể hoàn tác." class="rounded-2xl border border-red-200 px-3 py-2 text-sm font-medium text-red-600 dark:border-red-500/30 dark:text-red-300">
                                        Xóa
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">Chưa có tour nào khớp bộ lọc hiện tại.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $tours->links() }}
        </div>
    </section>

    @if ($syncTourPickerOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/70 p-3 sm:p-4">
            <div class="flex h-[min(92vh,900px)] max-h-[calc(100vh-1.5rem)] w-full max-w-6xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-zinc-900 sm:max-h-[calc(100vh-2rem)]">
                <div class="shrink-0 flex items-start justify-between gap-4 border-b border-zinc-200 px-5 py-4 dark:border-zinc-800">
                    <div>
                        <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Chọn hướng đồng bộ tour</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Tour CMS: {{ $syncTourTargetTitle ?: 'Chưa chọn' }}</p>
                    </div>
                    <button type="button" wire:click="closeTourSyncPicker" class="rounded-full border border-zinc-200 px-3 py-1 text-lg leading-none text-zinc-500 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800">
                        ×
                    </button>
                </div>

                <div class="flex min-h-0 flex-1 flex-col gap-4 overflow-hidden px-5 py-4">
                    @if ($syncSourceError)
                        <div class="rounded-2xl bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:bg-red-500/10 dark:text-red-300">
                            {{ $syncSourceError }}
                        </div>
                    @endif

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="flex cursor-pointer gap-3 rounded-2xl border p-4 transition {{ $syncTourDirection === 'cms_to_agency' ? 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-500/50 dark:bg-emerald-500/10 dark:text-emerald-100' : 'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800' }}">
                            <input type="radio" wire:model.live="syncTourDirection" value="cms_to_agency" class="mt-1 h-4 w-4 text-emerald-600">
                            <span>
                                <span class="block text-sm font-semibold">CMS HaidangTravel -> API Master Data DashBoard</span>
                                <span class="mt-1 block text-xs opacity-80">Chọn tour đích API Master Data DashBoard rồi gửi thông tin tour, ngày khởi hành và sale phụ trách từ CMS sang.</span>
                            </span>
                        </label>

                        <label class="flex cursor-pointer gap-3 rounded-2xl border p-4 transition {{ $syncTourDirection === 'agency_to_cms' ? 'border-sky-300 bg-sky-50 text-sky-900 dark:border-sky-500/50 dark:bg-sky-500/10 dark:text-sky-100' : 'border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-800' }}">
                            <input type="radio" wire:model.live="syncTourDirection" value="agency_to_cms" class="mt-1 h-4 w-4 text-sky-600">
                            <span>
                                <span class="block text-sm font-semibold">API Master Data DashBoard -> CMS HaidangTravel</span>
                                <span class="mt-1 block text-xs opacity-80">Chọn tour nguồn API Master Data DashBoard để kéo tên tour và ngày khởi hành về CMS.</span>
                            </span>
                        </label>
                    </div>

                    <div class="grid gap-3 xl:grid-cols-[1fr_180px_150px_auto] xl:items-center">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="syncSourceSearch"
                            placeholder="{{ $syncTourDirection === 'cms_to_agency' ? 'Tìm tour đích API Master Data DashBoard theo ID, mã tour, tên tour...' : 'Tìm tour nguồn API Master Data DashBoard theo ID, mã tour, tên tour...' }}"
                            class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
                        >
                        <select wire:model.live="syncSourceStatusFilter" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            <option value="">Tất cả trạng thái</option>
                            @foreach ($syncSourceTourStatusOptions as $statusOption)
                                <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                            @endforeach
                        </select>
                        <select wire:model.live="syncSourcePerPage" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                            <option value="10">10 / trang</option>
                            <option value="20">20 / trang</option>
                            <option value="50">50 / trang</option>
                        </select>
                        <button
                            type="button"
                            wire:click="refreshTourSyncSourceCatalog"
                            wire:loading.attr="disabled"
                            wire:target="refreshTourSyncSourceCatalog"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                        >
                            <span wire:loading.remove wire:target="refreshTourSyncSourceCatalog">Xóa cache & tải lại</span>
                            <span wire:loading wire:target="refreshTourSyncSourceCatalog">Đang tải lại...</span>
                        </button>
                    </div>

                    <div class="flex flex-col gap-2 text-xs text-zinc-500 sm:flex-row sm:items-center sm:justify-between dark:text-zinc-400">
                        <span>
                            Hiển thị {{ $syncSourcePagination['from'] }}-{{ $syncSourcePagination['to'] }} trong {{ $syncSourcePagination['total'] }} {{ $syncTourDirection === 'cms_to_agency' ? 'tour đích API Master Data DashBoard' : 'tour nguồn API Master Data DashBoard' }}
                        </span>
                        <span>Trang {{ $syncSourcePagination['current_page'] }} / {{ $syncSourcePagination['last_page'] }}</span>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto rounded-2xl border border-zinc-200 dark:border-zinc-800">
                        @forelse ($syncSourceTourOptions as $sourceTour)
                            <label wire:key="sync-source-tour-{{ $sourceTour['tour_id'] }}" class="flex cursor-pointer gap-3 border-b border-zinc-100 px-4 py-3 transition last:border-b-0 hover:bg-sky-50/70 dark:border-zinc-800 dark:hover:bg-sky-500/10">
                                <input type="radio" wire:model.live="syncSourceTourId" value="{{ $sourceTour['tour_id'] }}" class="mt-1 h-4 w-4 text-sky-600">
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold text-zinc-900 dark:text-white">{{ $sourceTour['title'] ?: 'Chưa có tên tour' }}</span>
                                    <span class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        <span>ID: {{ $sourceTour['tour_id'] }}</span>
                                        <span>Mã: {{ $sourceTour['tour_code'] ?: 'Chưa có' }}</span>
                                        <span>Slug: {{ $sourceTour['slug'] ?: 'Chưa có' }}</span>
                                        @if ($sourceTour['status_label'] || $sourceTour['status'])
                                            <span>Trạng thái: {{ $sourceTour['status_label'] ?: $sourceTour['status'] }}</span>
                                        @endif
                                    </span>
                                </span>
                            </label>
                        @empty
                            <div class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                Không có tour nguồn nào khớp bộ lọc hiện tại.
                            </div>
                        @endforelse
                    </div>

                    @if ($syncSourcePagination['last_page'] > 1)
                        <div class="shrink-0 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <button
                                type="button"
                                wire:click="previousSyncSourcePage"
                                @disabled($syncSourcePagination['current_page'] <= 1)
                                class="rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                Trang trước
                            </button>
                            <div class="flex flex-wrap justify-center gap-1">
                                @for ($page = max(1, $syncSourcePagination['current_page'] - 2); $page <= min($syncSourcePagination['last_page'], $syncSourcePagination['current_page'] + 2); $page++)
                                    <button
                                        type="button"
                                        wire:click="goToSyncSourcePage({{ $page }})"
                                        class="h-9 min-w-9 rounded-xl px-3 text-sm font-semibold transition {{ $page === $syncSourcePagination['current_page'] ? 'bg-sky-600 text-white' : 'border border-zinc-200 text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800' }}"
                                    >
                                        {{ $page }}
                                    </button>
                                @endfor
                            </div>
                            <button
                                type="button"
                                wire:click="nextSyncSourcePage"
                                @disabled($syncSourcePagination['current_page'] >= $syncSourcePagination['last_page'])
                                class="rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                            >
                                Trang sau
                            </button>
                        </div>
                    @endif
                </div>

                <div class="shrink-0 flex flex-col-reverse gap-2 border-t border-zinc-200 px-5 py-4 sm:flex-row sm:justify-end dark:border-zinc-800">
                    <button type="button" wire:click="closeTourSyncPicker" class="rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        Hủy
                    </button>
                    <button
                        type="button"
                        wire:key="tour-sync-action-cms-to-agency"
                        wire:click="pushSelectedTourToAgency"
                        wire:loading.attr="disabled"
                        wire:target="syncTourDirection,pushSelectedTourToAgency,syncSelectedTourFromWeb"
                        @disabled($syncSourceTourId === '')
                        class="rounded-2xl px-4 py-3 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60 {{ $syncTourDirection === 'cms_to_agency' ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'border border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-500/30 dark:text-emerald-300 dark:hover:bg-emerald-500/10' }}"
                    >
                        <span wire:loading.remove wire:target="pushSelectedTourToAgency">Gửi CMS sang API Master Data DashBoard</span>
                        <span wire:loading wire:target="pushSelectedTourToAgency">Đang đưa vào hàng đợi...</span>
                    </button>
                    <button
                        type="button"
                        wire:key="tour-sync-action-agency-to-cms"
                        wire:click="syncSelectedTourFromWeb"
                        wire:loading.attr="disabled"
                        wire:target="syncTourDirection,syncSelectedTourFromWeb,pushSelectedTourToAgency"
                        @disabled($syncSourceTourId === '')
                        class="rounded-2xl px-4 py-3 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60 {{ $syncTourDirection === 'agency_to_cms' ? 'bg-sky-600 text-white hover:bg-sky-700' : 'border border-sky-200 text-sky-700 hover:bg-sky-50 dark:border-sky-500/30 dark:text-sky-300 dark:hover:bg-sky-500/10' }}"
                    >
                        <span wire:loading.remove wire:target="syncSelectedTourFromWeb">Kéo API Master Data DashBoard về CMS</span>
                        <span wire:loading wire:target="syncSelectedTourFromWeb">Đang đồng bộ...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
