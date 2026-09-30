# Legacy Migration (module tạm)

Module này chỉ phục vụ hai giai đoạn:

1. Nhận run/chunk, xác minh checksum và đóng băng payload trong private storage.
2. Super-admin chọn URL/page đích hoặc chọn policy tự động theo loại rồi mới cast vào CMS.

## Luồng vận hành hai giai đoạn

### Giai đoạn 1 — chỉ nhận và staging

- `POST /api/v1/legacy-migrations/runs` tạo phiên.
- `PUT /api/v1/legacy-migrations/runs/{sourceRunUuid}/chunks/{sequence}` nhận chunk có idempotency/checksum.
- `POST /api/v1/legacy-migrations/runs/{sourceRunUuid}/finalize` chuyển phiên sang `ready_for_mapping` ngay khi nguồn chủ động kết thúc. Nếu thiếu sequence hoặc tổng URL staging lệch tổng khai báo, receiver vẫn finalize nhưng lưu cảnh báo có cấu trúc trong `summary_json.finalize_warnings`, ghi `error_text`, application log và trả warnings trong response. Chỉ URL thực tế đã staging được xử lý; chunk gửi sau finalize bị từ chối.
- Không endpoint nào trong giai đoạn này ghi vào model CMS hiện hữu.
- Checksum được tính bằng cách decode lại raw request body, bỏ `payload_hash`/`checksum` tương ứng rồi encode với `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`; không dùng JSON bag đã bị Laravel trim hoặc đổi chuỗi rỗng thành `null`.

### Giai đoạn 2 — chọn đích và cast

- Super-admin mở `/admin/legacy-migrations`; nút **Chọn** mở popup để chọn đúng model/page đích và xử lý riêng URL đó.
- `fill_blanks` chỉ bổ sung field trống; `overwrite` chỉ ghi đè các field đã whitelist trong caster.
- `cast_and_redirect` cast và tạo redirect; `cast_only` chỉ cast; `redirect_only` không sửa model; `blocked` bỏ qua URL.
- Popup cho phép **Chỉ lưu mapping** để đối soát trước hoặc **Lưu và xử lý URL này** trong một lần xác nhận. Cast vẫn kiểm tra lại phiên, trạng thái URL, loại đích và timestamp nguồn ở server.
- Khối **Xử lý tự động theo loại** tạo một queue job cho mỗi URL. Blog mặc định dùng `create_new`, ghi dữ liệu nguồn theo whitelist, tải ảnh về Media Library, gắn ảnh đầu tiên làm cover và dùng `cast_and_redirect`: URL nguồn 301 về URL CMS, còn URL CMS canonical về chính nó.
- Danh sách URL có checkbox từng dòng và checkbox theo trang. Các URL được chọn phải thuộc cùng phiên, cùng loại nguồn, còn ở trạng thái `pending / needs_review / failed` và có root object đầy đủ; popup hàng loạt chỉ queue đúng tập ID đã chọn.
- Các loại tour/taxonomy chỉ tự ghép bản ghi hiện có cùng slug; không tự tạo model ngoài blog.
- `LegacyObjectMap` khóa theo identity nguồn nên nhiều URL cùng trỏ một blog chỉ tạo một `BlogPost`; retry tiếp tục trên target đã tạo.
- `cast_and_redirect` là mặc định khuyến nghị: tạo mapping 301 lâu dài trong `public_url_mappings`, đặt canonical của target về URL CMS và chỉ để URL CMS vào sitemap. `cast_preserve_url` vẫn là tùy chọn ngoại lệ, tạo `render_target` để URL cũ trả 200 với canonical URL cũ.
- Với dữ liệu đã cast bằng `cast_preserve_url`, khối **Hợp nhất URL đã cast** chuyển mapping sang 301 trên chính target hiện có. Thao tác này không import/cast lại, không tạo blog mới và không đổi content, Media, `created_at`, `updated_at` hay `casted_at`; audit dùng action `promote_redirect`. Mỗi URL chạy trong transaction riêng và thao tác idempotent; nếu request dài bị ngắt, tải lại trang rồi bấm tiếp để xử lý phần còn lại.
- Trang không cần giữ nội dung dùng `redirect_only`: người vận hành chọn URL/page đích trong danh sách rồi tạo mapping 301 lâu dài.
- Target phải published/active mới được giữ URL cũ; target draft bị báo lỗi để tránh tạo URL 200 giả hoặc redirect tới trang 404.

## Khác biệt có chủ đích so với tài liệu nguồn

Yêu cầu vận hành hai giai đoạn được ưu tiên hơn ví dụ auto-apply trong tài liệu nguồn:

- Finalize không chuyển sang `applying` và không tự upsert; nó dừng ở `ready_for_mapping`.
- Token thô không lưu trong config; website nhận chỉ giữ SHA-256 qua `LEGACY_MIGRATION_TOKEN_HASH`.
- Module tạm ghi quyết định URL vào `public_url_mappings`; middleware và fallback route ngoài module tiếp tục render/redirect sau khi gỡ module.
- Chỉ flow tự động của root `blog` được tạo `BlogPost` mới. Những loại khác vẫn phải ghép theo slug hoặc chọn target thủ công; object map lưu liên kết legacy → native.

## Bật receiver

Tạo bearer token ngẫu nhiên, đưa token thô cho website nguồn và chỉ lưu SHA-256 tại website nhận:

```dotenv
LEGACY_MIGRATION_ENABLED=true
LEGACY_MIGRATION_TOKEN_HASH=<sha256-cua-token-tho>
LEGACY_MIGRATION_ALLOWED_SOURCES=haidangtravel_legacy
LEGACY_MIGRATION_QUEUE=default
LEGACY_MIGRATION_RECOVERY_STALE_AFTER_SECONDS=300
LEGACY_MIGRATION_MEDIA_ALLOW_ANY_PUBLIC_HOST=true
LEGACY_MIGRATION_MEDIA_ALLOWED_HOSTS=tour.org.vn,haidangtravel.com,www.haidangtravel.com
LEGACY_MIGRATION_MEDIA_REMAP_FROM_HOSTS=haidangtravel.com,www.haidangtravel.com
LEGACY_MIGRATION_MEDIA_REMAP_TO_HOST=tour.org.vn
LEGACY_MIGRATION_MEDIA_MAX_IMAGES_PER_OBJECT=100
LEGACY_MIGRATION_MEDIA_MAX_EMBEDDED_IMAGE_BYTES=5242880
LEGACY_MIGRATION_MEDIA_MAX_EMBEDDED_TOTAL_BYTES=10485760
LEGACY_MIGRATION_MEDIA_MAX_EMBEDDED_PIXELS=24000000
LEGACY_MIGRATION_MEDIA_MAX_REMOTE_PIXELS=24000000
```

Sau đó chạy migration và kiểm tra route:

```powershell
php artisan migrate
php artisan route:list --path=api/v1/legacy-migrations
```

Màn hình mapping nằm tại `/admin/legacy-migrations` và chỉ role `super_admin` truy cập được.
Khi dùng queue `database`, phải có worker đang chạy, ví dụ `php artisan queue:work --queue=default --timeout=80`.

## Khôi phục URL bị kẹt

- Trong trang chi tiết phiên, chọn **Loại dữ liệu nguồn** rồi bấm **Khôi phục URL bị kẹt và xử lý lại**. Nút áp dụng cho toàn bộ loại đã chọn trong phiên, không giới hạn theo tìm kiếm của list. Dòng `queued / processing` cũng có nút khôi phục riêng.
- Chỉ xét URL không cập nhật ít nhất `LEGACY_MIGRATION_RECOVERY_STALE_AFTER_SECONDS` (mặc định 300 giây, tối thiểu 5 phút và lớn hơn thời gian retry/lock của worker). Số **URL cần kiểm tra** chưa phải số được khôi phục: còn phải kiểm tra job và target.
- Chỉ hỗ trợ queue driver `database` dùng cùng database connection với staging. Nếu không đọc hoặc xác minh được job migration, hệ thống dừng, không đoán rằng queue rỗng.
- Job còn trong bảng queue được giữ nguyên, kể cả job hẹn chạy lại hoặc đã được worker reserve. Không xóa job, không tạo bản sao; worker vẫn phải chạy đúng connection/queue.
- URL không còn job và không có execution lock được đưa lại vào queue. Unique lock mồ côi chỉ được giải phóng sau hai kiểm tra này. Việc cập nhật URL, tạo job và audit nằm trong cùng transaction.
- Job khôi phục **chỉ tái sử dụng target** đã lưu trên URL; nếu chưa có thì dùng object map. Target bị xóa, không tương thích, thiếu map hoặc root partial sẽ bị từ chối, không tạo blog mới hay ghép sang bài khác theo slug.
- Giữ nguyên payload/content nguồn, timestamp nguồn, URL nguồn và cấu hình mapping/chính sách field đã lưu. Checkbox tải ảnh Media hiện tại được áp dụng khi tạo lại job blog. Không cần import lại; caster điền nội dung vào target cũ và lưu `created_at / updated_at` theo nguồn, tiếp tục giữ URL cũ hoặc redirect theo mapping đã chọn trước đó.
- Kết quả khôi phục và lỗi được lưu bằng action `recover_queue` trong `legacy_cast_audits`; audit lỗi trước đó không bị xóa. Khôi phục chỉ queue lại, không tự khởi động worker. **Sau triển khai, chạy `php artisan queue:restart` trước khi bấm khôi phục** để worker nạp code mới. Strategy nội bộ `reuse_existing` giúp worker chưa hỗ trợ recovery từ chối job, không tự ghép sang target khác bằng slug.

## Media và lỗi xử lý

- Giới hạn số nguồn ảnh mặc định là 100 mỗi bài. Nếu server đã đặt `LEGACY_MIGRATION_MEDIA_MAX_IMAGES_PER_OBJECT=20`, cần đổi thành 100 rồi clear config/restart worker; code mặc định không ghi đè setting môi trường hiện có.
- Chọn **Điều kiện xử lý → Ảnh vượt giới hạn mỗi bài** để thấy bài có cảnh báo mới `image_count_limit` hoặc thông báo vượt giới hạn cũ. Chỉ xét audit Media mới nhất, không đưa bài đã sửa thành công vào nhóm.
- Trong bộ lọc ảnh, nút **Tải lại ảnh … URL theo bộ lọc** áp dụng trên tất cả các trang khớp cả tìm kiếm/loại/trạng thái hiện tại. Có thể tick checkbox và bấm **Tải lại ảnh … URL đã chọn** để thu hẹp chính xác. Chỉ bài blog đã `casted` có cảnh báo Media, root đầy đủ, target và mapping cast đã lưu mới được đưa lại vào queue; URL queued/processing không tạo job trùng. Target đã bị xóa được bỏ qua, không tạo blog mới.
- Luồng tải lại bắt buộc giữ target, chế độ URL và nguồn timestamp, tái dùng Media tốt rồi cast content nguồn theo `overwrite` để thay placeholder. Cần đối soát nếu đã biên tập bài sau migrate. Audit `retry_media` và các cảnh báo cũ vẫn được giữ; ngân sách HTTP của bài vẫn tối đa 40 giây.
- Nút tải lại dùng queue `database` cùng DB connection với staging để trạng thái URL, job và audit commit nguyên tử. Khi worker chưa nhả execution/unique lock, URL được bỏ qua và vẫn `casted`, không ghi `queued` giả. Không phá khóa hiện hữu; lỗi ghi queue rollback trạng thái và nhả riêng khóa vừa lấy.

- Blog tự động mặc định tải ảnh HTTP/HTTPS từ mọi host Internet công khai và chuyển ảnh nhúng `data:image/...;base64` trong content vào collection `library`. Giữ HTTP cho nguồn chỉ hỗ trợ HTTP; URL dạng `//host/path` mặc định HTTPS và entity HTML như `&amp;` được giải mã một lần trước khi tải.
- Dấu cách/UTF-8 trong path được percent-encode trước khi tải, không đổi tên file thành dấu `-` và không encode lại `%xx` có sẵn. Query được giữ nguyên sau giải mã entity HTML. Fragment được bỏ riêng ở URL download; cho phép cổng chuẩn HTTP 80 / HTTPS 443 ghi tường minh. Dấu cách trong host/query, ký tự điều khiển và backslash vẫn bị từ chối. Giới hạn URL 2048 bytes được kiểm tra sau chuẩn hóa. Payload staging vẫn giữ nguồn ảnh gốc để retry/thay URL Media trong content.
- Riêng nguồn tải ảnh remote có host khớp chính xác `haidangtravel.com` hoặc `www.haidangtravel.com` được đổi sang `tour.org.vn` trước khi downloader chạy, đồng thời giữ nguyên path và query. Cấu hình này chỉ tác động lần tải/retry ảnh; không sửa payload staging, URL nguồn, canonical, redirect hay content CMS hàng loạt. Sau khi tải thành công, luồng hiện hữu vẫn thay URL ảnh trong bài bằng URL Media Library.
- `LegacyImageDownloader` nằm riêng trong module tạm, không nới downloader SEO dùng chung. Cho phép tối đa 5 chuyển hướng (kể cả Location tương đối), kiểm tra domain, DNS/public IP và ghim cURL tới IP hợp lệ ở **mỗi hop**. Ngân sách HTTP tải mỗi ảnh là 25 giây, trừ thời gian đã dùng ở các hop trước; DNS PHP là blocking nên vẫn cần worker timeout phù hợp cho bài nhiều ảnh/nguồn chậm. Không tự gửi tài khoản/cookie/referer tới domain khác. Vẫn chặn localhost, private/reserved IP, credential, cổng không chuẩn, scheme ngoài HTTP/HTTPS, ảnh vượt dung lượng/pixel và nội dung không phải JPEG/PNG/WebP thật. Không biến URL trang HTML thành ảnh và không bỏ kiểm tra TLS. Có thể đặt `LEGACY_MIGRATION_MEDIA_ALLOW_ANY_PUBLIC_HOST=false` để dùng allowlist, áp dụng cả target redirect.
- Lỗi tải mới nêu rõ HTTP 403/404/..., DNS/IP nội bộ, vòng lặp redirect, MIME hoặc nhóm lỗi cURL (timeout/kết nối/TLS). Thông báo được nhóm theo nguyên nhân, không đưa query/token ảnh vào lỗi. Lỗi lịch sử không bị xóa; cần queue lại để có kết quả mới.
- Mặc định `LEGACY_MIGRATION_MEDIA_SKIP_FAILED_IMAGES=true`: ảnh URL lỗi bị bỏ khỏi content, không chặn cast bài. Nguồn `tour.org.vn` / `haidangtravel.com` thử domain còn lại cùng path/query nếu tải/giải mã thất bại; domain ngoài không đổi host. Tái dùng Media tốt của cả hai nguồn trước khi tải. DOM bỏ thẻ `img` và `picture` liên quan, giữ chữ/chú thích/link có chữ/ảnh tốt; không tạo cover placeholder chỉ vì lỗi ảnh URL. Audit `import_media` lưu nguồn đã che query, cảnh báo và `content_action=removed`; bộ lọc **Ảnh cần kiểm tra** cùng popup **Chọn** giúp đối soát sau. JSON staging, target, URL cũ và timestamp nguồn giữ nguyên, có thể khôi phục ảnh bằng retry overwrite trên target cũ.
- Ảnh nhúng lỗi, vượt giới hạn số lượng, chưa được thử tải do hết ngân sách bài hoặc lỗi CA bundle local (`ca_bundle` / `curl_77`) vẫn dùng asset public lâu dài `/images/legacy-migration-placeholder.svg`, không bị coi là ảnh nguồn đã mất. Tổng ngân sách HTTP ảnh của bài tối đa 40 giây, dùng chung cho nguồn chính/dự phòng. Placeholder không được cache như ảnh nguồn thành công. Lỗi database/storage/cache vẫn làm job thất bại; nguồn dự phòng không bỏ kiểm tra TLS/IP/allowlist/bytes/pixels. Giữ asset placeholder khi tháo module.
- Đặt `LEGACY_MIGRATION_MEDIA_SKIP_FAILED_IMAGES=false` để dùng luồng nghiêm ngặt: lỗi ảnh chặn cast, HTTP 429/5xx và lỗi kết nối/timeout được retry theo tries/backoff; nguồn không an toàn, 403/404 hoặc MIME sai không được coi là lỗi mạng tạm thời. Xem `docs/LEGACY_MIGRATION_MEDIA_RECOVERY.md` để sửa nguồn và cast lại trên target cũ; lưu ý chính sách ghi đè lấy lại content nguồn, cần đối soát nếu đã biên tập bài sau migrate.
- Caster retry transaction tối đa 5 lần khi deadlock và cập nhật thống kê phiên sau transaction cast. Counter khóa run trước khi đọc snapshot, lưu counters/trạng thái trong một lần save với retry, giảm tranh chấp nhiều worker; không đưa download/upload Media vào transaction retry. Nếu recount sau cast thành công vẫn lỗi, exception được report vào log, không chuyển URL đã commit thành `failed`; thống kê sẽ được tính lại ở lượt refresh tiếp theo.
- Ảnh remote được kiểm tra thêm kích thước pixel trước khi đưa vào Media Library để tránh ảnh nén nhỏ nhưng gây quá tải khi tạo conversion.
- Ảnh nhúng chỉ nhận JPEG, PNG hoặc WebP thật; hệ thống kiểm tra MIME, dung lượng, kích thước pixel và tổng số ảnh trước khi lưu.
- SHA-256 của binary ảnh nhúng được dùng để tái sử dụng Media đã có. Content được thay `src` bằng URL Media trước khi sanitize; ảnh đầu tiên được dùng làm cover khi bài chưa có cover.
- Lỗi hiện tại nằm ở `legacy_staged_urls.error_text`. Mỗi lần lỗi được lưu lâu dài trong `legacy_cast_audits`, nên việc queue lại có thể xóa lỗi hiện tại mà không mất lịch sử.
- CMS hiển thị nhóm nguyên nhân lỗi, lỗi ngắn theo từng URL và lịch sử lỗi trong popup mapping.
- Bấm một nhóm lỗi để lọc đúng các URL `failed` có cùng thông báo lỗi đầy đủ trong phiên. CMS xóa tìm kiếm/bộ lọc xung đột, về trang đầu và bỏ checkbox cũ; có thể tìm URL hoặc lọc loại thêm sau đó. **Bỏ lọc nhóm lỗi** và **Xem tất cả URL lỗi** đưa danh sách về toàn bộ URL lỗi. Đổi trạng thái hoặc điều kiện xử lý khác sẽ bỏ bộ lọc nhóm lỗi. Đây chỉ là lọc danh sách, không queue lại hay sửa dữ liệu; nút xử lý tự động theo loại vẫn áp dụng toàn phiên.

## Contract timestamp

- Cast model yêu cầu object đầy đủ có `created_at` hợp lệ.
- `updated_at` thiếu thì dùng `created_at`; `updated_at < created_at` bị từ chối.
- Timestamp nguồn parse theo `Asia/Bangkok`, đổi sang timezone ứng dụng và save khi Eloquent timestamps bị tắt.
- Timestamp target cũ cùng before/after snapshot được lưu trong `legacy_cast_audits`.
- Redirect-only không sửa model hoặc timestamp.

## Gỡ module sau khi ổn định

1. Đặt `LEGACY_MIGRATION_ENABLED=false` để ngừng nhận dữ liệu. Các mapping public trong `public_url_mappings` tiếp tục hoạt động độc lập với module.
2. Xác nhận mọi URL cần dùng có trạng thái `casted` hoặc `blocked` và không còn thao tác đang chạy.
3. Tải `GET /admin/legacy-migrations/redirects/export` để lưu bản đối soát; kiểm tra URL `redirect` trả 301 đúng location. Chỉ các ngoại lệ chủ động giữ `render_target` mới trả 200 tại URL nguồn.
4. Archive thư mục `storage/app/private/legacy-migrations` cùng các bảng audit nếu cần đối soát lâu dài.
5. Gỡ `LegacyMigrationServiceProvider::class` khỏi `bootstrap/providers.php`. Không xóa `public_url_mappings`, `ResolvePublicUrlMapping` hoặc `PublicUrlTargetRenderer`.
6. Xóa `app/Modules/LegacyMigration` và các test `tests/Feature/LegacyMigration`.
7. Xóa block `LEGACY_MIGRATION_*` khỏi `.env.example`/môi trường triển khai.
8. Chỉ drop các bảng `legacy_*` khi đã hết thời hạn audit và đã có backup xác nhận.
9. Chạy `php artisan route:list` và regression test frontsite.

Lưu ý: bảng `legacy_migration_redirects` trong module chỉ còn dùng làm audit/export tạm. Runtime public đọc `public_url_mappings` qua middleware và fallback route lâu dài ngoài module.
