# Thiết kế bộ lọc tìm tour

## 1. Mục tiêu

Bộ lọc giúp người dùng đi từ nhu cầu chung sang danh sách tour phù hợp bằng một request `GET` có thể chia sẻ, quay lại và phân trang. Thiết kế tham chiếu bố cục trong ảnh nhưng chỉ hiển thị dữ liệu và sản phẩm đang tồn tại trong CMS Haidang Travel.

Phạm vi MVP:

- hiển thị cùng một bộ lọc đầy đủ ở đáy hero của các trang khám phá, listing và landing phù hợp trên desktop, cách đáy hero `20px`; không render trên trang chi tiết tour hoặc chi tiết bài viết/blog;
- không render panel filter đầy đủ trên mobile; mobile dùng cụm khám phá gọn trong header gồm điểm khởi hành, shortcut sản phẩm/tour và rail nổi bật;
- giữ thanh tìm kiếm gọn hiện hành trong nội dung các listing không dùng advanced filter; `/tim-tour` chỉ dùng advanced filter trong hero để tránh lặp panel;
- dùng `Tour`, `TourDeparture`, `TourCategory`, `Destination` và `Service` hiện hành, không tạo domain hoặc URL giả;
- không thêm `Khách sạn` hay `Vé máy bay + khách sạn` cho tới khi có domain, dữ liệu publish và route canonical tương ứng.

### Phạm vi theo loại trang

| Loại trang | Panel filter trong hero trên desktop | Header khám phá trên mobile |
| --- | --- | --- |
| Trang chủ, `/tim-tour`, trang khám phá/danh sách và landing phù hợp | Có, tối đa một panel ở hero đầu tiên | Có |
| Chi tiết tour `/chuong-trinh/{slug}` | Không; không chừa khoảng đệm đáy hero dành cho panel | Giữ cụm khám phá chung của header |
| Chi tiết tin tức/bài viết `/{category}/{post}` | Không; không chừa khoảng đệm đáy hero dành cho panel | Giữ cụm khám phá chung của header |

Việc loại trừ chỉ áp dụng cho advanced filter trong hero; không xóa H1, breadcrumb, CTA đặt tour/tư vấn hoặc nội dung chính của trang chi tiết. Trang danh sách blog và các hub khám phá không bị loại trừ theo quy tắc của trang bài viết chi tiết.

## 2. Kiểm kê thành phần

| Thành phần trong thiết kế | Nguồn hiện có | Trạng thái MVP |
| --- | --- | --- |
| Tour trọn gói | `Tour`, `/tim-tour` | Dùng, là tab mặc định |
| Vé máy bay | service category slug `ve-may-bay` | Chỉ hiện khi category có service publish |
| Dịch vụ cộng thêm | `Service`, `/dich-vu` | Dùng |
| Khách sạn | Chưa có domain/route | Chưa hiển thị |
| Vé máy bay + khách sạn | Chưa có package/combo domain | Chưa hiển thị |
| Trong nước / Nước ngoài | `TourScope` | Dùng |
| Tour đoàn | `TourScope::Group` | Dùng để không làm mất scope đang có |
| Điểm khởi hành | `Tour.departure_location` và `TourDeparture.departure_location` | Dùng, có chuẩn hóa alias |
| Điểm đến | `Destination` có tour publish | Dùng |
| Ngày đi | `TourDeparture.departure_date` public, từ ngày mai trở đi | Dùng |
| Tìm kiếm nổi bật | `TourCategory`/`Destination` featured có tour publish | Dùng link canonical |

## 3. Hợp đồng URL và dữ liệu

Endpoint kết quả: `GET /tim-tour`.

| Query key | Kiểu | Ý nghĩa |
| --- | --- | --- |
| `scope` | `domestic`, `international`, `group`, `non_group` hoặc rỗng | Phạm vi tour; `non_group` lấy tour trong nước và nước ngoài, loại tour đoàn |
| `departure_location` | canonical slug | Điểm khởi hành đã chuẩn hóa |
| `destination` | slug | Điểm đến/taxonomy cần khớp |
| `departure_date` | `YYYY-MM-DD` | Khớp chính xác một ngày khởi hành public |
| `q` | chuỗi tối đa 160 ký tự | Tìm tiêu đề, slug và taxonomy; giữ tương thích |
| `category` | slug | Chủ đề tour; giữ tương thích |
| `transport` | chuỗi | Phương tiện; giữ tương thích |
| `budget` | enum khoảng giá | Ngân sách; giữ tương thích |

Quy tắc:

- mọi field đều tùy chọn và được kết hợp theo điều kiện `AND`;
- `destination` khớp relation nhiều điểm đến của tour;
- `departure_date` chỉ khớp `TourDeparture` có trạng thái public và từ ngày mai trở đi; ngày hiện tại không phải lựa chọn hợp lệ;
- khi chọn đồng thời điểm khởi hành và ngày đi, hai điều kiện phải cùng khớp một `TourDeparture`; `Tour.departure_location` chỉ đóng vai trò điểm đi mặc định của tour;
- phân trang phải giữ nguyên query string;
- input không hợp lệ được chặn tại `TourSearchRequest`, không đẩy raw value vào query builder;
- `/tim-tour` dùng `noindex,follow` và không phát canonical tag theo SEO contract hiện hành; đây vẫn là route kết quả duy nhất của form.

## 4. Chuẩn hóa điểm khởi hành

Dữ liệu hiện hành có thể chứa nhiều cách ghi cùng một nơi. Runtime gom alias thành một khóa ổn định:

- `HCM`, `HCM4`, `TP HCM`, `Hồ Chí Minh`, `Sân bay Tân Sơn Nhất` -> `ho-chi-minh`;
- `HN`, `Hà Nội`, `Sân bay Nội Bài` -> `ha-noi`;
- giá trị khác -> slug tiếng Việt không dấu của giá trị đã trim/squish.

Option được dựng từ cả tour mặc định và departure public sắp tới. Khi lọc, canonical key được map ngược về toàn bộ raw value thuộc nhóm đó; không sửa dữ liệu gốc trong lần triển khai này.

## 5. Cấu trúc giao diện

Thứ tự trong panel:

1. tab sản phẩm có route thật;
2. loại tour dưới dạng select;
3. điểm khởi hành;
4. điểm đến;
5. ngày đi;
6. CTA `Tìm` với icon và chữ đủ lớn để nhận diện nhanh;
7. rail link tìm kiếm nổi bật có nút play tiến/lùi và action xóa filter khi có state.

Trên các trang được phép hiển thị, desktop dùng một surface bo tròn và grid năm cột, được đặt `absolute` trong hero với `bottom: 20px`; hero phải chừa đủ khoảng đệm đáy để nội dung không va vào panel. Nav sản phẩm không có nền riêng để hòa vào hero; tab đang chọn dùng nền trắng và màu cam primary, còn các tab phụ như `Vé máy bay`, `Dịch vụ cộng thêm` dùng nền cam nhạt của theme. Trạng thái active phải theo route hiện tại của trang có filter: route tìm/listing tour active `Tour trọn gói`, category/detail vé máy bay active `Vé máy bay`, các route dịch vụ còn lại active `Dịch vụ cộng thêm`; route ngoài ba nhóm không tự gán active sai. Scrollbar ngang của rail phải được ẩn; nút reverse-play nằm ở đầu rail trước chip đầu tiên, nút play nằm ở cuối rail sau chip cuối cùng, và cả hai có trạng thái disabled ở hai đầu. Select dùng lớp `Tom Select` hiện hành, chỉ hiển thị một giá trị hoặc placeholder chứ không lặp cả hai dòng. Riêng `Điểm khởi hành` và `Điểm đến` phải giữ một dòng có chiều cao cố định: giá trị được chọn thay trực tiếp vị trí placeholder, còn input tìm kiếm nội bộ co về `0` khi rỗng để không đẩy control xuống dòng mới. Ngày dùng `Flatpickr` hiện hành, lấy ngày mai làm mốc tối thiểu và vẫn là native `input[type=date]` khi JavaScript không chạy. Header tháng/năm, trạng thái chọn, focus và điều hướng của lịch dùng màu cam primary của theme.

Ở mobile, không thu nhỏ hay xếp dọc panel desktop. Header thay thế bằng:

- một selector điểm khởi hành mở dropdown hai cột từ dữ liệu runtime;
- tối đa sáu shortcut có route thật từ product tab và ba scope tour;
- rail `Nổi bật` cuộn ngang, ẩn scrollbar và chỉ chứa link canonical;
- không tạo shortcut `Khách sạn`, `Combo` hoặc sản phẩm khác khi domain/route publish chưa tồn tại.

Các yêu cầu truy cập:

- loại tour là select có label nhìn thấy và dùng lớp `Tom Select` chung;
- field có label nhìn thấy, focus ring và touch target tối thiểu khoảng 44px;
- icon chỉ trang trí có `aria-hidden`;
- form vẫn submit và lọc được khi JavaScript tắt.

## 6. Kiến trúc triển khai

Luồng xử lý:

```text
Hero khám phá/listing/landing phù hợp (desktop)
  -> hero-tour-search-overlay.blade.php
  -> tour-search-panel.blade.php
  -> GET /tim-tour
  -> TourSearchRequest (validate + normalize input boundary)
  -> FrontsiteController (page orchestration)
  -> TourSearchFilterService (options + query constraints)
  -> Tour query + eager loading + paginate(12)
  -> listing.blade.php

Header mobile
  -> mobile-tour-discovery.blade.php
  -> link canonical hoặc GET /tim-tour?departure_location=...
```

Trách nhiệm:

- Blade chỉ render view data, không query database;
- controller chỉ chọn page context và gọi service;
- `TourSearchFilterService` là nơi duy nhất chuẩn hóa filter, tạo option và áp constraint;
- view composer phạm vi hẹp cấp cùng một payload cho header và hero; service scoped memoize payload trong một request để không lặp query;
- mỗi response đủ điều kiện chỉ render overlay filter ở hero đầu tiên, tránh lặp khi page có nhiều hero block; các trang chi tiết tour/bài viết không render overlay hoặc host của nó;
- model scopes hiện hành (`published`, `forScope`, `upcomingPublic`) tiếp tục là source of truth.

Không cache HTML theo state người dùng. Nếu cache option filter ở giai đoạn sau, cache key phải chứa scope và được invalidated khi `Tour`, `TourDeparture`, `TourCategory`, `Destination`, `ContentCategory` hoặc `Service` thay đổi.

## 7. Tiêu chí nghiệm thu

- hero đầu tiên của trang khám phá, listing và landing phù hợp render đúng một advanced filter ở desktop, `bottom: 20px`; trang chi tiết tour và chi tiết bài viết/blog không render filter này;
- hero trang chi tiết tour/bài viết không giữ padding desktop dành riêng cho filter, còn cụm khám phá header mobile vẫn hoạt động;
- panel advanced có class ẩn trên mobile; header mobile render selector điểm đi, shortcut thật và rail nổi bật thay thế;
- homepage và `/tim-tour` không render thêm panel advanced trong luồng nội dung;
- chọn đồng thời scope, điểm đi, điểm đến và ngày đi chỉ trả tour thỏa toàn bộ điều kiện;
- các alias TP. Hồ Chí Minh trả cùng một nhóm kết quả;
- option không chứa taxonomy/service không publish hoặc không có tour/service publish;
- không xuất hiện tab Khách sạn/Combo khi chưa có domain thật;
- request sai enum, slug, ngày hiện tại hoặc ngày quá khứ bị validation từ chối;
- pagination giữ query string;
- chọn `Điểm khởi hành` hoặc `Điểm đến` chỉ thay placeholder bằng giá trị trên cùng một dòng, không làm thay đổi chiều cao panel;
- filter desktop và mobile discovery dùng được bằng bàn phím; form desktop vẫn submit được khi JavaScript tắt;
- build frontend và feature test filter đều pass.

## 8. Phần chưa thuộc MVP

- số lượng kết quả động ngay khi chưa submit;
- autocomplete gọi API riêng;
- khoảng ngày, số khách, phòng, hạng vé hoặc combo động;
- thay đổi schema để chuẩn hóa điểm khởi hành thành bảng riêng;
- admin analytics/funnel cho hành vi filter.
- phiên bản advanced filter đầy đủ trên mobile.

Khi cần các phần này, phải bổ sung product/domain contract trước, không mở rộng component bằng dữ liệu hard-code.
