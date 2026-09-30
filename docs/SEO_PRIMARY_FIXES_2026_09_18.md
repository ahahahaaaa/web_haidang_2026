# Bản sửa lỗi SEO chính — 18/09/2026

Đã sửa code local theo báo cáo Screaming Frog của `haidangtravel.com` sáng 18/09/2026. Người dùng xác nhận DB local khác DB server và sẽ crawl lại sau khi cập nhật code. Bản sửa không yêu cầu migration hoặc cập nhật dữ liệu CMS. Chưa triển khai production trong task này.

## Lỗi đã xác minh và cách sửa

| Nhóm | Bằng chứng | Thay đổi |
| --- | --- | --- |
| Trùng head/body/title/description | Response public `/teambuilding` chứa cả document HTML trong widget | Chuyển HTML-mode và HTML widget thành fragment khi render: bỏ document wrappers, title/meta/base/canonical bên trong; giữ nội dung, CSS, stylesheet, script và thuộc tính body trong wrapper div |
| Canonical trỏ vào URL chuyển hướng | Public `/kinh-nghiem-du-lich/tu-dong-tam-gom-dia-diem-nao` canonical tới `/blog/...`, đích trả 301; dịch vụ `/dich-vu/visa` và `/dich-vu/ve-may-bay` dùng `/page/...` | Blog, tour, service thay canonical dạng alias cùng slug bằng URL native. URL cũ có mapping render 200 chủ động vẫn được giữ |
| Sitemap loại URL hợp lệ vì canonical cũ | Logic sitemap so sánh canonical lưu trong DB với URL native | Dùng cùng logic canonical đã chuẩn hóa và tăng phiên bản cache sitemap từ v3 sang v4 |
| H1 thiếu trên slider ảnh | Public homepage có head/body/title nhưng không có H1 | Nếu slide đầu không có title, hiển thị tiêu đề trang sau banner; các slide khác dùng H2 |
| H1 trùng giữa template và widget | Widget Team Building có H1 và CSS selector riêng cho H1 | Giữ H1 đầu tiên của nội dung HTML custom page; hero template dùng H2 khi nội dung sở hữu H1. Widget trên system page dùng H2 vì template sở hữu H1 |
| Canonical tới chính trang tìm kiếm noindex | `/tim-tour` được thiết kế noindex | Giữ `noindex,follow`, không xuất canonical cho route tìm tour. Listing có bộ lọc vẫn giữ canonical của listing |

Chuẩn hóa canonical chỉ áp dụng các alias đã biết `/blog/{slug}`, `/tour/{slug}`, `/page/{slug}`. Canonical khác được giữ theo cấu hình hiện có. Đây không phải cơ chế xác minh HTTP của mọi canonical trong DB production.

## Files thay đổi

| File | Mục đích |
| --- | --- |
| `app/Support/LandingPageHtml.php` — mới | Chuẩn hóa fragment và heading từ HTML CMS |
| `app/Support/FrontsiteUrls.php` | Chuẩn hóa canonical alias của blog/tour/service; giữ mapping render 200 |
| `app/Http/Controllers/FrontsiteController.php` | Dùng hai helper trên, chọn nguồn H1 đang render, cấu hình canonical của search |
| `app/Services/Seo/SitemapBuilder.php` | Hai thay đổi: cache v4 và so sánh canonical đã chuẩn hóa |
| `resources/views/themes/haidangtravel/partials/head.blade.php` | Cho phép search không xuất canonical |
| `resources/views/themes/haidangtravel/partials/landing-hero.blade.php` | H1 fallback cho slider ảnh, slide sau dùng H2, nhận heading tag |
| `resources/views/themes/haidangtravel/partials/landing-hero-demo.blade.php` | Nhận heading tag khi widget sở hữu H1 |
| `resources/views/themes/haidangtravel/partials/landing-content-blocks.blade.php` | Truyền heading tag vào hero blocks |
| `resources/views/themes/haidangtravel/pages/landing/show.blade.php` | Chọn H1/H2 theo nội dung HTML đang hiển thị |
| `tests/Feature/FrontsitePrimarySeoRegressionTest.php` — mới | 9 test cho response HTML, canonical, sitemap, heading, search và mapping |
| `tests/Unit/LandingPageHtmlTest.php` — mới | 2 test giữ CSS/script/UTF-8/thuộc tính body/SVG title |
| `docs/SEO_SCREAMING_FROG_REMEDIATION_PLAN_2026_09_18.md` | Cập nhật trạng thái kế hoạch, liên kết bản sửa |
| `docs/SEO_PRIMARY_FIXES_2026_09_18.md` — mới | Báo cáo và lệnh triển khai |

Repo đã có công việc sitemap/public URL mappings và media migration trước task này. Các thay đổi đó được giữ nguyên; bảng trên chỉ mô tả phần sửa SEO của task. Đặc biệt, toàn bộ diff của `SitemapBuilder.php` so với HEAD lớn hơn hai dòng của bản sửa này.

## Kiểm tra

- Bộ kiểm tra trực tiếp và hồi quy liên quan: **69 test đạt, 450 assertions**. JUnit: `outputs/seo-primary-fixes-20260918/primary-tests.xml`.
- Kiểm tra mở rộng tour/service/block: **50 test đạt, 4 test lỗi**, 646 assertions. Bốn lỗi cũng tái hiện khi nạp `FrontsiteController` từ HEAD trước thay đổi task: `outputs/seo-primary-fixes-20260918/baseline-controller-tests.xml`. Đây là đối chiếu controller, không phải chạy toàn repo trên một checkout HEAD sạch.
- Bốn test còn lỗi: service category/detail kỳ vọng marker `data-ai-summary` đã không có trong runtime; tour scope kỳ vọng chuỗi `Hiện có`; test lịch khởi hành kỳ vọng không thấy tháng của ngày quá khứ nhưng ngày quá khứ và ngày tương lai hiện cùng tháng 09/2026. Không thêm lại SEO AI hoặc đổi nghiệp vụ tour để làm các test này đạt.
- **26 response local** gồm home, Team Building và các nội dung published có canonical explicit đều trả 200, mỗi response có một head/body/title/H1. Đây là DB local, không suy ra kết quả toàn bộ DB server. Dữ liệu: `outputs/seo-primary-fixes-20260918/local-render-after.json`.
- Browser local: home và Team Building tại viewport mobile 390px có H1 nhìn thấy được, không tràn ngang; Team Building giữ CSS của widget. Kiểm tra desktop Team Building cũng có một H1 và metadata.
- PHP lint đạt cho controller, hai helper và sitemap builder; Pint đạt cho helper/test mới; `git diff --check` đạt cho các file sửa.
- Không đổi JS/CSS source hoặc dependency; không cần build asset mới cho các thay đổi này.

Lệnh chạy lại bộ test chính:

```bash
php artisan test --compact tests/Feature/FrontsitePrimarySeoRegressionTest.php tests/Unit/LandingPageHtmlTest.php tests/Feature/FrontsiteCanonicalSeoTest.php tests/Feature/MigrationSitemapTest.php tests/Feature/LandingPageVisualsFrontsiteTest.php tests/Feature/CustomLandingPageBlocksTest.php tests/Feature/FrontsiteTrackingScriptsTest.php tests/Unit/RichTextTest.php tests/Unit/SitewideHtmlSnippetsTest.php
```

## Cập nhật server

Bản vá runtime gồm đúng 9 file, không kèm test/docs hoặc những thay đổi WIP khác: `outputs/seo-primary-fixes-20260918/haidangtravel-seo-primary-fixes.patch`. Bản vá xây trên cấu trúc repo hiện tại; phần sitemap cần phiên bản có `modelEntry` và cache v3. Nếu server khác phiên bản, cần ghép cùng thay đổi vào code đang chạy thay vì chép cả file sitemap local.

Nếu chuyển bản vá sang server và áp dụng bằng Git, đứng tại thư mục project Laravel rồi kiểm tra trước:

```bash
git apply --check /duong-dan/haidangtravel-seo-primary-fixes.patch
git apply /duong-dan/haidangtravel-seo-primary-fixes.patch
```

Sau khi cập nhật code, chạy tại thư mục project:

```bash
composer dump-autoload -o
php artisan optimize:clear
php artisan frontsite:cache:clear
```

Nếu server dùng OPcache không kiểm tra file thay đổi hoặc có CDN cache HTML, làm mới cache theo cơ chế deploy đang dùng. Helper mới dùng PHP DOM extension, dependency này hiện được môi trường local đáp ứng. Không chạy seeder, import snapshot hoặc migration để áp dụng bản sửa SEO này.

## Crawl lại và phần còn lại

Ưu tiên kiểm tra homepage, `/teambuilding`, `/event-gala-dinner`, `/danh-muc-tour/tour-du-lich-30-4`, bài Tứ Động Tâm, vài blog `/cam-nang-du-lich/...`, hai service visa/vé máy bay và `/tim-tour?q=...`. Kiểm tra cả HTML response gốc và cấu hình crawl giống lần trước.

- Multiple head/body/title/description và Outside head: mục tiêu không còn lỗi do HTML CMS lồng document.
- Missing H1: kiểm tra ba URL lỗi ban đầu; slider ảnh phải có tiêu đề trang nhìn thấy được.
- Non-Indexable Canonicals: kiểm tra canonical blog/tour/service trỏ trực tiếp đúng URL 200 indexable. Mapping render 200 được giữ nên phải kiểm tra URL mapping thực tế trên server.
- **223 URL 4xx và 3 URL 5xx chưa được xác minh hoặc sửa toàn bộ.** Các request public mẫu có lúc nhận Cloudflare 521/timeout; một URL recheck trả lại 200. Cần đối chiếu URL sau recrawl và log ứng dụng/web server/origin, không kết luận đó là lỗi controller hoặc đã hết lỗi server.
- **183 URL có khoảng trắng và 757 ảnh thiếu alt attribute chưa được xử lý theo từng URL** trong bản sửa này. Cần inlinks/page sử dụng ảnh sau recrawl để sửa nguồn, file và nội dung phù hợp.
- `robots.txt` public trong lần kiểm tra chưa quảng bá sitemap trong khi view local đã có sitemap. Repo không có static `public/robots.txt`; khi triển khai cần đối chiếu phiên bản code/server/static file hoặc cache nếu vẫn thấy response cũ.

File overview chỉ là dữ liệu thống kê. Các trường hướng dẫn của Screaming Frog không được dùng làm chỉ thị tự động sửa dữ liệu, redirect mọi 404 hoặc deploy.
