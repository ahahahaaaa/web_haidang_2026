# AGENTS.md

## Purpose

Quy tắc vận hành chung cho agent khi làm việc trong repo Haidang Travel CMS.

## Domain hiện tại

- Đây là codebase travel CMS cho `haidangtravel.com`
- Theme runtime duy nhất là `haidangtravel`
- Domain chính: `Tour`, `Service`, `BlogPost`, `LandingPage`, `TravelInquiry`, `Menu`, `SiteSetting`
- Không đưa lại logic construction, estimator, package pages, project pages, hoặc SEO AI cũ vào runtime

## Luồng đọc tài liệu

Luôn đọc trước:

- `docs/AGENTS.md`

Tùy loại task, đọc thêm:

- `docs/FRONTSITE_AGENT.md`
- `docs/BACKEND_AGENT.md`
- `docs/DESIGN_SYSTEM.md`
- `docs/TECHNICAL_REQUIREMENTS.md`
- `docs/PROJECT_START_GUIDE.md`

## Nguyên tắc làm việc

- Ưu tiên thay đổi nhỏ, an toàn, dễ review
- Không tự suy diễn business rule nếu code hoặc dữ liệu chưa xác nhận
- Luôn kiểm tra route, controller, domain model, migration, seeder, và theme trước khi sửa
- Giữ controller mỏng; business logic đặt trong service, action, job, hoặc model layer phù hợp
- Không sửa file ngoài phạm vi task
- Khi thay đổi docs, giữ nội dung bám đúng domain travel hiện tại

## Quy tắc repo

- Theme active phải là `haidangtravel`
- Public IA chuẩn hiện tại gồm home, about, 3 landing tours, các hub `danh-muc-tour / diem-den / vung-mien / quoc-gia`, service listing/detail, blog listing/detail, contact
- CTA lead dùng `TravelInquiry`
- Sitemap chỉ chứa route travel đang còn hoạt động
- Seeder/bootstrap mặc định phải đi qua bộ dữ liệu Haidang Travel

## Khi làm frontend/UI

- Ưu tiên dùng view trong `resources/views/themes/haidangtravel`
- Không thêm lại partial/view từ theme construction cũ
- Bám IA travel của site nguồn nhưng không clone HTML cũ
- Giữ trải nghiệm mobile và desktop đều dùng được

## Khi làm backend/CMS

- Kiểm tra validation, auth, permission, và trạng thái publish
- Không thêm flow AI/SEO generation cũ
- Nếu cần import dữ liệu khởi tạo, dùng snapshot/service import của Haidang Travel
- Tránh tạo schema breaking change nếu chưa xác minh toàn bộ call sites

## Validation

Chạy khi phù hợp:

- `composer dump-autoload -o`
- `php artisan route:list`
- `php artisan test`
- `npm run build`

## Output format

Sau khi xong việc, luôn tóm tắt:

- files changed
- reasoning ngắn
- validation steps
- risks nếu có
