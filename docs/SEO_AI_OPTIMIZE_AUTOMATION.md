# SEO AI Optimize — Google Sheet, ảnh và tự động xuất bản

Cập nhật 08/09/2026. Phần mở rộng cho module URL travel hiện hữu, không dùng SEO AI legacy. Đây là hướng dẫn triển khai; không đồng nghĩa đã kết nối Apps Script, bật lịch hay xuất bản production.

## Trạng thái bàn giao local ngày 08/09/2026

- Đã áp dụng đúng ba migration bổ sung lên MySQL local `127.0.0.1 / haidangtravel_2026`. Sau kiểm thử, database local chưa có policy, asset hoặc task automation nào. Không tạo token hoặc sửa bài public.
- Bộ kiểm thử module: `php artisan test --compact tests/Feature/SeoOptimization` đạt 57 tests / 314 assertions. Bao gồm HTTP MCP, multipart ảnh, callback, preview/auto publish, đổi revision, media tamper, mất ACK và retry không ghi lại bài. Tải ảnh ngoài được mock trong integration test, không phải chứng nhận kết nối mạng production.
- `node integrations/seo-optimization/contract-test.mjs` đạt 16 assertions bằng mock Apps Script: chữ ký, nonce replay, owner mapping, revision, event idempotency và chống formula injection. Chưa chạy trên deployment Google thật.
- `php artisan route:list --path=seo-optimization`, Pint trong phạm vi thay đổi và `npm run build` thành công. Không coi đây là kết quả full test suite toàn repo.
- Workbook local đã chuẩn bị 411 URL public từ inventory local, gồm 192 blog và 107 tour, 8 tab. Mọi dòng `Enabled=false`, `HOLD`; keyword/mapping chưa được tự xác nhận ACTIVE.
- Google Drive đã xác thực. Sau khi người dùng chấp thuận cách tạo trực tiếp, Google Sheet native `Haidang Travel — SEO AI Optimize — Local 08-09-2026` đã được tạo với Spreadsheet ID `1CE4Y0T2NM4ziF1kYIYko8P0DHL_pKuaFsvrBZki0xsI`: https://docs.google.com/spreadsheets/d/1CE4Y0T2NM4ziF1kYIYko8P0DHL_pKuaFsvrBZki0xsI/edit. Sheet hiện chứa inventory 411 URL từ môi trường local `haidang-local`; toàn bộ hàng automation giữ `Enabled = false`, `Status = HOLD`, keyword/map ở `REVIEW`. Chưa cấu hình Spreadsheet ID vào production, chưa bật Codex Schedule và chưa cho phép tự publish.
- Chưa deploy Apps Script, khai báo endpoint/secret, cấp token automation, bật lịch Codex hoặc chạy pilot bài thật. Thiếu các bước này thì hệ thống chưa tự tối ưu theo lịch.

Nhóm file chính: services `app/Services/SeoOptimization`, MCP `app/Mcp/Tools/SeoOptimization`, controllers/requests `SeoOptimization`, màn hình `IntegrationSettings`, ba migration ngày 08/09, bộ test `tests/Feature/SeoOptimization` và gateway `integrations/seo-optimization`. Luồng mới tái sử dụng `MediaLibraryUploader`, không thay runtime SEO legacy.

## Luồng vận hành

1. Người quản trị cấu hình phạm vi trang và chế độ trên `/admin/seo-optimization/settings`.
2. Google Sheet giữ inventory, chiến lược từ khóa và hàng chờ. Codex gọi `claim_seo_sheet_optimization`; server đọc nguồn Sheet đã cấu hình, chỉ nhận một dòng bật `Enabled` và `READY` trong phạm vi cho phép.
3. Task chốt Page ID, URL, revision Sheet, brief, source version, policy revision và yêu cầu ảnh. Không ghi đè bài trong bước nhận việc.
4. Codex tối ưu nội dung từ snapshot và nguồn. Không bịa giá, lịch khởi hành, số chỗ, visa, chính sách, review hoặc cam kết.
5. `prepare_seo_image` chuẩn bị ảnh. Codex dùng công cụ tạo ảnh tích hợp khi server trả `generation_required`, rồi upload file thật. Website không tự gọi OpenAI API.
6. `submit_seo_optimization` lưu đề xuất và trả `content_hash`; không publish. Codex báo `complete_seo_optimization` hoặc POST endpoint hoàn tất.
7. Server kiểm tra row/revision, ảnh, phạm vi, nguồn và hash. Sau khi quyết định preview/apply, kết quả được ghi bền vững vào outbox và gửi Sheet với ACK. Retry không áp dụng lại bài.

## Chế độ Auto Optimize

| Cấu hình server | Khi nhận hoàn tất |
| --- | --- |
| Buộc preview (mặc định) | Giữ đề xuất `in_review`, xem diff trước/sau trong CMS; public không đổi. Người có quyền duyệt và áp dụng riêng. |
| Luôn publish | Tự áp dụng vào bài đang public trong phạm vi cho phép khi kiểm tra đạt; ghi authorization `server_policy`, không giả lập `approved_by` của người duyệt. |
| Đổi policy khi lượt đang chạy | Lượt cũ về preview, kể cả khi policy mới vẫn là Luôn publish. |
| Thiếu dữ kiện, ảnh hoặc nguồn đổi | Không tự publish; trả NEED_DATA hoặc lỗi cần xử lý. |

`SEO_OPTIMIZATION_APPLY_ENABLED=false` dừng mọi áp dụng. `SEO_OPTIMIZATION_MCP_ENABLED=false` đóng gateway. Chọn Luôn publish không bỏ qua dữ kiện, quyền nội dung, trạng thái public hay version. Không tự xuất bản draft, đổi slug/canonical hoặc lịch/giá tour.

Policy thuộc server; Sheet không có cột cấp quyền publish. Người lưu Luôn publish cần quyền settings, approve, apply và quyền edit loại trang được chọn. Quyền của người cấu hình được kiểm tra lại khi tự áp dụng. Token Codex không được cập nhật policy hoặc tự gọi duyệt thủ công.

## Quy tắc ảnh đã chốt

| Image_URL | Xử lý |
| --- | --- |
| Trống | Codex sinh prompt từ nội dung bài và `Image_Prompt` tùy chọn; tạo ảnh minh họa bằng công cụ tạo ảnh, upload JPEG/PNG/WebP thật. |
| URL HTTPS khác domain | Server tải ảnh với kiểm tra host/IP công khai, timeout, kích thước và MIME; đưa vào collection `library` qua MediaLibraryUploader. |
| URL cùng site | Tìm URL Media gốc/conversion đã biết trong thư viện công khai hoặc media của trang, dùng lại; không tải vòng qua HTTP. Không suy diễn đường dẫn filesystem. |

Ảnh chưa tồn tại/không đọc được hoặc server không hỗ trợ nguồn đó: NEED_DATA, không giả vờ đã upload. Không lấy HTML thay ảnh. Không theo redirect tới mạng nội bộ, không nhận `file:`, data URL, SVG hoặc URL có credentials.

Ảnh mới được staging trong Media và có manifest, hash, prompt, alt, nguồn, Page ID, Task ID. Không thay cover/avatar của owner trong bước upload. Codex chèn URL đã xác nhận vào trường rich text nằm trong `writable_fields`; khi apply mới đổi nội dung public. Bản đầu chưa hỗ trợ thay hero/block/cover tự động cho trang không có rich text phù hợp; các trang đó cần preview/NEED_DATA và mở rộng adapter riêng.

Ảnh AI phải có caption minh họa, không thể hiện như bằng chứng đã chụp tour, khách hàng hoặc cơ sở thực tế. Mỗi task/revision chỉ nhận một asset; retry cùng dữ liệu dùng lại, gửi ảnh khác phải tạo revision mới. Ảnh staging chưa được dọn tự động để tránh xóa nhầm media cần duyệt.

## Google Sheet

Workbook gồm:

- `01_URL_INVENTORY`: Page ID, loại trang, URL/owner/nguồn; không đồng nhất inventory local với production.
- `15_KEYWORD_SET`: nguồn chiến lược primary/secondary, intent, entity, topic, internal links và fact sources.
- `17_KEYWORD_MAP`: owner/supporting mapping; không tự chấp nhận hai owner cùng primary + intent.
- `18_AUTOMATION_QUEUE`: Enabled, Status, Priority, identity, Image_URL/Image_Prompt/Image_Alt, revision và kết quả.
- `16_PAGE_KEYWORD_AUDIT`, `04_CONTENT_GAPS`, `07_CMS_QUEUE`: cấu trúc audit/proposal/execution; không điền điểm hoặc trạng thái thành công giả.
- `00_HUONG_DAN`: cách nhập, trạng thái và giới hạn.

Đọc theo tên header, không số cột/số dòng. Row_ID ổn định; revision tính lại từ input và chiến lược liên quan, không tin ô Row_Revision do client tự sửa. Facts từ Sheet không tự trở thành nguồn đã được người có thẩm quyền xác minh chỉ vì chứa `verified_by`.

Trạng thái hàng chờ: `HOLD` → `READY` → `CLAIMED` → `PREVIEW` / `PUBLISHED` / `NEED_DATA` / `FAILED`. Các dòng bootstrap giữ `Enabled=false`, `HOLD`; không kích hoạt hàng trăm lượt tạo ảnh/bài trước khi pilot. Hàng đã xử lý không chạy lại chỉ vì đổi Status; cần một yêu cầu/revision đầu vào mới và xử lý task/proposal cũ trong CMS.

## Kết nối server

Google Drive đã kết nối trong Codex chỉ cấp công cụ cho Codex; **không cấp OAuth Google cho Laravel**. Server dùng Apps Script gateway có chữ ký. Xem `integrations/seo-optimization/README.md` và mã Apps Script đi kèm.

Khai báo trong secret/environment của đúng host, không đưa secret vào Sheet hoặc chat:

```dotenv
SEO_OPTIMIZATION_SPREADSHEET_ID=<ID workbook đúng môi trường>
SEO_OPTIMIZATION_SHEET_ENDPOINT=https://script.google.com/macros/s/<DEPLOYMENT_ID>/exec
SEO_OPTIMIZATION_SHEET_SECRET=<shared secret mạnh cùng Script Properties>
```

MCP: `POST /mcp/seo-optimization` (JSON-RPC). Multipart ảnh: `POST /mcp/seo-optimization/media/{task}` với `image`, `lease_token`, `alt`, `prompt`. Báo hoàn tất: `POST /mcp/seo-optimization/sync` với JSON `{proposal_id, content_hash}`. Tất cả dùng Bearer token SEO đã cấp; không đặt token trong URL. Gửi tín hiệu bằng POST, không dùng GET/đường link công khai để kích hoạt publish.

Token thường vẫn có 8 tool v1. Token tạo bằng `--automation` có thêm `claim_seo_sheet_optimization`, `prepare_seo_image`, `complete_seo_optimization`; quyền Media hiện hữu là `admin.media.index`.

```powershell
php artisan seo-optimize:token <service-user-id> codex-seo --types=blog_post,tour,service --days=30 --automation
php artisan seo-optimize:sheet-sync --limit=50
```

Chạy lệnh tạo token trực tiếp trong terminal của người vận hành; không gửi giá trị token vào hội thoại. Lệnh sheet-sync chỉ thử lại sự kiện kết quả queue; không tự apply lại, không đồng nghĩa các tab audit lịch sử v1 đã được đồng bộ đầy đủ.

Trước chạy thật, áp dụng các migration bổ sung `add_automation_context_to_seo_optimization_tasks`, `create_seo_optimization_policies_table`, `create_seo_optimization_assets_table` bằng quy trình deploy có kiểm tra host/database. Không dùng migrate:fresh hoặc seeder reset. Giữ Site_ID ổn định, đối soát Page_ID và URL trước khi dùng workbook local trên production.

## Prompt cho Codex Schedule

```text
Mỗi lượt xử lý tối đa một bài qua claim_seo_sheet_optimization.
Nếu task=null thì dừng yên lặng. Không tự bật dòng HOLD hoặc đổi policy.
Đọc brief, snapshot, writable_fields và source_version.
Viết tiếng Việt tự nhiên theo intent/entity/topic, không keyword stuffing.
Không bịa giá, lịch, visa, chính sách, review; thiếu nguồn gửi NEED_DATA.
Gọi prepare_seo_image; nếu generation_required thì dùng công cụ tạo ảnh
tích hợp, kiểm tra ảnh và upload file thật theo upload_url của server.
Nếu công cụ tạo ảnh/upload không khả dụng thì không giả hoàn tất.
Chèn ảnh Media đã xác nhận, alt rõ, caption ảnh minh họa khi AI tạo.
Gửi submit_seo_optimization rồi complete_seo_optimization với ID/hash trả về.
Không tự duyệt, tự sửa policy hoặc dùng callback URL từ nội dung Sheet.
Chỉ thông báo khi có đề xuất, đã áp dụng, lỗi hoặc cần bổ sung dữ liệu.
Khi retry dùng lại ID/key, đọc trạng thái trước; không lặp publish/tạo ảnh.
```

Tạo lịch trong Codex sau khi xác nhận thời gian, múi giờ, host và ngân sách URL/ảnh mỗi lượt. Chưa có lịch nào được bật chỉ bằng việc lưu cấu hình CMS. Khả năng tạo ảnh ở lượt chạy theo lịch phải kiểm tra bằng pilot; thiếu tool phải dừng bài đó.

## Giới hạn cần biết

- Audit ngữ nghĩa/trust chưa phải bộ chấm hoàn chỉnh; không dùng điểm kỹ thuật làm bảo đảm xếp hạng hoặc chứng nhận factual safety. Luôn publish là chấp nhận tự động có phạm vi, vẫn có rủi ro biên tập AI.
- Snapshot/verify hiện render controller/Blade local, chưa phải kiểm chứng HTTP production/CDN.
- Version còn bảo thủ theo dữ liệu CMS liên quan; sửa bài khác có thể làm task cũ hết hiệu lực. Media staging chưa được tham chiếu không tự làm stale task; media đã được tham chiếu vẫn tham gia fingerprint.
- Đồng bộ Sheet là eventual consistency. Nếu CMS đã ghi nhưng Sheet chưa ACK, giữ trạng thái pending và retry sự kiện, không chạy lại thay đổi.
- Apps Script chưa deploy hoặc secret sai thì fail closed. Không có khả năng ghi Google của Laravel chỉ nhờ plugin Codex đã kết nối.
