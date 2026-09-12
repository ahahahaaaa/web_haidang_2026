# DOC_REFERENCE_MAP.md

Tài liệu này là điểm vào chính để đọc `docs/` theo đúng case triển khai, thay vì phải mở toàn bộ file rời rạc.

## 1. Toàn bộ file `.md` đã quét trong `docs`

| File | Nhóm chính | Vai trò | Mức ưu tiên |
| --- | --- | --- | --- |
| `docs/AGENTS.md` | Repo-wide | Quy tắc chung toàn dự án | Core |
| `docs/FRONTSITE_AGENT.md` | Frontend | Kiến trúc frontsite, page inventory, component, UX | Core |
| `docs/DESIGN_SYSTEM.md` | UX/UI | Màu sắc, typography, spacing, interaction | Core |
| `docs/BACKEND_AGENT.md` | Backend | Model, route, validation, lead flow, publish flow | Core |
| `docs/TECHNICAL_REQUIREMENTS.md` | Shared architecture | Stack, page inventory, SEO engine, queue, contracts | Core |
| `docs/CUSTOMER_LOYALTY_API_REQUIREMENTS.md` | Backend / API integration | Contract API điểm thưởng, danh sách quà, phiếu đổi quà chờ duyệt, và flag hiển thị đơn hàng/lịch sử đổi quà | Core |
| `docs/AGENCY_TOUR_SYNC_API.md` | Backend / API integration | Contract đồng bộ tour và ngày khởi hành hai chiều giữa CMS Haidang Travel và API Master Data DashBoard | Core |
| `docs/AGENCY_TOUR_SYNC_QUEUE_DESIGN.md` | Backend / admin ops | Thiết kế màn hình và lifecycle hàng chờ push tour từ CMS sang API Master Data DashBoard | Core |
| `docs/api-fix-requests/2026-05-23-agency-tour-sync-endpoints.md` | Backend / API handoff | Ticket yêu cầu API Master Data DashBoard mở endpoint tour sync và push update theo contract CMS | Support |
| `docs/SEO_SCHEMA_MAPPING.md` | SEO runtime | Mapping schema types theo từng page public và quy tắc render/schema coupling | Core |
| `docs/SEO_KEYWORD_MCP_DESIGN.md` | SEO / Keyword MCP | Thiết kế mở rộng audit và tối ưu URL travel hiện hữu; scope, UI, scoring, approval và rollout | Design proposal |
| `docs/SEO_DIRECT_MCP_OPTIMIZER_PLAN.md` | SEO / Runtime | Thiết kế và trạng thái baseline v2: direct CMS MCP, scoring, Media, backup/restore, preview/auto publish | Implemented baseline |
| `docs/SEO_KEYWORD_MCP_CONTRACTS.md` | SEO / Integration | Contract Google Sheet v1 đã retired; chỉ giữ tham chiếu lịch sử | Historical |
| `docs/SEO_AI_OPTIMIZE_RUNBOOK.md` | SEO / Runtime | Runbook v1 đã được v2 thay thế; giữ tham chiếu flow duyệt thủ công | Historical |
| `docs/SEO_AI_OPTIMIZE_AUTOMATION.md` | SEO / Automation | Thiết kế Google Sheet/Apps Script đã retired, không dùng runtime | Historical |
| `docs/TOUR_SITEMAP_BLOCKS.md` | Travel IA / SEO | Sitemap structure, page families, block inventory cho Tour / Category / Destination / Region / Country, và visual contract cho LandingPage / Slider | Core |
| `docs/PROJECT_START_GUIDE.md` | Setup | Cài đặt, migrate, chạy app, debug cơ bản | Support |
| `docs/SEO_PHASE_SUMMARY.md` | Backend / SEO | Tóm tắt tiến trình SEO engine theo phase | Historical |
| `docs/front_end/product_requirements_document.md` | Product planning | PRD template tổng quát, không ràng buộc runtime | Optional |
| `docs/front_end/demo/steel_stone/DESIGN.md` | UX/UI inspiration | Tài liệu cảm hứng thiết kế, không phải source of truth | Optional |
| `docs/DOC_REFERENCE_MAP.md` | Master map | Bản đồ tổng hợp theo case | Core |

## 2. Đường đọc mặc định để giảm dư thừa

- Task frontend: `AGENTS.md` -> `FRONTSITE_AGENT.md` -> `DESIGN_SYSTEM.md`
- Task backend: `AGENTS.md` -> `BACKEND_AGENT.md` -> `TECHNICAL_REQUIREMENTS.md`
- Task API Master Data DashBoard tour sync / hàng chờ: `AGENTS.md` -> `AGENCY_TOUR_SYNC_API.md` -> `AGENCY_TOUR_SYNC_QUEUE_DESIGN.md`
- Task SEO / render schema: `AGENTS.md` -> `FRONTSITE_AGENT.md` -> `TOUR_SITEMAP_BLOCKS.md` -> `TECHNICAL_REQUIREMENTS.md` -> `SEO_SCHEMA_MAPPING.md`
- Task SEO AI Optimize trực tiếp: `AGENTS.md` -> `TECHNICAL_REQUIREMENTS.md` -> `SEO_DIRECT_MCP_OPTIMIZER_PLAN.md` -> `.agents/skills/haidang-travel-seo-post-optimizer/SKILL.md`; đọc thêm `ADMIN_CMS.md` khi triển khai UI.
- Task UX/UI: `AGENTS.md` -> `DESIGN_SYSTEM.md` -> `TOUR_SITEMAP_BLOCKS.md` -> `FRONTSITE_AGENT.md`
- Task form: `AGENTS.md` -> `BACKEND_AGENT.md` -> `FRONTSITE_AGENT.md`
- Task slider / motion: `DESIGN_SYSTEM.md` -> `TOUR_SITEMAP_BLOCKS.md` -> `FRONTSITE_AGENT.md`
- Task setup / chạy local: `PROJECT_START_GUIDE.md`

## 3. Case Frontend

### Phạm vi

- homepage
- about/company
- landing pages biên tập như services, blog, contact
- services listing / detail
- tours listing / taxonomy / detail
- blog listing / detail
- contact / quotation CTA surfaces

### Tài liệu nên đọc

- `docs/FRONTSITE_AGENT.md`
- `docs/DESIGN_SYSTEM.md`
- `docs/TECHNICAL_REQUIREMENTS.md`
- `docs/TOUR_SITEMAP_BLOCKS.md` cho tour IA, taxonomy pages, sitemap, block order
- `docs/SEO_SCHEMA_MAPPING.md` nếu task có H1/FAQ/schema/meta

### Nội dung chính đã được gom

- page inventory và section order nằm ở `FRONTSITE_AGENT.md`
- sitemap structure và block inventory riêng cho family tour nằm ở `TOUR_SITEMAP_BLOCKS.md`
- brand, spacing, typography, material feel nằm ở `DESIGN_SYSTEM.md`
- stack và ranh giới triển khai nằm ở `TECHNICAL_REQUIREMENTS.md`
- mapping schema public runtime nằm ở `SEO_SCHEMA_MAPPING.md`
- motion text hiện được quy ước trực tiếp trong `DESIGN_SYSTEM.md`
- homepage browse-entry rails `Chủ đề tour` và `Điểm đến nổi bật` hiện được chốt ở `FRONTSITE_AGENT.md`, `DESIGN_SYSTEM.md`, `TOUR_SITEMAP_BLOCKS.md`, và `TECHNICAL_REQUIREMENTS.md`

### Không cần ưu tiên đọc cho frontend thông thường

- `docs/front_end/product_requirements_document.md`
- `docs/SEO_PHASE_SUMMARY.md`

## 4. Case UX/UI

### Tài liệu nên đọc

- `docs/DESIGN_SYSTEM.md`
- `docs/TOUR_SITEMAP_BLOCKS.md`
- `docs/FRONTSITE_AGENT.md`

### Quy ước chính

- `DESIGN_SYSTEM.md` là source of truth cho màu, type, layout, interaction
- `TOUR_SITEMAP_BLOCKS.md` giữ block contract cho travel landing page, hero/gallery slot, và quy ước `banner-location`
- `FRONTSITE_AGENT.md` dùng cho bố cục trang, component inventory và conversion UX
- motion text dùng chung quy ước trong `DESIGN_SYSTEM.md`, không thay thế design system

### Tài liệu chỉ nên xem khi cần cảm hứng

- `docs/front_end/demo/steel_stone/DESIGN.md`

Tài liệu này có thể gợi ý art direction, nhưng không nên override `DESIGN_SYSTEM.md`.

## 5. Case Form

### Phạm vi

- contact form
- consultation / quotation CTA flow
- listing filter form
- admin rich text / media form rules

### Tài liệu nên đọc

- `docs/BACKEND_AGENT.md`
- `docs/AGENTS.md`
- `docs/DESIGN_SYSTEM.md`
- `docs/FRONTSITE_AGENT.md`
- `docs/TECHNICAL_REQUIREMENTS.md`

### Nguồn nội dung theo vai trò

- validation, security, public write endpoints: `BACKEND_AGENT.md`
- lead / quotation flow: `BACKEND_AGENT.md`
- admin form action bar, SweetAlert2 feedback, visual hierarchy của nút submit: `DESIGN_SYSTEM.md`
- form UX, CTA placement, hotline visibility: `FRONTSITE_AGENT.md`
- rich text, media browser, textarea/editor rules: `AGENTS.md`
- page inventory có contact / quotation surfaces: `TECHNICAL_REQUIREMENTS.md`

### Ghi chú hợp lý hoá

Hiện chưa có một file riêng chỉ cho `form`. Vì vậy case form nên được hiểu là tổ hợp của:
- frontend conversion surface
- backend validation + persistence
- admin editor/media rules

## 6. Case Slider / Motion

### Phạm vi

- hero slider
- card carousel
- text reveal
- animation hierarchy

### Tài liệu nên đọc

- `docs/DESIGN_SYSTEM.md`
- `docs/TOUR_SITEMAP_BLOCKS.md`
- `docs/FRONTSITE_AGENT.md`
- `docs/TECHNICAL_REQUIREMENTS.md`

### Nguồn nội dung theo vai trò

- visual language, CTA hierarchy, gallery/hero slider UX: `DESIGN_SYSTEM.md`
- landing-page visual slot contract, `banner-location`, admin source selection: `TOUR_SITEMAP_BLOCKS.md`
- text reveal / reduced motion: `DESIGN_SYSTEM.md`
- motion restraint, avoid overuse, CTA motion level: `DESIGN_SYSTEM.md`
- performance rule và tránh lạm dụng slider: `FRONTSITE_AGENT.md`
- thư viện carousel chỉ là tuỳ chọn, không bắt buộc: `TECHNICAL_REQUIREMENTS.md`

### Ghi chú hợp lý hoá

Hiện đã có spec rõ hơn cho case này:
- layout visual, CTA hierarchy, và motion từ `DESIGN_SYSTEM.md`
- landing hero/gallery source rules và `banner-location` từ `TOUR_SITEMAP_BLOCKS.md`
- performance guardrail và SEO-safe rendering từ `FRONTSITE_AGENT.md`

## 7. Case Backend

### Phạm vi

- model/domain
- request validation
- routes/API
- lead capture
- quotation flow
- SEO engine
- queue chain
- publish workflow

### Tài liệu nên đọc

- `docs/BACKEND_AGENT.md`
- `docs/TECHNICAL_REQUIREMENTS.md`
- `docs/TOUR_SITEMAP_BLOCKS.md`
- `docs/SEO_SCHEMA_MAPPING.md`
- `docs/AGENTS.md`

### Tài liệu chỉ dùng thêm khi cần bối cảnh

- `docs/SEO_PHASE_SUMMARY.md`

### Nguồn nội dung theo vai trò

- domain model, API rules, validation, security: `BACKEND_AGENT.md`
- sitemap/page family/block order cho tour runtime: `TOUR_SITEMAP_BLOCKS.md`
- stack, queue chain, AI contracts, schema/meta rules: `TECHNICAL_REQUIREMENTS.md`
- mapping schema render public theo từng route: `SEO_SCHEMA_MAPPING.md`
- repo-wide coding behavior: `AGENTS.md`
- lịch sử evolution của SEO engine: `SEO_PHASE_SUMMARY.md`
- kế hoạch runtime hiện hành cho tối ưu trực tiếp kiểu RankMath, scoring, backup, media, publish policy và gỡ SEO legacy: `SEO_DIRECT_MCP_OPTIMIZER_PLAN.md`.
- thiết kế MCP/Sheet trước đây chỉ còn dùng tham chiếu inventory, keyword/entity và contract audit: `SEO_KEYWORD_MCP_DESIGN.md` + `SEO_KEYWORD_MCP_CONTRACTS.md`; không dùng làm runtime hoặc dùng `seo_pages` legacy làm inventory travel public.

## 8. Code HTML Mẫu

### Frontend chính

- `docs/front_end/demo/home_page/code.html`: homepage desktop
- `docs/front_end/demo/home_page_mobile/code.html`: homepage mobile
- `docs/front_end/demo/service_detail/code.html`: service detail desktop
- `docs/front_end/demo/mobile_service_detail/code.html`: service detail mobile
- `docs/front_end/demo/project_category/code.html`: listing / category desktop tham chiếu
- `docs/front_end/demo/mobile_project/code.html`: listing mobile tham chiếu
- `docs/front_end/demo/blog/code.html`: blog listing desktop
- `docs/front_end/demo/mobile_blog/code.html`: blog listing mobile
- `docs/front_end/demo/blog_detail/code.html`: blog detail desktop
- `docs/front_end/demo/interior_detail/code.html`: detail page tham chiếu thêm cho layout dài dạng editorial/detail

### Cách dùng hợp lý

- homepage: ưu tiên `home_page` + `home_page_mobile` cho nhịp bố cục tổng; taxonomy discovery rail hiện hành vẫn phải bám theo các rule mới trong `FRONTSITE_AGENT.md`, `DESIGN_SYSTEM.md`, `TOUR_SITEMAP_BLOCKS.md`, và `TECHNICAL_REQUIREMENTS.md`
- listing page: ưu tiên `project_category`, `blog`, `mobile_project`, `mobile_blog` như layout tham chiếu, rồi map lại sang travel runtime hiện tại
- detail page: ưu tiên `service_detail`, `mobile_service_detail`, `blog_detail`, `interior_detail`
- slider / motion / hero: ưu tiên `home_page` và `service_detail`
- form CTA / conversion section: tham chiếu `home_page` và `service_detail`

## 9. Thành phần dư thừa đã được gom lại

Các file sau không nên được đọc mặc định cho task code hằng ngày:

- `docs/front_end/product_requirements_document.md`
  - chỉ là PRD template tổng quát
  - dùng khi viết brief/product doc mới
- `docs/SEO_PHASE_SUMMARY.md`
  - dùng khi cần hiểu lịch sử phase SEO engine
  - không phải tài liệu triển khai chính hiện tại
- `docs/front_end/demo/steel_stone/DESIGN.md`
  - dùng để lấy cảm hứng visual
  - không override `DESIGN_SYSTEM.md`
- `docs/PROJECT_START_GUIDE.md`
  - chỉ dùng cho setup/run/debug
  - không chứa rule frontend/backend runtime

## 10. Conflict Rule

Thứ tự ưu tiên:

1. direct user instruction
2. case đúng nhất trong file này
3. nearest specialized document
4. `docs/AGENTS.md`

## 11. Kết luận ngắn

Nếu cần đọc ít nhưng đúng:

- frontend: `FRONTSITE_AGENT.md` + `DESIGN_SYSTEM.md`
- backend: `BACKEND_AGENT.md` + `TECHNICAL_REQUIREMENTS.md`
- ux/ui: `DESIGN_SYSTEM.md` + `TOUR_SITEMAP_BLOCKS.md`
- form: `BACKEND_AGENT.md` + `FRONTSITE_AGENT.md`
- slider/motion: `DESIGN_SYSTEM.md` + `TOUR_SITEMAP_BLOCKS.md`
