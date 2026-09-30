# ADMIN_CMS.md

## 1. Mục đích

Tài liệu này là source of truth cho layout, nhịp giao diện và logic điều hướng của admin CMS Haidang Travel.

Áp dụng khi task chạm tới:

- admin sidebar
- page-local submenu
- list/index pages trong CMS
- create/edit pages trong CMS
- filter, table, pagination của admin
- spacing, padding, gap của bề mặt quản trị

---

## 2. Điều hướng admin

### Sidebar chính

- Sidebar trái dùng một card chung cho toàn bộ navigation CMS, không tách mỗi group thành một card riêng.
- Mỗi group vẫn là một khối collapse độc lập bên trong card chung.
- Trạng thái mở/đóng của group vẫn được nhớ theo browser.
- Group hiện tại phải mở sẵn từ server-rendered HTML.
- Spacing của sidebar phải gọn: ưu tiên `p-2` đến `p-3`, item cao vừa đủ để scan nhanh.
- Panel con của từng group trong sidebar như `sidebar-group-panel-*` không thêm `padding-left`; các child action phải canh thẳng trong flow chung của card thay vì bị thụt sâu như tree view.
- Cuối sidebar chính luôn có liên kết ngoài `App quản lý` trỏ tới `https://dashboard.haidangtravel.com`; link này mở tab mới, không dùng `wire:navigate`, và không thuộc permission matrix nội bộ CMS.

### Submenu nội trang

- Mỗi page có submenu nội trang theo `AdminNavigationRegistry`.
- Submenu nội trang dùng một card collapse gọn, không render thành dàn card rời.
- Child action giữ label ngắn, mô tả ngắn, click nhanh giữa `index`, `create`, `edit taxonomy`, v.v.

---

## 3. Chuẩn list page

Mọi trang danh sách active trong admin CMS phải theo chuẩn:

1. Header trang
2. Submenu cùng group
3. Filter toolbar
4. Grid table / data table
5. Pagination

Quy tắc:

- Trang danh sách là route riêng, không trộn form create/edit trong cùng màn.
- Bảng phải hỗ trợ scan tốt trên desktop và vẫn usable trên mobile qua `overflow-x-auto`.
- Filter ưu tiên các thuộc tính business-facing: tên, trạng thái, danh mục, loại page, role, v.v.
- Mọi field ngày/giờ trong filter, table và detail panel của CMS phải hiển thị theo chuẩn `dd/mm/yyyy`; nếu có giờ thì dùng `dd/mm/yyyy HH:mm` hoặc `dd/mm/yyyy HH:mm:ss`, không dùng `mm/dd/yyyy`.
- Tour list bắt buộc có thêm filter `Điểm đến` ngoài các filter cơ bản khác; filter `Chủ đề tour` và `Điểm đến` phải đi theo `scope` đang chọn để không trộn taxonomy tour trong nước với tour nước ngoài. Taxonomy có `scope` rỗng/null được xem là dùng chung và có thể xuất hiện ở mọi scope.
- Khi đổi `scope` trên Tour list, các filter taxonomy phụ thuộc như `Chủ đề tour` và `Điểm đến` phải reset để tránh giữ lại option không còn thuộc phạm vi đang xem.
- `Travel Inquiries` phải có filter riêng cho nguồn voucher campaign: `Không lọc voucher campaign`, `Tất cả lead voucher`, và từng `VoucherCampaign` cụ thể. Filter này đọc cả `meta.voucher_campaign_slug` và `meta.voucher.campaign_slug` để không bỏ sót lead đã submit nhưng campaign hết mã/hết hạn hoặc lead đã được cấp mã.
- Review item của `Tour`, `Chủ đề tour`, `Điểm đến` phải đi qua route manager riêng dạng `index/create/edit`; không nhúng form review item trực tiếp vào editor của thực thể cha.
- Empty state phải nằm trong body của table, không dùng card-list fallback khác pattern.

### Các list page chính

- `Tours`
- `Tour categories`
- `Destinations`
- `Regions`
- `Countries`
- `Services`
- `Service categories`
- `Blogs`
- `Blog categories`
- `Landing Pages`
- `Voucher Campaigns`
- `Sliders`
- `Accounts`
- `Travel Inquiries`

---

## 4. Chuẩn create/edit page

- Mỗi create/edit screen là route riêng, tách khỏi index.
- Đầu trang luôn có action quay lại index tương ứng.
- Form dài phải dùng action bar cố định/sticky ở cuối.
- Delete action chỉ hiển thị ở editor khi record đã tồn tại.
- Editor page chỉ tập trung vào biên tập dữ liệu; không nhúng lại panel danh sách ở cạnh bên.
- Các trường ngày/giờ biên tập trong CMS phải hướng dẫn nhập `dd/mm/yyyy`; phần xử lý lưu có thể convert về ISO/date object phía sau, nhưng UI không được bắt editor nhìn thấy hoặc nhập theo thứ tự tháng/ngày/năm.
- Trong editor `Blogs`, field `slug` được phép để trống để hệ thống tự sinh từ tiêu đề; nếu slug tự sinh bị trùng thì CMS tự thêm hậu tố `-2`, `-3`, v.v. Nếu editor nhập tay một slug đã tồn tại, form phải trả validation tại field `slug` thay vì để lỗi SQL unique index bung ra thành lỗi hệ thống.
- Với `Tour`, `Chủ đề tour`, `Điểm đến`, phần `rating_average` và `rating_count` là config aggregate rating ảo trên editor cha; review item chi tiết được mở qua action `Quản lý đánh giá`.
- Với `Tour`, editor cha là nơi tạo các lượt QR đánh giá theo tour - ngày khởi hành; mỗi lượt có link/QR riêng, bật/tắt riêng và mật khẩu tùy chọn. Màn `Quản lý đánh giá` dùng để duyệt/sửa review item đã gửi, lọc theo lượt đánh giá hoặc nhập thủ công.
- Với system page `home` trong `Landing Pages`, editor có thể chứa thêm các cụm `home_config` riêng cho homepage như search bar, tab tour nổi bật, slider điểm đến, dịch vụ hỗ trợ, trust, quy trình và blog preview; các field này vẫn phải ở cùng flow save của landing page thay vì tách thành manager khác.
- Cấu hình `home_config.featured_tours` luôn có tab hệ thống `Tất cả` tải trước; editor được thêm tối đa 12 filter theo `Loại tour`, `Điểm đến`, `Chủ đề` hoặc `Vùng miền / Châu`, với thứ tự repeater chính là thứ tự tab ngoài frontsite. Mỗi filter chỉ cần chọn loại nguồn và đối tượng; nhãn tab tự lấy từ đối tượng đã publish, còn tiêu đề/mô tả block dùng chung từ cấu hình `Tất cả`. Danh sách `Tìm kiếm nổi bật` bên dưới cho phép tối đa 12 cặp nhãn/URL tùy ý và mỗi liên kết có thể chọn `Thuộc filter` bằng UUID ổn định. Tab `Tất cả` hiện toàn bộ liên kết; tab phụ chỉ hiện liên kết được gán đúng filter đó; liên kết không gán chỉ hiện ở `Tất cả`. URL chỉ nhận đường dẫn nội bộ, query/hash nội bộ hoặc HTTP(S), không nhận protocol thực thi như `javascript:`.
- `Voucher Campaigns` là manager riêng cho promotion gắn với custom landing page: editor phải có thời gian áp dụng, số lượng mã, prefix, frame hình chung, trạng thái bật/tắt và action đổi mới bộ mã để làm mất hiệu lực cookie cũ.
- Trong editor `Voucher Campaigns`, bảng mã đã cấp phát cần có filter riêng theo mã/khách/liên hệ/ngữ cảnh, trạng thái lead và ngày cấp phát từ-đến; filter này chỉ áp dụng trong campaign đang mở, không thay đổi danh sách campaign.
- `Flash Sale tour` là editor campaign riêng với repeater theo nhịp `Tour / lịch khởi hành -> Giá Flash Sale -> Tổng số vé -> Thứ tự`; mỗi dòng phải gắn đúng một lịch khởi hành công khai, còn giá thường của lịch không bị ghi đè.
- Widget `Flash Sale` trong LandingPage dùng `Bật block` để ẩn/hiện mà vẫn giữ cấu hình. Nút `Xem thêm` có công tắc riêng; nhãn và URL để trống sẽ lấy từ campaign, còn giá trị nhập tại widget chỉ áp dụng cho instance đó. URL ghi đè chỉ nhận đường dẫn nội bộ, query/hash hoặc HTTP(S).
- Một vé Flash Sale tương ứng một khách, không phân biệt người lớn hay trẻ em. CMS phải hiển thị số vé đã ghi nhận và không cho giảm tổng số vé xuống thấp hơn số đã ghi nhận; item đã có vé đặt không được xóa để tránh mất lịch sử quota.
- `Giá Flash Sale` nhận số nguyên dương và phải thấp hơn giá bán hiện tại của lịch khởi hành. Constraint native của trình duyệt phải đồng bộ với Livewire `integer|min:1`: input dùng `min="1"` và `step="1"`; không dùng cặp `min="1" step="1000"` vì sẽ chặn sai các giá tròn như `14.400.000` trước khi form đến được validation phía server.
- Dữ liệu minh họa Flash Sale được tạo thủ công bằng `php artisan db:seed --class=HomeFlashSaleDemoSeeder`. Seeder mới gán quota mẫu lần lượt cho tối đa 6 tour đủ điều kiện và giữ `booked_quantity = 0`. Nếu campaign đã có item, seeder chỉ điền quota còn thiếu; không thêm tour mới hoặc ghi đè campaign, item, block và số vé đã được chỉnh trong CMS khi chạy lại.
- Các cụm cố định của homepage trong `home_config` phải có toggle bật/tắt ngay trong CMS; khi tắt, cấu hình vẫn được lưu để bật lại nhưng frontsite không render block đó.
- LandingPage block picker chỉ hiển thị một widget tour `Tour: danh sách hoặc tab`; editor chọn kiểu hiển thị trong cùng widget. `tour_list` cũ được đưa vào chế độ danh sách khi mở CMS, giữ UUID và cấu hình, rồi lưu thành `tour_taxonomy_tabs`.
- `Tour hot trong tháng` (`home_config.featured_tours`) và widget tour tab như `Tour đoàn nổi bật` có công tắc `Hiển thị filter tour`. Mặc định bật; khi tắt, frontsite chỉ hiện danh sách `Tất cả` theo phạm vi tour của block, đồng thời giữ cấu hình các filter để bật lại.
- `Tìm kiếm nổi bật` của block tour có tab tự lấy điểm đến con từ các tab Vùng miền/Miền/Châu còn hoạt động. Mục thêm thủ công là liên kết URL riêng: nhãn, URL và tab gắn kèm; URL đã nhập được ưu tiên nếu trùng nhãn điểm đến tự sinh trong cùng tab. Block Tour đoàn chỉ tự lấy điểm đến có tour đoàn published.
- Danh sách `Blocks` trong editor LandingPage mở với nội dung từng hàng ở trạng thái thu gọn. Nếu block có tiêu đề, dùng tiêu đề làm tên chính và hiển thị chức năng/loại block bằng dòng chữ nhỏ bên dưới; block chưa có tiêu đề dùng tên loại block làm tên chính. Phần đầu hàng vẫn giữ trạng thái, bật/tắt và các thao tác. Biên tập viên mở hàng bằng `Chỉnh sửa`, kéo tay nắm để đổi vị trí hoặc dùng Lên/Xuống. Với homepage, thao tác kéo cập nhật thứ tự chung giữa widget và section cố định; cần bấm lưu landing page để ghi nhận.
- Với `Tour`, `Departure` là đối tượng thương mại chính cho từng ngày đi; nhóm field `departure_date`, `sale_price`, `base_price`, `standard_label` phải được coi là cụm dữ liệu chính để biên tập và scan nhanh trên editor.
- `Theme Settings` tách tab `Cấu hình giao diện` khỏi cấu hình vận hành chung. Tab này gom theme runtime, media nhận diện, hero trang điểm thưởng, heading section frontsite và các toggle giao diện dùng chung như bật/tắt hero trang chi tiết tour.
- Trong editor `Tour`, field `scope` là nguồn điều khiển danh sách `Chủ đề tour` và `Điểm đến`; đổi scope phải làm mới option list nhưng vẫn bảo toàn giá trị taxonomy đã chọn khi đang sửa dữ liệu cũ.
- Select `Điểm đến` trong editor `Tour` phải hiển thị theo cây quốc gia: mỗi quốc gia là một `optgroup`, option quốc gia root đứng đầu với nhãn `Tên quốc gia (quốc gia)`, các điểm đến con nằm bên dưới cùng nhóm.
- `Điểm đến chính` được phép chọn trực tiếp quốc gia root. `Điểm đến bổ sung` và filter `Điểm đến` của Tour list cũng được phép bao gồm quốc gia root khi đó là option đúng với scope.
- Editor `Điểm đến` không được ẩn hai toggle `Hiển thị tour trên trang điểm đến` và `Hiển thị blog trên trang điểm đến` khi record là quốc gia root; trang `/tour-{slug}` phải đọc và áp dụng cùng hai flag này như trang điểm đến thường.
- Repeater `Departure theo tour` nên ưu tiên nhịp nhập theo thứ tự `ngày khởi hành -> giá bán -> giá gốc -> tiêu chuẩn`, sau đó mới tới `ngày về`, `điểm khởi hành`, `phương tiện`, `trạng thái`, `số chỗ`, và các metadata khác.
- `pricing_table` của tour vẫn được giữ cho phụ thu, ghi chú giá, hoặc các dòng giá bổ sung; không dùng nó như source of truth chính cho lịch khởi hành theo từng ngày khi `departures` đã có dữ liệu usable.

---

## 5. Spacing và surface

- Admin CMS dùng nhịp gọn hơn bản cũ.
- Ưu tiên `space-y-4`, `gap-3`, `gap-4`, `p-4`, `p-5`.
- Hạn chế `p-6` và khoảng trắng lớn nếu không có lý do rõ ràng.
- Card chính của page dùng bo góc lớn, nhưng padding bên trong giữ chặt để tăng mật độ thông tin.
- Table header, filter toolbar và row actions phải nhìn compact, rõ ràng, không quá “card-heavy”.

---

## 6. Quy tắc implementation

- Nếu thêm một manager/list page mới, mặc định phải đi theo pattern `index riêng + editor riêng`.
- Nếu sửa sidebar hoặc permission matrix, luôn đối chiếu lại `AdminNavigationRegistry`.
- Màn tài khoản luôn hiển thị trạng thái quyền theo vai trò: `Admin` toàn quyền, `Sale` dùng bộ quyền tour cố định, `Content` có ma trận quyền bổ sung cập nhật ngay khi đổi vai trò.
- Chỉ tài khoản mang role `admin` hoặc `super_admin` được truy cập và thao tác quản lý tài khoản; quyền `admin.accounts.*` không được cấp bổ sung cho `Content`.
- Nếu sửa list page, giữ filter state và pagination ổn định với Livewire.
- Nếu sửa editor, không phá flow `back to index`.

---

## 7. Validation khi thay đổi admin CMS

Khi phù hợp, chạy:

- `composer dump-autoload -o`
- `php artisan route:list`
- `php artisan test`

Nếu patch chạm view Livewire nhiều:

- kiểm tra lại route `index/create/edit`
- kiểm tra sidebar current state
- kiểm tra submenu collapse
- kiểm tra save/delete/back-to-index flow

---

## 8. Nội dung mới do Codex tạo

- `/admin/seo-optimization/content-creation` theo dõi payload, media, trạng thái và editor URL của record mới do Codex gửi.
- `BlogPost`, `Tour`, `Service`, taxonomy tour/địa lý được tạo `draft`; landing custom được tạo `inactive` và không được dùng `page_key` hệ thống.
- Danh mục blog/dịch vụ chưa có lifecycle draft phải dừng ở `ready_for_review`; chỉ người có quyền duyệt SEO và sửa taxonomy mới xác nhận tạo record thật.
- Token ability `create` tách khỏi `automate`; chỉ cấp cho tài khoản có quyền Media và quyền edit đúng loại nội dung.
- Màn kết nối MCP phải chọn tài khoản rồi chọn đúng credential đã cấp để hiển thị abilities, phạm vi và danh sách tool thực tế; không dùng trạng thái checkbox của form tạo token mới làm quyền của token hiện hữu.
- Credential chỉ được xóa sau khi đã thu hồi. Thao tác xóa dùng soft delete để ẩn token khỏi quản trị và xác thực nhưng vẫn giữ quan hệ task cùng audit lịch sử.
- Binary ảnh nằm trên Media disk; bảng `media` và bảng task chỉ lưu metadata/relation. Không lưu base64/BLOB ảnh trong field content hoặc bảng nghiệp vụ.
