# Khôi phục ảnh khi chuyển dữ liệu website cũ

Phạm vi: module tạm `app/Modules/LegacyMigration`, CMS `/admin/legacy-migrations/{run}`. Không thay đổi downloader SEO dùng chung, JSON staging hoặc route/canonical của page nguồn.

## Luồng cast và xử lý lại

- Popup **Chọn → Lưu và xử lý URL** đưa ảnh blog vào Media Library trước khi cast, gồm ảnh URL trong manifest và ảnh `data:image/...;base64,...` trong content, kể cả khi manifest rỗng. Chỉ lưu mapping, redirect-only hoặc blocked không tải ảnh.
- Luồng tự động và khôi phục dùng cùng caster. Bật **Tải ảnh blog về Media Library và đổi URL ảnh trong nội dung**. Nếu tắt mà content còn ảnh base64, URL báo lỗi thay vì sanitizer âm thầm loại ảnh.
- HTTP và xử lý ảnh chạy ngoài transaction cast. Mặc định ảnh lỗi không chặn bài: ảnh tải/giải mã được vào Media; ảnh URL không tải/giải mã được bị xóa thẻ `img` khỏi content. Ảnh nhúng lỗi, ảnh vượt số lượng, chưa được tải do hết ngân sách bài hoặc lỗi CA bundle local (`ca_bundle` / `curl_77`) vẫn dùng `/images/legacy-migration-placeholder.svg`, vì chưa chứng minh nguồn ảnh đã mất. URL vẫn được cast cùng timestamp nguồn. Lỗi database/storage/cache vẫn làm thất bại tác vụ, không bị coi là ảnh lỗi.
- Ảnh thuộc `tour.org.vn` / `haidangtravel.com` dùng remap hiện có (mặc định tour trước), rồi thử domain còn lại với cùng path/query và cùng deadline khi tải/giải mã thất bại. Tái dùng Media tốt từ cả hai nguồn trước khi tải mới. Domain ngoài chỉ thử nguồn của chính nó, không ghép tùy tiện sang domain legacy. Mọi nguồn dự phòng vẫn qua allowlist nếu bật, IP công khai, TLS, bytes/pixels và giải mã thật.
- Xóa ảnh dùng DOM: bỏ cả `picture/source/srcset` của ảnh thất bại và link bao ảnh nếu link không còn nội dung; giữ chữ, link tham khảo có chữ, chú thích và ảnh tốt. Không dùng replace URL ảnh lỗi thành chuỗi rỗng. Không tạo cover placeholder chỉ vì ảnh URL tải lỗi; không sửa cover tốt đã có.
- Ảnh URL trong content được tìm cả khi manifest thiếu. Lỗi TLS/DNS/403/404/MIME/giải mã, ảnh base64 không hợp lệ hoặc vượt giới hạn đều được lưu trong audit `import_media` có `status=warning`, `skipped` và `warnings`. Không lưu file JavaScript/HTML/SVG từ nguồn vào Media và không tắt xác thực SSL.
- Khôi phục dùng target đã lưu. Không nhập lại dữ liệu staging; giữ nguyên object key, JSON nguồn, timestamp nguồn và đường dẫn page cũ. Chế độ `cast_preserve_url` phục vụ page tại URL cũ; `cast_and_redirect` là 301 tới URL đích, không phải page 200 tại URL cũ.
- Sau khi sửa nguồn/cấu hình, chọn các URL lỗi hoặc khôi phục URL bị kẹt rồi xử lý lại. Không khôi phục một URL đang thực sự được worker xử lý; bộ khôi phục có kiểm tra job/lock.

## Xem ảnh nào gây lỗi

Trong danh sách, bấm nhóm lỗi để lọc URL, sau đó **Chọn → Lịch sử các lần lỗi gần nhất**. Audit mới có ảnh nguồn, mã lỗi và thông tin kết nối. Query/fragment/tài khoản không được ghi vào URL ảnh trong context audit; ảnh nhúng được định danh bằng `legacy-inline://{sha256}`, không lưu chuỗi base64 vào context.

Đối với bài đã cast có ảnh bị bỏ qua hoặc ảnh tạm: chọn **Điều kiện xử lý → Ảnh cần kiểm tra (đã bỏ qua / dùng placeholder)**, rồi **Chọn** để xem nguồn và nguyên nhân từng ảnh trong popup. Audit mới có `warnings[].content_action=removed|placeholder`; danh sách nguồn raw dùng để xóa HTML không được ghi vào audit. Bộ lọc dùng kết quả Media mới nhất; khi xử lý lại thành công, URL tự ra khỏi nhóm nhưng audit cảnh báo cũ vẫn được giữ. Sửa nguồn hoặc cấu hình URL thay thế, giữ nguyên target đã chọn và dùng chính sách **ghi đè dữ liệu nguồn** trước khi submit lại popup. Việc này cast lại content nguồn, vì vậy cần đối soát nếu bài đã được biên tập thêm sau migrate. Không bấm tạo blog mới hoặc import lại staging.

Ảnh tạm là asset public lâu dài, không nằm trên route module; giữ file SVG khi gỡ module để các bài chưa thay ảnh không mất placeholder. Placeholder không được lưu vào Media cache dưới URL ảnh gốc, nên lần sau vẫn thử tải nguồn thật.

### Tải lại ảnh bị bỏ qua do giới hạn

Giới hạn mặc định đã tăng từ 20 lên 100 nguồn ảnh mỗi bài. Đặt trên server (setting `.env` cũ vẫn ưu tiên hơn default code):

```dotenv
LEGACY_MIGRATION_MEDIA_MAX_IMAGES_PER_OBJECT=100
```

Sau khi triển khai, clear config và restart worker như phần bên dưới. Tại `/admin/legacy-migrations/{run}`:

1. Chọn **Điều kiện xử lý → Ảnh vượt giới hạn mỗi bài (kể cả cảnh báo cũ)**. Bộ lọc nhận mã `image_count_limit` mới và thông báo vượt giới hạn cũ `image_validation`; chỉ xét kết quả Media mới nhất.
2. Thêm tìm kiếm/loại/trạng thái nếu muốn thu hẹp. Bấm **Tải lại ảnh … URL theo bộ lọc** để chạy tất cả các trang khớp bộ lọc, hoặc tick checkbox rồi bấm **Tải lại ảnh … URL đã chọn**.
3. Xác nhận ghi đè content từ nguồn. Luồng này giữ target và chế độ URL đã lưu, không dùng setting tạo bài mới hoặc thay mapping ở khối tự động. JSON staging và timestamp nguồn không đổi; ảnh Media tốt được tái dùng.

Nút cũng có trong bộ lọc **Ảnh cần kiểm tra (đã bỏ qua / dùng placeholder)** để tải lại các nguyên nhân khác sau khi đã sửa nguồn. Chỉ URL blog đã `casted` với root đầy đủ, target và mapping cast đã lưu được queue. URL queued/processing hoặc không còn cảnh báo không được queue trùng; target bị xóa được bỏ qua, không tạo lại blog. Lịch sử cảnh báo và audit `retry_media` được giữ. Worker vẫn phải chạy đúng connection/queue.

Tải lại an toàn yêu cầu queue driver `database` cùng DB connection với staging (cấu hình mặc định của repo). Job, trạng thái URL và audit được ghi trong cùng transaction. Worker chưa nhả execution/unique lock thì URL được bỏ qua, vẫn giữ `casted`; thử lại sau khi worker kết thúc. Không giải phóng khóa của worker. Nếu ghi job thất bại, trạng thái URL được rollback và chỉ khóa do thao tác mới lấy được nhả.

Giới hạn 100 không bỏ ngân sách HTTP 40 giây, dung lượng/pixels hoặc kiểm tra nguồn an toàn. Bài nhiều ảnh nguồn chậm vẫn có thể nhận cảnh báo `object_download_budget`; lần tải lại sau sẽ tái dùng ảnh tốt đã tải để dành ngân sách cho ảnh còn thiếu. Số nguồn ảnh hiện tính cả cover, ảnh lỗi và Media tái dùng; 0 không có nghĩa là không giới hạn.

Audit cũ chỉ có thông báo lỗi sẽ không tự có thêm context. Chạy lại sau khi cập nhật code để có thông tin mới. JSON object nguồn vẫn là bản gốc và có thể chứa URL có query hoặc base64.

## Các nhóm lỗi và cách xử lý

| Nhóm | Hành vi và việc cần làm |
| --- | --- |
| `curl_35` | Lỗi bắt tay TLS, khác lỗi xác thực chứng chỉ. Thử IP công khai tiếp theo nếu có; lỗi tạm được queue retry. Kiểm tra TLS/cURL của server và server nguồn nếu vẫn lỗi. |
| `curl_60`, `curl_77`, `ca_bundle` | Chứng chỉ nguồn hoặc CA trên server tải. Sửa chứng chỉ/CA; không tắt xác thực SSL. Không retry mù lỗi cấu hình này. |
| `dns`, `curl_6` | Khi truy vấn DNS A/AAAA không có kết quả, thử resolver hệ điều hành. Mọi IP vẫn được kiểm tra và pin trước request. Domain đã mất DNS cần sửa hoặc cung cấp ảnh thay thế. |
| `http_403` | Thử thêm một request với Referer là origin của chính host ảnh; không gửi cookie/tài khoản. Nếu vẫn 403, nguồn có thể chặn hotlink/WAF hoặc cần quyền; dùng URL ảnh công khai khác. |
| `http_404` | File không có tại nguồn. Khôi phục file hoặc cấu hình URL thay thế; code không thể tái tạo ảnh đã mất. |
| `image_decode` | File có MIME/kích thước hợp lệ nhưng không giải mã được đầy đủ. Không lưu Media mới; sửa file nguồn hoặc thay ảnh. Media cache bị mất/hỏng không được tái dùng; tải/giải mã nguồn lại. Cover do migrate tạo có original hỏng sẽ được phục hồi tại cùng Media ID từ ảnh nguồn hợp lệ cùng MIME, rồi tạo lại conversion; original tốt thì chỉ tạo conversion thiếu. Không xóa record Media lỗi cũ hoặc sửa cover do quản trị chọn. Nếu ảnh thay thế khác định dạng, chọn lại cover trong CMS. |
| `invalid_mime` | Dữ liệu thực là JavaScript/HTML/SVG... bị từ chối dù URL hoặc header có đuôi ảnh. Cung cấp URL trả bytes ảnh thật; không mở quyền nhập JavaScript vào Media. |
| AVIF | Giải mã rồi chuyển thành WebP thật trước khi lưu Media/cover và thay `src`. Server cần GD hỗ trợ AVIF + WebP hoặc Imagick đọc AVIF/ghi WebP. Chỉ đổi đuôi file không đủ. |

Mặc định lỗi ảnh URL được bỏ qua bằng cách xóa thẻ ảnh, không ném lại lỗi ảnh để queue retry cả bài; cảnh báo vẫn giữ để khôi phục từ nguồn sau. Mỗi lần tải nguồn có ngân sách HTTP tối đa 25 giây, tối đa 3 IP đã kiểm tra, tối đa 5 redirect; nguồn dự phòng dùng ngân sách còn lại của bài. Tổng ngân sách tải ảnh của bài mặc định 40 giây để chừa thời gian cast trong job 80 giây. Nếu chưa thể thử nguồn dự phòng hoặc ảnh tiếp theo vì hết ngân sách, ghi cảnh báo `object_download_budget` và dùng placeholder, không kết luận ảnh đã mất. DNS PHP vẫn blocking, nên giới hạn này không bảo đảm tổng thời gian job khi resolver/storage/codec chậm.

Nếu cần luồng nghiêm ngặt cũ, đặt `LEGACY_MIGRATION_MEDIA_SKIP_FAILED_IMAGES=false`: ảnh lỗi tiếp tục chặn cast và lỗi ảnh tạm thời được queue retry (3 lần, backoff 10/30 giây).

## Cấu hình riêng cho lần migrate

```dotenv
LEGACY_MIGRATION_MEDIA_SKIP_FAILED_IMAGES=true
LEGACY_MIGRATION_MEDIA_MAX_DOWNLOAD_SECONDS_PER_OBJECT=40
LEGACY_MIGRATION_MEDIA_MAX_IMAGES_PER_OBJECT=100
```

Ngân sách HTTP của bài được giới hạn tối đa 40 giây; đặt 0 để dùng ảnh tạm cho các nguồn remote chưa có Media cache. Không làm thay đổi giới hạn bytes/pixels hoặc kiểm tra IP công khai.

Mặc định cho phép tải ảnh từ mọi **host Internet công khai** (`LEGACY_MIGRATION_MEDIA_ALLOW_ANY_PUBLIC_HOST=true`), HTTP/HTTPS, port chuẩn 80/443; không cho IP nội bộ, URL có tài khoản, file ngoài raster hợp lệ hoặc ảnh vượt giới hạn bytes/pixels.

CA bundle riêng, nếu CA hệ thống chưa phù hợp:

```dotenv
LEGACY_MIGRATION_MEDIA_CA_BUNDLE=/etc/ssl/certs/ca-certificates.crt
```

Dùng đường dẫn bundle đáng tin cậy, tồn tại và đọc được trên server. Bỏ trống để dùng CA mặc định. Không tạo bundle tự ký để bỏ qua xác thực nguồn.

URL thay thế cho file mất/hỏng/bị chặn:

```dotenv
LEGACY_MIGRATION_MEDIA_SOURCE_URL_OVERRIDES='{"https://tour.org.vn/image/deleted.png":"https://cdn.example.com/restored.png"}'
```

JSON phải là object ánh xạ URL đầy đủ sang URL ảnh mới. Key là URL đã chuẩn hóa: bỏ fragment, decode HTML entity, encode khoảng trắng/ký tự Unicode trong path; cấu hình này áp dụng **trước** remap host `haidangtravel.com → tour.org.vn`, nên ảnh còn mang host haidang phải dùng key haidang tương ứng. URL đích vẫn qua kiểm tra host/IP/MIME/giải mã như mọi ảnh khác.

Chỉ nguồn tải ảnh và URL ảnh trong content được thay khi cast thành công. Không sửa hàng loạt JSON staging, không đổi URL page cũ và không tạo blog mới khi khôi phục.

## Áp dụng trên server

Sau khi triển khai code và cập nhật cấu hình:

```bash
php artisan optimize:clear
php artisan queue:restart
```

Đảm bảo Supervisor/service quản lý worker khởi động lại và dùng PHP có codec cần thiết. Kiểm tra lại vài URL lỗi trước khi xử lý cả nhóm. Không cần upload lại XLSX hoặc import lại staging.

Module vẫn là phần tạm có thể tháo sau migrate. Blog, Media và public URL mappings đã tạo là dữ liệu/runtime lâu dài, không xóa cùng module nhận migrate.

## Thành phần đã cập nhật

- `app/Modules/LegacyMigration/Services/LegacyContentCaster.php`: caster dùng chung, chuẩn bị Media trước transaction, lưu audit và giữ timestamp nguồn.
- `app/Modules/LegacyMigration/Services/LegacyAutomaticProcessor.php`: dùng caster chung, lưu target trước xử lý ảnh để retry không tạo blog mới.
- `app/Modules/LegacyMigration/Services/LegacyBlogMediaImporter.php`: ảnh base64/manifest, URL thay thế, kiểm tra Media tái dùng và phục hồi cover migrate bị hỏng.
- `app/Modules/LegacyMigration/Models/LegacyStagedUrl.php`: quan hệ kết quả Media mới nhất cho badge và bộ lọc ảnh cần kiểm tra; không cần migration schema mới.
- `public/images/legacy-migration-placeholder.svg`: ảnh tạm public lâu dài, giữ khi gỡ module.
- `app/Modules/LegacyMigration/Services/LegacyImageFileNormalizer.php`: kiểm tra giải mã raster đầy đủ, chuyển AVIF thành WebP.
- `app/Modules/LegacyMigration/Services/LegacyImageDownloader.php`: CA, DNS fallback/IP failover, Referer retry 403 và thông báo lỗi nguồn.
- `app/Modules/LegacyMigration/Exceptions/LegacyImageDownloadFailure.php` và `Jobs/ProcessLegacyMigrationUrl.php`: context lỗi ảnh và phân biệt retryable/terminal.
- `app/Modules/LegacyMigration/Livewire/Admin/MigrationManager.php` và `Resources/views/admin/index.blade.php`: popup cast có xử lý Media và hiển thị context lỗi trong lịch sử.
- `app/Modules/LegacyMigration/Config/legacy_migration.php`, `.env.example`: CA bundle và ánh xạ URL ảnh thay thế.
- `tests/Feature/LegacyMigration/LegacyMigrationAdminTest.php`, `LegacyAutomaticProcessorTest.php`, `LegacyImageDownloaderTest.php`, `LegacyImageFileNormalizerTest.php`: test popup, retry/idempotency, ảnh base64 lớn, AVIF, PNG hỏng, lỗi tải và rào chắn IP/SSL.
- `docs/LEGACY_MIGRATION_MEDIA_RECOVERY.md`: hướng dẫn này.
