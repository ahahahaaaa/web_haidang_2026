@php
    $typeLabels = \Src\Domains\Seo\Enums\SeoPageType::labels();
    $statusLabels = [
        'draft' => 'Nháp',
        'generated' => 'Đã sinh nháp',
        'qa_failed' => 'QA lỗi',
        'pending_review' => 'Chờ duyệt',
        'approved' => 'Đã duyệt',
        'published' => 'Đã xuất bản',
        'archived' => 'Lưu trữ',
    ];

    $statusClasses = [
        'draft' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
        'generated' => 'bg-sky-50 text-sky-700 dark:bg-sky-500/10 dark:text-sky-300',
        'qa_failed' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300',
        'pending_review' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        'approved' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
        'published' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        'archived' => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400',
    ];
@endphp

<div class="space-y-6">
    <x-admin.form-feedback :status="session('status')" />

    <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <form wire:submit="save" class="grid gap-6 pb-32 md:pb-6 xl:grid-cols-[1.2fr_0.8fr]" data-admin-feedback-form data-admin-loading-text="Đang lưu bản nháp SEO...">
            <div class="space-y-5">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-full bg-white px-3 py-1 font-semibold text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:ring-zinc-700">#{{ $page->id }}</span>
                    <span class="rounded-full px-3 py-1 font-semibold {{ $statusClasses[$page->status->value] ?? 'bg-zinc-100 text-zinc-700' }}">
                        {{ $statusLabels[$page->status->value] ?? $page->status->value }}
                    </span>
                    <span class="rounded-full bg-red-50 px-3 py-1 font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">
                        {{ $typeLabels[$page->page_type->value] ?? $page->page_type->value }}
                    </span>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tiêu đề trang</label>
                        <input type="text" wire:model.defer="page.title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Tên nội bộ hoặc tiêu đề trang để quản lý và hiển thị nội dung chính.</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Slug</label>
                        <input type="text" wire:model.defer="page.slug" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Có thể để trống để hệ thống tự sinh lại slug từ tiêu đề, H1 hoặc keyword chính.</p>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">H1</label>
                        <input type="text" wire:model.defer="page.h1" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">H1 là tiêu đề chính hiển thị cho người đọc. Nên rõ intent, tự nhiên và chỉ có một H1 cho mỗi trang.</p>
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Nội dung</label>
                        <x-admin.quill-editor wire:key="seo-page-content-{{ $page->id }}-{{ md5((string) ($page->content ?? '')) }}" model="page.content" :value="$page->content ?? ''" mode="rich" :allow-images="true" rows="16" placeholder="Nội dung SEO, outline, internal link, CTA và hình minh họa." />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Ưu tiên cấu trúc rõ ràng: mở bài, phần giải thích chính, FAQ/CTA và liên kết nội bộ liên quan.</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Meta title</label>
                        <input type="text" wire:model.defer="page.meta_title" class="w-full rounded-2xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm outline-none transition focus:border-red-400 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Mục tiêu khoảng 50-60 ký tự, ưu tiên đưa keyword chính lên đầu nếu tự nhiên.</p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Meta description</label>
                        <x-admin.quill-editor wire:key="seo-page-meta-description-{{ $page->id }}-{{ md5((string) ($page->meta_description ?? '')) }}" model="page.meta_description" :value="$page->meta_description ?? ''" rows="4" placeholder="Mô tả ngắn gọn cho thẻ SEO." />
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Nên khoảng 140-160 ký tự, nêu đúng chủ đề trang, lợi ích và lời kêu gọi hành động ngắn.</p>
                    </div>
                </div>

            </div>

            <aside class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/70">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Hướng dẫn nhập liệu</h2>
                    <div class="mt-3 space-y-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        <div>
                            <p class="font-semibold text-zinc-900 dark:text-white">Từ khóa và intent</p>
                            <p>Giữ một chủ đề chính cho mỗi page. Nếu intent khác nhau nhiều, nên tách thành page mới để tránh cannibalization.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-zinc-900 dark:text-white">Nội dung do AI hỗ trợ, không thay người kiểm duyệt</p>
                            <p>AI giúp lên nháp nhanh, nhưng cần sửa lại giọng điệu, CTA, điểm đến, travel signals và internal link theo đúng thực tế website du lịch.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-zinc-900 dark:text-white">Không nhập dữ liệu chưa xác thực</p>
                            <p>Tránh thêm lịch khởi hành, giá, ưu đãi, visa guarantee, địa chỉ đối tác hoặc thông tin vận hành nếu chưa được xác minh.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Quy trình xử lý đề xuất</h3>
                    <div class="mt-3 space-y-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        <p><span class="font-semibold text-zinc-900 dark:text-white">1.</span> Kiểm tra slug, H1, nội dung và meta trước khi lưu.</p>
                        <p><span class="font-semibold text-zinc-900 dark:text-white">2.</span> Bấm <span class="font-medium text-zinc-900 dark:text-white">Tạo lại bằng AI</span> nếu cần cập nhật nháp.</p>
                        <p><span class="font-semibold text-zinc-900 dark:text-white">3.</span> Bấm <span class="font-medium text-zinc-900 dark:text-white">Chạy QA</span> để kiểm tra điều kiện tối thiểu về nội dung và metadata.</p>
                        <p><span class="font-semibold text-zinc-900 dark:text-white">4.</span> Khi QA ổn, chuyển <span class="font-medium text-zinc-900 dark:text-white">Phê duyệt</span> rồi mới <span class="font-medium text-zinc-900 dark:text-white">Xuất bản</span>.</p>
                    </div>
                </div>

                <div>
                    <h3 class="mb-2 text-sm font-semibold text-zinc-900 dark:text-white">QA Report</h3>
                    <pre class="overflow-auto rounded-2xl border border-zinc-200 bg-white p-4 text-xs text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ json_encode($page->qa_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </aside>
            <div class="xl:col-span-2">
                <x-admin.form-action-bar>
                    <button type="button" wire:click="regenerate" wire:loading.attr="disabled" wire:target="regenerate" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-70 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300">
                        <i class="fa-solid fa-rotate" wire:loading.remove wire:target="regenerate"></i>
                        <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="regenerate"></i>
                        Tạo lại bằng AI
                    </button>
                    <button type="button" wire:click="runQa" wire:loading.attr="disabled" wire:target="runQa" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-70 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300">
                        <i class="fa-solid fa-shield-check" wire:loading.remove wire:target="runQa"></i>
                        <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="runQa"></i>
                        Chạy QA
                    </button>
                    <button type="button" wire:click="approve" wire:loading.attr="disabled" wire:target="approve" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-zinc-200 px-4 py-3 text-sm font-medium text-zinc-700 transition hover:border-red-300 hover:text-red-600 disabled:cursor-not-allowed disabled:opacity-70 dark:border-zinc-700 dark:text-zinc-200 dark:hover:border-red-500/40 dark:hover:text-red-300">
                        <i class="fa-solid fa-circle-check" wire:loading.remove wire:target="approve"></i>
                        <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="approve"></i>
                        Phê duyệt
                    </button>
                    <button type="button" wire:click="publish" wire:loading.attr="disabled" wire:target="publish" class="inline-flex items-center justify-center gap-2 rounded-2xl border border-emerald-200 px-4 py-3 text-sm font-medium text-emerald-700 transition hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-70 dark:border-emerald-500/30 dark:text-emerald-300 dark:hover:bg-emerald-500/10">
                        <i class="fa-solid fa-upload" wire:loading.remove wire:target="publish"></i>
                        <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="publish"></i>
                        Xuất bản
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-red-600 to-red-700 px-5 py-3 text-sm font-semibold text-white transition hover:from-red-500 hover:to-red-600 disabled:cursor-not-allowed disabled:opacity-70">
                        <i class="fa-solid fa-floppy-disk" wire:loading.remove wire:target="save"></i>
                        <i class="fa-solid fa-spinner animate-spin" wire:loading wire:target="save"></i>
                        Lưu
                    </button>
                </x-admin.form-action-bar>
            </div>
        </form>
    </section>
</div>
