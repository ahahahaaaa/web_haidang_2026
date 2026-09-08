# Yêu cầu API điểm thưởng và đổi quà

Tài liệu này mô tả contract API cần có để hoàn thiện trang `/diem-thuong` của Haidang Travel FrontStore.

## Mục tiêu

Trang `/diem-thuong?phone=...` tra cứu dữ liệu theo số điện thoại khách hàng và hiển thị theo cấu hình giao diện:

- Thông tin khách hàng và tổng điểm hiện có.
- Danh sách quà có thể đổi và gửi yêu cầu đổi quà chờ duyệt; khối này luôn hiển thị, kể cả khi khách chưa nhập số điện thoại.
- Đơn hàng đã có, chỉ hiển thị khi bật `CUSTOMER_LOYALTY_HISTORY_ENABLED=true`.
- Lịch sử đổi quà, chỉ hiển thị khi bật `CUSTOMER_LOYALTY_HISTORY_ENABLED=true`.

Mặc định `CUSTOMER_LOYALTY_HISTORY_ENABLED=false`, nên FrontStore không render khối đơn hàng và lịch sử đổi quà. API vẫn nên trả đủ `orders` và `redemption_history` nếu có, để có thể bật lại hai khối này bằng cấu hình mà không đổi contract.

Khi chưa có số điện thoại, FrontStore lấy danh sách quà từ endpoint catalog độc lập `CUSTOMER_LOYALTY_GIFTS_PATH` và chỉ hiển thị nút nhắc khách nhập SĐT. Sau khi khách tra cứu thành công, danh sách quà lấy từ lookup theo số điện thoại để tính trạng thái đủ điểm và mở form đổi quà.

Hiện tại API test mới trả `customer` và `point`, nên FrontStore chỉ hiển thị được số điểm. Danh sách quà cần API bổ sung dữ liệu để hoàn thiện flow đổi quà; đơn hàng và lịch sử đổi quà là nhóm dữ liệu tùy chọn theo cấu hình trên.

## Cấu hình và xác thực

Base URL được cấu hình trong Theme Settings, ví dụ:

```text
https://haidangtravel.local:8443/api
```

Tất cả request cần:

```http
Accept: application/json
Content-Type: application/json
Authorization: Bearer <token>
```

### Đăng nhập lấy token

```http
POST /DashboardLogin
```

Khi ghép với base URL test:

```http
POST https://haidangtravel.local:8443/api/DashboardLogin
```

Body phải là JSON, key phân biệt hoa thường:

```json
{
  "UserName": "phucannhien12@gmail.com",
  "Password": "Abc123!@#"
}
```

Lưu ý: password có ký tự `#`, phía gọi API sẽ gửi bằng JSON body, không gửi query string.

Response đề xuất:

```json
{
  "status": "success",
  "data": [
    {
      "token": "eyJ0eXAiOiJKV1QiLCJhbGciOi...",
      "expires_in": 3600
    }
  ]
}
```

FrontStore sẽ tự lưu token, tự dùng lại token còn hạn, và tự đăng nhập lại khi token hết hạn hoặc API trả `401`.

## Danh sách quà khi chưa tra cứu số điện thoại

```http
GET /frontstore/gifts
```

Endpoint này được cấu hình bằng:

```env
CUSTOMER_LOYALTY_GIFTS_METHOD=GET
CUSTOMER_LOYALTY_GIFTS_PATH=/frontstore/gifts
```

Response nên dùng cùng shape `gifts` với endpoint lookup. Khi chưa có số điện thoại, FrontStore chỉ render catalog quà và chưa gửi được yêu cầu đổi quà:

```json
{
  "status": "success",
  "data": {
    "gifts": [
      {
        "id": 5,
        "name": "Voucher giảm giá 50.000 cho mỗi khách",
        "description": "Áp dụng theo điều kiện chương trình.",
        "image_url": "/image/thumb/voucher-50k.jpg",
        "point": 10000,
        "status": "active",
        "expire": "2026-12-31"
      }
    ]
  }
}
```

## Tra cứu khách hàng, điểm, đơn hàng, lịch sử, quà

```http
GET /frontstore/customer-points?phone=0909794299
```

Endpoint này nên trả đầy đủ toàn bộ dữ liệu để trang `/diem-thuong` có thể render xong sau một lần tra cứu. Giao diện FrontStore sẽ tự quyết định ẩn/hiện khối đơn hàng và lịch sử đổi quà theo `CUSTOMER_LOYALTY_HISTORY_ENABLED`.

Khi `CUSTOMER_LOYALTY_HISTORY_ENABLED=true`, phần `orders` trên FrontStore phân trang 10 đơn/trang. Nếu số lượng đơn hàng của một khách không lớn, API có thể trả toàn bộ `orders` và FrontStore sẽ tự chia trang. Nếu số lượng đơn hàng có thể lớn, API nên hỗ trợ phân trang phía server:

```http
GET /frontstore/customer-points?phone=0909794299&orders_page=1&orders_per_page=10
```

Response khi phân trang phía server nên kèm metadata:

```json
{
  "orders": [
    {
      "id": 10001,
      "order_code": "HD001",
      "tour_name": "Tour Đà Lạt 3N2Đ"
    }
  ],
  "orders_meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 35,
    "last_page": 4
  }
}
```

Nếu API chưa hỗ trợ `orders_page`/`orders_per_page`, vẫn cần trả key `orders` là mảng.

### Response tối thiểu để hoàn thiện giao diện

```json
{
  "status": "success",
  "data": {
    "customer": {
      "id": 7050,
      "phone": "0909794299",
      "fullname": "Khách Hàng",
      "email": "khanhduy2610@gmail.com",
      "status": 1,
      "point": 215566,
      "member_card": "",
      "member_card_type": ""
    },
    "orders": [
      {
        "id": 10001,
        "order_code": "HD001",
        "tour_name": "Tour Đà Lạt 3N2Đ",
        "departure_date": "2026-06-20",
        "status": "completed",
        "total": 3500000,
        "earned_points": 1500
      }
    ],
    "redemption_history": [
      {
        "id": 88,
        "gift_title": "Voucher 100k",
        "required_point": 100,
        "amount": 1,
        "approval_status": "pending",
        "point_deducted": false,
        "created_at": "2026-05-14 17:24:00",
        "note": "Đang chờ nhân sự liên hệ xác nhận"
      }
    ],
    "gifts": [
      {
        "id": 5,
        "name": "Voucher giảm giá 50.000 cho mỗi khách",
        "description": "Áp dụng theo điều kiện chương trình.",
        "image_url": "/image/thumb/voucher-50k.jpg",
        "point": 10000,
        "can_redeem": true,
        "status": "active",
        "expire": "2026-12-31"
      }
    ]
  }
}
```

Nếu khách chưa có đơn hàng, lịch sử đổi quà, hoặc chưa có quà phù hợp, API vẫn nên trả mảng rỗng để FrontStore phân biệt được "không có dữ liệu" và "API chưa hỗ trợ phần này". Khi `CUSTOMER_LOYALTY_HISTORY_ENABLED=false`, hai mảng `orders` và `redemption_history` vẫn được phép có trong response nhưng không được render ra giao diện:

```json
{
  "status": "success",
  "data": {
    "customer": {
      "id": 7050,
      "phone": "0909794299",
      "fullname": "Khách Hàng",
      "point": 215566
    },
    "orders": [],
    "redemption_history": [],
    "gifts": []
  }
}
```

## Chi tiết field

### `data.customer`

| Field | Bắt buộc | Kiểu | Ghi chú |
| --- | --- | --- | --- |
| `id` | Có | number/string | Dùng làm `customer_id` khi tạo yêu cầu đổi quà. |
| `phone` | Có | string | Số điện thoại đang tra cứu. |
| `fullname` | Có | string | Tên khách hiển thị trên giao diện. |
| `email` | Không | string | Email khách nếu có. |
| `status` | Không | number/string | Trạng thái khách hàng. |
| `point` | Có | number | Tổng điểm hiện có. |
| `member_card` | Không | string | Mã thẻ thành viên nếu có. |
| `member_card_type` | Không | string | Hạng thẻ nếu có. |

### `data.orders`

| Field | Bắt buộc | Kiểu | Ghi chú |
| --- | --- | --- | --- |
| `id` | Không | number/string | ID nội bộ đơn hàng. |
| `order_code` | Có | string | Mã đơn hiển thị ở cột "Mã". |
| `tour_name` | Có | string | Tên tour hoặc dịch vụ. Có thể dùng `title` hoặc `service_name`. |
| `departure_date` | Không | string | Ngày đi/ngày sử dụng dịch vụ. |
| `status` | Không | string | Trạng thái đơn hàng. |
| `total` | Không | number | Giá trị đơn hàng nếu có. |
| `earned_points` | Không | number | Số điểm phát sinh từ đơn hàng. |

### `data.redemption_history`

| Field | Bắt buộc | Kiểu | Ghi chú |
| --- | --- | --- | --- |
| `id` | Có | number/string | Mã tham chiếu yêu cầu đổi quà. |
| `gift_title` | Có | string | Tên quà đã yêu cầu đổi. |
| `required_point` | Không | number | Số điểm của quà. |
| `amount` | Không | number | Số lượng đổi. |
| `approval_status` | Có | string | Ví dụ `pending`, `approved`, `rejected`, `cancelled`. |
| `point_deducted` | Không | boolean | `false` nếu chỉ mới tạo phiếu chờ duyệt. |
| `created_at` | Có | string | Thời điểm tạo yêu cầu. |
| `note` | Không | string | Ghi chú nếu có. |

### `data.gifts`

| Field | Bắt buộc | Kiểu | Ghi chú |
| --- | --- | --- | --- |
| `id` | Có | number/string | Giá trị này sẽ được gửi lại qua `gift_id` khi khách bấm đổi. |
| `name` | Có | string | Tên quà hiển thị. Có thể dùng `title` hoặc `gift_title`. |
| `description` | Không | string | Mô tả ngắn. |
| `image_url` | Không | string | URL ảnh quà. Nên trả URL đầy đủ hoặc path tương đối như `/image/thumb/voucher-50k.jpg`; FrontStore sẽ ghép path tương đối với domain gốc của Base URL API. Không nên trả URL root-file như `http://127.0.0.1:8282/voucher-50k.jpg` vì endpoint này có thể trả HTML thay vì ảnh. |
| `point` | Có | number | Điểm cần để đổi. Có thể dùng `required_point`. |
| `can_redeem` | Không | boolean | Nếu không trả, FrontStore tự tính theo `customer.point >= gift.point`. |
| `status` | Không | string | Ví dụ `active`, `inactive`, `out_of_stock`. |
| `expire` | Không | string | Ngày hết hạn nếu quà có thời hạn. |

## Tạo phiếu yêu cầu đổi quà chờ duyệt

```http
POST /frontstore/gift-redemption-requests
```

Body bằng `customer_id`:

```json
{
  "customer_id": 7050,
  "gift_id": 5,
  "amount": 1,
  "expire": "2026-12-31"
}
```

Hoặc body bằng `phone` nếu không có `customer_id`:

```json
{
  "phone": "0909794299",
  "gift_id": 5,
  "amount": 1
}
```

Response đề xuất:

```json
{
  "status": "success",
  "message": "Đã tạo yêu cầu đổi quà chờ duyệt.",
  "data": {
    "request": {
      "id": 88,
      "user_id": 7050,
      "amount": 1,
      "status": 0,
      "approval_status": "pending",
      "gift_title": "Voucher 100k",
      "point": 100,
      "required_point": 100,
      "point_deducted": false,
      "created_by": 6682,
      "created_by_name": "Staff Name"
    },
    "customer": {
      "id": 7050,
      "phone": "0909794299",
      "fullname": "Khách Hàng",
      "point": 215566
    }
  }
}
```

Business rule mong muốn:

- Khi khách bấm đổi trên FrontStore, API chỉ tạo phiếu `pending`.
- Chưa trừ điểm ngay nếu nhân sự còn phải gọi lại xác nhận.
- Nếu có trừ điểm ngay, API cần trả rõ `point_deducted: true` và số điểm còn lại.

## Error contract

Khi lỗi, API nên trả JSON nhất quán:

```json
{
  "status": "error",
  "message": "Không tìm thấy khách hàng theo số điện thoại.",
  "errors": {}
}
```

Quy ước:

- `401`: token không hợp lệ hoặc hết hạn. FrontStore sẽ tự đăng nhập lại và retry một lần.
- `404`: không tìm thấy khách hàng hoặc endpoint không tồn tại.
- `422`: dữ liệu gửi lên không hợp lệ.
- `500`: lỗi hệ thống phía API.

FrontStore sẽ hiển thị `message` trực tiếp cho người dùng nếu có.

## Checklist bàn giao phía API

- `POST /DashboardLogin` nhận JSON body với key `UserName` và `Password`.
- `GET /frontstore/gifts` trả danh sách quà public để `/diem-thuong` luôn có catalog quà trước khi khách nhập số điện thoại.
- `GET /frontstore/customer-points?phone=...` trả đủ `customer`, `orders`, `redemption_history`, `gifts`.
- `orders` nên hỗ trợ phân trang 10 đơn/trang nếu dữ liệu lớn; tối thiểu phải trả mảng `orders` để FrontStore tự chia trang.
- Khi chưa có dữ liệu, trả mảng rỗng `[]`, không bỏ hẳn key.
- `gifts[].id` phải dùng được làm `gift_id` trong endpoint đổi quà.
- `gifts[].point` hoặc `gifts[].required_point` phải là số.
- `POST /frontstore/gift-redemption-requests` tạo phiếu chờ duyệt và trả `data.request.id`.
- Tất cả endpoint trả JSON với `status` và `message` rõ ràng.
