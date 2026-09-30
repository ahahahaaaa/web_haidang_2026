# Kế hoạch xử lý lỗi Screaming Frog — Haidang Travel

Ngày lập: 18/09/2026. Ban đầu là kế hoạch; sau yêu cầu kiểm tra và sửa lỗi chính, đã sửa runtime trong repo local. Chi tiết bản sửa, kiểm tra và lệnh triển khai nằm trong [báo cáo sửa lỗi](SEO_PRIMARY_FIXES_2026_09_18.md). Chưa triển khai lên production; dữ liệu CMS không được sửa trong task này.

## 1. Căn cứ và giới hạn

- Nguồn: `C:/Users/Admin/Downloads/issues_overview_report_haidang_travel_2026.csv`.
- Người dùng xác nhận báo cáo crawl **`haidangtravel.com`, code hiện tại trên server, sáng 18/09/2026**. Đây là baseline production cho kế hoạch.
- Báo cáo có **13 nhóm lỗi**: 7 High, 3 Medium, 3 Low theo Screaming Frog.
- Đây là báo cáo tổng hợp, không có URL cụ thể, inlinks, mã HTTP từng URL, thời điểm crawl, cấu hình crawler hoặc bản HTML lỗi. Chưa thể chỉ định chính xác URL/file gây từng lỗi.
- Một URL có thể thuộc nhiều nhóm. Không cộng số URL hoặc phần trăm để suy ra tổng số trang lỗi. Mẫu số của phần trăm cũng khác nhau giữa nhóm HTML, ảnh và tài nguyên crawl.
- Số liệu nhóm Images là URL ảnh được công cụ báo cáo, không phải số bài cần biên tập hay số lần ảnh xuất hiện. Cần Image Details để xác định từng trang và vị trí ảnh.
- Chưa xác nhận commit local có khớp phiên bản đang chạy trên production hay không. Code local chỉ dùng xác định điểm cần kiểm tra, không chứng minh nguyên nhân lỗi trên website.
- Các trường Description/How To Fix của CSV là hướng dẫn tham khảo của Screaming Frog; không coi đó là yêu cầu tự động sửa hoặc deploy.

Mục tiêu đầu tiên là khôi phục truy cập và tính nhất quán của URL được phép index; tiếp theo sửa HTML, nội dung và ảnh. Mức ưu tiên dưới đây được điều chỉnh theo tác động thực tế, không chỉ theo nhãn trong CSV.

## 2. Toàn bộ lỗi và thứ tự xử lý

| Lỗi trong CSV | URLs | % theo báo cáo | SF | Ưu tiên đề xuất | Việc xử lý | Tiêu chí hoàn tất |
| --- | ---: | ---: | --- | --- | --- | --- |
| Response Codes: Internal Server Error (5xx) | 3 | 0.030 | High | P0 | Xác minh lỗi, kiểm tra log ứng dụng/web server/upstream, sửa nguyên nhân | URL cần tồn tại trả 200 ổn định; không còn 5xx tái hiện |
| Canonicals: Non-Indexable Canonical | 206 | 13.230 | High | P1 | Kiểm tra từng cặp trang nguồn–canonical; khôi phục đích hoặc chọn đích hợp lệ | Canonical trực tiếp tới trang 200, crawl được, không noindex, không canonical sang URL khác |
| Response Codes: Internal Client Error (4xx) | 223 | 2.280 | High | P1 | Tách HTML/ảnh/file và 404/410/403/429; sửa nguồn liên kết hoặc khôi phục tài nguyên | Không còn liên kết nội bộ/sitemap trỏ tới lỗi ngoài chủ đích |
| Validation: Multiple <head> Tags | 2 | 0.020 | High | P1 | Tìm document HTML bị lồng, view/HTML tùy chỉnh gây trùng | Một head trong HTML response |
| Validation: Multiple <body> Tags | 2 | 0.020 | High | P1 | Kiểm tra cùng nguồn với lỗi head, giữ phần nội dung cần thiết | Một body trong HTML response |
| Page Titles: Outside <head> | 1 | 0.060 | High | P1 | Sửa cấu trúc head, vị trí title và thẻ không hợp lệ trước metadata | Title nằm trong head hợp lệ |
| Page Titles: Multiple | 1 | 0.060 | High | P1 | Loại nguồn xuất title thứ hai | Một title có nội dung |
| Meta Description: Outside <head> | 1 | 0.060 | Medium | P2; làm cùng P1 HTML | Đưa metadata về nguồn render chung | Description nằm trong head |
| Meta Description: Multiple | 1 | 0.060 | Medium | P2; làm cùng P1 HTML | Loại nguồn xuất description thứ hai | Một meta description |
| H1: Missing | 3 | 0.190 | Medium | P2 | Xác minh loại trang và title trong CMS; sửa render heading | Trang nội dung public có một H1 rõ nghĩa, nhìn thấy được |
| URL: Contains Space | 183 | 1.950 | Low | P3; nâng P1 nếu gây lỗi truy cập | Tách URL trang và tài nguyên, kiểm tra khoảng trắng/%20, sửa có mapping | Link hợp lệ; URL đổi có đích và cơ chế chuyển tiếp phù hợp |
| Images: Missing Alt Attribute | 757 | 10.140 | Low | P3 | Sửa template dùng chung, rồi biên tập ảnh trong body/import/snippet | Mỗi img có alt phù hợp vai trò |
| Images: Missing Alt Text | 1 | 0.010 | Low | P3 | Kiểm tra ảnh thông tin hay trang trí | Ảnh thông tin có mô tả; ảnh trang trí được phép alt rỗng |

P0: xử lý ngay khi xác minh lỗi còn tồn tại. P1: ảnh hưởng truy cập/index hoặc metadata. P2: hoàn thiện nội dung trang. P3: chuẩn hóa URL và ảnh. URL tour tạo lead, URL có traffic/backlink và thành phần xuất hiện toàn site được đưa lên trước trong từng nhóm; mức traffic cần dữ liệu Search Console, không tự suy đoán.

## 3. Giai đoạn 0 — Chốt baseline và danh sách công việc

**Phụ trách:** SEO phối hợp developer. **Dự kiến:** 0,5–1 ngày làm việc khi có file crawl.

1. Ghi lại domain, thời điểm crawl, phiên bản production, chế độ HTML/JavaScript, user-agent, tốc độ crawl, phạm vi include/exclude, robots và cấu hình crawl canonical.
2. Lưu crawl production sáng 18/09/2026 để so sánh. Xác nhận phiên bản server và repo local, rồi request lại các URL lỗi; đối chiếu URL cũ/mapping nếu chúng nằm trong danh sách chi tiết. Crawl bổ sung nếu thiếu dữ liệu đích hoặc cấu hình crawl chưa đủ.
3. Xuất chi tiết từ Screaming Frog:
   - `Reports > Canonicals > Non-Indexable Canonicals` để có trang nguồn, canonical và tình trạng đích.
   - `Bulk Export > Response Codes > Internal > Client Error (4xx) inlinks` và báo cáo tương ứng `Server Error (5xx) inlinks`.
   - Danh sách URL từ các filter Multiple head/body, Multiple/Outside head của title/description, Missing H1 và Contains Space.
   - Hai filter Images về alt, kèm Image Details/inlinks để biết trang sử dụng và vị trí thiếu alt.
   - `Internal > HTML` và sitemap để xác định page type, status, indexability, canonical và metadata.
4. Lập bảng theo dõi: `issue / source_url / resource_or_canonical_url / HTTP / indexability_reason / page_type / inlink_source / target_CMS / nguyên_nhân / cách_sửa / owner / trạng_thái / kết_quả_recrawl`.
5. Gộp các lỗi cùng URL/template/nguyên nhân thành một ticket; giữ liên kết tới các nhóm lỗi gốc. Nếu có Search Console, thêm clicks/impressions và URL Google chọn làm canonical để ưu tiên.

**Đầu ra:** backlog theo URL, baseline crawl và danh sách lỗi còn tồn tại trên phiên bản production cần xử lý.

## 4. Giai đoạn 1 — Khôi phục 5xx và sửa cấu trúc HTML

**Phụ trách:** developer; hạ tầng phối hợp nếu lỗi upstream/WAF. **Dự kiến:** 1–2 ngày, tùy nguyên nhân.

### 4.1. Ba URL lỗi máy chủ

- Request lại từng URL và kiểm tra log đúng thời điểm phát sinh. Phân biệt 500 do ứng dụng, 502/504 do upstream, 503 do bảo trì/tải hoặc lỗi tạm khi crawl.
- Nếu chỉ xuất hiện khi crawl nhanh, thử lại tốc độ phù hợp và kiểm tra khả năng phục vụ thực tế; giảm tốc crawler không thay thế việc sửa lỗi máy chủ có thật.
- Trang cần tồn tại phải render nội dung đúng với HTTP 200. URL thực sự bị loại bỏ có thể trả 404/410 đúng chủ đích; không tạo trang rỗng 200 để che lỗi.
- Khi phân loại 223 lỗi 4xx ở giai đoạn sau, URL 429 do quá tải cần chuyển vào nhóm xử lý hạ tầng ưu tiên này.

Theo [Google về mã HTTP](https://developers.google.com/crawling/docs/troubleshooting/http-status-codes), 5xx và 429 có thể làm crawler giảm tốc; lỗi kéo dài có thể ảnh hưởng việc giữ URL trong chỉ mục.

### 4.2. Nhóm head/body/title/description

- Đối chiếu HTML response gốc với DOM trình duyệt và rendered HTML nếu crawl bằng JavaScript. Trình duyệt có thể tự sửa HTML, nên DOM nhìn đúng chưa chứng minh response gốc hợp lệ.
- Kiểm tra layout/partial, nội dung import, HTML tùy chỉnh của SiteSetting và script chèn metadata. Việc nhiều nhóm báo 1–2 URL gợi ý khả năng chung nguyên nhân; chỉ gộp sau khi so URL.
- Giữ layout sở hữu html/head/body; nội dung CMS là fragment. Metadata xuất ở partial head chung. Giữ nội dung hợp lệ khi làm sạch document bị lồng.
- Kiểm tra cả thẻ không hợp lệ xuất hiện sớm trong head, vì chúng có thể khiến metadata sau đó bị hiểu là nằm ngoài head. Tham khảo [Google về HTML metadata hợp lệ](https://developers.google.com/search/docs/crawling-indexing/valid-page-metadata).

**Nghiệm thu:** không tái hiện 5xx ngoài chủ đích; các URL HTML lỗi có một head, một body, một title và một description; metadata nằm trong head hợp lệ. Crawl mẫu các page type khác để kiểm tra ảnh hưởng của thay đổi dùng chung.

## 5. Giai đoạn 2 — Sửa đích URL, canonical, liên kết và sitemap

**Phụ trách:** developer + SEO; editor xác nhận nội dung thay thế. **Dự kiến:** 2–4 ngày khi đã có danh sách chi tiết.

### 5.1. Phân loại 223 URL lỗi 4xx

| Trường hợp được xác minh | Cách xử lý |
| --- | --- |
| Trang còn cần nhưng mất do route/mapping/publish/import | Khôi phục đúng đối tượng/trạng thái public và URL tương ứng |
| Trang đã chuyển sang URL tương đương | 301 tới đích phù hợp; sửa các inlinks sang đích trực tiếp |
| Trang đã bỏ, không có nội dung thay thế phù hợp | Giữ 404/410; gỡ liên kết và sitemap trỏ vào; ghi nhận chủ đích |
| Ảnh/PDF/tài nguyên mất | Khôi phục file hoặc thay nguồn; kiểm tra đường dẫn, encoding và media conversion |
| 403 trên nội dung public | Kiểm tra quyền/WAF/CDN/user-agent; thử request đối chứng để xác định lỗi crawl hay lỗi truy cập thật |
| 429 | Kiểm tra rate limit và tải; phối hợp hạ tầng, đưa lên ưu tiên khôi phục truy cập |

Không redirect hàng loạt URL lỗi về home hoặc trang danh mục không tương đương. Không mở public các URL vốn cần xác thực chỉ để làm sạch báo cáo. HTTP 200 với nội dung lỗi/rỗng cũng cần kiểm tra soft 404.

### 5.2. Phân loại 206 canonical có đích không indexable

Mỗi cặp cần kiểm tra status cuối, redirect chain, robots.txt, meta robots, X-Robots-Tag, canonical của đích, trạng thái publish và độ tương đương nội dung. Non-indexable trong Screaming Frog không đồng nghĩa đã xác nhận Google chưa index trang đó.

- Đích 3xx: canonical trỏ trực tiếp tới URL cuối hợp lệ.
- Đích 4xx/5xx/no response: khôi phục đích hoặc sửa canonical tới trang tương đương đã xác minh.
- Đích noindex/blocked: xác định ý định index. Chỉ bỏ chặn khi trang phải public/index; nếu chặn có chủ đích thì chọn lại canonical phù hợp.
- Canonical chain/loop: gom về một URL ưu tiên; đích ưu tiên self-canonical.
- Kiểm tra cấu hình host HTTPS/www/index.php và `canonical_url` trên dữ liệu import, không chỉ sửa thẻ trong Blade.
- Giữ chính sách hiện tại cho search/filter: không mở index hàng loạt URL tham số để xóa cảnh báo.

[Screaming Frog mô tả lỗi này](https://www.screamingfrog.co.uk/seo-spider/issues/canonicals/non-indexable-canonical/) gồm đích redirect, lỗi HTTP, blocked, noindex hoặc canonical tiếp. [Google về canonical](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls) khuyến nghị tín hiệu canonical và sitemap nhất quán; không dùng noindex chỉ để ép lựa chọn canonical giữa các bản trùng.

### 5.3. Lưu ý riêng về migration trong repo

Code local đang có cơ chế giữ URL cũ bằng `public_url_mappings` với hai mode: render target 200 hoặc redirect. `PublicUrlTargetRenderer` hiện đặt canonical theo URL nguồn khi render mapping. Tài liệu `docs/MIGRATION_SITEMAPS.md` cũng ghi nhận URL cũ và URL native của cùng object có thể cùng self-canonical, và thay đổi sitemap hiện tại chưa gom canonical giữa các alias.

Đây là **điểm cần đối chiếu**, chưa phải nguyên nhân đã xác nhận của 206 URL. Với mỗi object có nhiều URL, chọn URL ưu tiên dựa trên URL đang được giữ, intent, traffic và mapping thực tế; đồng bộ metadata native, mapping renderer, alias, inlinks và sitemap. Không tự đổi toàn bộ URL cũ sang URL native.

URL native để đối chiếu: tour `/chuong-trinh/{slug}`, điểm đến/country root `/tour-{slug}`, blog `/{category}/{slug}`, service `/dich-vu/{slug}`. URL cũ có mapping hợp lệ có thể được giữ theo chính sách migration.

Sitemap nghiệm thu chỉ liệt kê URL canonical ưu tiên, public/published, 200 và được phép index; không đưa URL redirect, lỗi, noindex hoặc filter vào sitemap. Kiểm tra cả `/sitemap.xml`, `/sitemap-index.xml`, sitemap nhóm và robots.txt. Xóa cache liên quan sau khi triển khai để crawl không đọc dữ liệu cũ.

**Nghiệm thu:** không còn canonical có đích non-indexable ngoài trường hợp được giải thích; không còn inlinks/sitemap tới 4xx ngoài chủ đích; redirect không vòng lặp và ưu tiên một bước tới đích cuối; URL cũ được kiểm tra cùng URL native.

## 6. Giai đoạn 3 — H1, URL chứa khoảng trắng và alt

**Phụ trách:** developer sửa nguồn render; editor biên tập theo ngữ cảnh; SEO kiểm tra. **Dự kiến:** 2–4 ngày cho lượt đầu; thời gian biên tập toàn bộ ảnh chốt sau khi biết số trang/vị trí thực tế.

### 6.1. Ba URL thiếu H1

Xác minh đó là trang nội dung public hợp lệ, trang chức năng hay response lỗi. H1 lấy từ title/tên tour/tên hub thực tế; kiểm tra title rỗng và cấu hình ẩn heading. Không thêm một H1 vào body mỗi bài nếu template đã sở hữu H1. Trang public nghiệm thu có một H1 nhìn thấy được và đúng intent.

### 6.2. 183 URL chứa khoảng trắng

- Phân loại URL trang, ảnh và tài nguyên; xem khoảng trắng ở path hay query và kiểm tra encoding. `%20` có thể là đường dẫn file đang hoạt động, không mặc định là lỗi truy cập.
- Link bị tạo sai: sửa tại menu, nội dung, import hoặc helper sinh URL.
- Nếu đổi slug trang sang dấu gạch ngang: kiểm tra trùng slug, chuẩn bị mapping cũ → mới, 301, rồi cập nhật canonical/inlinks/sitemap.
- Nếu đổi tên file ảnh/tài nguyên: cập nhật tham chiếu và duy trì đường dẫn cũ hoặc redirect phù hợp để không làm hỏng bài và liên kết ngoài.
- URL/tài nguyên gây 4xx hoặc canonical lỗi được xử lý trong giai đoạn 2; URL vẫn hoạt động được chuẩn hóa sau khi đánh giá lợi ích và chi phí.

### 6.3. 757 URL ảnh thiếu thuộc tính alt, một URL ảnh alt rỗng

- Dùng Image Details để xác định nơi thiếu alt: logo/icon, card tour/blog, gallery, nội dung Quill/import hoặc HTML tùy chỉnh.
- Sửa template dùng chung trước để xử lý nhiều trang cùng lúc; sau đó biên tập ảnh trong rich text theo từng nhóm trang.
- Ảnh thông tin: mô tả ngắn và đúng hình/ngữ cảnh. Ảnh đóng vai trò link/nút: mô tả chức năng. Ảnh trang trí: `alt=""` hợp lệ; không ép thêm mô tả chỉ để đưa cảnh báo về 0. Áp dụng [W3C alt decision tree](https://www.w3.org/WAI/tutorials/images/decision-tree/).
- Ưu tiên trang tour tạo lead, hub chính và bài có traffic; không dùng tên file hoặc cùng một title cho mọi ảnh trong bài.
- Với template cover/card, tận dụng `cover_alt` và fallback có ngữ cảnh đang có; với ảnh body, cần dữ liệu alt theo vị trí sử dụng, vì một ảnh có thể xuất hiện trong nhiều ngữ cảnh.
- Cập nhật kiểm tra đầu vào/import/editor để hạn chế tái phát; không tạo flow upload riêng hoặc viết lại toàn bộ bài chỉ để thêm alt.

**Nghiệm thu:** không còn H1 missing trên trang nội dung cần index; link chứa khoảng trắng được đánh giá và xử lý có bằng chứng; không còn img thiếu thuộc tính alt; alt rỗng có chủ đích được ghi nhận và kiểm tra thủ công.

## 7. Điểm triển khai dự kiến trong codebase

Đây là phạm vi kiểm tra khi triển khai, không phải danh sách file chắc chắn cần sửa.

| Nhóm việc | Điểm kiểm tra |
| --- | --- |
| Route và redirect native/legacy | `routes/frontsite.php`, `FrontsiteController`, `CanonicalizeFrontsiteUrl`, `FrontsiteUrls`, `config/frontsite_seo.php` |
| URL cũ đang được giữ | `ResolvePublicUrlMapping`, `PublicUrlTargetRenderer`, `PublicUrlMapping`, `config/public_url_mappings.php` |
| Canonical/robots dữ liệu CMS | Model Tour, BlogPost, Service, LandingPage trong `src/Domains/Cms/Models`; các manager hiện có; dữ liệu import tương ứng |
| HTML và metadata | `resources/views/themes/haidangtravel/layouts/app.blade.php`, `partials/head.blade.php`, `SitewideHtmlSnippets`, `RichText`, nội dung CMS lỗi |
| Sitemap/cache | `SitemapBuilder`, `SitemapController`, `resources/views/seo`, `FrontsiteCacheInvalidator` |
| H1/ảnh | Page views, `partials/tour-card.blade.php`, gallery/cover presenters, rich text, media library hiện có |

Repo đang có thay đổi chưa commit về migration URL và sitemap. Triển khai cần review và phối hợp với phần đang làm; không ghi đè hoặc coi diff hiện hữu là phần sửa từ kế hoạch này. Trước sửa code phải đối chiếu migration, seeder/import và mọi call site liên quan. Không khôi phục runtime construction hoặc SEO AI cũ.

## 8. Lịch dự kiến, validation và theo dõi

| Giai đoạn | Thời gian sơ bộ | Đầu ra |
| --- | --- | --- |
| 0. Baseline và exports | 0,5–1 ngày | Backlog có URL/inlinks, phiên bản production được xác nhận |
| 1. 5xx và HTML | 1–2 ngày | Trang phục vụ ổn định; metadata trong cấu trúc hợp lệ |
| 2. URL/canonical/inlinks/sitemap | 2–4 ngày | Tín hiệu URL nhất quán, mappings được kiểm tra |
| 3. H1/URL khoảng trắng/alt | 2–4 ngày cho lượt đầu | Template và nội dung ưu tiên được sửa |
| 4. Recrawl và nghiệm thu | 0,5–1 ngày | Báo cáo trước/sau, ngoại lệ có lý do |

Ước lượng **6–12 ngày làm việc** cho vòng xử lý đầu với developer và editor phối hợp, khi có exports và quyền truy cập cần thiết. Chưa phải cam kết hoàn tất mọi vị trí ảnh; cần chốt lại sau giai đoạn 0, nhất là file nguồn bị mất hoặc số trang biên tập lớn. Hạ tầng và thời gian Google cập nhật chỉ mục là phụ thuộc riêng.

Validation khi triển khai:

1. Chạy check theo phần thay đổi, chẳng hạn:
   - `php artisan route:list --path=sitemap` và kiểm tra các route public vừa sửa.
   - `php artisan test --compact tests/Feature/FrontsiteCanonicalSeoTest.php tests/Feature/MigrationSitemapTest.php tests/Feature/TourSitemapAndBlocksTest.php tests/Feature/FrontsitePagesTest.php` khi các phần đó bị tác động; ưu tiên subset liên quan.
   - `php -l` cho PHP vừa sửa; `npm run build` nếu thay đổi frontend cần build; `composer dump-autoload -o` nếu thay namespace/autoload.
   - Tests sử dụng môi trường test biệt lập, không DB production.
2. Request lại toàn bộ URL lỗi đã xuất và URL đích; kiểm tra HTML response, HTTP, canonical, robots/header và XML sitemap. Thêm test hồi quy cho nguyên nhân có thể tái phát trong shared render/mapping, không viết một test cho từng URL nội dung.
3. Deploy từng nhóm thay đổi đã kiểm tra; clear đúng cache frontsite/CDN nếu có. Kiểm tra mẫu tour, hub, blog, service và URL cũ sau deploy.
4. Recrawl toàn site với baseline có thể so sánh; nếu cần đổi tốc độ do tải thì ghi lại khác biệt. Crawl bổ sung bằng JavaScript cho nhóm phụ thuộc render, không thay cấu hình âm thầm chỉ để báo cáo sạch.
5. So sánh số lỗi từng nhóm, URL đã hết lỗi, lỗi mới và ngoại lệ có chủ đích. Không hạ tổng số URL crawl để làm số lỗi giảm.
6. Trong Search Console, kiểm tra mẫu URL ưu tiên và canonical Google chọn; cập nhật/submit sitemap sau khi đã hợp lệ. Theo dõi sau 7–14 ngày, rồi 28 ngày nếu có dữ liệu; crawl sạch và HTTP 200 không bảo đảm được index hay tăng thứ hạng.

Mục tiêu nghiệm thu: 0 lỗi máy chủ tái hiện ngoài chủ đích; 0 canonical có đích không indexable chưa giải thích; 0 liên kết nội bộ/sitemap tới 4xx ngoài chủ đích; 0 lỗi cấu trúc/metadata của các URL đã xác minh; 0 H1 missing trên trang nội dung cần index; 0 img thiếu thuộc tính alt. URL bỏ hợp lệ, ảnh trang trí alt rỗng và URL file chứa khoảng trắng vẫn hoạt động được ghi riêng theo quyết định có căn cứ.

## 9. Trạng thái kế hoạch

- Đã đọc toàn bộ 13 dòng issue và đối chiếu số lượng/mức ưu tiên; đã kiểm tra các điểm route, render, metadata, mapping và sitemap trong code local.
- File tạo mới: `docs/SEO_SCREAMING_FROG_REMEDIATION_PLAN_2026_09_18.md`.
- Chưa chạy test/build hoặc crawl production vì chưa triển khai thay đổi; validation phía trên là các bước nghiệm thu dự kiến.
- Đã xác nhận domain và thời điểm baseline production. Điểm chưa xác nhận: danh sách URL chi tiết, cấu hình crawler, mức độ khớp commit local/server, trạng thái deploy migration URL/sitemap, root cause từng URL và khối lượng biên tập ảnh thực tế.
