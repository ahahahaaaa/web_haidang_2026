# Admin Specification

## Mục tiêu

Cho phép quản trị viên cấu hình và publish toàn bộ hệ thống dự toán mà không cần sửa code.

## Module admin

### 1. Formula Sets
Quản lý bộ công thức:
- code
- name
- description
- status: draft / published / archived
- level support
- effective date
- published at
- published by
- version

### 2. Formula Items
Quản lý từng rule:
- section
- key
- label
- component type
- coefficient
- fixed/conditional
- expression
- sort order
- visibility by level
- notes

### 3. Price Sets
Quản lý đơn giá:
- package key
- package label
- unit price
- currency
- level support
- effective date
- status

### 4. Field Catalog
Quản lý field:
- key
- label
- type
- options
- level visibility
- validation rules
- help text

### 5. Preview / Simulator
Nhập sample input để xem:
- breakdown
- tổng diện tích quy đổi
- bảng thành tiền
- formula version dùng để tính

### 6. Publish Workflow
- draft
- review
- publish
- rollback

## Màn hình admin đề xuất

1. Danh sách formula sets
2. Chi tiết formula set + items
3. Danh sách price sets
4. Danh sách field catalog
5. Màn preview/simulator
6. Import/export JSON
7. Audit log

## Quy tắc quản trị

- không cho publish nếu thiếu formula item bắt buộc
- không cho publish nếu không có price set tương ứng
- key phải unique trong phạm vi formula set
- xóa mềm thay vì xóa cứng đối với config đã dùng
- mọi request lead phải lưu formula_version và price_version

