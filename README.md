# Haidang Travel CMS

Laravel + Livewire CMS cho `haidangtravel.com`, được refactor từ codebase cũ sang domain du lịch.

## Phạm vi hiện tại

- Frontsite travel với theme duy nhất: `haidangtravel`
- Quản trị menu, landing pages, blog, tours, services, theme settings, media
- Lead form hợp nhất qua `TravelInquiry`
- Seed/import dữ liệu khởi tạo cho Haidang Travel từ snapshot nội bộ
- SEO thủ công ở mức metadata, schema cơ bản và sitemap

## Public routes

- `/`
- `/ve-chung-toi`
- `/tour-trong-nuoc`
- `/tour-nuoc-ngoai`
- `/tour-doan`
- `/chuong-trinh/{tour}`
- `/dich-vu`
- `/dich-vu/{service}`
- `/blog`
- `/blog/{post}`
- `/lien-he`
- `POST /yeu-cau-tu-van`

## Admin routes

- `/admin/blogs`
- `/admin/tours`
- `/admin/services`
- `/admin/travel-inquiries`
- `/admin/landing-pages`
- `/admin/media`
- `/admin/menus`
- `/admin/theme-settings`

## Cấu trúc chính

```text
app/
database/
docs/
resources/views/themes/haidangtravel/
routes/
src/Domains/Cms/
tests/
AGENTS.md
README.md
```

## Khởi chạy local

### 1. Cài dependencies
```bash
composer install
npm install
```

### 2. Chuẩn bị môi trường
```bash
copy .env.example .env
php artisan key:generate
```

### 3. Migrate và seed
```bash
php artisan migrate
php artisan db:seed
```

Seeder mặc định hiện là `Database\\Seeders\\HaidangTravelBootstrapSeeder`.

### 4. Build frontend
```bash
npm run dev
```

Hoặc build production:
```bash
npm run build
```

### 5. Chạy ứng dụng
```bash
php artisan serve
```

## Import dữ liệu launch

Repo dùng snapshot deterministic trong:

- `database/seeders/Data/haidangtravel/core_snapshot.json`

Để import lại bộ dữ liệu khởi tạo:

```bash
php artisan haidangtravel:import-snapshot
```

## Theme

- Theme runtime duy nhất là `haidangtravel`
- Theme active được đọc từ `site_settings.active_theme`
- Nếu DB chưa có bản ghi, hệ thống fallback về `haidangtravel`

## Validation nên chạy khi thay đổi lớn

```bash
composer dump-autoload -o
php artisan route:list
php artisan test
npm run build
```

## Ghi chú

- Không thêm lại flow estimator, project construction, package construction, hoặc SEO AI cũ
- Khi cần nội dung seed/demo mới, dùng domain du lịch thay vì tái sử dụng dữ liệu construction legacy
- Nếu chỉnh docs cho agent, luôn cập nhật đồng thời `AGENTS.md` và `docs/AGENTS.md`
