<x-layouts::app :title="'SEO Pages'">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-semibold text-zinc-900 dark:text-white">SEO Pages</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Quản lý vòng đời trang SEO AI từ tạo page thủ công hoặc wizard cluster, qua bản nháp AI, QA, phê duyệt rồi mới xuất bản.</p>
            </div>
        </div>

        <section class="rounded-3xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="space-y-4">
                <div class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-red-700 dark:bg-red-500/10 dark:text-red-300">
                    SEO AI workflow
                </div>

                <div>
                    <h2 class="text-2xl font-semibold text-zinc-900 dark:text-white">SEO Pages là kho travel landing page được AI hỗ trợ biên tập, nay hỗ trợ cả tạo page nhanh và tạo cluster chuẩn workflow.</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                        Mỗi bản ghi đại diện cho một travel SEO page theo từ khóa chính, loại trang, nội dung, metadata và báo cáo QA. Hệ thống chạy theo quy trình queue-first:
                        tạo nháp bằng AI, kiểm tra chất lượng, chuyển chờ duyệt, sau đó mới xuất bản.
                    </p>
                </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/70">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Tính năng chính</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                Sinh bản nháp SEO, quản lý H1 và slug, tối ưu meta title/meta description, dựng schema theo page type du lịch và dispatch publish theo queue riêng.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/70">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Khi nào nên dùng</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                Dùng cho danh mục tour, điểm đến, vùng miền, quốc gia, dịch vụ, blog hoặc contact page khi cần mở rộng topical coverage mà vẫn giữ chuẩn review thủ công.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/70">
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Lưu ý vận hành</h3>
                            <p class="mt-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                Không publish trực tiếp khi chưa QA. Không đưa vào nội dung các thông tin chưa xác thực như khuyến mãi, lịch khởi hành, giá, visa guarantee hoặc dữ liệu vận hành không có cơ sở.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 rounded-3xl border border-zinc-200 bg-zinc-50 p-5 dark:border-zinc-700 dark:bg-zinc-800/70">
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-white">Hướng dẫn sử dụng nhanh</h2>
                        <div class="mt-3 space-y-3 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-white">1. Chọn đúng trang cần xử lý</p>
                                <p>Dùng ô tìm kiếm theo từ khóa chính và lọc theo trạng thái để tìm page cần biên tập hoặc QA.</p>
                            </div>
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-white">2. Hoàn thiện dữ liệu đầu vào</p>
                                <p>Kiểm tra title, slug, H1, nội dung chính và metadata. Đây là phần AI và QA sẽ dựa vào để đánh giá chất lượng.</p>
                            </div>
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-white">3. Chạy lại AI hoặc QA khi cần</p>
                                <p>Dùng <span class="font-medium text-zinc-900 dark:text-white">Regenerate</span> để tạo lại bản nháp, <span class="font-medium text-zinc-900 dark:text-white">Run QA</span> để kiểm tra schema, metadata, word count và internal link trước duyệt.</p>
                            </div>
                            <div>
                                <p class="font-semibold text-zinc-900 dark:text-white">4. Duyệt và publish</p>
                                <p>Khi page đạt yêu cầu, chuyển trạng thái phê duyệt rồi dispatch publish. Quy trình chuẩn là <span class="font-medium text-zinc-900 dark:text-white">Generated/QA Failed → Pending review → Approved → Published</span>.</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Dữ liệu nên chuẩn bị trước khi nhập</h3>
                        <div class="mt-3 space-y-2 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                            <p><span class="font-semibold text-zinc-900 dark:text-white">Từ khóa chính:</span> một intent rõ ràng, không nhồi nhiều chủ đề trong cùng một page.</p>
                            <p><span class="font-semibold text-zinc-900 dark:text-white">Slug:</span> ngắn, không dấu, dùng dấu gạch ngang, sát với keyword chính.</p>
                            <p><span class="font-semibold text-zinc-900 dark:text-white">H1:</span> tiêu đề hiển thị cho người đọc, nên khớp intent tìm kiếm nhưng vẫn tự nhiên.</p>
                            <p><span class="font-semibold text-zinc-900 dark:text-white">Content:</span> nội dung chính có cấu trúc, có CTA và internal link phù hợp.</p>
                            <p><span class="font-semibold text-zinc-900 dark:text-white">Meta:</span> title khoảng 50-60 ký tự, description khoảng 140-160 ký tự và nêu rõ lợi ích.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <livewire:admin.seo.seo-pages-index />
    </div>
</x-layouts::app>
