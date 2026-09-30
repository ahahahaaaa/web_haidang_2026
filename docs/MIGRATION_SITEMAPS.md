# Sitemap cho URL đã migrate

## Gửi vào Google Search Console

Trong property `https://haidangtravel.com`, mở **Sitemaps**, gửi `sitemap-index.xml`. Sitemap index tự liệt kê các nhóm có URL; không cần gửi từng bài và không cần import lại dữ liệu migrate. Có thể gửi từng sitemap nhóm nếu muốn theo dõi riêng trong Search Console.

| Endpoint | Nội dung |
| --- | --- |
| `/sitemap-index.xml` | Index của các sitemap nhóm và các phần phân trang |
| `/sitemap-pages.xml` | Home, about, contact, listing blog/dịch vụ, ba scope tour |
| `/sitemap-blogs.xml` | Blog đã publish; URL cũ chỉ xuất hiện nếu chủ động giữ chế độ `render_target` |
| `/sitemap-blog-categories.xml` | Danh mục blog có bài public trong branch |
| `/sitemap-tours.xml` | Tour public; URL cũ chỉ xuất hiện nếu chủ động giữ chế độ `render_target` |
| `/sitemap-tour-categories.xml` | Chủ đề tour có tour public |
| `/sitemap-destinations.xml` | Điểm đến có tour hoặc blog public theo setting |
| `/sitemap-countries.xml` | Country root có nội dung public |
| `/sitemap-regions.xml` | Vùng miền có tour public |
| `/sitemap-services.xml` | Dịch vụ public |
| `/sitemap-service-categories.xml` | Danh mục dịch vụ có dịch vụ public |
| `/sitemap-landings.xml` | Custom landing active, không noindex |

Mỗi file nhóm có tối đa 10.000 URL. Phần đầu dùng tên không có số; từ phần hai dùng `/sitemap-blogs-2.xml`, `/sitemap-blogs-3.xml`, v.v. Index tự bổ sung các phần. Nhóm rỗng không xuất hiện trong index, endpoint nhóm rỗng vẫn trả XML hợp lệ. Loại không hỗ trợ hoặc số trang vượt phạm vi trả 404.

`/sitemap.xml` vẫn trả danh sách chung để tương thích với URL đã submit trước đây. Khi tổng vượt 10.000 URL, endpoint này tự trả sitemap index thay vì một file quá lớn. `robots.txt` quảng bá cả endpoint tương thích và index mới. Các `<loc>` dùng host canonical trong `config/frontsite_seo.php`, không dùng domain nguồn tải ảnh `tour.org.vn`.

## Điều kiện lấy URL cũ

- Nguồn là bảng lâu dài `public_url_mappings`, không phải staging/audit của module tạm.
- Mapping phải active, `mode=render_target`, `status_code=200`; hash và path phải đúng quy ước middleware public.
- Target phải tồn tại và thỏa cùng điều kiện sitemap CMS: published/active, đến thời điểm publish, không noindex; taxonomy phải có nội dung public. Không thêm system landing qua mapping custom landing.
- Mapping redirect không đưa URL nguồn vào sitemap, kể cả khi URL nguồn trùng URL CMS vốn có. URL đích chỉ được liệt kê khi bản thân nó đáp ứng điều kiện sitemap.
- URL lỗi, pending, queued hoặc chỉ mới submit staging không tự xuất hiện. Một mapping public đã tồn tại từ lần cast thành công trước vẫn có thể xuất hiện khi lần chạy lại lỗi; sitemap phản ánh runtime public hiện tại, không trạng thái queue mới nhất.
- `lastmod` lấy `updated_at` của target CMS, không dùng ngày tạo mapping hoặc ngày retry; không sửa content, timestamp, Media hay URL cũ.
- Deduplicate theo URL chính xác trên toàn danh sách, rồi chia theo loại target, không suy loại từ prefix URL cũ.
- Nếu render mapping chiếm một URL vốn là native của object khác, URL đó chỉ thuộc nhóm của target thực tế. Nếu target không đủ điều kiện thì source bị loại bảo thủ, không dùng entry native thay thế; cần sửa/deactivate mapping không hợp lệ để khôi phục entry native.

Sitemap bảo thủ loại cả target có `robots_directive=noindex/none`, dù một số controller detail hiện chỉ render robots theo cấu hình toàn site. Nếu toàn site đặt `seo_robots=noindex/none`, các sitemap không liệt kê URL.

## Canonical và URL cùng object

Mặc định migration hợp nhất URL cùng object theo một đích duy nhất:

```text
/tin-tuc/du-lich-moc-chau-thang-5
    -> 301 /bai-viet/du-lich-moc-chau-thang-5

/bai-viet/du-lich-moc-chau-thang-5
    -> canonical chính nó
```

URL nguồn ở chế độ `redirect` không nằm trong sitemap; URL CMS đích được liệt kê khi published, indexable và canonical về chính nó. Caster đặt `canonical_url` của target theo URL CMS mà không làm thay đổi timestamp nguồn.

Chế độ `cast_preserve_url` vẫn tồn tại cho ngoại lệ cần URL cũ trả 200 độc lập; renderer của chế độ này tự đặt canonical theo URL cũ và sitemap có thể giữ URL đó. Trong CMS migration, dùng **Hợp nhất URL đã cast** để đổi toàn bộ mapping loại này của phiên sang 301 trên target hiện có. Thao tác chỉ đổi mapping/canonical, không import lại hoặc đổi content, Media và timestamp.

Google khuyến nghị sitemap chứa URL canonical ưu tiên. Sitemap là gợi ý crawl/index, không bảo đảm index hoặc giữ traffic. Tham khảo [Build and submit a sitemap](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap) và [Canonical signals](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls).

## Triển khai và kiểm tra

```bash
php artisan optimize:clear
php artisan frontsite:cache:clear sitemap settings
```

Mở `/sitemap-index.xml`, `/sitemap-blogs.xml`, một URL cũ và URL CMS đích: sitemap phải trả XML 200; URL cũ trả 301; URL đích trả 200 với canonical chính nó. Sitemap chỉ chứa URL đích và `lastmod` phải khớp ngày cập nhật nguồn đã cast.

Cache sitemap được invalidated khi model mapping được save/delete và khi target CMS đổi trạng thái/nội dung. Update/delete qua query builder không phát model event: script thao tác trực tiếp phải gọi clear nhóm `sitemap`. Khi gỡ module LegacyMigration, giữ model/table public mappings, middleware/renderer, sitemap builder và invalidator ngoài module.

Validation tự động: `php artisan test --compact tests/Feature/MigrationSitemapTest.php` và `php artisan route:list --path=sitemap`. Test dùng SQLite in-memory, không chỉnh dữ liệu production hoặc tự submit vào Search Console.
