# AGENTS.md

Project: Haidang Travel CMS  
Scope: Frontsite travel site + admin CMS + import/bootstrap data + manual SEO basics  
Primary environment: Codex / IDE agent / repo-based workflow

---

## 1. Mission

Repository này phục vụ website travel cho `haidangtravel.com`, không còn là hệ construction/estimator cũ.

Agent phải tối ưu cho:

- consistency,
- safe incremental changes,
- clear CMS boundaries,
- maintainable Laravel + Livewire code,
- travel conversion readiness,
- deterministic bootstrap data.

---

## 2. Agent operating principles

### Required behavior

Agent phải:

- đọc file này trước khi sửa,
- xác minh codebase hiện tại thay vì dựa vào lịch sử repo,
- giữ diff gọn và reviewable,
- dùng tiếng Việt có dấu cho mọi copy hiển thị cho người dùng,
- xem tiếng Việt là ngôn ngữ mặc định của runtime; `APP_LOCALE` và fallback locale dùng `vi`, Faker dùng `vi_VN`, và Open Graph public dùng `og:locale` là `vi`,
- tôn trọng domain travel đang có: tours, services, blog, inquiries, landing pages.

### Rich text and media rules

- Dùng shared editor wrapper cho các field rich text trong admin
- Field meta, excerpt, summary ưu tiên lưu text sạch, không để HTML noise
- Field body dài mới dùng rich text rendering đầy đủ
- Media dùng chung qua media library/popup hiện có, không tạo flow upload riêng lẻ nếu không cần
- Các dropzone admin phải tương thích flow chọn ảnh từ thư viện

### Seeder and DB safety

- Seeder/bootstrap không được tự wipe, truncate, reset DB
- Import snapshot chỉ là bootstrap dữ liệu, không phải sync phá hủy dữ liệu hiện có
- Khi sửa migration cũ, chỉ thay default/metadata nếu điều đó giúp fresh install phản ánh state travel hiện tại

### Forbidden behavior

Agent không được:

- thêm lại theme `phong_thanh_dat`,
- thêm lại estimator, project pages construction, package pages, hoặc SEO AI flow cũ vào runtime,
- bịa business rule hoặc nội dung travel không có cơ sở,
- trộn presentation logic nặng vào controller/service domain,
- bỏ qua validation, permission, hoặc publish state.

---

## 3. Instruction chain and specialization

Tài liệu liên quan:

- `AGENTS.md`
- `docs/FRONTSITE_AGENT.md`
- `docs/BACKEND_AGENT.md`
- `docs/ADMIN_CMS.md`
- `docs/DESIGN_SYSTEM.md`
- `docs/TECHNICAL_REQUIREMENTS.md`
- `docs/TOUR_SITEMAP_BLOCKS.md`
- `docs/PROJECT_START_GUIDE.md`

Áp dụng:

- dùng root `AGENTS.md` cho repo-wide rules,
- dùng `FRONTSITE_AGENT.md` cho theme/view/layout/CTA/frontsite UX,
- dùng `ADMIN_CMS.md` khi task chạm tới admin CMS, sidebar, list page, create/edit page, table filter, hoặc nhịp spacing của giao diện quản trị,
- dùng `TOUR_SITEMAP_BLOCKS.md` khi task chạm tới tour IA, sitemap, taxonomy pages, hoặc block order của family tour,
- dùng `BACKEND_AGENT.md` cho route/controller/service/admin/domain validation,
- dùng `DESIGN_SYSTEM.md` cho visual direction,
- dùng `TECHNICAL_REQUIREMENTS.md` cho boundaries kỹ thuật.

Nếu có xung đột:

1. direct user instruction
2. file gần task nhất
3. `docs/AGENTS.md`
4. root `AGENTS.md`

---

## 4. Product intent

Frontsite phải thể hiện:

- uy tín thương hiệu,
- danh mục tour rõ ràng,
- dịch vụ du lịch dễ hiểu,
- bài viết hỗ trợ chuyển đổi,
- contact và CTA đặt tour/tư vấn nhất quán.

Page types chính:

- homepage
- about
- domestic tour landing
- international tour landing
- group tour landing
- tour category hub
- destination hub
- region hub
- country hub
- tour detail
- service listing
- service detail
- blog listing
- blog detail
- contact

---

## 5. Runtime boundaries

Runtime hiện tại nên xoay quanh:

- `Tour`
- `Service`
- `BlogPost`
- `LandingPage`
- `TravelInquiry`
- `Menu`
- `SiteSetting`

Deprecated:

- estimator runtime
- construction project runtime
- package landing runtime
- SEO AI admin/runtime
- catch-all SEO slug routes cũ

Không dùng các path deprecated làm nền cho tính năng mới.

---

## 6. Development workflow

Với task không nhỏ:

1. Inspect current structure
2. Find active runtime path
3. Identify exact files to edit
4. Implement minimal safe diff
5. Validate
6. Summarize remaining risks

---

## 7. Validation checklist

### Frontend

- theme `haidangtravel` render được
- route public không gọi view/theme cũ
- mobile/desktop đều usable
- heading structure và CTA hợp lý
- metadata cơ bản tồn tại

### Backend

- routes resolve
- validation tồn tại cho form public/admin
- database writes explicit
- service/controller boundaries hợp lý
- sitemap không chứa route deprecated

---

## 8. Content and SEO priorities

Ưu tiên:

1. clarity
2. trust
3. conversion
4. editorial maintainability
5. deterministic bootstrap data

Giữ:

- one H1 per page
- slugs rõ ràng
- metadata fields
- Organization / Breadcrumb / Article / Service / Product schema khi phù hợp, với tour detail hiện tại bám `Product`
- internal links chỉ sang route còn hoạt động

---

## 9. Completion contract

Task chỉ được coi là xong khi:

- không còn lệ thuộc theme/runtime construction ở phần vừa sửa,
- change phù hợp domain Haidang Travel,
- validation steps được nêu rõ,
- risk còn lại được nói thẳng.
