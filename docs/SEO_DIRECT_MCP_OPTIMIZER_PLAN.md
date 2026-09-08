# SEO AI Direct MCP Optimizer v2

Ngày chốt thiết kế: 08/09/2026  
Trạng thái: kế hoạch triển khai, chưa phải xác nhận đã hoàn tất runtime

## 1. Quyết định kiến trúc

SEO AI Optimize hoạt động trực tiếp trên nội dung CMS hiện hữu theo cách tương tự một trợ lý RankMath:

1. Codex nhận một bài từ server qua MCP, không cần Google Sheet làm hàng đợi bắt buộc.
2. Server trả snapshot, field được phép sửa, keyword và dữ kiện có nguồn.
3. Codex audit, chấm điểm, tạo nội dung và ảnh nếu cần.
4. Server tạo backup bất biến trước khi ghi.
5. Server kiểm tra revision, policy, HTML, SEO gate và quyền rồi mới commit.
6. Nếu policy của trang/phạm vi là `always_publish`, server publish và xác minh public; các trường hợp khác bắt buộc tạo preview chờ duyệt.
7. Mọi kết quả đều có audit log, before/after và khả năng tạo bản khôi phục.

Google Sheet chỉ có thể tồn tại như bộ nhập/xuất keyword tùy chọn về sau. Sheet không còn là source of truth, queue, approval hay điều kiện để Codex chạy theo lịch.

## 2. Mục tiêu và giới hạn

Mục tiêu:

- Tự tìm và tối ưu `Tour`, `Service`, `BlogPost`, `LandingPage` và các taxonomy có adapter thật.
- Cho phép tối ưu `title`, `slug`, `description/excerpt`, nội dung, SEO meta, OG, FAQ, heading, internal link và media/alt khi model tương ứng hỗ trợ.
- Ưu tiên keyword riêng của trang; fallback theo loại trang rồi mới tới bộ keyword tổng.
- Backup trước mọi mutation và khôi phục bằng proposal mới, không ghi đè mù lên bản sửa mới hơn.
- Chấm điểm SEO theo rule có bằng chứng; tách điểm accessibility và cơ hội GSC.
- Mặc định preview; `always_publish` chỉ do quản trị viên cấu hình trên server.

Không làm:

- Không tạo nguồn content thứ hai trong `seo_pages` legacy.
- Không bịa giá, ngày khởi hành, visa, rating, refund, số chỗ hoặc cam kết dịch vụ.
- Không coi điểm nội bộ là điểm RankMath chính thức, thứ hạng Google hay chứng nhận ADA/WCAG.
- Không cho Codex tự đổi policy publish, permission, canonical hoặc master data ngoài allowlist.
- Không tự drop bảng cũ trước khi xuất snapshot và xác nhận dữ liệu không cần chuyển.

## 3. Luồng tự động một bài

```text
Codex Schedule
  -> claim_next_content
  -> snapshot + keyword brief + writable fields + source version
  -> audit_content
  -> tạo patch/FAQ/link/media phù hợp
  -> commit_content_optimization
      -> validate revision + policy + facts + HTML
      -> immutable backup
      -> transaction ghi CMS + audit/outbox
      -> preview hoặc publish do server quyết định
      -> cache invalidation + public verification
  -> get_content_optimization_result
```

`claim_next_content` chọn bài theo `enabled`, trạng thái publish, điểm thấp, P0/P1/P2, độ cũ, GSC opportunity và thời điểm tối ưu gần nhất. Lease có hạn, giới hạn số bài/lượt và không nhận lại cùng bài khi worker khác đang giữ.

Khi không đủ dữ kiện, Codex trả `NEED_DATA` cùng danh sách field/nguồn còn thiếu. Hệ thống không cố viết cho đủ điểm.

## 4. Keyword và chống cannibalization

Thứ tự chọn keyword:

1. Keyword set ACTIVE gắn trực tiếp Page ID.
2. Keyword set ACTIVE của entity/taxonomy liên quan.
3. Profile theo page type.
4. Bộ keyword tổng của site.
5. AI suy luận từ nội dung chỉ tạo đề xuất, không tự trở thành OWNER.

Mỗi primary keyword chỉ có một OWNER trong cùng locale/site. Trang khác chỉ có thể là SUPPORTING hoặc AVOID. Trước khi đổi title, slug, H1 hoặc intent, server kiểm tra ownership và các trang gần nghĩa. Xung đột trả `KEYWORD_CONFLICT`, không tự publish.

Brief tối thiểu gồm primary keyword, secondary keywords, intent, entities, required/optional topics, forbidden claims, fact references, internal-link candidates và keyword source/version.

## 5. Field và quy tắc ghi

Mỗi adapter khai báo `readable_fields`, `writable_fields`, validator và tác động render. Không có generic mass assignment.

| Field | Mặc định | Gate bổ sung |
| --- | --- | --- |
| `title`, `h1`, `excerpt/description` | Có thể đề xuất/ghi | Không đổi intent trái keyword OWNER |
| `meta_title`, `meta_description`, OG | Có thể đề xuất/ghi | Duy nhất, mô tả đúng nội dung; độ dài chỉ là cảnh báo preview |
| `content` và block hỗ trợ | Có thể đề xuất/ghi | Sanitize HTML, giữ CTA/shortcode/component hợp lệ |
| `faq` | Có thể đề xuất/ghi | Câu trả lời phải hiện trên trang; không tạo fact nhạy cảm thiếu nguồn |
| Internal link | Có thể đề xuất/ghi | Đích canonical hợp lệ, anchor tự nhiên, không loop/broken |
| Media, alt, caption | Có thể đề xuất/ghi | MIME/kích thước/quyền nguồn/alt ngữ cảnh hợp lệ |
| `slug` | Rủi ro cao | Bắt buộc unique, tạo redirect 301 từ URL cũ, không collision/reserved slug |
| Canonical, publish status | Server-only | Codex không được patch trực tiếp |
| Giá, lịch đi, số chỗ, rating | Không qua content patch | Chỉ lấy từ domain data đã xác thực |

Slug chỉ được đưa vào auto publish nếu redirect đã ghi cùng transaction/outbox và kiểm tra URL mới thành công. Nếu chưa có redirect adapter hoàn chỉnh, slug luôn chuyển preview.

## 6. Media và ảnh

- Có `image_url` cùng domain: tìm media hiện hữu theo canonical URL/hash, tái sử dụng thay vì tải trùng.
- Có `image_url` ngoài domain: server tải qua downloader chống SSRF, host allowlist/policy nguồn, kiểm MIME, dung lượng, pixel và hash rồi đưa vào Spatie Media Library.
- Không có `image_url`: Codex tạo prompt từ page type, destination/entity, bố cục, tỷ lệ và brand constraint; render ảnh qua công cụ tạo ảnh được cấp cho task; upload bằng endpoint media có lease.
- Không render được ảnh: nội dung vẫn có thể tạo preview, ghi `MEDIA_PENDING`; không dùng URL hoặc ảnh giả.
- Alt mô tả mục đích ảnh trong ngữ cảnh, không nhồi keyword; ảnh trang trí dùng alt rỗng khi đúng semantic.

## 7. Backup, commit và khôi phục

Backup được tạo phía server ngay trước mutation và chứa:

- Page ID, owner type/id, public URL và source version.
- Toàn bộ field bị tác động, media relation cần phục hồi và checksum.
- Keyword/rule/policy version, actor/service identity, proposal và idempotency key.
- Thời điểm, lý do tối ưu, publish state trước mutation.

Commit dùng optimistic locking. Source version đổi sau khi Codex nhận bài trả `STALE_SOURCE`; patch không được rebase tự động. Database mutation, backup, audit và outbox ghi trong cùng transaction khi storage cho phép.

Khôi phục không phải nút ghi đè tức thời. `restore_content_backup` tạo reverse proposal dựa trên version hiện tại; nếu bài đã được sửa tiếp thì reviewer phải xem diff/xung đột trước khi áp dụng.

## 8. Điểm SEO 100 và cấp đánh giá

| Dimension | Điểm |
| --- | ---: |
| Crawl, indexability, canonical, sitemap | 10 |
| Keyword ownership và cannibalization | 8 |
| Search intent | 10 |
| Title, H1, meta, slug, OG | 10 |
| People-first content và tính hữu ích | 15 |
| Topic completeness | 10 |
| Entity coverage | 8 |
| Heading, cấu trúc, readability | 7 |
| Internal/external links | 7 |
| Media và alt | 4 |
| Schema khớp visible content | 4 |
| Trust, fact provenance, freshness | 7 |
| Tổng | 100 |

Rule được version hóa, có weight, applicability, evidence và rubric 0/partial/100. `UNASSESSED` không được biến thành PASS hoặc 100 điểm. Khi thiếu dimension bắt buộc, `overall_score = null` và hiển thị coverage.

| Cấp | Điểm | Ý nghĩa |
| --- | ---: | --- |
| A+ | 95–100 | Xuất sắc, vẫn phải qua publish gate |
| A | 90–94.9 | Đủ ngưỡng SEO acceptance |
| B | 80–89.9 | Tốt nhưng còn P2/P3; preview |
| C | 65–79.9 | Cần tối ưu đáng kể |
| D | 50–64.9 | Yếu |
| F | 0–49.9 | Không đạt |

Severity độc lập với điểm:

- `P0`: nguy cơ bảo mật/private content, sai owner hoặc mutation nguy hiểm; chặn commit/publish và cap điểm 49.
- `P1`: index/canonical/intent/fact nghiêm trọng; chặn auto publish và cap điểm 79.
- `P2`: thiếu topic/entity/link/meta quan trọng; ưu tiên sửa.
- `P3`: polish readability/alt/heading; backlog.

Publish gate yêu cầu đồng thời: score từ 90, không P0/P1/NEED_DATA, keyword OWNER duy nhất, source hiện hành, backup thành công, validation pass và public verification pass. Cấu hình `always_publish` không được bỏ qua các gate này.

## 9. Điểm ADA/accessibility và GSC

`accessibility_score` là thang 100 riêng cho nội dung/render có thể kiểm tra: H1/heading semantic, alt, link text, language/readability, list/table markup, form label liên quan, contrast/focus khi crawler trình duyệt có bằng chứng. Đây là quality signal, không phải chứng nhận tuân thủ ADA hoặc WCAG.

`gsc_opportunity_score` là thang 0–100 riêng để ưu tiên backlog từ impressions, CTR so với nhóm vị trí, average position, click loss, query/page overlap và độ mới dữ liệu. Không có kết nối hoặc dữ liệu GSC hiện hành thì để `null`; không suy đoán bằng AI. Điểm GSC không cộng vào SEO quality score.

## 10. Hợp đồng MCP v2

| Tool | Vai trò |
| --- | --- |
| `list_content_items` | Lọc inventory và trạng thái tối ưu |
| `claim_next_content` | Claim một batch nhỏ, trả lease và brief |
| `get_content_snapshot` | Snapshot đầy đủ theo scope/field allowlist |
| `audit_content` | Lưu audit có evidence, score/grade/gates |
| `prepare_content_image` | Tái sử dụng, tải hoặc cấp upload lease cho ảnh |
| `commit_content_optimization` | Nộp patch; server backup, quyết định preview/publish và commit |
| `get_content_optimization_result` | Trả proposal/apply/verify/public URL và lỗi chuẩn |
| `list_content_backups` | Liệt kê version có thể xem/khôi phục |
| `restore_content_backup` | Tạo reverse proposal an toàn |
| `report_content_failure` | Ghi lỗi có thể retry hoặc cần người xử lý |

Tool cũ của module `SeoOptimization` được giữ alias trong một release nếu Codex Schedule hiện hữu còn dùng. Sau khi schedule chuyển sang v2 và log không còn caller, alias cùng code Sheet mới được gỡ.

## 11. Gỡ SEO legacy và code dư thừa

### 11.1. Bằng chứng hiện tại

- `bootstrap/app.php` vẫn nạp `routes/admin_seo.php`, `routes/ai.php`, `routes/api_v1/seo.php`.
- Route legacy đang lộ `/admin/seo/pages`, `/ai/seo/pages/*` và `/v1/seo/*` song song với `/admin/seo-optimization` và `/mcp/seo-optimization`.
- Database local đang có 7 `content_clusters`, 10 `seo_pages`, 30 `seo_links`.
- Không có job chứa SEO legacy trong `jobs` hoặc `failed_jobs` tại thời điểm rà soát.
- `FrontsiteSeoPageController` không có route caller được tìm thấy; public travel không được phép lấy `seo_pages` làm nguồn.
- Một số job `App\Jobs\Seo` import model `App\Models\SeoPage/SeoLink/ContentCluster` không tồn tại, cho thấy nhánh cũ không còn nhất quán.

### 11.2. Giữ lại

- `app/Services/Seo/SitemapBuilder.php`, sitemap/robots views và SEO frontsite đang render từ entity travel thật.
- Canonical, metadata, JSON-LD/GEO helper của theme `haidangtravel`.
- `config/frontsite_seo.php` và route/public IA travel hiện hành.
- Module `App\Services\SeoOptimization`, model/table/audit/proposal/permission hiện tại làm nền cho v2, sau khi đơn giản hóa.
- Spatie Media Library và cache invalidation hiện hành.

### 11.3. Retire rồi xóa

Thực hiện theo reference graph và diff review, không xóa thư mục rộng theo tên `seo`:

1. Bỏ đăng ký ba route legacy khỏi `bootstrap/app.php`; sau một release không caller mới xóa các route file.
2. Gỡ controller, request, Livewire, view admin/public chỉ phục vụ `seo_pages`.
3. Gỡ command/job/service/provider/policy chỉ phục vụ cluster/page/link legacy.
4. Xóa namespace `src/Domains/Seo` sau khi `rg`, container bindings, queue payload và tests không còn caller.
5. Gỡ `config/seo_ai.php`, env key, permission `viewSeoAdmin`, menu và test legacy sau khi xác nhận không còn consumer ngoài nhánh cũ.
6. Gỡ example/demo construction và cập nhật docs để không hướng dẫn seed/publish nội dung đó.
7. Gỡ `OptimizationSheetGateway`, `ClaimSeoSheetOptimization`, Sheet sync command/config/UI và Apps Script chỉ sau khi MCP v2 + schedule trực tiếp đã pilot thành công. Nếu vẫn cần import/export, tách adapter tùy chọn không tham gia runtime chính.

Không xóa `SitemapBuilder`, robots/sitemap, trường meta/schema của Tour/Service/BlogPost/LandingPage hoặc SEO helper theme chỉ vì có chữ “SEO”. Thư mục lồng `haidangtravel/` phải xác minh ownership/deploy trước khi xử lý; không đưa vào lệnh xóa đệ quy chung.

### 11.4. Dữ liệu và migration

1. Xuất JSON/SQL có checksum cho ba bảng legacy và lưu ngoài public storage.
2. Chỉ map keyword/intent hữu ích, có người duyệt, sang keyword profile mới. Không chuyển HTML construction hoặc tự publish record legacy.
3. Thêm forward migration để drop foreign key và ba bảng sau thời gian quan sát/cutover.
4. Không xóa lịch sử migration cũ ngay vì có thể làm hỏng fresh install/upgrade. Chỉ squash baseline trong ticket riêng sau khi cả hai đường migration đã được kiểm thử.
5. Có rollback vận hành bằng backup database trước deploy; migration drop không được coi là cơ chế rollback nội dung.

## 12. Lộ trình triển khai

| Phase | Phạm vi | Điều kiện hoàn tất |
| --- | --- | --- |
| P0 — Legacy isolation | Export dữ liệu; bỏ route/menu/command cũ; khóa write vào bảng legacy | Route list không còn endpoint cũ; frontsite/sitemap không đổi; snapshot kiểm tra được |
| P1 — Core v2 | Adapter nội dung, keyword resolver, audit score/gate, immutable backup, revision lock | Test đủ page type; score không giả khi thiếu input; restore conflict an toàn |
| P2 — Direct MCP | Các tool v2, idempotency, lease, server-side publish decision | Không có đường bypass backup/review/policy; retry không ghi lặp |
| P3 — Content/media | Patch title/slug/meta/content/FAQ/link; media reuse/download/generate/upload | HTML/media/redirect/fact gate và preview render đạt |
| P4 — Admin/policy | Diff, score theo cấp, P0–P3, backup/restore, `preview`/`always_publish` | Superadmin/permission đúng; policy thay đổi có audit |
| P5 — Skill/Schedule | Skill Codex mới và lịch batch nhỏ | Hết queue thì dừng; báo kết quả có proposal/apply/verify; không lộ secret |
| P6 — Legacy deletion | Xóa class/view/test/config/Sheet runtime dư; forward drop table | `rg` zero caller, upgrade + fresh migration pass, pilot v2 ổn định |
| P7 — Rollout | Pilot BlogPost, rồi Tour/Service/Landing/taxonomy | Không P0/P1, backup/restore diễn tập đạt, coverage và lỗi rõ ràng |

## 13. Kiểm thử và Definition of Done

- `php artisan route:list`: chỉ còn `/admin/seo-optimization` và `/mcp/seo-optimization`; không còn `/admin/seo/pages`, `/ai/seo/*`, `/v1/seo/*`.
- `rg` không còn runtime reference tới `SeoPage`, `SeoLink`, `ContentCluster`, `viewSeoAdmin`, `config('seo_ai')` sau phase xóa.
- Migration được kiểm thử cả database nâng cấp có bảng cũ và fresh database; drop chỉ xảy ra ở forward migration đã duyệt.
- Test matrix bao gồm permissions, claim lease, stale source, idempotency, backup, restore conflict, preview, always publish gate, slug redirect, sanitize HTML, facts, media SSRF/MIME/size và public verification.
- Điểm kiểm thử đủ biên 49.9/50/64.9/65/79.9/80/89.9/90/94.9/95/100; P0/P1 cap; `overall_score=null` khi chưa assessed.
- Regression đạt cho Tour, Service, BlogPost, LandingPage, taxonomy, sitemap, robots, canonical, schema/GEO, media và CTA `TravelInquiry`.
- Chạy `php artisan test`, `composer dump-autoload -o`, `php artisan route:list` và `npm run build` khi UI/assets thay đổi.
- Production rollout có kill switch apply, batch limit, log/audit/outbox, cảnh báo lỗi và hướng dẫn restore.

## 14. Rủi ro cần khóa

- Route cache hoặc worker cũ có thể còn class đã xóa: clear/rebuild cache, restart worker và kiểm tra queue trước deploy.
- Record legacy có thể được người dùng lưu để tham khảo: export và duyệt mapping trước drop.
- Thay slug làm mất traffic nếu redirect/canonical/sitemap/cache không commit đồng bộ.
- `always_publish` làm tăng blast radius: vẫn bắt buộc score/gate, scope theo page type và có kill switch.
- AI image hoặc remote image có vấn đề bản quyền/chất lượng: nguồn và quyền sử dụng phải là metadata bắt buộc theo policy.
- Điểm cao không bảo đảm hiệu quả tìm kiếm: theo dõi GSC riêng và không tự rollback chỉ vì biến động ngắn hạn.
