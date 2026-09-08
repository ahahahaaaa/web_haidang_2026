<div id="admin-media-browser" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-hidden="true">
    <div data-media-backdrop class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"></div>

    <div class="relative flex min-h-full items-start justify-center p-4 lg:p-8">
        <div class="relative my-4 flex w-full max-w-7xl flex-col overflow-hidden rounded-[2rem] border border-zinc-200 bg-white shadow-2xl lg:my-8 lg:max-h-[calc(100vh-4rem)] dark:border-zinc-800 dark:bg-zinc-900">
            <div class="shrink-0 flex flex-col gap-4 border-b border-zinc-200 px-6 py-5 dark:border-zinc-800 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-red-600">Insert Image</p>
                    <h2 class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">Chọn ảnh từ thư viện media</h2>
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Lọc toàn bộ ảnh của Spatie Media Library rồi chèn trực tiếp vào nội dung Quill.</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <a data-media-manage-link href="#" class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300">
                        <i class="fa-solid fa-photo-film"></i>
                        Quản lý media
                    </a>
                    <button type="button" data-media-close class="inline-flex size-11 items-center justify-center rounded-2xl border border-zinc-200 text-zinc-600 transition hover:border-red-300 hover:text-red-600 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-red-500/40 dark:hover:text-red-300">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>
            </div>

            <div class="grid min-h-0 flex-1 gap-6 overflow-y-auto p-6 lg:grid-cols-[1.15fr_0.85fr]">
                <div class="space-y-5">
                    <div class="grid gap-3 md:grid-cols-[1fr_220px_220px]">
                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">Tìm ảnh</span>
                            <input type="text" data-media-search placeholder="Tên ảnh hoặc file..." class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">Collection</span>
                            <select data-media-collection class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <option value="">Tất cả collection</option>
                            </select>
                        </label>

                        <label class="space-y-2">
                            <span class="text-xs font-semibold uppercase tracking-[0.25em] text-zinc-500 dark:text-zinc-400">Đối tượng</span>
                            <select data-media-model class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                <option value="">Tất cả đối tượng</option>
                            </select>
                        </label>
                    </div>

                    <div data-media-results-summary class="text-sm text-zinc-500 dark:text-zinc-400">Đang tải danh sách ảnh...</div>

                    <div data-media-grid class="grid min-h-[24rem] gap-4 sm:grid-cols-2 xl:grid-cols-3"></div>

                    <div class="flex flex-col gap-3 border-t border-zinc-200 pt-4 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                        <p data-media-pagination class="text-sm text-zinc-500 dark:text-zinc-400"></p>

                        <div class="flex items-center gap-3">
                            <button type="button" data-media-prev class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-200">
                                <i class="fa-solid fa-arrow-left"></i>
                                Trước
                            </button>
                            <button type="button" data-media-next class="inline-flex items-center gap-2 rounded-2xl border border-zinc-200 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-50 dark:border-zinc-700 dark:text-zinc-200">
                                Sau
                                <i class="fa-solid fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <aside class="rounded-[1.75rem] border border-zinc-200 bg-zinc-50/70 p-5 dark:border-zinc-800 dark:bg-zinc-950/40 lg:max-h-full lg:overflow-y-auto">
                    <div class="space-y-5">
                        <section class="rounded-[1.5rem] border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="space-y-2">
                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-red-600">Upload nhanh</p>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">Tải ảnh mới ngay trong popup</h3>
                                <p class="text-sm leading-6 text-zinc-500 dark:text-zinc-400">Ảnh mới sẽ được lưu vào collection `library` để dùng lại ở mọi editor và trang Media.</p>
                            </div>

                            <div class="mt-4 space-y-4">
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Chọn file ảnh</span>
                                    <input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-media-upload-file class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 outline-none transition file:mr-4 file:rounded-xl file:border-0 file:bg-red-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-red-500 focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white dark:file:bg-red-500 dark:hover:file:bg-red-400">
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tên media</span>
                                    <input type="text" data-media-upload-name placeholder="Tên hiển thị trong thư viện" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>

                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt text mặc định</span>
                                    <input type="text" data-media-upload-alt placeholder="Mô tả ngắn cho ảnh" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-red-400 focus:bg-white dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                                </label>

                                <p data-media-upload-status class="admin-media-status hidden"></p>

                                <button type="button" data-media-upload class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600 disabled:cursor-not-allowed disabled:opacity-70">
                                    <i class="fa-solid fa-cloud-arrow-up"></i>
                                    <span data-media-upload-label>Tải ảnh lên thư viện</span>
                                </button>
                            </div>
                        </section>

                        <div data-media-empty class="flex min-h-[22rem] flex-col items-center justify-center gap-4 text-center text-zinc-500 dark:text-zinc-400">
                            <div class="flex size-16 items-center justify-center rounded-3xl bg-white text-zinc-400 shadow-sm ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-700">
                                <i class="fa-regular fa-image text-2xl"></i>
                            </div>
                            <div class="space-y-2">
                                <p class="text-lg font-semibold text-zinc-900 dark:text-white">Chưa chọn ảnh</p>
                                <p class="text-sm leading-7">Chọn một ảnh bên trái để điền `alt`, `class`, `style` rồi chèn vào editor.</p>
                            </div>
                        </div>

                        <div data-media-panel class="hidden space-y-5">
                            <div class="overflow-hidden rounded-[1.5rem] border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                                <img data-media-preview-image src="" alt="" class="h-72 w-full object-cover">
                            </div>

                            <div class="space-y-2">
                                <p data-media-preview-name class="text-lg font-semibold text-zinc-900 dark:text-white"></p>
                                <p data-media-preview-meta class="text-sm text-zinc-500 dark:text-zinc-400"></p>
                            </div>

                            <div class="space-y-4">
                                <label class="space-y-2">
                                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Alt text</span>
                                    <input type="text" data-media-alt class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                </label>

                                <div data-media-editor-fields class="space-y-4">
                                    <label class="space-y-2">
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">CSS class</span>
                                        <input type="text" data-media-class placeholder="rounded-3xl shadow-lg" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
                                    </label>

                                    <label class="space-y-2">
                                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Inline style</span>
                                        <textarea rows="4" data-media-style placeholder="max-width: 720px; margin: 0 auto 1.5rem;" class="w-full rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-900 outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                                    </label>

                                    <p class="rounded-2xl bg-white px-4 py-3 text-xs leading-6 text-zinc-500 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-400 dark:ring-zinc-700">
                                        Mẹo: `class` nên dùng các utility đang có trong theme. `style` chỉ nên dùng cho kích thước/căn lề ảnh nội dung.
                                    </p>
                                </div>
                            </div>

                            <button type="button" data-media-insert class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600">
                                <i class="fa-solid fa-image"></i>
                                <span data-media-insert-text>Chèn ảnh vào editor</span>
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>
