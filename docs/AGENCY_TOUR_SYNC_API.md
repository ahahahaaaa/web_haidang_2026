# API Master Data DashBoard Tour Sync API

Tài liệu này mô tả cơ chế đồng bộ hai chiều giữa Haidang Travel CMS và hệ `API Master Data DashBoard` cho dữ liệu `Tour` và `tour_departures`.

Tên hiển thị trong admin và tài liệu vận hành là `API Master Data DashBoard`. Một số route, biến môi trường, class và field nội bộ vẫn giữ chữ `agency` để không phá contract đồng bộ đã triển khai.

## 0. Route Chính Xác

Các endpoint dưới đây cần phân biệt rõ theo hướng gọi:

| Hướng | Bên mở endpoint | URL/path | Trạng thái trong CMS |
| --- | --- | --- | --- |
| `API Master Data DashBoard` gọi CMS để lấy toàn bộ tour/ngày khởi hành | CMS Haidang Travel | `GET {CMS_BASE_URL}/api/v1/agency/tours` | Đã mở trong `routes/api.php`. |
| CMS gọi `API Master Data DashBoard` để lấy danh sách tour nguồn | `API Master Data DashBoard` API | `GET {TOUR_SYNC_API_BASE_URL}/tour/agency/sync/tours` | CMS chỉ là client gọi sang `API Master Data DashBoard`. |
| CMS gọi `API Master Data DashBoard` để lấy ngày khởi hành | `API Master Data DashBoard` API | `GET {TOUR_SYNC_API_BASE_URL}/tour/agency/sync/startdates` | CMS chỉ là client gọi sang `API Master Data DashBoard`. |
| CMS push cập nhật tour/ngày khởi hành về `API Master Data DashBoard` | `API Master Data DashBoard` API | `POST {TOUR_SYNC_API_BASE_URL}/tour/agency/sync/cms-updates` | CMS chỉ là client gọi sang `API Master Data DashBoard`. |

Hai URL dạng `https://haidangtravel.com/api/tour/agency/sync/tours` và `https://haidangtravel.com/api/tour/agency/sync/startdates` hiện không phải route inbound của CMS. Hai path `/tour/agency/sync/tours` và `/tour/agency/sync/startdates` là endpoint mà `API Master Data DashBoard` cần cung cấp để CMS gọi sang.

## 1. Luồng Đồng Bộ

- `API Master Data DashBoard` -> CMS: CMS dùng API `API Master Data DashBoard` hiện có để lấy danh sách tour nguồn và ngày khởi hành.
- CMS -> `API Master Data DashBoard` dạng pull: `API Master Data DashBoard` gọi API CMS để lấy toàn bộ tour và toàn bộ ngày khởi hành.
- CMS -> `API Master Data DashBoard` dạng push: sau khi admin CMS lưu tour/ngày khởi hành đã có mapping `API Master Data DashBoard`, CMS gửi payload cập nhật ngược về `API Master Data DashBoard`.

CMS chỉ push ngược những tour đã có mapping với `API Master Data DashBoard`, tức là đã từng đồng bộ từ `API Master Data DashBoard` về CMS hoặc đã có `source_tour_id` trong `tour_departure_sync_states`.

## 2. API Master Data DashBoard Gọi CMS Để Lấy Tour

Endpoint:

```http
GET {CMS_BASE_URL}/api/v1/agency/tours
Authorization: Bearer {AGENCY_EXPORT_API_TOKEN}
Accept: application/json
```

Query params:

| Param | Bắt buộc | Mô tả |
| --- | --- | --- |
| `page` | Không | Trang hiện tại, mặc định `1`. |
| `per_page` | Không | Số tour mỗi trang, mặc định `100`, tối đa `500`. |
| `tour_id` | Không | Lọc một tour CMS cụ thể. |
| `updated_since` | Không | ISO datetime. Trả tour có `tours.updated_at` hoặc `tour_departures.updated_at` sau mốc này. |

Response:

```json
{
  "data": [
    {
      "tour_id": 123,
      "title": "Tour Đà Lạt mùa hoa",
      "slug": "tour-da-lat-mua-hoa",
      "scope": "domestic",
      "status": "published",
      "transport": "Máy bay",
      "standard_label": "Khách sạn 4 sao",
      "duration_days": 4,
      "duration_nights": 3,
      "price": 5490000,
      "base_price": 5490000,
      "sale_price": 5490000,
      "manager_email": "sale@example.com",
      "manager": {
        "cms_user_id": 12,
        "name": "Nguyễn Sale",
        "email": "sale@example.com",
        "phone": "0909 111 222",
        "is_active": true,
        "roles": ["sale"]
      },
      "published_at": "2026-05-20T10:00:00+07:00",
      "updated_at": "2026-05-22T09:00:00+07:00",
      "departures": [
        {
          "departure_id": 456,
          "tour_id": 123,
          "departure_date": "2026-06-20",
          "return_date": "2026-06-24",
          "departure_location": "TP. Hồ Chí Minh",
          "transport_label": "Xe giường nằm",
          "standard_label": "Khách sạn 3 sao",
          "adult_price": 5490000,
          "price": 5490000,
          "base_price": 5490000,
          "sale_price": 5490000,
          "available_slots": 12,
          "pricing_note": "Giá áp dụng cho khách lẻ",
          "status": "published",
          "is_featured": false,
          "sort_order": 0,
          "created_at": "2026-05-21T10:00:00+07:00",
          "updated_at": "2026-05-21T10:00:00+07:00"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 100,
    "total": 1
  }
}
```

`API Master Data DashBoard` nên dùng `tour_id` và `departure_id` của CMS làm khóa source để upsert.

Giá CMS gửi ra ở chiều CMS -> `API Master Data DashBoard` là một giá duy nhất: giá đang bán của CMS. CMS lấy `sale_price` nếu có, nếu chưa có thì fallback `base_price`. Các field giá legacy như `price`, `adult_price`, `base_price`, `sale_price` cùng mang giá đang bán này để tránh hiểu thành nhiều loại giá khác nhau.

Nếu `manager_email` có giá trị, `API Master Data DashBoard` nên dùng email này làm khóa chính để tìm hoặc tạo staff phụ trách tour. `manager.cms_user_id` chỉ là khóa tham chiếu từ CMS, không nên thay thế email làm khóa đồng bộ staff.

## 3. CMS Gọi API Master Data DashBoard Để Lấy Tour Nguồn

CMS đang dùng các endpoint này để admin chọn tour nguồn và đồng bộ ngày khởi hành về CMS.

Login:

```http
POST {TOUR_SYNC_API_BASE_URL}/DashboardLogin
Content-Type: application/json
```

Payload:

```json
{
  "UserName": "staff-sync",
  "Password": "secret"
}
```

Response yêu cầu:

```json
{
  "status": "success",
  "data": {
    "token": "bearer-token",
    "expires_in": 3600
  }
}
```

Danh sách tour nguồn:

```http
GET {TOUR_SYNC_API_BASE_URL}/tour/agency/sync/tours?include_inactive=1&page=1&per_page=100
Authorization: Bearer {token}
Accept: application/json
```

Mỗi tour nên trả:

```json
{
  "tour_id": 998,
  "tour_name": "Tour Đà Lạt mùa hoa",
  "title": "Tour Đà Lạt mùa hoa",
  "tour_code": "HDL998",
  "slug": "tour-da-lat-mua-hoa",
  "transport": "Máy bay",
  "duration_days": 4,
  "duration_nights": 3,
  "status": "active",
  "status_label": "Mở bán"
}
```

Danh sách ngày khởi hành:

```http
GET {TOUR_SYNC_API_BASE_URL}/tour/agency/sync/startdates?tour_id=998&future_only=1&page=1&per_page=100
Authorization: Bearer {token}
Accept: application/json
```

Mỗi ngày khởi hành nên trả:

```json
{
  "startdate_id": 23288,
  "tour_id": 998,
  "tour_code": "HDL998",
  "startdate": "2026-06-20",
  "traffic": "Xe giường nằm",
  "adult_price": 5990000
}
```

CMS lưu mapping:

- `source_tour_id` = `tour_id` từ `API Master Data DashBoard`.
- `tour_code` = `tour_code` từ `API Master Data DashBoard`.
- `source_startdate_id` = `startdate_id` từ `API Master Data DashBoard`.
- Chiều `API Master Data DashBoard` -> CMS đồng bộ tour-level: tên tour (`tour_name` hoặc `title`), `slug`, `transport`, `duration_days`, `duration_nights`.
- Chiều `API Master Data DashBoard` -> CMS chỉ đồng bộ startdate-level: `startdate` -> `departure_date`, `traffic` -> `transport_label`, `adult_price` -> `base_price`.
- Các field khác từ `API Master Data DashBoard` nếu có trong response sẽ bị CMS bỏ qua ở chiều kéo về, gồm `standard_label`, `startdate_label`, `price`, `available_seat`, `notice_agency`, `status_label`, `enddate`, `start_place`.
- Nếu `API Master Data DashBoard` không trả `transport`, `duration_days`, hoặc `duration_nights` ở tour-level, CMS giữ nguyên dữ liệu đang có ở field đó.
- Một dòng ngày khởi hành chỉ được CMS tạo/cập nhật khi có đủ `startdate`, `traffic`, và `adult_price`; thiếu một trong ba field này thì CMS bỏ qua dòng đó để tránh ghi dữ liệu ngày đi không đủ định danh.

## 4. CMS Push Ngược Sang API Master Data DashBoard Khi Admin Cập Nhật

Endpoint `API Master Data DashBoard` cần cung cấp:

```http
POST {TOUR_SYNC_API_BASE_URL}/tour/agency/sync/cms-updates
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

CMS gọi endpoint này sau khi admin lưu tour trong CMS. Job chạy sau response, nên lỗi từ `API Master Data DashBoard` sẽ được report nhưng không làm mất thao tác lưu của admin.

Payload:

```json
{
  "source": "haidangtravel_cms",
  "event": "tour_saved",
  "client_sync_log_id": "2d5bc3f2-5c5b-41c3-8fb3-5e2b33b0d5f7",
  "payload_schema_version": "cms_legacy_agency_v3",
  "sync_mode": "full",
  "synced_at": "2026-05-23T09:30:00+07:00",
  "tour": {
    "cms_tour_id": 123,
    "tour_id": 998,
    "tour_code": "HDL998",
    "tour_name": "Tour Đà Lạt mùa hoa cập nhật",
    "title": "Tour Đà Lạt mùa hoa cập nhật",
    "slug": "tour-da-lat-mua-hoa",
    "status": "published",
    "scope": "domestic",
    "isOutbound": 0,
    "tour_type": 0,
    "manager_email": "sale@example.com",
    "manager": {
      "cms_user_id": 12,
      "name": "Nguyễn Sale",
      "email": "sale@example.com",
      "phone": "0909 111 222",
      "is_active": true,
      "roles": ["sale"]
    },
    "excerpt": "Mô tả ngắn đã cập nhật.",
    "seodescription": "Mô tả ngắn đã cập nhật.",
    "departure_location": "TP. Hồ Chí Minh",
    "transport": "Xe",
    "duration_days": 4,
    "duration_nights": 3,
    "standard_label": "Khách sạn 3 sao",
    "price": 5490000,
    "base_price": 5490000,
    "sale_price": 5490000,
    "sort_order": 0,
    "published_at": "2026-05-20T10:00:00+07:00",
    "updated_at": "2026-05-23T09:29:00+07:00"
  },
  "departures": [
    {
      "cms_departure_id": 456,
      "tour_id": 998,
      "tour_code": "HDL998",
      "startdate_id": 23288,
      "startdate": "2026-06-20",
      "enddate": "2026-06-24",
      "departure_date": "2026-06-20",
      "return_date": "2026-06-24",
      "departure_location": "TP. Hồ Chí Minh",
      "traffic": "Xe giường nằm",
      "transport_label": "Xe giường nằm",
      "startdate_label": "Khách sạn 3 sao",
      "standard_label": "Khách sạn 3 sao",
      "adult_price": 5490000,
      "price": 5490000,
      "base_price": 5490000,
      "sale_price": 5490000,
      "seat": 12,
      "total_seat": 12,
      "save_agency": 12,
      "available_seat": 12,
      "available_slots": 12,
      "notice_agency": "Giá áp dụng cho khách lẻ",
      "pricing_note": "Giá áp dụng cho khách lẻ",
      "status": "published",
      "status_label": "published",
      "is_agency": 1,
      "is_featured": false,
      "sort_order": 0,
      "updated_at": "2026-05-23T09:29:00+07:00"
    },
    {
      "cms_departure_id": 789,
      "tour_id": 998,
      "tour_code": "HDL998",
      "startdate_id": null,
      "startdate": "2026-07-10",
      "price": 7490000,
      "adult_price": 7490000,
      "seat": 8,
      "total_seat": 8,
      "available_seat": 8,
      "available_slots": 8,
      "save_agency": 8,
      "status": "scheduled",
      "status_label": "scheduled",
      "is_agency": 1
    }
  ],
  "deleted_startdates": [
    {
      "cms_tour_id": 123,
      "cms_departure_id": 777,
      "tour_id": 998,
      "tour_code": "HDL998",
      "startdate_id": 23290,
      "deleted_at": "2026-05-23T09:30:00+07:00"
    }
  ]
}
```

Quy tắc xử lý phía `API Master Data DashBoard`:

- Upsert tour theo `tour.tour_id`; nếu cần trace ngược CMS, lưu thêm `tour.cms_tour_id`.
- Khi CMS là nguồn dữ liệu, `tour.slug` là slug hiện tại của tour trên CMS và `API Master Data DashBoard` cần cập nhật slug tour đích theo giá trị này.
- Upsert hoặc map staff quản lý theo `tour.manager_email` / `tour.manager.email`. Email là khóa đồng bộ chính cho staff giữa website mới và hệ `API Master Data DashBoard`.
- Nếu chưa có staff theo email này, `API Master Data DashBoard` có thể tạo staff mới với `name`, `phone`, `is_active`, `roles`, rồi gán làm người phụ trách tour.
- Nếu `manager_email = null`, `API Master Data DashBoard` nên giữ nguyên người phụ trách hiện tại hoặc đưa tour về trạng thái chưa gán tùy chính sách nội bộ.
- `sync_mode=full` là chế độ đồng bộ đầy đủ khi lưu tour hoặc chọn map thủ công; CMS có thể gửi departure chưa có `startdate_id` để `API Master Data DashBoard` tạo mới và trả mapping.
- Upsert ngày khởi hành theo `departures.*.startdate_id` khi có.
- Nếu `startdate_id = null`, tạo ngày khởi hành mới bên `API Master Data DashBoard` và trả mapping mới cho CMS.
- Với `deleted_startdates`, `API Master Data DashBoard` nên hủy/xóa/đánh dấu inactive bản ghi `startdate_id` tương ứng theo chính sách nội bộ.
- Giá gửi từ CMS sang `API Master Data DashBoard` chỉ là một loại giá đang bán. CMS lấy `sale_price` nếu có, fallback `base_price`; các alias legacy `price`, `adult_price`, `base_price`, `sale_price` cùng mang giá này.
- Các field số như `price`, `adult_price`, `available_seat`, `base_price`, `sale_price`, `available_slots` phải giữ kiểu number.
- Các field ngày dùng `YYYY-MM-DD`; datetime dùng ISO 8601.
- CMS giới hạn chuỗi gửi sang `API Master Data DashBoard` tối đa 255 ký tự cho các field map vào cột legacy `varchar`, gồm `tour_name`, `title`, `slug`, `excerpt`, `seodescription`, `transport`, `standard_label`, `departure_location`, `traffic`, `notice_agency`, `pricing_note`.
- CMS gửi `client_sync_log_id`, `payload_schema_version` và `sync_mode` ở top-level để đối chiếu log hai bên. Bản payload hiện tại là `cms_legacy_agency_v3`.
- CMS không gửi các optional string khi giá trị là `null` hoặc chuỗi rỗng, ví dụ `tour.manager.phone`, `tour.excerpt`, `tour.seodescription`. Điều này tránh lỗi validator legacy dạng `sometimes|string` nhưng field lại có giá trị `null`.
- CMS gửi đồng thời `scope` và cặp legacy `isOutbound`/`tour_type`: `international` -> `1`, `domestic`/`group` -> `0`.
- CMS chuẩn hóa tour status trước khi push: `published` -> `published`, `draft` -> `draft`, `archived` -> `inactive`.
- CMS chuẩn hóa departure status trước khi push: `published`/`scheduled` giữ trạng thái mở, `sold_out`/`finished` -> `closed`, `cancelled` -> `cancelled`. Các departure đã đóng/hủy gửi `is_agency = 0`; departure mở gửi `is_agency = 1`.
- CMS gửi thêm `seat`, `total_seat`, `save_agency` cùng giá trị với `available_slots` để khớp các cột ghế legacy của `start_dates`.
- Với departure thiếu field bắt buộc của API legacy, CMS fallback trước khi gửi: `return_date` được suy ra từ `departure_date + duration_days - 1`, `traffic` lấy từ tour-level `transport`, giá đang bán lấy từ departure `sale_price` hoặc `base_price`, nếu vẫn thiếu thì fallback giá tour-level.
- Nếu một departure vẫn thiếu dữ liệu tối thiểu sau fallback (`departure_date`, `return_date`, `traffic`, giá đang bán, hoặc `status`), CMS bỏ qua departure đó trong payload và ghi log `tour_sync.master_data_dashboard.departure_skipped` để không làm hỏng toàn bộ request đồng bộ tour.

Response `API Master Data DashBoard` cần trả:

```json
{
  "status": "success",
  "data": {
    "tour_id": 998,
    "tour_code": "HDL998",
    "startdates": [
      {
        "cms_departure_id": 456,
        "startdate_id": 23288
      },
      {
        "cms_departure_id": 789,
        "startdate_id": 30001
      }
    ]
  }
}
```

CMS dùng response này để cập nhật `tour_departure_sync_states` cho các ngày mới tạo ở `API Master Data DashBoard`.

## 5. Cấu Hình CMS

Các biến môi trường liên quan:

```env
CUSTOMER_LOYALTY_API_BASE_URL=https://agency.example.test/api
CUSTOMER_LOYALTY_API_USERNAME=
CUSTOMER_LOYALTY_API_PASSWORD=
CUSTOMER_LOYALTY_API_TOKEN=

TOUR_SYNC_API_BASE_URL=https://agency.example.test/api
TOUR_SYNC_API_USERNAME=
TOUR_SYNC_API_PASSWORD=
TOUR_SYNC_API_TOKEN=
TOUR_SYNC_LOGIN_PATH=/DashboardLogin
TOUR_SYNC_TOURS_PATH=/tour/agency/sync/tours
TOUR_SYNC_STARTDATES_PATH=/tour/agency/sync/startdates
TOUR_SYNC_PUSH_ENABLED=true
TOUR_SYNC_PUSH_PATH=/tour/agency/sync/cms-updates
TOUR_SYNC_PUSH_QUEUE=default

AGENCY_EXPORT_API_TOKEN=
```

`TOUR_SYNC_API_BASE_URL` là base URL `API Master Data DashBoard` mà CMS gọi sang. Nếu biến này trống, CMS fallback về `CUSTOMER_LOYALTY_API_BASE_URL`. Trong admin CMS, field `customer_loyalty_api_base_url` ở Theme Settings vẫn có ưu tiên cao hơn env fallback.

`CUSTOMER_LOYALTY_API_*` hiện được CMS dùng chung cho login/token `API Master Data DashBoard`. Nếu muốn tách riêng credential tour sync, set `TOUR_SYNC_API_USERNAME`, `TOUR_SYNC_API_PASSWORD`, và `TOUR_SYNC_API_TOKEN`.

## 6. Lưu Ý Vận Hành

- Thiết kế chi tiết màn hình hàng chờ, lifecycle trạng thái và cách chạy thủ công nằm ở `docs/AGENCY_TOUR_SYNC_QUEUE_DESIGN.md`.
- Push ngược chỉ chạy cho tour đã có mapping `API Master Data DashBoard`.
- Trong admin `/admin/tours`, nút `Chọn tour đồng bộ` mở modal có 2 hướng:
  - Mặc định chọn chiều `CMS HaidangTravel -> API Master Data DashBoard`.
  - `CMS HaidangTravel -> API Master Data DashBoard`: chọn tour đích `API Master Data DashBoard`, lưu mapping tour theo `tour_id`, rồi đưa job push dữ liệu CMS sang `API Master Data DashBoard` vào hàng đợi.
  - `API Master Data DashBoard -> CMS HaidangTravel`: chọn tour nguồn `API Master Data DashBoard` để kéo tên tour, slug, phương tiện, thời gian tour; với ngày khởi hành chỉ kéo giá người lớn, phương tiện và ngày khởi hành về CMS.
- Hàng chờ push được quản lý tại `/admin/tours/agency-sync-queue`. Trang này hiển thị trạng thái `pending`, `running`, `succeeded`, `failed`, `skipped`, lỗi cuối cùng và cho phép chạy ngay từng hàng chờ hoặc toàn bộ hàng chờ `pending`.
- Nếu dùng `QUEUE_CONNECTION=database`, job pending chỉ tự chạy khi có worker như `php artisan queue:work --queue=default` hoặc queue theo `TOUR_SYNC_PUSH_QUEUE`. Nút chạy ngay trong CMS gọi API `API Master Data DashBoard` trực tiếp trong request hiện tại, không cần đợi worker.
- Pull từ `API Master Data DashBoard` về CMS không tự echo push ngược để tránh vòng lặp đồng bộ.
- Nếu endpoint push của `API Master Data DashBoard` lỗi, CMS vẫn lưu tour thành công và ghi lỗi vào log/report.
- `API Master Data DashBoard` nên xử lý idempotent vì CMS có thể gửi lại toàn bộ tour và toàn bộ departure hiện có sau mỗi lần admin lưu.
- `API Master Data DashBoard` không nên tin `title`/`tour_name` là khóa duy nhất; dùng `tour_id`, `startdate_id`, `cms_tour_id`, `cms_departure_id`.
