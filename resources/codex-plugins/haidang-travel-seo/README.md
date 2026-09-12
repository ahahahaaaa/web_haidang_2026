# Hải Đăng Travel SEO for Codex

Plugin kết nối Codex với SEO Optimization MCP của CMS Hải Đăng Travel. Gói plugin không chứa bearer token và kèm playbook theo đúng 12 tiêu chí chấm điểm của server.

## Cài đặt

1. Đặt biến môi trường `SEO_HAIDANG_MCP_TOKEN` bằng token được tạo tại trang quản trị SEO.
2. Thêm thư mục marketplace đã giải nén: `codex plugin marketplace add <marketplace-root>`.
3. Cài plugin: `codex plugin add haidang-travel-seo@haidang-travel`.
4. Thoát hoàn toàn, mở lại Codex và bắt đầu bằng một task mới.

## Test an toàn

Yêu cầu Codex gọi `list_seo_pages` với `limit=1`. Không dùng thao tác claim, submit hoặc commit để test kết nối.

## Cơ chế nội dung và publish

Nội dung/facts đã có trong snapshot CMS là baseline chính xác để giữ hoặc diễn đạt rõ hơn; không cần tài liệu ngoài. Chỉ dữ kiện mới hoặc bị yêu cầu thay đổi mới cần nguồn. Ở chế độ Luôn publish, server tự ghi khi điểm sau lớn hơn điểm trước; chế độ Buộc preview luôn chờ duyệt. Mọi lần ghi đều tạo backup trước.

Sau khi claim, plugin gọi `seo_page_check` để đọc điểm và bằng chứng nền. Mục tiêu là đủ 12/12 tiêu chí, không còn P0/P1, đạt `PASS` từ 90 điểm và hướng tới A+ từ 95 điểm khi dữ liệu/contract cho phép. Plugin ưu tiên sửa blocker, intent, metadata, nội dung hữu ích, topic/entity, cấu trúc, liên kết và alt; không bịa facts hoặc nhồi từ khóa để “game” điểm.

Snapshot trả `content_contract_version`, `field_contracts`, `writable_fields` và, với LandingPage dạng blocks, `content_units`. Codex phải làm theo contract này. Landing HTML dùng `body`; Landing blocks dùng `block_changes` theo đúng UUID/type/field, không gửi lại toàn bộ JSON blocks và không sửa layout, media, query hoặc URL CTA. Block thiếu UUID ổn định, trùng UUID hoặc đang tắt sẽ không xuất hiện trong `content_units` và không được phép ghi.

## Schedule

Tạo Scheduled task trong Codex bằng mẫu hiển thị tại `/admin/seo-optimization/settings`. Mỗi lượt claim ưu tiên task Đang chờ do người dùng đưa vào `/admin/seo-optimization/tasks`; task có audit hiện hành trên 80 điểm được bỏ qua, điểm đúng 80 vẫn xử lý, và worker tiếp tục task kế tiếp. Các task admin chỉ tạo đề xuất chờ duyệt. Khi hàng chờ hết, server mới tự chọn URL theo policy. Server giữ quyền quyết định preview/publish và tạo backup trước khi ghi CMS.

## Bảo mật

- Chỉ tên biến môi trường nằm trong cấu hình; giá trị token không nằm trong plugin.
- Thu hồi token từ CMS khi thiết bị hoặc secret có nguy cơ bị lộ.
- Không đưa bearer token hoặc lease token vào prompt, nội dung hay log.
