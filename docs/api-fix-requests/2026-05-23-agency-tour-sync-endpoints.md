# Ticket: API Master Data DashBoard Cần Cung Cấp Endpoint Đồng Bộ Tour Hai Chiều

## Tóm Tắt

CMS Haidang Travel đã có endpoint để `API Master Data DashBoard` kéo toàn bộ tour/ngày khởi hành:

```http
GET https://haidangtravel.com/api/v1/agency/tours
Authorization: Bearer {AGENCY_EXPORT_API_TOKEN}
Accept: application/json
```

`API Master Data DashBoard` cần cung cấp các endpoint bên dưới để CMS có thể:

- lấy danh sách tour nguồn cho admin chọn đồng bộ;
- lấy toàn bộ ngày khởi hành của tour nguồn;
- nhận cập nhật ngược khi admin CMS chỉnh tour/ngày khởi hành;
- đồng bộ staff quản lý tour theo email.

Lưu ý: `https://haidangtravel.com/api/tour/agency/sync/tours` và `https://haidangtravel.com/api/tour/agency/sync/startdates` không phải route CMS hiện có. Hai path `/tour/agency/sync/tours` và `/tour/agency/sync/startdates` là endpoint cần mở trên base URL `API Master Data DashBoard`.

## Base URL Và Auth

CMS sẽ gọi sang:

```text
{TOUR_SYNC_API_BASE_URL}
```

Giá trị này cấu hình trong CMS bằng env `TOUR_SYNC_API_BASE_URL`, fallback về `CUSTOMER_LOYALTY_API_BASE_URL` nếu để trống.

Auth hiện tại:

```http
POST {TOUR_SYNC_API_BASE_URL}/DashboardLogin
Content-Type: application/json
```

Request:

```json
{
  "UserName": "staff-sync",
  "Password": "secret"
}
```

Response cần hỗ trợ:

```json
{
  "status": "success",
  "data": {
    "token": "bearer-token",
    "expires_in": 3600
  }
}
```

Các request sau dùng:

```http
Authorization: Bearer {token}
Accept: application/json
```

## Endpoint 1: Danh Sách Tour Nguồn

```http
GET {TOUR_SYNC_API_BASE_URL}/tour/agency/sync/tours?include_inactive=1&page=1&per_page=100
```

Mục đích: CMS dùng để hiển thị danh sách tour nguồn cho admin chọn đồng bộ.

Query:

| Param | Bắt buộc | Mô tả |
| --- | --- | --- |
| `page` | Không | Trang hiện tại. |
| `per_page` | Không | Số bản ghi mỗi trang. |
| `include_inactive` | Không | `1` để trả cả tour ngưng bán/inactive cho mục đích mapping. |

Mỗi item cần có tối thiểu:

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

## Endpoint 2: Danh Sách Ngày Khởi Hành

```http
GET {TOUR_SYNC_API_BASE_URL}/tour/agency/sync/startdates?tour_id=998&future_only=1&page=1&per_page=100
```

Mục đích: CMS lấy ngày khởi hành của tour nguồn để tạo/cập nhật `tour_departures`.

Query:

| Param | Bắt buộc | Mô tả |
| --- | --- | --- |
| `tour_id` | Có | ID tour bên `API Master Data DashBoard`. |
| `future_only` | Không | `1` nếu chỉ lấy ngày hiện tại/tương lai. |
| `page` | Không | Trang hiện tại. |
| `per_page` | Không | Số bản ghi mỗi trang. |

Mỗi item cần có tối thiểu:

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

- `source_tour_id` = `tour_id`;
- `tour_code` = `tour_code`;
- `source_startdate_id` = `startdate_id`.

Phạm vi dữ liệu CMS sẽ kéo từ `API Master Data DashBoard`:

- Tour: tên tour (`tour_name` hoặc `title`), `slug`, `transport`, `duration_days`, `duration_nights`.
- Ngày khởi hành: `startdate` -> ngày khởi hành CMS, `traffic` -> phương tiện ngày khởi hành, `adult_price` -> giá người lớn/base price.
- CMS không dùng các field ngoài phạm vi trên ở chiều kéo về, ví dụ `standard_label`, `startdate_label`, `price`, `available_seat`, `notice_agency`, `status_label`, `enddate`, `start_place`.
- Một dòng ngày khởi hành chỉ được CMS tạo/cập nhật khi có đủ `startdate`, `traffic`, và `adult_price`; thiếu một trong ba field này thì CMS bỏ qua dòng đó.

## Endpoint 3: CMS Push Cập Nhật Về API Master Data DashBoard

```http
POST {TOUR_SYNC_API_BASE_URL}/tour/agency/sync/cms-updates
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

Mục đích: khi admin CMS cập nhật tour hoặc ngày khởi hành đã có mapping `API Master Data DashBoard`, CMS gửi lại toàn bộ thông tin tour và departures để `API Master Data DashBoard` upsert.

Payload mẫu:

```json
{
  "source": "haidangtravel_cms",
  "event": "tour_saved",
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
      "available_seat": 12,
      "available_slots": 12,
      "notice_agency": "Giá áp dụng cho khách lẻ",
      "pricing_note": "Giá áp dụng cho khách lẻ",
      "status": "published",
      "is_featured": false,
      "sort_order": 0,
      "updated_at": "2026-05-23T09:29:00+07:00"
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

## Quy Tắc Xử Lý Phía API Master Data DashBoard

- Upsert tour theo `tour.tour_id`.
- Lưu `tour.cms_tour_id` nếu cần trace ngược về CMS.
- Khi CMS là nguồn dữ liệu, dùng `tour.slug` từ payload để cập nhật slug tour bên `API Master Data DashBoard`.
- Upsert staff quản lý theo `tour.manager_email` hoặc `tour.manager.email`. Email là khóa đồng bộ chính giữa website mới và `API Master Data DashBoard`.
- Nếu staff chưa tồn tại, `API Master Data DashBoard` tạo staff theo `name`, `phone`, `is_active`, `roles`, rồi gán vào tour.
- Upsert ngày khởi hành theo `departures.*.startdate_id` khi có.
- Nếu `startdate_id = null`, `API Master Data DashBoard` tạo ngày khởi hành mới và trả mapping mới cho CMS.
- Với `deleted_startdates`, `API Master Data DashBoard` hủy/xóa/đánh dấu inactive theo `startdate_id`.
- Giá gửi từ CMS chỉ là một loại giá đang bán: CMS lấy `sale_price` nếu có, fallback `base_price`; các alias `price`, `adult_price`, `base_price`, `sale_price` cùng mang giá này.
- Các field giá/số lượng giữ kiểu number.
- Date dùng `YYYY-MM-DD`; datetime dùng ISO 8601.

Response thành công:

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

## Acceptance Criteria

- `GET /tour/agency/sync/tours` trả được danh sách tour nguồn có phân trang.
- `GET /tour/agency/sync/startdates` trả được ngày khởi hành theo `tour_id`.
- `POST /tour/agency/sync/cms-updates` nhận payload CMS và upsert tour/departures idempotent.
- `API Master Data DashBoard` xử lý staff quản lý theo email.
- `API Master Data DashBoard` trả mapping `cms_departure_id -> startdate_id` cho departure mới tạo.
- Lỗi auth trả `401`/`403`; lỗi validation trả `422`; lỗi server trả `5xx` với message JSON.

## Compatibility Risk

- Không đổi contract cũ của CMS export `GET /api/v1/agency/tours`.
- Không yêu cầu CMS mở `/api/tour/agency/sync/*` trên domain `haidangtravel.com`.
- `API Master Data DashBoard` nên xử lý idempotent vì CMS có thể gửi lại cùng payload sau retry/job.
