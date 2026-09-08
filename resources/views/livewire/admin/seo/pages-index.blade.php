<div class="space-y-6">
    @if (session('status'))
        <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Tạo mới SEO Page</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Bạn có thể đi theo 2 hướng: tạo page thủ công để vào biên tập ngay, hoặc tạo cluster để AI sinh brief, draft và QA theo chuỗi queue cho travel hub pages.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-red-700 dark:bg-red-500/10 dark:text-red-300">
                    Hai hướng tạo mới
                </div>
                <button
                    type="button"
                    wire:click="seedDemoPages"
                    class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                >
                    <i class="fa-solid fa-layer-group"></i>
                    Nạp demo travel pages
                </button>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <form wire:submit="createManualPage" class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/70">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">1. Tạo SEO page thủ công</h3>
                    <p class="mt-1 text-sm leading-6 text-zinc-600 dark:text-zinc-300">Phù hợp khi bạn đã rõ keyword và muốn có page nháp ngay để chỉnh `slug`, `H1`, nội dung, metadata và internal linking theo ngữ cảnh du lịch.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Từ khóa chính</label>
                        <input type="text" wire:model.defer="manualForm.primary_keyword" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        @error('manualForm.primary_keyword') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Loại trang</label>
                        <select wire:model.defer="manualForm.page_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            @foreach ($pageTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('manualForm.page_type') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề trang</label>
                        <input type="text" wire:model.defer="manualForm.title" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        @error('manualForm.title') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug tùy chọn</label>
                        <input type="text" wire:model.defer="manualForm.slug" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Để trống nếu muốn hệ thống tự sinh slug từ keyword hoặc tiêu đề, rồi map sang URL family phù hợp như điểm đến hoặc danh mục tour.</p>
                        @error('manualForm.slug') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">H1 tùy chọn</label>
                        <input type="text" wire:model.defer="manualForm.h1" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Để trống nếu muốn H1 mặc định theo tiêu đề. Với điểm đến hoặc vùng miền, nên nhắc rõ địa danh trong H1.</p>
                        @error('manualForm.h1') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Từ khóa phụ</label>
                        <textarea wire:model.defer="manualForm.secondary_keywords_text" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Nhập mỗi dòng một từ khóa hoặc phân tách bằng dấu phẩy.</p>
                        @error('manualForm.secondary_keywords_text') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                    <input type="checkbox" wire:model.defer="manualForm.generate_after_create" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                    Tạo xong thì đưa ngay tác vụ generate AI vào hàng đợi
                </label>

                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600">
                    <i class="fa-solid fa-file-circle-plus"></i>
                    Tạo SEO page
                </button>
            </form>

            <form wire:submit="createCluster" class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/70">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">2. Wizard tạo cluster SEO</h3>
                    <p class="mt-1 text-sm leading-6 text-zinc-600 dark:text-zinc-300">Phù hợp khi muốn đi đúng quy trình AI: cluster → brief → draft → internal link → QA. Cách này hợp với các travel hub như danh mục tour, điểm đến, vùng miền và quốc gia.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tên cluster</label>
                        <input type="text" wire:model.defer="clusterForm.name" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        @error('clusterForm.name') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Từ khóa chính</label>
                        <input type="text" wire:model.defer="clusterForm.primary_keyword" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        @error('clusterForm.primary_keyword') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Loại trang mục tiêu</label>
                        <select wire:model.defer="clusterForm.target_page_type" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                            @foreach ($pageTypeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('clusterForm.target_page_type') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Intent</label>
                        <input type="text" wire:model.defer="clusterForm.intent" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Ví dụ: commercial, informational, local.</p>
                        @error('clusterForm.intent') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Từ khóa phụ</label>
                        <textarea wire:model.defer="clusterForm.secondary_keywords_text" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                        @error('clusterForm.secondary_keywords_text') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">LSI / semantic keywords</label>
                        <textarea wire:model.defer="clusterForm.lsi_keywords_text" rows="3" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                        @error('clusterForm.lsi_keywords_text') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">CTA chính</label>
                        <input type="text" wire:model.defer="clusterForm.cta" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        @error('clusterForm.cta') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Địa danh / ngữ cảnh địa lý</label>
                        <input type="text" wire:model.defer="clusterForm.location" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                        @error('clusterForm.location') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Ghi chú ngữ cảnh cho AI</label>
                        <textarea wire:model.defer="clusterForm.context_notes" rows="4" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Gợi ý cho AI về điểm đến, lịch trình, giọng điệu, CTA chính hoặc điều cần tránh khi viết nội dung travel.</p>
                        @error('clusterForm.context_notes') <p class="text-sm text-red-600 dark:text-red-300">{{ $message }}</p> @enderror
                    </div>
                </div>

                <label class="flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                    <input type="checkbox" wire:model.defer="clusterForm.dispatch_generation" class="rounded border-zinc-300 text-red-600 focus:ring-red-500">
                    Tạo xong thì dispatch generate brief/draft ngay
                </label>

                <button type="submit" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 bg-white px-5 py-3 text-sm font-semibold text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                    Tạo cluster SEO
                </button>
            </form>
        </div>
    </section>

    <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">Danh sách SEO Pages</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Theo dõi keyword, loại trang, trạng thái QA và đường dẫn slug trước khi vào trang biên tập chi tiết.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                {{ $pages->total() }} trang
            </div>
        </div>

        <div class="mb-5 grid gap-3 md:grid-cols-[1fr_220px]">
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Tìm theo từ khóa chính..."
                class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
            >
            <select
                wire:model.live="status"
                class="rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white"
            >
                <option value="">Tất cả trạng thái</option>
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="space-y-3">
            @forelse ($pages as $page)
                <div wire:key="seo-page-{{ $page->id }}" class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 transition hover:border-red-300 dark:border-zinc-700 dark:bg-zinc-800/70">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-3">
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span class="rounded-full bg-white px-3 py-1 font-semibold text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">#{{ $page->id }}</span>
                                <span class="rounded-full px-3 py-1 font-semibold {{ $statusClasses[$page->status->value] ?? 'bg-zinc-100 text-zinc-700' }}">
                                    {{ $statusLabels[$page->status->value] ?? $page->status->value }}
                                </span>
                                <span class="rounded-full bg-red-50 px-3 py-1 font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">
                                    {{ $pageTypeOptions[$page->page_type->value] ?? $page->page_type->value }}
                                </span>
                            </div>

                            <div>
                                <a href="{{ route('admin.seo.pages.edit', $page) }}" wire:navigate class="text-base font-semibold text-zinc-900 transition hover:text-red-600 dark:text-white">
                                    {{ $page->primary_keyword ?: 'Chưa có từ khóa chính' }}
                                </a>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $page->title ?: 'Chưa có tiêu đề trang SEO.' }}
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-x-5 gap-y-2 text-sm text-zinc-500 dark:text-zinc-400">
                                <span><span class="font-medium text-zinc-700 dark:text-zinc-200">Slug:</span> {{ $page->slug ?: 'Chưa có slug' }}</span>
                                <span><span class="font-medium text-zinc-700 dark:text-zinc-200">H1:</span> {{ $page->h1 ?: 'Chưa có H1' }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a
                                href="{{ route('admin.seo.pages.preview', $page) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                            >
                                Xem trước
                            </a>
                            @if ($page->status->value === 'published')
                                <a
                                    href="{{ $page->canonical_url ?: route('seo-pages.show', $page->slug) }}"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-emerald-200 px-4 py-2 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50 dark:border-emerald-500/30 dark:text-emerald-300 dark:hover:bg-emerald-500/10"
                                >
                                    Mở live
                                </a>
                            @endif
                            <a
                                href="{{ route('admin.seo.pages.edit', $page) }}"
                                wire:navigate
                                class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300"
                            >
                                Mở biên tập
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-3xl border border-dashed border-zinc-300 px-6 py-10 text-center text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Chưa có SEO page nào khớp bộ lọc hiện tại.
                </div>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $pages->links() }}
        </div>
    </section>
</div>
