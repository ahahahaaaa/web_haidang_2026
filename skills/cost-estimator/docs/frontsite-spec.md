# Frontsite Specification

## Mục tiêu

Tạo trang tính dự toán chi phí để khách hàng nhập thông số và xem ngay:
- diện tích quy đổi
- breakdown từng hạng mục
- thành tiền theo gói
- form nhận báo giá

## Chế độ level

### Level 1
Hiển thị:
- chiều dài
- chiều rộng
- số tầng
- có sân thượng
- sân vườn
- có lửng / không
- diện tích thông tầng
- diện tích tum
- diện tích sân thượng
- loại mái
- loại móng
- có đổ bê tông nền trệt
- có hầm / loại hầm
- gói hoàn thiện

### Level 2
Hiển thị thêm:
- loại công trình
- ban công diện tích + số mặt thoáng
- sân trước / sân sau
- basement_area riêng
- roof_slope_factor
- roof_count
- location_zone
- access_condition
- soil_type
- elevator
- pool

### Level 3
Hiển thị thêm:
- pile_type
- pile_depth
- pile_count
- groundwater_level
- floor_height
- long_span_structure
- contract_scope
- VAT / discount / surcharge

## Thành phần giao diện

### 1. EstimatorForm
- chia theo group
- field visibility theo level
- support live recalculation

### 2. EstimatorBreakdownTable
Hiển thị:
- tên hạng mục
- diện tích
- hệ số
- diện tích quy đổi
- ghi chú

### 3. EstimatorPricingTable
Hiển thị:
- tên gói
- đơn giá/m2
- tổng diện tích quy đổi
- thành tiền

### 4. EstimatorIllustration
- mô hình nhà minh họa
- highlight theo option nếu có

### 5. EstimateLeadForm
- họ tên
- email
- điện thoại
- ghi chú
- hidden raw estimate json
- hidden formula version
- hidden total converted area

## Yêu cầu hành vi

- thay đổi field phải tính lại ngay
- breakdown phải đồng bộ với pricing
- không submit nếu input chính chưa hợp lệ
- phải có chế độ loading cho tính toán qua API
- nếu tính cục bộ, vẫn cần serialize kết quả để gửi cùng form lead
- nếu bật `Có sân thượng`, UI phải hiển thị `tum_area` và `terrace_area`, tự điền mặc định theo công thức, và giữ cho tổng hai giá trị này luôn bằng diện tích sân thượng quy đổi `125%` diện tích sàn

### Quy tắc riêng cho case `Có sân thượng`
- `has_terrace = true` là một mode tính riêng của engine, không chỉ là trạng thái hiển thị field
- khi bật mode này:
  - tầng trên cùng không render thành một dòng `Lầu điển hình`
  - hệ thống sinh cụm breakdown gồm `Tum`, `Sân thượng`, `Hệ mái`
  - frontsite phải dùng các key dẫn xuất từ formula để render breakdown và mô hình minh họa
  - frontsite phải hiển thị ô nhập `Tum` và `Sân thượng` để khách hàng có thể điều chỉnh tỷ trọng
- key dẫn xuất chuẩn:
  - `typical_floor_count`
  - `terrace_rooftop_area`
  - `terrace_default_tum_area`
  - `terrace_default_area`
  - `tum_effective_area`
  - `terrace_effective_area`
  - `roof_effective_area`
- tỷ lệ mặc định:
  - `terrace_rooftop_area = base_area * 1.25`
  - `terrace_default_tum_area = terrace_rooftop_area * 0.3`
  - `terrace_default_area = terrace_rooftop_area - terrace_default_tum_area`
  - `roof_effective_area = tum_effective_area` nếu không có `roof_area` riêng
- ràng buộc bắt buộc:
  - `tum_effective_area + terrace_effective_area = terrace_rooftop_area`
- hệ số mặc định:
  - `Tum`: `100%`
  - `Sân thượng không mái che`: `50%`
  - `Sân thượng có mái che`: `100%`
  - `Hệ mái`: theo `roof_type`
- frontsite nên hiển thị thêm dòng nhắc về tổng diện tích tum và sân thượng để người dùng hiểu đây là một cụm phân bổ trong sân thượng quy đổi `125%` sàn
- nếu UI cần làm tròn số để dễ đọc, việc làm tròn chỉ áp dụng ở lớp hiển thị; engine vẫn giữ số thực để tránh lệch tổng

### Quy tắc riêng cho case `Không có sân thượng`
- `has_terrace = false` phải ẩn hoàn toàn các field `tum_area` và `terrace_area`
- mô hình minh họa không được render nhãn `Tum`, `ST` hoặc khối SVG riêng của tum
- số tầng hiển thị vẫn phải bám theo `typical_floor_count = floors - 1`
- với nhà `2 tầng`, mô hình phải hiển thị đủ `Tầng 2 (Lầu 1)` và `Tầng Trệt`
- mái của case này là mái phẳng: một thanh ngang đỏ nằm đúng trên box tầng trên cùng

## Gợi ý state shape

```json
{
  "level": "level_1",
  "input": {},
  "derived": {
    "base_area": 0
  },
  "result": {
    "components": [],
    "totals": {
      "converted_area": 0
    },
    "pricing": {
      "packages": []
    }
  }
}
```
