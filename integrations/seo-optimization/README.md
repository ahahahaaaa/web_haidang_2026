# Apps Script gateway cho SEO AI Optimize

Mã `Code.gs` là companion của Laravel, chưa tự deploy vào tài khoản Google. Google Drive plugin trong Codex không cấp token Google cho server Laravel.

1. Mở workbook SEO đã tạo, chọn Extensions → Apps Script; thêm nội dung `Code.gs`.
2. Script Properties: `SPREADSHEET_ID` là ID workbook; `SHARED_SECRET` là secret ngẫu nhiên tối thiểu 32 ký tự. Không lưu secret vào ô Sheet hoặc log.
3. Deploy Web app, execute as tài khoản sở hữu, chọn phạm vi truy cập cho phép Laravel gửi request. Endpoint phải nhận được POST từ server không có cookie Google; lớp xác thực ứng dụng là chữ ký HMAC bắt buộc. Việc triển khai endpoint nhận request công khai cần chủ tài khoản xác nhận; không công khai quyền đọc Sheet.
4. Khai báo Laravel `SEO_OPTIMIZATION_SPREADSHEET_ID`, `SEO_OPTIMIZATION_SHEET_ENDPOINT`, `SEO_OPTIMIZATION_SHEET_SECRET` tương ứng. URL chuẩn `https://script.google.com/macros/s/DEPLOYMENT_ID/exec`.
5. Pilot một dòng: chuẩn bị ACTIVE keyword set tab 15 và OWNER map ACTIVE tab 17, đối soát Page_ID/site/locale, bật Enabled và Status READY tab 18; chọn Buộc preview trên server. Không bật toàn bộ inventory.

HMAC-SHA256 ký chuỗi `timestamp + "\n" + nonce + "\n" + payload`, payload là JSON string UTF-8. Timestamp UTC epoch seconds trong 5 phút, nonce UUID dùng một lần lưu 10 phút. HTTPS, timeout, workbook identity và ACK event ID được kiểm tra phía Laravel. Chỉ theo một redirect ContentService sang `https://script.googleusercontent.com/macros/echo` bằng GET không chuyển tiếp request body/secret.

`next` chọn READY theo Priority rồi Row_ID. `row` đối soát revision hiện hành. `event` chốt CLAIMED/result dưới LockService; ACK idempotent theo event ID/hash. Khóa task và revision không phụ thuộc số dòng Sheet. `_SEO_SYNC_EVENTS` lưu lịch sử ACK; `_SEO_NONCES` là dữ liệu kỹ thuật chống replay. Hai tab ẩn không chứa secret. Chỉ cấp quyền editor workbook cho người được tin cậy; ẩn tab không phải ranh giới bảo mật.

Keyword source of truth: `15_KEYWORD_SET` và `17_KEYWORD_MAP`. Các cột keyword trong queue chỉ là projection hiển thị, không phải nơi thay chiến lược. Chỉnh input ảnh/nội dung chiến lược làm revision thay đổi. `Row_Revision`, `Task_ID`, `Proposal_ID`, `Result_URL`, `Last_Error`, `Updated_At` là output, không tự điền quyền publish.

Gateway không triển khai toàn bộ adapter audit/rule-history v1. Hiện đồng bộ kết quả tab 18; các tab 04/07/16 có cấu trúc để mở rộng nhưng không được báo SYNCED khi chưa ghi. Khi sửa đồng thời Sheet bằng giao diện Google, LockService không khóa các thao tác của người dùng: server đọc lại revision trước khi apply, nhưng không có transaction phân tán tuyệt đối giữa Google Sheet và CMS. Giữ ổn định dòng CLAIMED; dùng Buộc preview khi cần duyệt từng revision nghiêm ngặt.

Nếu mất kết nối sau khi CMS đã ghi: chạy `php artisan seo-optimize:sheet-sync` để gửi lại ACK, không đặt lại READY và chạy lại nội dung. Nếu task lỗi/hết lượt: xử lý task cũ và tạo yêu cầu với revision input mới; không xóa nhật ký để ép chạy lại.

Giới hạn script: 10.000 dòng/tab, 100 cột, JSON request 150.000 ký tự; array keyword/source tối đa 50 phần tử. Không có trigger hoặc lịch nào được tạo tự động bởi mã này.

Kiểm thử hợp đồng offline: `node integrations/seo-optimization/contract-test.mjs`. Kiểm thử dùng mock Google và không sửa workbook thật. Sau deploy vẫn cần pilot preview với một dòng, kiểm tra ACK và quyền trước khi bật lịch.
