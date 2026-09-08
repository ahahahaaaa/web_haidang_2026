# Business Rules

## 1. Base area

`base_area = length * width`

Đây là diện tích cơ sở của footprint công trình, dùng làm nền để suy ra phần lớn các thành phần nếu không có diện tích riêng.

## 2. Nhóm quy đổi diện tích cốt lõi

### 2.1 Tầng trệt
- hệ số mặc định: `100%`
- công thức: `ground_floor = base_area * 1.0`

### 2.2 Sân vườn / sân trước / sân sau
- không có: `0%`
- sân BTCT có móng, đà kiềng, sàn BTCT, hàng rào: `70%`
- sân trước/sau mức thông thường: `50%`

### 2.3 Tầng lửng
- có lửng: `100%`
- thông tầng lửng:
  - ô trống `< 8m2`: `100%`
  - ô trống `> 8m2`: `50%`
- nếu hệ thống UI chỉ hỗ trợ 1 rule đơn giản, mặc định dùng `50%` cho phần thông tầng được nhập.

### 2.4 Lầu / tầng điển hình
- phần có mái che: `100%`
- sân thượng không mái che: `70%`
- sân thượng có mái che: `100%`
- tum có mái che: `100%`

### 2.4.1 Chế độ tự tách khi bật `Có sân thượng`
- dùng khi frontsite chọn checkbox `Có sân thượng`
- tầng trên cùng không tính như một `lầu điển hình` nữa, mà chuyển sang cụm sân thượng / tum
- frontsite hiển thị ô `tum_area` và `terrace_area`, rồi điền sẵn giá trị mặc định theo công thức
- diện tích sân thượng quy đổi: `125%` của `base_area`
- tum mặc định: `30%` của `terrace_rooftop_area`
- sân thượng mặc định: `70%` của `terrace_rooftop_area`
- tổng diện tích tum và sân thượng bắt buộc bằng `terrace_rooftop_area`
- sân thượng không mái che trong chế độ này: `50%`
- mái chỉ tính trên phần tum có mái che
- khách hàng có thể chỉnh tay `tum_area` hoặc `terrace_area`, nhưng hệ thống phải giữ tổng hai giá trị bằng cụm sân thượng quy đổi `125%` sàn
- engine phải giữ số thực khi nhân hệ số; nếu cần làm tròn để hiển thị như mockup thì chỉ làm ở lớp UI, không làm tròn từng bước trong lõi tính toán

### 2.4.2 Chế độ thường khi `Không có sân thượng`
- `typical_floor_count = floors - 1`
- không hiển thị `tum_area`, `terrace_area`, nhãn `Tum`, nhãn `ST`, hoặc khối tum trong minh họa
- mô hình minh họa dùng mái phẳng, đặt ngang trên tầng cao nhất của công trình
- ví dụ:
  - `floors = 1` -> chỉ có `Tầng Trệt`
  - `floors = 2` -> `Tầng 2 (Lầu 1)` + `Tầng Trệt`

### 2.5 Ban công
- ban công `3 mặt thoáng`: `70%`
- lô gia / ban công `1-2 mặt thoáng`: `100%`

## 3. Hệ mái

### Mái bằng BTCT
- không lát gạch chống thấm: `50%`
- có lát gạch chống thấm: `70%`

### Mái tôn
- `30%` theo mặt nghiêng

### Mái ngói kèo sắt hộp mạ kẽm
- `70%` theo mặt nghiêng

### Mái ngói BTCT nghiêng
- `80%` nếu nhà có `1-2 mái`, góc mái chuẩn
- `100%` nếu `> 45 độ` hoặc `>= 3 mái`

### Quy tắc diện tích mái
- nếu có `roof_area` riêng, dùng giá trị đó
- nếu không có `roof_area`, có thể dùng `base_area * roof_slope_factor`
- nếu cấu hình đơn giản, dùng `base_area`

## 4. Móng và nền

### Móng
- móng băng 1 phương hoặc móng cọc: `50%`
- móng băng 2 phương: `65%`
- móng bè: `50%`
- móng đơn: `30%`

### Nền BTCT tầng trệt
- cộng thêm `20%` diện tích trệt nếu có đổ bê tông nền trệt

## 5. Hầm

### Hệ số hầm chính
- sâu `1.5m - 2m`: `150%`
- sâu `2m - 2.5m`: `170%`
- sâu `2.5m - <3m`: `200%`

### Hầm diện tích nhỏ
- đối với hầm có diện tích sử dụng nhỏ hơn `70m2`, có thể áp thêm hệ số phụ `20%` nếu cấu hình công ty yêu cầu

### Diện tích hầm
- nếu có `basement_area` riêng, dùng `basement_area`
- nếu không có, fallback về `base_area`

## 6. Tham số kỹ thuật mở rộng

### Level 2
- loại công trình
- loại đất nền
- roof_slope_factor
- roof_count
- balcony_open_sides
- basement_area
- vị trí xây dựng
- điều kiện thi công hẻm / mặt tiền
- finish_package
- elevator / pool / special utilities

### Level 3
- soil_class
- groundwater_level
- pile_type
- pile_depth
- pile_count
- retaining_wall
- floor_height
- long_span_structure
- façade complexity
- contract scope flags
- VAT, discount, surcharge
- effective_date
- formula_version

## 7. Nguyên tắc giá

Tách riêng:
- **diện tích quy đổi**
- **đơn giá theo gói**

Công thức chuẩn:

`amount = total_converted_area * unit_price`

## 8. Cảnh báo nghiệp vụ

- Level 1 là dự toán nhanh, không nên coi là dự toán thi công cuối cùng.
- Hệ số móng và hầm có thể thay đổi mạnh theo địa chất và biện pháp thi công.
- Mọi thay đổi hệ số phải đi qua config hoặc admin publish workflow.
