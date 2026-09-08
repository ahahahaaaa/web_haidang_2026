# Thiết kế mở rộng SEO AI / Keyword MCP cho toàn bộ URL Haidang Travel

> **Trạng thái 08/09/2026:** thiết kế phụ thuộc Google Sheet trong tài liệu này đã được thay thế bởi luồng MCP trực tiếp kiểu RankMath. Xem [SEO AI Direct MCP Optimizer v2](SEO_DIRECT_MCP_OPTIMIZER_PLAN.md). Tài liệu này chỉ còn giá trị tham chiếu cho inventory, keyword/entity coverage và các quyết định audit trước đây; không dùng làm kế hoạch triển khai runtime mới.

Phiên bản: 1.0 — ngày 06/09/2026. Trạng thái: **đề xuất triển khai; chưa phải xác nhận chức năng đã hoạt động**.

Cập nhật implementation 07/09/2026: module SEO AI Optimize đã có luồng registry → snapshot → Codex MCP → đề xuất → duyệt → áp dụng/hoàn tác. Phạm vi thực tế, cách cấu hình và phần chưa triển khai nằm trong [SEO_AI_OPTIMIZE_RUNBOOK.md](SEO_AI_OPTIMIZE_RUNBOOK.md). Không coi toàn bộ thiết kế dưới đây đã hoàn thành.

Phạm vi tài liệu: quản trị từ khóa, kiểm kê URL, audit nội dung thực tế, đề xuất tối ưu và áp dụng thay đổi có duyệt trên travel CMS hiện tại. Tài liệu không cấp quyền tự động sửa website hoặc tạo/ghi Google Sheet.

Chuẩn nghiệp vụ: skill `haidang-travel-seo-keyword-mcp`, bao gồm các reference về keyword governance, Google Sheet, content audit và CMS contract. Hợp đồng kỹ thuật chi tiết nằm trong [SEO_KEYWORD_MCP_CONTRACTS.md](SEO_KEYWORD_MCP_CONTRACTS.md). Các quy tắc runtime hiện hành tiếp tục theo [TECHNICAL_REQUIREMENTS.md](TECHNICAL_REQUIREMENTS.md), [TOUR_SITEMAP_BLOCKS.md](TOUR_SITEMAP_BLOCKS.md), [SEO_SCHEMA_MAPPING.md](SEO_SCHEMA_MAPPING.md) và [ADMIN_CMS.md](ADMIN_CMS.md).

## 1. Kết quả mong muốn

Người quản trị mở một danh sách tập trung và trả lời được, với từng URL:

1. Trang thuộc thực thể CMS nào, có được chủ định index không và đang render phiên bản nào?
2. Trang sở hữu từ khóa/intent nào, hỗ trợ cluster nào, có cạnh tranh với trang khác không?
3. Nội dung công khai đạt điểm nào; thiếu topic, entity, liên kết hay bằng chứng nào?
4. Thay đổi được đề xuất là gì, dựa vào nguồn nào, ai được duyệt và ảnh hưởng những trang nào?
5. Sau khi duyệt, thay đổi đã vào đúng CMS, hiện ra trên URL và được đồng bộ kết quả về Sheet chưa?

Mục tiêu phủ tất cả URL hiện hữu có hai lớp: **kiểm kê toàn bộ URL trong phạm vi site** và **audit chuyên sâu toàn bộ trang canonical được chủ định index**. URL lọc, chuyển hướng, trang vận hành và trang riêng tư vẫn có phân loại; chúng không được đưa vào mẫu số điểm nội dung như trang SEO.

Điểm SEO là chỉ số kiểm tra nội bộ theo rule của skill. Không coi điểm này là điểm Rank Math chính thức, thứ hạng Google hoặc cam kết được ChatGPT/Claude/Gemini trích dẫn.

## 2. Hiện trạng làm cơ sở thiết kế

### 2.1. Phạm vi chứng cứ

Đợt kiểm tra trước tài liệu này dựa trên code và database **local**, với host sitemap local `http://localhost:8001`. Chưa có một lượt crawl production hoàn chỉnh cho từng URL. Các số dưới đây là snapshot tham chiếu, phải kiểm kê lại khi triển khai; không hard-code vào chương trình.

| Thành phần | Quan sát local | Ý nghĩa đối với phần mở rộng |
| --- | --- | --- |
| Public travel runtime | Route travel, metadata, canonical, sitemap và JSON-LD đã có | Tích hợp vào model/controller/presenter hiện hành |
| GEO | Đã có `FrontsiteGeoPresenter`, `geo_config`, cấu hình và block tóm tắt | Tái sử dụng dữ liệu và render này; audit thêm chất lượng, nguồn và mức phủ |
| Sitemap | 391 đường dẫn ở thời điểm kiểm tra | Điểm khởi đầu kiểm kê, không phải chứng nhận toàn bộ đã được tối ưu |
| SEO AI legacy | 10 `seo_pages`, 7 cluster; 10 page đang `qa_failed`, nội dung mẫu construction | Không dùng làm inventory hoặc nguồn public travel |
| Liên kết legacy với sitemap | Chưa có canonical path của 10 page legacy khớp 391 path travel | Cần Page Registry và adapter thực thể CMS thật |
| Route legacy | Admin SEO/preview và `/v1/seo/*` còn đăng ký; không có public route tới `FrontsiteSeoPageController` | Cô lập; kế hoạch retire riêng, không kích hoạt lại |
| Keyword MCP nghiệp vụ | Chưa tìm thấy triển khai các tool, ba sheet và audit mở rộng trong runtime đã rà soát | Đây là chức năng cần xây |
| Workbook chiến lược | Chưa xác minh được workbook đúng ba tab trong phạm vi Drive truy cập được | Cần chủ sở hữu cung cấp/xác nhận workbook ID; không kết luận Sheet không tồn tại |
| AI provider local | Khóa OpenAI hiệu lực chưa được cấu hình trong lần kiểm tra | Audit kỹ thuật vẫn phải dùng được khi AI chưa sẵn sàng |

Nhóm đường dẫn trong snapshot sitemap:

| Nhóm | Số lượng |
| --- | ---: |
| Trang cố định: home, about, contact, services, blog và 3 landing tour | 8 |
| Custom landing | 10 |
| Tour | 105 |
| Chủ đề tour | 8 |
| Điểm đến và quốc gia root | 47 |
| Vùng miền | 10 |
| Danh mục dịch vụ | 4 |
| Dịch vụ | 4 |
| Danh mục blog | 10 |
| Bài viết | 185 |
| Tổng | 391 |

Kiểm tra test chọn lọc trước đó: 59 test, 53 pass và 6 fail. Nhóm fail liên quan public SEO legacy, marker tóm tắt dịch vụ và một assertion nhạy theo ngày/tháng. Đây là baseline cần phân loại khi triển khai, không phải kết quả kiểm thử phần mở rộng này và không đủ để kết luận lỗi nghiệp vụ cho từng trang.

### 2.2. Những phần cần kế thừa và giới hạn

- Public URL lấy từ route resolver hiện hành, không ghép đường dẫn từ `SeoPageType` legacy.
- Country hiện là `Destination` root (`is_country_root = true`, `country_id = null`). Không tạo bảng Country thứ hai.
- `ContentCategory` phục vụ blog/service; hiện không có đầy đủ cột meta giống Tour/BlogPost. Adapter phải công bố đúng khả năng đọc/ghi.
- Giá/lịch khởi hành lấy từ `tour_departures` và luồng đồng bộ master data đang dùng. Keyword Sheet không sở hữu những dữ liệu này.
- JSON-LD được tạo từ dữ liệu hiển thị. Cột `schema` hoặc `canonical_url` rỗng không tự động có nghĩa public thiếu schema/canonical, vì runtime có fallback.
- H1 có thể lấy từ `title`, `name`, hero hoặc block. Sửa H1 có thể đồng thời đổi tiêu đề/card/breadcrumb; phải hiển thị tác động đó khi duyệt.
- Không tự di chuyển dữ liệu `seo_pages` vào Tour/Service/BlogPost hoặc publish lại nội dung mẫu construction.

## 3. Phạm vi chức năng và điều kiện hoàn thành

| ID | Chức năng | Đầu ra bắt buộc |
| --- | --- | --- |
| F01 | Inventory và đối soát URL toàn site | Page Registry, phân loại, owner CMS, lỗi thiếu/trùng sitemap và canonical |
| F02 | Quản trị keyword/cluster/intent | Chiến lược có revision, owner, từ phụ, entity, topic, freshness, nguồn facts |
| F03 | Mapping keyword ↔ page | Một OWNER cho cùng từ khóa + intent + site + locale; SUPPORTING/AVOID rõ ràng |
| F04 | Snapshot nội dung public | HTML đã trích vùng chính, metadata, heading, schema, media, link và source version |
| F05 | Audit theo rule | Bằng chứng, score, độ đầy đủ, mức nghiêm trọng và hành động cho từng rule |
| F06 | Audit toàn site | Wrong intent, cannibalization, liên kết nội bộ, orphan và mức phủ cluster |
| F07 | Đề xuất tối ưu bằng AI | Diff có nguồn, phạm vi sửa, tác động và loại PROPOSE_ONLY/NEED_DATA/AUTO_FIX_ALLOWED |
| F08 | Google Sheet + Apps Script + MCP | Đọc strategy, lưu audit/gap, đồng bộ queue; có kiểm soát phiên bản và lỗi |
| F09 | Review và áp dụng | Dry-run, approval, kiểm tra quyền/version, idempotency, changelog, rollback có điều kiện |
| F10 | Kiểm tra sau áp dụng | Render mới, cache đúng, re-audit, Sheet ACK và kết quả xác minh |
| F11 | Admin và quyền | Dashboard, danh sách URL/cluster/gap, chi tiết audit, hàng chờ, cấu hình |
| F12 | Vận hành và mở rộng | Job batch/resume/cancel, freshness, ngân sách AI, log, kiểm thử và rollout |

Ngoài phạm vi v1: tự mua dữ liệu keyword, tự có số search volume/ranking, xuất bản hàng loạt trang mới, tự sửa slug/canonical/redirect, tự xác minh chính sách visa từ suy đoán, tạo domain Attraction/Visa mới, và bảo đảm thứ hạng hay AI citation. Những tính năng này cần yêu cầu và nguồn riêng.

## 4. Kiểm kê và định danh tất cả URL

### 4.1. Ma trận page adapter

Tên adapter dưới đây là thiết kế mới. Path phải được resolve qua route và slug thực tế, kể cả khi category của blog thay đổi.

| `page_type` | Public path hiện hành | Nguồn CMS/route | Trọng tâm audit |
| --- | --- | --- | --- |
| `home` | `/` | System LandingPage `home`, SiteSetting, live blocks | Thương hiệu, điều hướng cluster, main entity |
| `about` | `/ve-chung-toi` | System LandingPage và thông tin doanh nghiệp | Danh tính, nguồn trust, thông tin nhất quán |
| `contact` | `/lien-he` | System LandingPage, SiteSetting | Intent liên hệ, thông tin xác thực, CTA |
| `tour_scope` | `/tour-trong-nuoc`, `/tour-nuoc-ngoai`, `/tour-doan` | System LandingPage + live tour query | Intent listing, phạm vi tour, hub links |
| `tour` | `/chuong-trinh/{slug}` | Tour + departures + quan hệ | Sản phẩm, lịch trình, giá/điều kiện có nguồn |
| `tour_category` | `/danh-muc-tour/{slug}` | TourCategory + tour query | Chủ đề, tập tour, hỗ trợ cluster |
| `destination` | `/tour-{slug}` | Destination thường + tour/blog query | Destination Hub, entity/topic/intent |
| `country` | `/tour-{slug}` | Destination root | Country Hub, điểm đến con, visa nếu liên quan |
| `region` | `/vung-mien/{slug}` | Region + tour/taxonomy query | Quan hệ vùng → điểm đến → tour |
| `service_index` | `/dich-vu` | System LandingPage + services | Điều hướng nhóm nhu cầu |
| `service_category` | `/dich-vu/danh-muc/{slug}` | ContentCategory taxonomy service | Nhóm dịch vụ và liên kết tới detail |
| `service` | `/dich-vu/{slug}` | Service | Phạm vi hỗ trợ, process, điều kiện có nguồn |
| `blog_index` | `/blog` | System LandingPage + BlogPost query | Điều hướng nội dung theo chủ đề |
| `blog_category` | `/danh-muc/{slug}` | ContentCategory taxonomy blog | Hub bài viết, tránh cạnh tranh commercial owner |
| `blog_post` | `/{category-slug}/{post-slug}` | BlogPost + category/author context | Informational intent, nguồn, link commercial |
| `landing` | `/{custom-slug}` | LandingPage, blocks hoặc HTML | Intent cấu hình, hero, block và query hiển thị |

Một record model có nhiều role vẫn chỉ đại diện một public page canonical trong cùng locale. System page và route fallback không được sinh hai Page_ID cho cùng URL. Registry lưu owner chính và các dependency, không nhân bản public page theo số quan hệ.

### 4.2. Nguồn kiểm kê và chuẩn hóa

Inventory hợp nhất bốn nguồn: route cố định được allowlist; entity CMS và trạng thái public; SitemapBuilder; liên kết nội bộ public đã phát hiện. Mỗi URL lưu nguồn phát hiện để đối soát.

`page_id` là ULID ổn định; `site_id + entity_type + entity_key + locale` duy nhất cho nguồn có owner. Trang cố định thiếu record dùng `entity_type = route`, `entity_key = route_name`. Backfill record sau này giữ nguyên Page_ID. URL phát hiện chưa resolve owner nằm trong hàng đối soát, chưa được quyền patch.

Chuẩn hóa scheme/host theo site config, fragment tách riêng, query được phân loại, path tuân theo resolver runtime. Không tự lower-case path hoặc giải mã ký tự đến mức làm hai URL khác nhau thành một. Lưu URL gốc, final URL, declared canonical và canonical dự kiến riêng biệt.

Theo dõi redirect legacy như `/tour/{slug}`, `/diem-den/{slug}`, `/quoc-gia/{slug}`, `/blog/{post}` cùng URL sạch tương ứng. Không tạo keyword OWNER riêng cho alias. Redirect target phải được kiểm tra thực tế, không mặc định mọi route legacy trả 301.

### 4.3. Phân loại và mẫu số

| Phân loại | Xử lý |
| --- | --- |
| `INDEXABLE` | Audit đầy đủ, có keyword mapping hoặc hàng chờ mapping |
| `EXPECTED_INDEXABLE_ERROR` | Trang đáng lẽ public/index nhưng lỗi HTTP, robots, canonical hoặc render; vẫn nằm trong mẫu số mục tiêu |
| `NOINDEX_INTENTIONAL` | Kiểm tra kỹ thuật/noindex/canonical; không yêu cầu keyword owner |
| `REDIRECT` | Kiểm tra đích, status, vòng lặp, chuỗi; gom về owner canonical |
| `FILTER_VARIANT` | Query/filter/search, đối chiếu noindex + canonical contract; không crawl tổ hợp vô hạn |
| `DRAFT_OR_PRIVATE` | Chỉ ghi metadata kiểm kê theo quyền; không crawl bằng session admin hoặc gửi nội dung cho AI |
| `OPERATIONAL` | Admin/auth/API/Livewire, loyalty, voucher, review token, action endpoints; loại khỏi audit nội dung |
| `ORPHAN_CANDIDATE` | Không tìm được incoming link trong phạm vi crawl; cần nêu độ đầy đủ graph trước khi kết luận |
| `UNRESOLVED` | URL chưa resolve owner/route hoặc conflict; gửi xử lý, không bỏ qua âm thầm |

`ORPHAN_CANDIDATE` là cờ bổ sung, không thay thế phân loại indexability. Robots.txt, sitemap và asset được kiểm tra dưới nhóm tài nguyên kỹ thuật; không coi là content page. Pagination có policy riêng theo runtime; không mặc định mọi `?page=` là filter bị noindex.

Coverage chính = số Page_ID mục tiêu có audit hiện hành đầy đủ / tổng Page_ID được chủ định index. `EXPECTED_INDEXABLE_ERROR` và trang chưa mapping vẫn ở mẫu số. Mỗi lần chạy phải lưu inventory revision, số bị loại và lý do; mẫu số bằng 0 hiển thị “chưa có phạm vi”, không hiển thị 100%.

## 5. Kiến trúc và quyền sở hữu dữ liệu

### 5.1. Luồng tổng thể

```text
Google Sheet: 15_KEYWORD_SET + 17_KEYWORD_MAP
    → Apps Script adapter → Strategy validation → CMS strategy cache
    → Page Registry → Public snapshot → Technical + semantic audits
    → 16_PAGE_KEYWORD_AUDIT → 03_SEO_AUDIT / 04_CONTENT_GAPS
    → Đề xuất / Yêu cầu dữ liệu → Review → 07_CMS_QUEUE
    → CMS apply action → Cache invalidation → Public re-audit → Sheet ACK

Codex / Claude / CMS UI → cùng application services qua MCP hoặc admin actions
```

### 5.2. Source of truth

| Dữ liệu | Nguồn có thẩm quyền | Bản sao/ghi chú |
| --- | --- | --- |
| Keyword, intent, ownership, topic/entity requirements | Google Sheet sau validation/activation | CMS giữ revision đã chấp nhận để đọc nhanh |
| Báo cáo nghiệp vụ audit/gap đã đồng bộ | Google Sheet | CMS giữ bằng chứng, lịch sử và projection phục vụ UI |
| Nội dung public, trạng thái publish, media, URL resolver | CMS travel | Sheet không ghi đè trực tiếp model |
| Giá/ngày đi/chỗ/điều kiện thương mại | Nguồn nghiệp vụ CMS/master data tương ứng | Audit đọc kèm provenance, không tự sửa master data |
| Job, snapshot, idempotency, outbox, trạng thái apply | SQL/CMS | Đây là execution state, không tạo strategy riêng cạnh tranh Sheet |
| Quyền, approval và quyết định apply | CMS authenticated actions | Queue Sheet lưu tham chiếu; text “APPROVED” trong ô không phải quyền thực thi |

Workbook không sẵn sàng: có thể đọc cache chiến lược và chạy kiểm tra kỹ thuật, nhưng UI ghi rõ stale/sync pending. V1 chặn apply khi không thể kiểm tra revision chiến lược và queue đã được ghi nhận. CMS content đã commit không bị rollback chỉ vì ghi Sheet sau đó lỗi; outbox phải tiếp tục đồng bộ.

### 5.3. Các khối cần bổ sung

- `PageRegistryService` và `PageAdapterRegistry`: resolve loại trang, source/dependency, khả năng đọc/ghi.
- `PageSnapshotService` và parser: chụp public response và evidence versioned.
- `KeywordStrategyService`: đọc/validate/activate revision từ Sheet, kiểm tra ownership.
- `PageAuditService`: điều phối rule kỹ thuật và semantic, tính điểm, lưu evidence.
- `SiteAuditService`: graph link, duplicate topic/intent và cannibalization theo candidate set.
- `OptimizationProposalService`: tạo diff có nguồn và scope, không tự apply.
- `ApplyOptimizationAction`: dry-run, kiểm tra approval/version, transaction, outbox.
- `SheetSyncService`: adapter Apps Script, retry, ACK và đối soát.
- Jobs, admin managers, MCP tools gọi các service này; không nhân đôi logic trong Livewire/tool/controller.

Namespace mới đề xuất `App\Services\SeoOptimization` và các model tên `SeoOptimization*`. Không sửa `Src\Domains\Seo` legacy thành backend mặc định của hệ thống mới. Không cần tách microservice cho audit v1; MCP transport và Apps Script chỉ là integration boundary.

## 6. Quản trị keyword, intent và facts

### 6.1. Cluster và ownership

Mỗi keyword set có primary, secondary, semantic terms, entities, intent, target page type/owner, supporting pages, priority, required topics/links, freshness class và fact sources. Danh sách có cấu trúc; tránh dùng một ô văn bản dài không thể kiểm tra.

Các intent chuẩn: `INFORMATIONAL`, `COMMERCIAL_INVESTIGATION`, `TRANSACTIONAL`, `NAVIGATIONAL`, `LOCAL_SERVICE`. Nhãn tiếng Việt hiển thị ở UI. Một primary keyword có thể dùng ở các intent khác nhau khi reviewer xác nhận ranh giới, mục tiêu và link hỗ trợ; không tự miễn trừ vì URL hoặc loại model khác nhau.

Quan hệ mapping:

- `OWNER`: trang chịu trách nhiệm trả lời chính cho keyword + intent.
- `SUPPORTING`: trang bổ trợ; có chủ đích liên kết về owner phù hợp.
- `AVOID`: ghi nhận trường hợp không nên tối ưu keyword này cho trang; vẫn cho phép nhắc từ tự nhiên khi nội dung cần.

Chiến lược mới hoặc sửa trực tiếp Sheet vào trạng thái cần review. Khi activation phải đối chiếu Target_Page_ID, URL hiện hành, status public, type, locale và uniqueness. Đổi keyword owner làm stale các audit/proposal liên quan và yêu cầu duyệt lại.

Không tự gán cluster từ slug rồi coi là dữ liệu đã duyệt. AI có thể đưa `propose_keyword_mapping` với lý do và các owner cạnh tranh. Khi chưa mapping, vẫn chạy technical audit; semantic audit đầy đủ trả trạng thái thiếu mapping.

### 6.2. Topic pack theo loại trang

| Trang/intent | Nội dung cần xét, tùy cluster và facts có thật |
| --- | --- |
| Destination/Country quốc tế | Tổng quan, Tours, Prices, Places, Guides, Visa, Images, FAQ |
| Destination trong nước | Tổng quan, Tours, Prices, Places, Guides, Transport, Images, FAQ |
| Tour detail | Điểm đến, thời lượng, lịch trình, lịch đi/giá, bao gồm/không bao gồm, điều kiện, điểm đón, FAQ, CTA |
| Tour scope/category/region | Định nghĩa phạm vi, tập tour live, hướng dẫn chọn, taxonomy liên quan, bài bổ trợ |
| Service/detail/category | Nhu cầu, phạm vi, quy trình, hồ sơ nếu có, lưu ý và nguồn, dịch vụ liên quan, CTA |
| Blog/detail/category | Câu trả lời đúng informational intent, cấu trúc nguồn, tác giả/cập nhật, guide và commercial link phù hợp |
| Home/about/contact | Nhận diện doanh nghiệp, điều hướng hoặc thông tin liên hệ đúng mục tiêu của từng trang |
| Custom landing | Topic pack từ intent đã duyệt, chỉ đánh giá block đang bật/render |

Topic pack là baseline xét tính phù hợp, không phải mệnh lệnh chèn mọi section vào mọi trang. Topic không áp dụng phải có lý do được duyệt. Thiếu giá/visa/facts cần thiết tạo NEED_DATA, không điền số giả cho đủ điểm.

Attraction trong skill được map tới BlogPost/LandingPage hoặc section điểm tham quan có thật; Visa map tới Service/BlogPost/LandingPage đúng nội dung. Không phát sinh URL `/attraction/*` hay `/visa/*` khi repo không có route tương ứng.

### 6.3. Fact provenance và freshness

Mỗi claim nhạy cảm gắn nguồn: loại nguồn, record/URL tham chiếu, trường hoặc trích đoạn, `source_version`, thời điểm xác minh, hiệu lực nếu biết và người chịu trách nhiệm. Nguồn không có ngày hết hạn không đồng nghĩa có hiệu lực vô thời hạn.

Các lớp freshness của skill: `STATIC`, `SEASONAL`, `FREQUENT`, `REGULATORY`. Khoảng recheck là cấu hình được chủ nghiệp vụ duyệt, không gán một TTL cố định cho mọi giá/visa. Các dependency thay đổi phải làm stale ngay dù TTL chưa hết.

Thiếu hoặc mâu thuẫn nguồn giá, ngày đi, số chỗ, visa, hạng khách sạn, rating/review, hủy/đổi/hoàn tiền: tạo `NEED_DATA`, nêu claim và người cần xác nhận. Model AI không đóng vai trò nguồn sự thật. Không coi thông tin có sẵn trong CMS hoặc hiển thị trên trang là đã xác minh độc lập.

Các quy tắc runtime thương mại hiện hành, ví dụ availability hoặc aggregate rating fallback, cần được đối chiếu nguồn. Nếu phát hiện xung đột với yêu cầu evidence, tạo issue chuyển người phụ trách; module audit không tự thay đổi business rule trong schema/presenter.

## 7. Snapshot và bộ máy audit

### 7.1. Dữ liệu đầu vào

Snapshot gồm: Page_ID, URL yêu cầu/final/canonical, status HTTP, robots, metadata hiệu lực, H1–H6, nội dung chính, GEO/FAQ đang render, JSON-LD, media/alt/caption, liên kết và anchor, source/dependency versions, renderer/parser versions, thời gian capture và content hash.

Parser phải loại navigation/footer, script/style, form chứa token, widget vận hành và block CMS đang tắt khỏi việc chấm nội dung chính. Vẫn thu navigation/footer link ở nhóm riêng để phân tích graph. Nội dung accordion/tab người dùng có thể mở phải được nhận diện riêng, không kết luận “ẩn” chỉ vì panel ban đầu đóng. Không tính bản sao desktop/mobile hai lần.

HTML server-rendered là baseline. Với rule phụ thuộc trạng thái JavaScript, tạo browser verification job riêng; nếu chưa render được thì `unassessed`, không phán PASS. Không crawl nội dung có login/token hoặc mang session quản trị.

`source_version` phải đại diện owner cùng dependency ảnh hưởng nội dung public: departures, query results, taxonomy, media, SiteSetting, template/config phù hợp. Bỏ token CSRF và dữ liệu biến thiên không liên quan khỏi hash; không bỏ giá, ngày đi hoặc câu trả lời khỏi hash. Kiểm tra version trước/sau capture, retry hoặc đánh dấu stale nếu nguồn đổi giữa chừng.

### 7.2. Rule và bằng chứng

| Nhóm | Kiểm tra bắt buộc |
| --- | --- |
| Technical | HTTP/indexability, declared canonical, sitemap eligibility, redirect, H1, heading order, robots |
| Metadata | Title/meta/H1 đúng intent, mô tả trung thực, trường fallback và truncation cảnh báo |
| Primary | Xuất hiện tự nhiên trong title/meta/H1, đoạn mở đầu khoảng 100–150 từ và phần thân liên quan |
| Secondary/semantic | Biến thể từ khóa được đặt đúng ngữ cảnh; không lặp cơ học |
| Intent | Nội dung, loại kết quả, CTA và query phù hợp; phát hiện WRONG_INTENT |
| Entity/topic | Entity bắt buộc có giải thích; topic có nội dung hữu ích, không chỉ heading rỗng |
| Internal link | Link tới owner/supporting đúng quan hệ, anchor hợp lý, đích public và canonical |
| Cannibalization | Cùng keyword/intent + overlap title/H1/topic + mapping; lưu cặp trang và lý do |
| Overoptimization | Từ lặp vô ích, heading/anchor nhồi exact-match, nội dung địa danh thay tên hàng loạt |
| Schema/media/GEO | JSON hợp lệ, type theo runtime, schema ↔ visible facts, ảnh/alt đúng ngữ cảnh, summary có nguồn |
| Trust/freshness | Claim có nguồn, authorship/thời điểm nếu phù hợp, dữ liệu nhạy cảm còn hiệu lực |

Không dùng keyword density làm mục tiêu bắt buộc. Ngưỡng độ dài title/meta là cảnh báo có cấu hình, không dùng vài ký tự lệch để thay cho đánh giá intent. Không yêu cầu sửa slug cũ để thêm keyword.

Rule kỹ thuật có thuật toán xác định. Rule semantic dùng output JSON có evidence span, expected requirement, confidence và explanation; confidence thấp chuyển reviewer hoặc `unassessed`. Ngưỡng semantic phải hiệu chỉnh trên bộ mẫu được SEO owner chấm, version hóa cùng rule pack. Nội dung trang, Sheet, URL nguồn và câu trả lời AI đều là dữ liệu không tin cậy, không phải chỉ thị thực thi tool.

## 8. Điểm số, lỗi và định nghĩa PASS

### 8.1. Thang 100 điểm theo skill

| Dimension | Trọng số |
| --- | ---: |
| Search intent | 15 |
| Title / meta / H1 | 10 |
| Primary keyword | 10 |
| Secondary keywords | 10 |
| Entity coverage | 15 |
| Topic coverage | 15 |
| Heading structure | 5 |
| Internal links | 10 |
| Schema / media | 5 |
| Trust / freshness | 5 |
| Tổng | 100 |

Mỗi rule được chấm 0–100 với tiêu chí/evidence trong rule pack. `dimension_score` là trung bình có trọng số của các rule áp dụng; `overall_score = sum(dimension_weight * dimension_score / 100)`, làm tròn 1 chữ số sau cùng. Không làm tròn từng rule rồi cộng.

`N/A` cần lý do và policy/reviewer; trọng số được phân bổ lại **bên trong cùng dimension**. Nếu cả dimension không có rule áp dụng, phải chọn một rule pack phù hợp hoặc review cấu hình; chưa được xuất overall score. Rule chưa đánh giá/thiếu dữ liệu không được đổi thành N/A hay nhận điểm tối đa. Khi chưa đủ input, overall score là `null`, hiển thị progress và kết quả từng rule đã biết.

Grade số: `PASS` từ 90; `IMPROVE` từ 75 đến dưới 90; `WEAK` từ 50 đến dưới 75; `FAIL` dưới 50. Ghi riêng `score_grade`, `assessment_status`, `acceptance_status` và `sheet_sync_status`.

### 8.2. Gate chấp nhận

Một trang chỉ được đánh dấu hoàn tất tối ưu khi:

1. Có owner/intent rõ, mapping active và không còn tranh chấp keyword owner.
2. Snapshot, strategy revision và rule version đang hiện hành.
3. Đã đánh giá đầy đủ; score đạt từ 90; metadata/keyword tự nhiên.
4. Entity/topic/link requirements đạt hoặc có ngoại lệ phù hợp được duyệt.
5. Không còn WRONG_INTENT, NEED_DATA bắt buộc hay cannibalization chưa giải quyết.
6. Schema bám nội dung hiển thị, claim nhạy cảm có nguồn.
7. Không có P0/P1 đang mở; re-audit public thành công.
8. Audit đã ghi nhận tại Sheet; nếu có patch thì có approval, CMS changelog và queue verification.

Trang không sửa CMS không cần tạo approval giả; ghi `NO_CHANGE`. Điểm 92 nhưng mapping stale hoặc còn NEED_DATA không được hiện badge “đã tối ưu”.

### 8.3. Severity và hướng xử lý

| Severity | Ví dụ | Điều kiện xử lý |
| --- | --- | --- |
| P0 | Rò rỉ nội dung riêng tư, patch sai owner, thay fact thương mại không được phép | Dừng apply phạm vi ảnh hưởng, báo người vận hành |
| P1 | Trang cần index bị lỗi, canonical sai owner, intent chính sai, claim nhạy cảm không có nguồn | Chặn acceptance; xử lý hoặc xác minh trước rollout |
| P2 | Topic/entity/link quan trọng thiếu, meta chưa sát intent | Lập proposal/gap có ưu tiên |
| P3 | Cải thiện diễn đạt, alt hoặc heading nhỏ | Theo backlog tối ưu |

Severity issue và Priority của keyword là hai field khác nhau. Phát hiện bằng AI chưa đủ evidence không được tự nâng thành kết luận P0/P1; có thể đặt blocker chờ xác minh để bảo vệ apply.

## 9. Đề xuất, duyệt và áp dụng thay đổi

### 9.1. Chính sách hành động

| Nhóm | Xử lý mặc định |
| --- | --- |
| Sửa cấu trúc heading kỹ thuật, meta description dựa facts có sẵn, alt theo ngữ cảnh, format ngữ nghĩa | Có thể `AUTO_FIX_ALLOWED` nếu adapter hỗ trợ và không đổi ý nghĩa/facts |
| Gợi ý internal link | Có thể tạo gợi ý an toàn; chèn link vẫn phải qua patch policy, kiểm tra đích và duyệt |
| Schema sai cú pháp | Được đề xuất sửa kỹ thuật; schema do code sinh phải chuyển ticket code, không ghi JSON override vào CMS |
| Viết lại/nội dung mới, title/H1 đổi intent, keyword owner | `PROPOSE_ONLY`, người duyệt xem tác động |
| Giá, visa, refund/policy, rating, lịch đi, số chỗ | Thiếu nguồn → `NEED_DATA`; có nguồn → review nghiệp vụ, không auto-fix |
| Slug, canonical, redirect | `PROPOSE_ONLY`; v1 chỉ lập đề xuất/ticket, không cho apply tự động qua generic patch |
| Field/block chưa có adapter ghi | Read-only + ticket triển khai, không ghi trực tiếp field tùy ý |

`AUTO_FIX_ALLOWED` mô tả mức rủi ro của một loại thao tác, không tự cấp quyền publish. V1 mặc định mọi patch cần người duyệt; automation policy có phạm vi hẹp chỉ được bật qua quyết định quản trị riêng, có audit và kill switch.

### 9.2. Luồng một trang

1. Lấy snapshot public và strategy active; tạo audit có version.
2. Ghi audit vào `16_PAGE_KEYWORD_AUDIT`, issue/gap vào sheet tương ứng; đợi ACK.
3. Tạo proposal chứa before/after, evidence, fact references, capability và affected pages.
4. Dry-run bằng chính validator của apply; render preview không public, so sánh metadata/H1/schema/link/CTA.
5. Người có quyền review chấp thuận proposal hash cùng source/strategy version. Proposal đã sửa phải duyệt lại.
6. Ghi `07_CMS_QUEUE` và nhận ACK; worker kiểm tra lại quyền, approval, revision, source, policy trước apply.
7. Lock ngắn theo page; cập nhật owner qua adapter trong transaction; lưu changelog và outbox cùng transaction.
8. Invalidate đúng frontsite cache groups/dependencies; fetch public mới, không dùng snapshot trước apply.
9. Re-audit và ghi Sheet; cập nhật `VERIFIED` khi apply đúng và kiểm tra hoàn tất, ghi acceptance riêng.

`VERIFIED` không có nghĩa score luôn PASS: một patch có thể chỉ xử lý một issue. Hệ thống phải cho thấy issue nào đã đóng, vấn đề nào còn lại và điểm thay đổi bao nhiêu.

### 9.3. Xung đột, bulk và rollback

- Source/dependency đổi sau audit: trả `STALE_SOURCE`, giữ proposal để tham khảo, tạo snapshot mới; approval cũ mất hiệu lực.
- Strategy hoặc policy đổi: trả `STRATEGY_CONFLICT`/`POLICY_CHANGED`; không tự dùng bản cũ.
- Một `idempotency_key` cùng payload trả lại kết quả cũ; khác payload bị từ chối. Timeout phải tra execution trước retry.
- Bulk lưu danh sách Page_ID + version + phạm vi được chọn lúc xác nhận. Phân trang/filter UI không được biến thao tác thành “tất cả” ngoài ý muốn.
- Transaction theo từng page, không khóa toàn site; kết quả từng phần rõ ràng. Cancel dừng job chưa apply; job đã commit tiếp tục verification và sync.
- Rollback là proposal đảo ngược dựa trên before snapshot, có expected version hiện tại và quyền rollback. Không ghi đè sửa tay hoặc master-data update xảy ra sau patch.
- Verification lỗi: `VERIFY_FAILED`, giữ evidence, ngăn vòng lặp tự sửa; reviewer chọn retry verify, patch mới hoặc rollback an toàn.

## 10. Thiết kế giao diện CMS

Nhóm sidebar đề xuất: **SEO & Từ khóa**. Tích hợp `AdminNavigationRegistry`, permission matrix và submenu hiện hành. Route tách khỏi `/admin/seo/pages` legacy. Bố cục theo chuẩn list/editor riêng của CMS, bảng scroll ngang trên mobile, ngày `dd/mm/yyyy` và giờ `dd/mm/yyyy HH:mm`.

| Màn hình đề xuất | Nội dung và thao tác |
| --- | --- |
| `/admin/seo-optimization` | Coverage, trạng thái mapping/audit, P0/P1, NEED_DATA, pending review, sync/provider health |
| `/admin/seo-optimization/pages` | URL, page type, owner CMS, intent, primary, score/grade, freshness, issue, last audit; filter và bulk audit |
| `/admin/seo-optimization/pages/{page}` | Snapshot và link mở public/editor; tabs Tổng quan, Keyword, Audit, Content gaps, Links, Schema/GEO, Lịch sử |
| `/admin/seo-optimization/keywords` | Cluster/keyword, owner/supporting, priority, required topics/entities, nguồn và revision |
| `/admin/seo-optimization/keywords/{keyword}/edit` | Chỉnh strategy qua cùng Sheet gateway; preview conflict, submit review/activate theo quyền |
| `/admin/seo-optimization/gaps` | Lọc KEYWORD/TOPIC/ENTITY/INTERNAL_LINK/FACT/SCHEMA/INTENT, severity, owner và trạng thái giải quyết |
| `/admin/seo-optimization/proposals/{proposal}` | Before/after, evidence, tác động shared fields, dry-run, Approve/Reject/Request data |
| `/admin/seo-optimization/queue` | Hàng chờ, version, reviewer, progress từng page, retry/cancel/verify/rollback theo quyền |
| `/admin/seo-optimization/settings` | Kết nối đã che secret, workbook mapping, rule pack, ngân sách, freshness, quyền và kill switches |

Trong editor Tour/Service/BlogPost/LandingPage/taxonomy thêm panel gọn: keyword owner, điểm mới nhất, “dữ liệu đã thay đổi” nếu stale, các issue chính và nút mở SEO detail. Không đưa cả manager SEO vào form dài; audit/propose không tự submit form đang sửa. Bản nháp editor chưa lưu phải được ghi rõ khác public snapshot.

Mọi finding có vị trí và trích đoạn; click xem đúng vùng nội dung. Mọi proposal có diff theo field/block; không chỉ hiển thị bản viết lại hoàn chỉnh. Nút apply bị vô hiệu hóa phải nói lý do cụ thể: thiếu quyền, nguồn đổi, thiếu facts, Sheet chưa ACK hoặc chưa được duyệt.

Các trạng thái rỗng/lỗi cần thiết: chưa kết nối Sheet, chưa kiểm kê, chưa mapping, AI không sẵn sàng, job chạy dở, bị giới hạn quota, sync conflict, không có adapter ghi. Không hiển thị score 0 khi thực tế chưa audit.

Dashboard tách số “đã scan”, “audit đầy đủ hiện hành” và “đạt acceptance PASS”. Thống kê PASS/IMPROVE/WEAK/FAIL chỉ dùng latest complete scored audit cho từng page; hiển thị riêng UNMAPPED/NEED_DATA/PARTIAL/STALE. Điểm trung bình phải kèm số trang được tính và coverage, không loại trang lỗi rồi trình bày như điểm toàn site. Có KPI missing primary/secondary/entity/topic, wrong intent, cannibalization và overoptimization; so sánh các lần audit cùng rule version hoặc nêu rõ phiên bản đã đổi.

### Frontsite khi áp dụng tối ưu

Giữ một H1 đúng intent, thứ bậc Destination Hub, các block đang bật và CTA `TravelInquiry`. Tái sử dụng `geo_config`/GEO presenter và shared media flow. Không chỉnh UI chỉ để tăng điểm.

Theo skill, hero và section Hot đầu tiên không dùng nền/overlay/gradient Navy hoặc filled CTA Navy; CTA chính dùng cam `#F47721`, chữ charcoal theo design system. Thay đổi content không được làm mất tương phản, mobile layout, chức năng tab/accordion hoặc semantic heading.

## 11. MCP, API, Sheet và bảo mật

Bộ tool theo skill gồm đọc strategy/mapping, audit từng chiều, phát hiện cannibalization/overoptimization, đề xuất gap/link/mapping và `seo_page_check`. Tool tạo proposal chỉ ghi proposal/audit/gap, không sửa nội dung CMS. Contract input/output, sheet columns và REST endpoints nằm trong [tài liệu contracts](SEO_KEYWORD_MCP_CONTRACTS.md).

Thiết kế dùng MCP gateway gọi application service; Apps Script là cầu nối HTTPS JSON tới Sheet. Apps Script web app đơn thuần không được coi là MCP server. Khi triển khai remote gateway, pin transport/auth theo SDK và phiên bản đã kiểm thử; MCP hỗ trợ transport chuẩn như stdio và Streamable HTTP. [Đặc tả MCP transports](https://modelcontextprotocol.io/specification/2025-11-25/basic/transports).

Yêu cầu bảo mật:

- Authenticated admin/API/MCP và permission theo action + page scope; mặc định không cấp quyền mới cho role Content.
- Tách audit/propose/approve/apply/rollback/integration-config; người hoặc service tạo proposal không tự nhận approval.
- Không tin các cột `Approved_By`/`Status` do người sửa Sheet nhập tay; apply tra approval có thẩm quyền trong CMS.
- Secrets chỉ ở secret/config store; không đưa vào Sheet, prompt, URL query, log hoặc report. Trả tình trạng “đã cấu hình” thay cho giá trị.
- Crawler chỉ đọc host/site allowlist, giới hạn redirect/size/time; chống SSRF và không dùng endpoint fetch arbitrary URL từ tool arguments. Local loopback chỉ bật rõ trong môi trường local.
- Không gửi TravelInquiry, số điện thoại review, token voucher/QR, cookies, form secrets hay dữ liệu private cho AI/Sheet.
- Output AI validate schema, allowlist field/operation, sanitize HTML, kiểm tra link/facts; không execute code/script trong trang hay ô Sheet.
- Sheet export phải neutralize formula injection cho dữ liệu text bắt đầu bằng `=`, `+`, `-`, `@`; không dùng công thức từ đầu vào không tin cậy.
- API write chống replay, có request ID, idempotency và giới hạn tốc độ; audit log ghi actor thật và service executor riêng.

Skill có nhắc `haidang-travel-mcp-control`, nhưng contract của hệ thống điều khiển đó chưa được xác minh trong lần thiết kế. Việc nối các tab `03`, `04`, `07` phải đi qua compatibility mapping đã kiểm tra workbook; không giả định tên cột hay cơ chế approval hiện hữu đã tương thích.

## 12. Batch, hiệu năng và vận hành

- “Audit toàn site” tạo batch từ inventory revision; job chia theo Page_ID, có cursor/progress, resume và kết quả lỗi từng trang.
- Kỹ thuật chạy trước; semantic chỉ chạy khi có strategy và input phù hợp. Audit cùng snapshot + strategy/rules/model version được tái sử dụng; ghi rõ reused, không đổi thời điểm capture cũ thành mới.
- Cannibalization lọc candidate theo keyword/intent/entity trước, không mặc định gọi AI cho mọi cặp URL.
- Event owner/dependency thay đổi đánh dấu stale; lịch kiểm tra định kỳ chỉ bật khi cấu hình vận hành được duyệt. Không tự tạo automation trong quá trình viết tài liệu.
- Giới hạn concurrency, số trang/batch, token/request, token/batch và retry là cấu hình; có dry-run báo khối lượng trước full run. Không ghi chi phí ước tính thành chi phí thực tế nếu provider chưa trả usage/đơn giá chưa xác nhận.
- Apps Script xử lý read/write ngắn, theo batch; audit/AI chạy ở worker CMS. Dùng backoff có jitter cho lỗi tạm thời/quota, không retry vô hạn. Google áp dụng quota và giới hạn runtime có thể thay đổi; kiểm tra account/deployment khi rollout. [Apps Script quotas](https://developers.google.com/apps-script/guides/services/quotas).
- Không flush toàn cache. Dùng invalidator hiện có, bao gồm trang liên quan, sitemap khi eligibility thay đổi, và verify MISS/HIT khi patch tác động render.
- Log theo batch/audit/proposal/queue/request ID; ghi latency, retries, provider usage, conflict, ACK lag, failed verification. Không log nguyên HTML/secret ở log mặc định.
- Snapshot đầy đủ có retention cấu hình được duyệt; giữ hash, evidence cần thiết và changelog để trace sau khi archive. Không xóa audit/queue đang mở để giảm dung lượng.
- Health check tách: CMS adapter, database/queue, fetch public, Sheet schema/auth, AI provider, outbox lag. Thiếu AI không làm public website lỗi.

## 13. Lộ trình triển khai và migration an toàn

| Phase | Phạm vi | Điều kiện qua phase |
| --- | --- | --- |
| P0 — Chốt hợp đồng | Xác minh workbook/control adapter, quyền, môi trường, nguồn facts, baseline test | Có decisions cho các mục ở phần 15; runtime vẫn giữ nguyên |
| P1 — Inventory read-only | Registry và adapter đọc đủ 16 page types; đối soát route/entity/sitemap/link | Mọi URL được phân loại, canonical conflicts có report, không public mutation |
| P2 — Strategy/Sheet | Map tab, schema migration additive, đọc/validate/activate keyword + map | Read/write idempotent, conflict và offline được kiểm thử; không tự tạo OWNER |
| P3 — Audit | Snapshot/parser, rule pack 100 điểm, semantic, graph/site batch, lưu audit/gap | Bộ mẫu mọi page type có evidence; thiếu input không PASS; resume/cancel đạt |
| P4 — CMS và proposal | Dashboard, editor panel, diff, NEED_DATA, approvals, dry-run | Permission đầy đủ, preview đúng nguồn và field, không apply tự phát |
| P5 — Apply có duyệt | Queue 07, adapters ghi allowlist, transaction/outbox/cache/re-audit/rollback | Pilot scope hẹp không P0/P1, test stale/idempotency/quyền/facts thành công |
| P6 — Phủ toàn site | Mở rộng page types và toàn inventory production đã xác nhận | Tất cả URL mục tiêu có kết quả hiện hành hoặc blocker rõ; đối soát số lượng |

Migration mặc định additive/idempotent, feature flags tắt write; không `migrate:fresh`, truncate hoặc seed mẫu construction. Backfill Page Registry từ CMS, preview diff rồi mới ghi registry. Không copy toàn HTML vào `seo_pages` để tạo nguồn content thứ hai.

Meta tùy biến cho ContentCategory nếu cần phải là migration riêng nullable, giữ fallback hiện tại và kiểm tra cả service/blog categories. Các field H1/GEO/block/media chưa có điểm ghi an toàn ở phase đầu vẫn read-only; UI phải nêu capability thay vì tạo nút apply hỏng.

Rollout deployment: schema → code với flags off → permissions/config → worker → registry/Sheet validation → pilot audit → pilot write được duyệt → mở rộng. Rollback vận hành ưu tiên tắt apply/AI mới và drain verification/outbox; không drop bảng hoặc đảo nội dung hàng loạt khi chưa đối soát.

Legacy SEO AI có ticket riêng: kiểm kê caller, policy disable/hide và lưu lịch sử nếu cần. Tài liệu này không yêu cầu xóa legacy code hoặc đổi public route trong phase nền tảng.

## 14. Kiểm thử và tiêu chí nghiệm thu

### 14.1. Ma trận bắt buộc

| Nhóm test | Case tối thiểu |
| --- | --- |
| Inventory | Đủ 16 loại; country root không trùng destination; fixed fallback; draft/noindex/filter/redirect/unknown; entity ngoài sitemap |
| URL/canonical | Blog category đổi; reserved slug; canonical manual/fallback; redirect loop; pagination policy; hostname local/production tách biệt |
| Parser | Một H1, nội dung main, desktop/mobile duplicate, accordion/tab, hidden block, HTML thủ công, GEO fallback, JSON-LD thực tế |
| Facts | Giá/visa/rating thiếu nguồn → NEED_DATA; departure đổi làm stale; AI không được tự thêm facts |
| Keyword | OWNER conflict; khác intent cần review; SUPPORTING/AVOID; chưa mapping; secondary/entity/topic evidence |
| Score | Trọng số 100; biên 49.9/50/74.9/75/89.9/90/100; N/A; chưa assessed; điểm cao nhưng gate fail |
| Semantic | Gold set được reviewer chấm; WRONG_INTENT; false-positive cannibalization; prompt injection; JSON sai/timeout |
| Sheet | Cột/tab thiếu, row reorder, duplicate ID, đổi revision giữa chừng, quota, ACK thất lạc, formula injection, user sửa trực tiếp |
| Apply | Dry-run không ghi CMS; approval giả/stale/thu hồi; actor thiếu quyền; field ngoài allowlist; cùng idempotency khác payload |
| Đồng thời | Editor/master data đổi sau audit; hai worker cùng page; commit thành công nhưng response mất; queue delivery lặp |
| Cache/verify | Public dữ liệu mới, invalidation dependency, verify fail, Sheet offline sau commit, không reapply khi retry sync |
| UI | Filter/pagination, bulk frozen selection, empty/error/loading, date format, mobile, deep-link permission, diff/impact |
| Rollback | Đảo patch được phép, ngăn ghi đè sửa mới, verify và log đầy đủ |
| Regression | Frontsite travel, sitemap/schema, CTA TravelInquiry, admin hiện hữu không bị ảnh hưởng |

### 14.2. Definition of Done của hệ thống

- Inventory production được chủ site xác nhận phạm vi; mọi route/entity được phân loại có lý do, không chỉ lấy số sitemap.
- Đủ adapter đọc cho 16 loại trang; capabilities ghi được khai báo và kiểm thử theo field.
- Keyword, audit và mapping đi qua đúng ba sheet của skill; compatibility với `03/04/07` có fixture và kiểm tra.
- Toàn bộ tool bắt buộc có auth, JSON contract, integration test và error semantics.
- Không còn apply path bỏ qua version, approval hoặc idempotency; rollback có kiểm tra nguồn mới.
- Full-site report chỉ ra coverage, unmapped, stale, NEED_DATA, conflicts và các URL lỗi; không đánh đồng “đã scan” với “đã tối ưu”.
- Tài liệu vận hành, quyền, feature flags, nguồn facts và cách phục hồi được bàn giao cùng implementation.

Khi triển khai chạy test theo phạm vi và các lệnh repo phù hợp: `php artisan route:list`, `php artisan test`, `composer dump-autoload -o`, `npm run build` nếu có UI/assets. Các lệnh đề xuất cho module mới chỉ có hiệu lực sau khi đã được implement.

## 15. Quyết định cần xác nhận trước khi bật runtime

| Mục | Mặc định thiết kế | Điều kiện cần chốt |
| --- | --- | --- |
| Workbook | Không tự tạo hoặc ghi workbook chưa xác nhận | ID, owner, tab mapping, quyền và backup |
| MCP control hiện hữu | Compatibility adapter, chưa giả định đã có | Tool/auth/queue contract thực tế của control skill/hệ thống |
| Môi trường | Local/staging/production tách site_id, URL, credentials | Host allowlist và staging public access an toàn |
| AI | Technical audit dùng được khi AI off | Provider/model, data-sharing policy, budget, confidence calibration |
| Approval | Human review cho mọi patch | Người duyệt theo page scope và người có quyền apply/rollback |
| Facts | Không tự bổ sung claim thiếu bằng chứng | Chủ nguồn giá/visa/policy/rating và quy trình xác minh |
| Audit định kỳ | Chưa tự bật | Freshness intervals, concurrency, cửa sổ chạy và cảnh báo |
| Meta/field chưa có | Read-only cho tới khi adapter/migration được duyệt | Danh sách field cần mở ghi, shared-field impact |
| Schema business conflict | Issue + review riêng | Quyết định nghiệp vụ có nguồn, không auto-fix rule thương mại |

Các mục này là gate triển khai, không cản việc hoàn tất bộ tài liệu thiết kế. Mọi thay đổi phạm vi sau khi chốt phải cập nhật phiên bản design, contracts và acceptance tests cùng nhau.
