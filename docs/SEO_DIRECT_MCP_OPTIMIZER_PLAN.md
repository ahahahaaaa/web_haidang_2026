# SEO AI Direct MCP Optimizer v2

Ngày chốt thiết kế: 08/09/2026  
Trạng thái: baseline runtime v2 đã triển khai ngày 08/09/2026; rollout production và drop bảng legacy vẫn cần thao tác deploy riêng

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
  -> claim_next_content_optimization
      -> ưu tiên task Đang chờ do người dùng tạo trong admin
      -> khi hàng chờ phù hợp đã hết mới tự chọn URL mới
  -> snapshot + keyword brief + writable fields + source version
  -> audit_content
  -> tạo patch/FAQ/link/media phù hợp
  -> submit_seo_optimization
      -> admin_queue: dừng ở proposal chờ người duyệt
      -> automatic_selection: commit_content_optimization
      -> validate revision + policy + facts + HTML
      -> immutable backup
      -> transaction ghi CMS + audit/outbox
      -> preview hoặc publish do server quyết định
      -> cache invalidation + public verification
  -> get_content_optimization_result
```

`claim_next_content_optimization` luôn ưu tiên task `queued` do người dùng đã đưa vào `/admin/seo-optimization/tasks`, theo thứ tự tạo cũ trước và trong phạm vi quyền/token. Task admin luôn dừng sau submit để chờ người duyệt, kể cả khi policy là `always_publish`. Chỉ khi hàng chờ admin phù hợp đã hết, tool mới chọn bài public/indexable trong phạm vi policy/token, ưu tiên trang chưa audit hoặc điểm thấp và không nhận lại cùng source/brief revision. Lượt chạy chỉ dành cho danh sách admin truyền `admin_queue_only=true`; hết danh sách sẽ trả `task=null` mà không tạo task tự chọn. Lease có hạn và worker khác không được giữ cùng bài.

Task có audit cùng source version, brief revision và rule version với điểm lớn hơn `SEO_OPTIMIZATION_PROCESSING_SCORE_THRESHOLD` (mặc định `80`) được chuyển sang `skipped`, ghi rõ điểm/ngưỡng trong event rồi worker tiếp tục task kế tiếp. Điểm đúng 80 vẫn được xử lý. Quy tắc áp dụng cho cả hàng chờ admin và lựa chọn tự động; audit cũ của nội dung/brief/rule khác không được dùng để bỏ qua nhầm URL.

Nội dung và facts đã có trong snapshot CMS là baseline chính xác để giữ hoặc diễn đạt rõ hơn, không cần tài liệu ngoài. `NEED_DATA` chỉ dùng khi yêu cầu bắt buộc thêm hoặc đổi một fact nhưng không có nguồn cho giá trị mới; nếu không có yêu cầu đổi fact, Codex giữ nguyên đoạn đó và tiếp tục tối ưu phần còn lại.

## 4. Keyword và chống cannibalization

Thứ tự chọn keyword mục tiêu đầy đủ:

1. Keyword set ACTIVE gắn trực tiếp Page ID.
2. Keyword set ACTIVE của entity/taxonomy liên quan.
3. Profile theo page type.
4. Bộ keyword tổng của site.
5. AI suy luận từ nội dung chỉ tạo đề xuất, không tự trở thành OWNER.

Baseline hiện hành dùng keyword brief đã gắn trực tiếp trang. Khi brief thiếu, server ưu tiên một keyword trong `SiteSetting.seo_keywords` nếu keyword đó khớp chính xác tiêu đề hoặc đường dẫn trang; nếu không có kết quả khớp duy nhất, tiêu đề trang được dùng làm primary riêng. Các keyword tổng còn lại trở thành secondary keywords. Trang danh sách admin có thao tác đối soát hàng loạt theo toàn bộ bộ lọc hiện tại; thao tác này chỉ tạo mới hoặc làm mới brief có nguồn tự động và luôn giữ brief đã chỉnh thủ công. Các profile entity/taxonomy chuyên sâu ở bước 2–3 là phần mở rộng tiếp theo, chưa được ghi nhận là đã triển khai.

Mỗi cặp primary keyword + intent chỉ có một OWNER trong cùng locale/site ở baseline. Khi claim tự động, trang trùng cặp này bị bỏ qua và ghi sự kiện `automation.keyword_conflict_skipped`; server không tự publish. Kiểm tra gần nghĩa và vai trò SUPPORTING/AVOID là bước mở rộng tiếp theo.

Brief tối thiểu gồm primary keyword, secondary keywords, intent, entities, required/optional topics, forbidden claims, fact references, internal-link candidates và keyword source/version.

## 5. Field và quy tắc ghi

Mỗi adapter khai báo `readable_fields`, `writable_fields`, validator và tác động render. Không có generic mass assignment.

Contract runtime hiện dùng version `cms-content-contract-v3`. Snapshot trả `field_contracts` để mô tả label/kind của từng field. Tour và BlogPost ghi `title/slug/excerpt/content/meta/cover_alt/faq`; Destination/Country/TourCategory ghi `name/slug/excerpt/content/meta/cover_alt/faq`; danh mục bài viết chỉ ghi `name/slug/description` dạng plain text và `faq_items`, không dùng cột `content` đang không có trong editor blog.

LandingPage có hai contract tách biệt:

- `editor_mode=html`: nội dung chính là `body` dạng HTML thủ công, được kiểm tra mã thực thi nguy hiểm nhưng không đi qua sanitizer rich-text làm mất layout.
- `editor_mode=blocks`: patch dùng `block_changes` theo `uuid + type + changes`; server chỉ mở các field nội dung được công bố trong `content_units`. Block tắt, thiếu UUID ổn định hoặc trùng UUID bị loại khỏi contract; thứ tự, trạng thái, media, query/filter, URL CTA, `home_position` và `home_config` luôn được giữ nguyên. Sau merge, server normalize blocks và đồng bộ lại các cột compatibility hero/intro/body/CTA/FAQ/visual giống CMS editor.

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

Ở policy `preview`, slug chờ người duyệt. Ở policy `always_publish`, slug được server tự áp dụng khi điểm sau lớn hơn điểm trước và sau khi kiểm tra unique/collision; redirect 301 được ghi cùng transaction nội dung, còn redirect cũ được rút gọn để tránh chuỗi redirect.

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

Commit tự động dùng optimistic locking. Source version đổi sau khi Codex nhận bài trả `STALE_SOURCE`; luồng Codex/`always_publish` không được tự rebase. Riêng khi người có quyền duyệt chủ động duyệt trong CMS, quyết định của người dùng là ưu tiên cao nhất: server kiểm tra lại quyền, integrity, facts và allowlist, rồi rebase `before` cùng source/brief version về dữ liệu CMS mới nhất và ghi audit `human_override`. Nội dung public vẫn chỉ đổi ở thao tác Áp dụng; nếu trường được duyệt bị sửa thêm sau lần duyệt thì optimistic field check chặn ghi đè và yêu cầu duyệt lại. Database mutation, backup, audit và outbox ghi trong cùng transaction khi storage cho phép.

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

Mỗi proposal mới lưu tóm tắt điểm baseline từ snapshot gốc trong `qa.baseline_seo_gate`, giữ `qa.seo_gate` là điểm sau đề xuất mang tính dự kiến, và dùng audit liên kết qua `audit_id` làm điểm sau áp dụng đã tái kiểm tra CMS. Proposal cũ không có baseline phải hiển thị thiếu dữ liệu nền, không tính lại bằng rule hiện hành vì có thể làm sai lệch lịch sử.

| Cấp | Điểm | Ý nghĩa |
| --- | ---: | --- |
| A+ | 95–100 | Xuất sắc |
| A | 90–94.9 | Rất tốt |
| B | 80–89.9 | Tốt nhưng còn P2/P3 |
| C | 65–79.9 | Cần tối ưu đáng kể |
| D | 50–64.9 | Yếu |
| F | 0–49.9 | Không đạt |

Severity độc lập với điểm:

- `P0`: nguy cơ bảo mật/private content, sai owner hoặc mutation nguy hiểm; chặn commit/publish và cap điểm 49.
- `P1`: index/canonical/intent/fact nghiêm trọng; cap điểm 79 và hiển thị cảnh báo chất lượng.
- `P2`: thiếu topic/entity/link/meta quan trọng; ưu tiên sửa.
- `P3`: polish readability/alt/heading; backlog.

Quy tắc publish tăng điểm: cấu hình `always_publish` tự áp dụng mọi đề xuất có score sau lớn hơn score trước, không phụ thuộc cấp điểm hoặc ngưỡng PASS tuyệt đối. Nếu score không tăng hoặc không tính được cả hai mốc, đề xuất giữ ở preview. Quy tắc này không bỏ qua quyền, phạm vi page type, source version, patch/HTML an toàn, dữ kiện mới, Media, backup, slug unique/redirect và kiểm tra sau áp dụng.

Mọi luồng publish/apply nội dung SEO thông thường, gồm cả thao tác thủ công sau duyệt, đều phải chấm lại trên snapshot CMS mới nhất và qua cùng điều kiện cuối tại server: `new_score > old_score`. Không có điểm ở một trong hai mốc, bằng điểm hoặc giảm điểm thì không được ghi CMS. Đề xuất khôi phục từ backup là ngoại lệ có chủ đích vì phục hồi sự cố không được phụ thuộc vào điểm SEO; khôi phục vẫn bắt buộc duyệt, kiểm tra quyền, source version, integrity và tạo audit.

Trong màn hình duyệt, SEOer có thể sửa trực tiếp các field đã nằm trong đề xuất và yêu cầu chấm lại. Server tách patch thành từng field; riêng LandingPage blocks tách tới từng field của block. Mỗi vòng chỉ giữ đơn vị làm tăng tổng điểm so với tập đã chấp nhận, lưu danh sách giữ/bỏ cùng delta theo dimension vào QA. Nếu kết quả cuối tăng điểm và người thao tác có cả quyền duyệt lẫn áp dụng, server có thể duyệt và commit ngay theo yêu cầu chủ động đó; vẫn bắt buộc source/brief hiện hành, approval hash, fact/HTML/media gate, backup bất biến, audit và verification. Điểm không tăng, không tính được hoặc thiếu fact thì không tự ghi CMS.

## 9. Điểm ADA/accessibility và GSC

`accessibility_score` là thang 100 riêng cho nội dung/render có thể kiểm tra: H1/heading semantic, alt, link text, language/readability, list/table markup, form label liên quan, contrast/focus khi crawler trình duyệt có bằng chứng. Đây là quality signal, không phải chứng nhận tuân thủ ADA hoặc WCAG.

`search_console_readiness` là thang 0–100 riêng cho điều kiện kỹ thuật có thể kiểm tra tại server: HTTP 200, indexability, canonical và robots. Đây không phải dữ liệu hiệu suất Google Search Console thật. Khi bổ sung connector GSC, `gsc_opportunity_score` phải là chỉ số riêng từ impressions/CTR/position có timestamp; không có dữ liệu thì để `null`.

## 10. Hợp đồng MCP v2

| Tool | Vai trò |
| --- | --- |
| `list_seo_pages` | Lọc inventory và trạng thái tối ưu |
| `claim_next_content_optimization` | Ưu tiên claim hàng chờ admin; hỗ trợ `admin_queue_only=true`, nếu không thì khi hết mới tự chọn bài trực tiếp từ CMS; trả lease và brief |
| `get_seo_page_snapshot` | Snapshot đầy đủ theo scope/field allowlist |
| `seo_page_check` | Lưu audit có evidence, score/grade/gates |
| `prepare_seo_image` | Tái sử dụng, tải hoặc cấp upload lease cho ảnh |
| `commit_content_optimization` | Nộp patch; server backup, quyết định preview/publish và commit |
| `get_seo_proposal` | Trả proposal/apply/verify, QA và lỗi chuẩn |
| `list_content_backups` | Liệt kê version có thể xem/khôi phục |
| `request_content_restore` | Tạo reverse proposal an toàn, luôn chờ duyệt |
| `report_seo_optimization_failure` | Ghi lỗi có thể retry hoặc cần người xử lý |

Các tool tạo đề xuất thủ công v1 vẫn được giữ vì cùng dùng service an toàn hiện hành. Tool, gateway, command và cấu hình Google Sheet đã được gỡ; không còn alias Sheet trong MCP v2.

### 10.1. Tạo nội dung CMS mới (v2.2)

Luồng tạo record mới tách khỏi task tối ưu URL hiện hữu:

```text
list_cms_content_creation_types
  -> start_cms_content_creation
  -> search_cms_content_media hoặc upload multipart vào upload_url
  -> submit_cms_content_creation
  -> get_cms_content_creation
```

- Token phải có ability `create`, quyền Media và quyền edit đúng loại CMS; ability `automate` không tự cấp quyền tạo bài.
- Payload chỉ nhận field công bố trong `cms-content-creation-v1`; field publish/canonical/schema và dữ liệu thương mại là server-only.
- `BlogPost`, `Tour`, `Service`, `TourCategory`, `Destination/Country`, `Region` luôn được tạo `draft`; landing custom luôn `is_active=false`.
- `ContentCategory` blog/service chưa có lifecycle draft nên submit chỉ lưu `ready_for_review`; quản trị viên phải xác nhận trước khi tạo taxonomy thật.
- File ảnh mới được kiểm MIME, phần mở rộng, dung lượng và pixel, chuyển WebP rồi lưu ở Spatie collection `library`. Database chỉ lưu metadata/relation Media, không lưu binary/base64.
- Ảnh đại diện dùng collection `cover` hoặc `avatar`; gallery/landing dùng collection theo UUID; ảnh trong nội dung dùng marker `[[media:ref]]` và server dựng HTML từ Media ID đã xác thực.
- `seo_readiness` là kiểm tra payload trước publish, không thay cho audit 12 tiêu chí dựa trên URL public/render.

## 11. Gỡ SEO legacy và code dư thừa

### 11.1. Kết quả isolation đã thực hiện

- `bootstrap/app.php` không còn nạp `routes/admin_seo.php`, `routes/ai.php`, `routes/api_v1/seo.php`.
- Route legacy `/admin/seo/pages`, `/ai/seo/*` và `/v1/seo/*` đã bị gỡ; runtime chỉ còn `/admin/seo-optimization` và `/mcp/seo-optimization` cho module này.
- Database local đang có 7 `content_clusters`, 10 `seo_pages`, 30 `seo_links`.
- Không có job chứa SEO legacy trong `jobs` hoặc `failed_jobs` tại thời điểm rà soát.
- Controller/job/command/provider/model/view/test chỉ phục vụ SEO Pages legacy đã được gỡ khỏi code chạy.
- `OptimizationSheetGateway`, tool Sheet, sync command/config và nội dung UI Sheet đã được gỡ.
- Bảng dữ liệu legacy vẫn được giữ để xuất/đối soát trước forward migration drop; không còn route hoặc service runtime ghi vào các bảng này.

### 11.2. Giữ lại

- `app/Services/Seo/SitemapBuilder.php`, sitemap/robots views và SEO frontsite đang render từ entity travel thật.
- Canonical, metadata, JSON-LD/GEO helper của theme `haidangtravel`.
- `config/frontsite_seo.php` và route/public IA travel hiện hành.
- Module `App\Services\SeoOptimization`, model/table/audit/proposal/permission hiện tại làm nền cho v2, sau khi đơn giản hóa.
- Spatie Media Library và cache invalidation hiện hành.

### 11.3. Đã gỡ khỏi runtime

Thực hiện theo reference graph và diff review, không xóa thư mục rộng theo tên `seo`:

1. Ba route legacy, controller/request/Livewire/view và namespace `src/Domains/Seo` đã được xóa.
2. Command/job/service/provider/test chỉ phục vụ cluster/page/link cũ đã được xóa.
3. `config/seo_ai.php` và luồng `blog:automation:run` dùng OpenAI trực tiếp, còn mang prompt construction, đã được xóa; CRUD BlogPost bình thường vẫn giữ nguyên.
4. Example/demo construction của nhánh SEO cũ đã được gỡ.
5. `OptimizationSheetGateway`, `ClaimSeoSheetOptimization`, Sheet sync command/config/UI đã gỡ. Nếu cần import/export về sau, xây adapter tùy chọn không tham gia runtime chính.
6. Bảng legacy chỉ còn ở database để xuất/đối soát; chưa có migration drop trong baseline này.

### 11.4. Baseline v2 đã triển khai

- Direct CMS selector + keyword fallback từ brief riêng, tiêu đề trang và `SiteSetting.seo_keywords`.
- Brief có từ khóa chính nhưng để trống topic/entity được bổ sung bằng bộ tiêu chí xác định theo loại trang trước khi enqueue; audit dùng cùng fallback ở chế độ chỉ đọc. Danh sách người dùng đã nhập luôn được giữ nguyên.
- Patch allowlist hỗ trợ title/name, slug, excerpt, meta, rich content, cover alt và FAQ tùy adapter.
- SEO score 12 dimension, cấp A+–F, P0–P3, companion score accessibility và Search Console readiness.
- `always_publish` tự áp dụng khi điểm sau lớn hơn điểm trước; điểm không tăng hoặc không tính được hai mốc tự giữ preview.
- Backup bất biến có checksum được tạo trước mutation; MCP liệt kê backup và chỉ tạo restore proposal.
- Slug được kiểm tra unique và tạo redirect 301 trong cùng transaction; `always_publish` tự áp dụng khi điểm tăng, còn `preview` chờ người có quyền duyệt.
- Media trực tiếp dùng Spatie Library: reuse cùng site, download ngoài site có kiểm tra, hoặc nhận ảnh Codex tạo.
- Hàng chờ admin hỗ trợ xóa từng task và dọn toàn bộ task đã kết thúc bằng soft delete; khóa task đang có lease, đồng thời giữ nguyên proposal, asset, backup và audit event.
- Trang cấu hình MCP hiển thị catalog tool theo đúng credential đã chọn của từng tài khoản. Credential đã thu hồi được phép soft delete; task tạo nội dung và audit vẫn truy xuất được credential lịch sử.
- Skill `.agents/skills/haidang-travel-seo-post-optimizer` mô tả luồng Codex Schedule v2.

Không xóa `SitemapBuilder`, robots/sitemap, trường meta/schema của Tour/Service/BlogPost/LandingPage hoặc SEO helper theme chỉ vì có chữ “SEO”. Thư mục lồng `haidangtravel/` phải xác minh ownership/deploy trước khi xử lý; không đưa vào lệnh xóa đệ quy chung.

### 11.5. Dữ liệu và migration

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
- Test matrix bao gồm permissions, claim lease, stale source, human approval rebase có audit, chặn sửa mới sau duyệt, idempotency, backup, restore conflict, preview, publish khi score tăng, giữ preview khi score không tăng, slug redirect, sanitize HTML, facts gốc/facts mới, media SSRF/MIME/size và public verification.
- Điểm kiểm thử đủ biên 49.9/50/64.9/65/79.9/80/89.9/90/94.9/95/100; P0/P1 cap; `overall_score=null` khi chưa assessed.
- Regression đạt cho Tour, Service, BlogPost, LandingPage, taxonomy, sitemap, robots, canonical, schema/GEO, media và CTA `TravelInquiry`.
- Chạy `php artisan test`, `composer dump-autoload -o`, `php artisan route:list` và `npm run build` khi UI/assets thay đổi.
- Production rollout có kill switch apply, batch limit, log/audit/outbox, cảnh báo lỗi và hướng dẫn restore.

## 14. Rủi ro cần khóa

- Route cache hoặc worker cũ có thể còn class đã xóa: clear/rebuild cache, restart worker và kiểm tra queue trước deploy.
- Record legacy có thể được người dùng lưu để tham khảo: export và duyệt mapping trước drop.
- Thay slug làm mất traffic nếu redirect/canonical/sitemap/cache không commit đồng bộ.
- `always_publish` làm tăng blast radius vì không còn ngưỡng tuyệt đối: vẫn bắt buộc score tăng, scope theo page type, backup, kiểm tra xung đột và kill switch.
- AI image hoặc remote image có vấn đề bản quyền/chất lượng: nguồn và quyền sử dụng phải là metadata bắt buộc theo policy.
- Điểm cao không bảo đảm hiệu quả tìm kiếm: theo dõi GSC riêng và không tự rollback chỉ vì biến động ngắn hạn.
