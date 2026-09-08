# TECHNICAL_REQUIREMENTS.md

## 1. Active travel runtime

This repository now serves the Haidang Travel CMS and frontsite only.

Active runtime scope:

- theme: `haidangtravel`
- domains: `Tour`, `TourCategory`, `Destination`, `Region`, `Country`, `Service`, `BlogPost`, `LandingPage`, `Slider`, `TravelInquiry`, `Menu`, `SiteSetting`
- public IA: home, about, contact, service listing/detail, blog listing/detail, tour scope pages, tour taxonomy pages, tour detail pages, and custom landing pages
- default language: Vietnamese; Laravel `locale` and `fallback_locale` default to `vi`, Faker defaults to `vi_VN`, and public Open Graph locale renders as `vi`
- homepage browse-entry layer should prefer crawlable taxonomy rails for `Chủ đề tour` and `Điểm đến nổi bật` when data is available
- service category hub path: `/dich-vu/danh-muc/{slug}` for SEO-friendly service taxonomy navigation

Do not reintroduce:

- construction runtime,
- estimator flows,
- package or project pages,
- old SEO AI admin runtime.

---

## 2. Default stack and UI architecture

### Backend

- Laravel application runtime
- Livewire-powered CMS managers
- Spatie roles/permissions for admin authorization
- Spatie media library for shared media, galleries, and slider assets

### Frontend

- server-rendered Blade views under `resources/views/themes/haidangtravel`
- Tailwind-based styling with Flux UI admin surfaces
- optional slider/carousel behavior only where hero or gallery blocks truly need sequencing
- one shared frontsite inquiry modal mounted from the main theme layout and reused by header, CTA banners, contact page, tour detail, service detail, and other conversion surfaces

### Architectural defaults

- keep controllers thin,
- keep write authorization explicit on every manager route and write action,
- keep CMS editor state in Livewire components,
- keep public content rendering in frontsite controllers and theme views,
- keep custom landing-page route catch-alls at the end of `routes/frontsite.php`.

### Frontsite cache contract

- cache scope is frontsite-only; admin, API, Livewire manager screens, write requests, and non-GET requests must not be response-cached
- public frontsite response caching is attached through the `frontsite.cache` route middleware in `routes/frontsite.php`
- cache runtime ownership lives in:
  - `App\Services\Frontsite\FrontsiteCache`
  - `App\Services\Frontsite\FrontsiteCacheInvalidator`
  - `App\Http\Middleware\CacheFrontsiteResponse`
  - `App\Console\Commands\ClearFrontsiteCacheCommand`
  - `App\Console\Commands\WarmFrontsiteCacheCommand`
- frontsite cache keys use versioned groups instead of `Cache::flush()`, so clearing frontsite cache must not clear unrelated framework, queue, admin, or permission cache
- manual cache operations:
  - clear all frontsite cache: `php artisan frontsite:cache:clear`
  - clear selected groups: `php artisan frontsite:cache:clear home tours blog services landing chrome menus settings sliders taxonomies sitemap`
  - warm canonical frontsite URLs: `php artisan frontsite:cache:warm`
  - warm a small sample during local checks: `php artisan frontsite:cache:warm --limit=1`
- admin operators can also clear frontsite cache from `/admin/theme-settings`, where the page must show cache status and the result of the latest clear action
- response cache must keep CSRF safe by storing a placeholder and restoring the current request/session token on render; do not cache raw per-session tokens in HTML
- response cache must bypass session-driven feedback such as old input, validation errors, and inquiry success/error flashes
- cache Eloquent query results as arrays or stable payloads unless a local pattern explicitly hydrates models after reading; do not store mutable Eloquent model instances directly as the default cache contract
- sitemap and high-traffic frontsite query data may use stale-while-revalidate through the shared frontsite cache service when freshness tolerance is acceptable
- when adding or changing any public model, query source, media collection, menu source, slider source, landing block, or shared view composer that can change rendered frontsite HTML, update `FrontsiteCacheInvalidator` in the same patch
- when changing cache behavior, validate at least route registration, one MISS/HIT page cycle, relevant invalidation or clear command, and the shared inquiry form CSRF/session feedback behavior
- `FRONTSITE_CACHE_ENABLED` and `FRONTSITE_RESPONSE_CACHE_ENABLED` control the feature from config; cache remains disabled by default in the testing environment unless a test explicitly enables it

### Shared image upload and conversion contract

- shared image upload/pick flows in admin should use the existing Spatie Media Library popup plus `image-dropzone`; do not add a second bespoke upload contract for travel CMS image fields when the shared popup can already serve the need
- main travel objects that need single representative visuals should use Spatie collections instead of free-form URL-only fields whenever possible:
  - `Tour`, `Service`, `BlogPost`: `cover`
  - `TourCategory`, `Destination`, `Region`, `ContentCategory`: `avatar`
  - `SliderItem`: `image`, `mobile_image`, `inner_image`
  - `SiteSetting`: `logo`, `og_image`
  - `LandingPage`: hero/gallery collections resolved through `LandingPageBlocks` and `LandingPageVisuals`
- shared frontsite image conversions are `small`, `medium`, and `full`
- current conversion bounds are:
  - `small`: `500x500`
  - `medium`: `1000x1000`
  - `full`: `2400x2400`
- conversion behavior should preserve the original composition with fit-max semantics; frontsite should not assume hard crop behavior from these shared conversions
- shared conversions should keep the original image format when possible so transparent assets such as PNG avatars or logos do not get flattened onto a dark background by an implicit JPG conversion
- shared frontsite conversions should run non-queued so admin upload and targeted regenerate flows do not depend on a queue worker for core runtime image sizes
- frontsite runtime mapping is:
  - `small` for `og:image` and related social-preview image contexts
  - `small` for all frontsite card surfaces that are not the primary stage of a slider, including standard listing cards, larger taxonomy cards, blog category cards, process cards, topic rails, and static gallery tiles
  - `small` for gallery thumbnail rails and other compact thumb-strip contexts
  - `medium` only for the primary in-page stage/main image of non-hero sliders or galleries
  - `full` for hero surfaces, hero side media, and full-size open/lightbox/zoom targets
- tour/gallery UX must keep `medium` for the in-page stage preview and `full` for the opened lightbox asset; horizontal `TourCard` sliders follow the same rule while their thumbnails stay `small`
- taxonomy detail galleries for `Điểm đến` and `Chủ đề tour` override that default stage rule: their visible slider stage should resolve image slides at `full`, with the opened/lightbox source also remaining `full`
- taxonomy page-gallery behavior is page-specific rather than globally forced: the current `Chủ đề tour` detail page must build its frontsite gallery from the taxonomy `gallery` field only and must not prepend the taxonomy `avatar` as slide `1`
- taxonomy detail galleries for `Điểm đến` and `Chủ đề tour` also override the default thumbnail layout on desktop: keep mobile thumbs as the existing bottom horizontal rail, but render desktop thumbs as a vertical rail in a right-side column using an `8:2` main-stage/thumb-column ratio
- when deploying the shared conversion contract to existing media and the size definitions themselves have changed, do not use `--only-missing`; regenerate the existing files again so the new pixel bounds are actually written:
  - `php artisan media-library:regenerate --only=small --only=medium --only=full --force --no-interaction`
- `--only-missing` is only appropriate when the conversion definitions stay the same and you merely need to backfill missing derived files
- when a conversion used to render as `.jpg` and now preserves the original transparent format such as `.png`, follow the regenerate step with a media cleanup pass so stale deprecated conversion files are removed:
  - `php artisan media-library:clean --force --no-interaction`
- troubleshooting card or gallery images that do not display on local or server:
  - first clear runtime caches and ensure the public storage link exists:
  - `php artisan optimize:clear`
  - `php artisan storage:link`
  - then regenerate the shared frontsite conversions again:
  - `php artisan media-library:regenerate --only=small --only=medium --only=full --force --no-interaction`
  - and clean stale derived files:
  - `php artisan media-library:clean --force --no-interaction`
  - if `/storage/...` returns `403` while the page HTML itself still returns `200`, treat that as a missing-file symptom: the request likely fell through to Laravel's `storage/{path}` route because the underlying public file was absent
  - verify the referenced file exists on disk before assuming the symlink is broken:
  - `curl -I http://127.0.0.1:8000/storage/<path-from-html>`
  - `Test-Path storage/app/public/<same-path-without-/storage/>` on Windows PowerShell, or `test -f storage/app/public/<same-path-without-/storage/>` on Linux
  - if the original file exists but `conversions/` is missing, regenerate is the correct fix
  - if the original file itself is missing, regenerate cannot restore it; re-import or restore the source media first, or resync the dataset/storage backup that supplied that legacy `cover_image_url` / raw `/storage/...` path
  - when local development intentionally resets to the deterministic Haidang Travel launch dataset, the travel bootstrap commands remain available, but do not run them on an editor-modified database unless that reset is desired:
  - `php artisan travel:import-haidang`
  - `php artisan travel:backfill-tour-cover-images --refresh`

---

## 3. CMS navigation and permission contract

Source of truth:

- `App\Support\Admin\AdminNavigationRegistry`

Every CMS management surface must define:

- `group`
- `action`
- `route`
- `active route patterns`
- `permission key`
- icon/label metadata for the sidebar

The following systems must stay aligned with that registry:

- desktop sidebar
- mobile sidebar
- page-local admin submenus
- seeded permissions and default roles
- account permission matrix
- route middleware
- design and technical docs

Current CMS groups:

- `Quản lý tour`
- `Quản lý dịch vụ`
- `Quản lý blog`
- `Landing Pages`
- `Voucher campaigns` nằm trong nhóm `Landing Pages` để quản lý campaign promotion gắn với custom landing page.
- `Sliders`
- `Travel Inquiries`
- `Media`
- `Menus & Theme`
- `Tài khoản`

Travel review contract:

- `travel_reviews` là bảng review item riêng cho `Tour`, `TourCategory`, `Destination`
- admin review flow dùng manager route riêng theo owner: `index`, `create`, `edit`
- aggregate rating ảo vẫn nằm trên model cha qua `rating_average` và `rating_count`
- `Tour` quản lý QR đánh giá qua bảng `tour_review_batches`, mỗi batch là một lượt đánh giá theo tour - ngày khởi hành, có `departure_date`, `tour_departure_id`, `label`, `enabled`, `token`, và mật khẩu tùy chọn
- các cột cũ `review_submission_enabled`, `review_submission_password`, và `review_submission_token` chỉ giữ tương thích/backfill; source chính mới cho QR là `tour_review_batches`
- khách mở link QR `/danh-gia-tour/{tour}/{token}` được resolve theo batch đang bật và thuộc đúng tour; nếu batch có mật khẩu thì phải mở khóa, nếu mật khẩu trống thì vào form đánh giá trực tiếp
- review khách gửi từ QR lưu vào `travel_reviews` với `source = public_qr`, `tour_review_batch_id`, trạng thái mặc định `draft`, và chỉ hiển thị/schema sau khi admin duyệt sang `published`
- tour detail có form đánh giá public ngay trong trang, không cần mật khẩu, lưu `source = public_web`, có thể gắn `tour_review_batch_id` khi khách chọn ngày/lượt khởi hành
- mọi review public từ QR hoặc tour detail bắt buộc nhập số điện thoại Việt Nam hợp lệ; nếu Google reCAPTCHA v3 đang bật trong Theme Settings thì request phải validate action `tour_review`
- `TRAVEL_REVIEWS_ENABLED` chỉ kiểm soát review item chi tiết: link/màn CMS review, frontsite review block, và schema property `review`
- feature flag review không được tắt field `rating_average` / `rating_count`, rating summary trên UI, hoặc schema `AggregateRating` khi cùng rating summary đó đang hiển thị
- khi `TRAVEL_REVIEWS_ENABLED=true` và owner có review item published hợp lệ, aggregate summary/schema phải ưu tiên tính từ review thật; chỉ fallback sang `rating_average` / `rating_count` khi chưa có review thật hoặc review item đang bị tắt

Permission model:

- list routes use `.index`
- create/edit/delete flows use `.edit`
- `Admin` has full access
- `Content` defaults to:
  - `admin.blogs.index`
  - `admin.blogs.edit`
  - `admin.blogs.categories.index`
  - `admin.blogs.categories.edit`
- admins may add extra child-action permissions to content accounts
- `super_admin` remains a compatibility full-access role in storage

When adding, editing, or deleting a CMS model or manager:

- update `AdminNavigationRegistry`
- update route middleware
- update seeder role/permission sync
- update account permission UI if the new action is grantable
- update the relevant design docs in the same patch

Admin render note:

- when binding Alpine state inside Flux admin sidebar components, do not leave Blade helpers such as `@js(...)` as literal attribute text inside `x-data`; emit real JavaScript values with `Js::from(...)` or equivalent server-rendered JSON,
- current sidebar group must be visibly open from server-rendered HTML, not only after client-side hydration,
- local-storage state for admin sidebar expand/collapse must be parsed defensively so one bad stored payload does not break the whole menu.

---

## 4. Landing page block-builder contract

Landing-page authoring is block-first.

`HTML thủ công` là một escape hatch tường minh cho custom landing page cần page rỗng và dán markup trực tiếp.

Do not treat legacy landing-page fields as the primary authoring contract after rollout.

### Data rules

- system pages use `page_key`
- custom pages use a unique root `slug`
- `page_key` must stay nullable for custom landing pages
- `slug` must be unique when present
- `template_key` stores the chosen preset
- `editor_mode` stores `blocks` or `html`
- `blocks` JSON stores the ordered block configuration
- `body` may store raw pasted HTML only when `editor_mode = html`
- legacy fields may remain only for compatibility or fallback rendering
- mỗi block có trường `home_position` dùng khi `page_key = home`; giá trị này là fallback để đưa block mới vào đúng cụm ban đầu quanh các mốc homepage cố định như search, GEO answer, `home-tour-topics`, `featured-tours`, taxonomy tabs, destination slider, gallery, services, trust, process, blog preview, FAQ và CTA
- trên homepage, thứ tự public canonical nằm ở `home_config.layout_order`; danh sách này trộn token `section:{key}` của section hardcode và token `block:{uuid}` của dynamic block trong cùng một luồng hiển thị sau hero
- danh sách `Blocks` trên system page home tập trung vào nội dung block; việc move dynamic block giữa các section hardcode phải đi qua `home_config.layout_order`, còn `home_position` chỉ hỗ trợ fallback/khởi tạo layout cho block chưa có token
- mọi dynamic block không phải hero trên homepage phải có fallback anchor theo loại block, ví dụ `html_widget` và tour list trước `featured_tours`, gallery trước `gallery`, FAQ trước `faq`, CTA trước `cta`, blog list trước `blog_preview`, còn rich text / voucher / trust proof trước `trust`; không để block rơi khỏi render order chỉ vì `home_position` đang là default
- các block hero vẫn bị khoá ở đầu trang chủ; không được dùng `home_position` để đẩy H1/CTA hero xuống dưới những block khác
- các section cố định trong `home.blade.php` không được xoá khỏi template để đổi layout; dùng `home_config.layout_order` để đổi vị trí sau hero và dùng `home_config.{section}.is_enabled` để ẩn/hiện từng section
- `section_order` chỉ còn là trường tương thích được derive từ `layout_order`; không dùng nó làm source of truth mới cho homepage

### Route rules

- system pages continue to use dedicated public routes
- custom landing pages publish at `/{slug}`
- the custom landing route must stay last in `routes/frontsite.php`
- reserved root slugs must be rejected before save/publish

Reserved examples:

- `ve-chung-toi`
- `tour-trong-nuoc`
- `tour-nuoc-ngoai`
- `tour-doan`
- `chuong-trinh`
- `tour`
- `dich-vu`
- `blog`
- `lien-he`
- `danh-muc`
- `danh-muc-tour`
- `diem-den`
- `vung-mien`
- `quoc-gia`
- `admin`
- `login`
- `register`
- `robots.txt`
- `sitemap.xml`

### Template presets

- `home`
- `about`
- `contact`
- `services`
- `blog`
- `domestic_tours`
- `international_tours`
- `group_tours`
- `blank`
- `generic`

### Block catalog v1

- `hero_slider`
- `hero_media`
- `hero_demo_landingpage`
- `gallery_slider`
- `gallery_media`
- `html_widget`
- `rich_text`
- `region_rail`
- `region_taxonomy_tabs`
- `topic_rail`
- `tour_taxonomy_tabs`
- `trust_proof`
- `cta`
- `faq`
- `tour_list`
- `blog_list`

### Block rules

- every landing block carries `is_enabled`; disabled blocks stay editable in CMS but must not render on the public page
- query blocks must only resolve published runtime data; taxonomy query blocks must count related tours or posts by their published scopes instead of including draft content in visible counts
- `html_widget` starts empty and renders the pasted HTML directly at its position in the block stack for widget/snippet use cases
- `html_widget` trên homepage mặc định neo trước `featured_tours` để banner/widget không rơi xuống sau destination slider; nội dung giữ theo `uuid`, khi có nhiều widget thì move bằng `home_config.layout_order` để tránh lẫn nội dung giữa các block
- `hero_*` and `gallery_*` may source only from `slider` or shared `media popup`
- `hero_demo_landingpage` reuses the homepage demo hero section as an optional landing hero block, queries live published tours across domestic, international, and group scopes, suppresses the default landing hero when enabled, and keeps the primary CTA wired to `TravelInquiry`
- `region_rail` queries published `Region` hubs with published tours, supports `scope`, `featured`, and `limit`, and renders with the same taxonomy-card carousel family used on homepage browse-entry rails
- `region_taxonomy_tabs` renders a region-first tab block: the tablist auto-queries published `Region` hubs with live content, the active panel switches between child `Destination` or `TourCategory` cards based on `card_source_type`, mobile keeps the tablist above the cards, and desktop moves the tablist into a left sidebar
- `region_taxonomy_tabs` should pass `imageSize = medium` to active panel cards in both dynamic landing-page widgets and the homepage fixed section, even though the default frontsite card contract otherwise prefers `small`
- `topic_rail` queries the same featured `TourCategory` set currently used by homepage `Chủ đề tour`, supports `eyebrow`, `title`, `description`, `show_navigation`, and `limit`, and must hide empty heading sub-parts instead of forcing fallback copy on custom landing pages
- `tour_taxonomy_tabs` renders the same tablist family used by the homepage featured-tour section, but each tab can target one live `Region`, `Destination`, or `TourCategory` source and query tours by that taxonomy plus optional shared `scope`, `featured`, `limit`, and `sort`
- `tour_list` queries published tours by `category`, `destination`, `region`, `country`, `scope`, `featured`, `limit`, and `sort`
- `blog_list` queries published blog posts by `category`, `featured`, `limit`, and `sort`
- frontsite rendering must use live query data instead of copied snapshots
- cloned landing pages must preserve block order and media-backed assets for continued editing

Workflow standard:

- use the repo-local skill `[$landingpage-creator](../.agents/skills/landingpage-creator/SKILL.md)` when building or restructuring landing pages in this CMS.

Homepage system-page note:

- `landing_pages.home.home_config` hiện là nơi cấu hình copy và lựa chọn item ưu tiên cho các block homepage như search bar, tab tour nổi bật, slider điểm đến, dịch vụ hỗ trợ, trust section, quy trình tư vấn và blog preview,
- các cụm cố định trong `home_config` phải có `is_enabled`; khi tắt trong CMS thì frontsite không render block đó và schema homepage không dùng dữ liệu ẩn làm `mainEntity`,
- các cụm cố định trong `home_config` cũng phải giữ thứ tự qua `layout_order`; hero không nằm trong danh sách này vì hero luôn giữ H1 và CTA đầu trang,
- riêng block `tour_taxonomy_tabs` của homepage nằm trong `landing_pages.home.blocks` để có thể tái sử dụng đúng contract block-builder cho landing page khác; nếu homepage chưa cấu hình block này thì runtime có thể fallback sang một bộ tab mặc định theo taxonomy domestic đang có tour publish,
- riêng `home_config.trust` dùng contract biên tập ngắn gồm `title`, `description`, và các card `highlight/title/text`; không dùng nó như một rich-text section dài,
- các block này vẫn phải render dữ liệu live từ runtime `Tour`, `Destination`, `Service`, `BlogPost` đang publish; không snapshot cứng dữ liệu card vào `home_config`.

---

## 5. Travel SEO and schema requirements

### Thiết kế mở rộng SEO Keyword MCP

Bộ thiết kế ngày 06/09/2026 nằm tại [SEO_KEYWORD_MCP_DESIGN.md](SEO_KEYWORD_MCP_DESIGN.md) và [SEO_KEYWORD_MCP_CONTRACTS.md](SEO_KEYWORD_MCP_CONTRACTS.md). Đây là phạm vi đề xuất triển khai, chưa phải danh sách chức năng đang hoạt động.

Module `SEO AI Optimize` mới dùng URL/owner travel thật, tách khỏi SEO legacy, đã có registry/snapshot/audit kỹ thuật và luồng Codex MCP tạo đề xuất có duyệt. Xem [SEO_AI_OPTIMIZE_RUNBOOK.md](SEO_AI_OPTIMIZE_RUNBOOK.md) cho 8 tool cơ bản. Mở rộng 08/09/2026 tại [SEO_AI_OPTIMIZE_AUTOMATION.md](SEO_AI_OPTIMIZE_AUTOMATION.md) bổ sung Sheet adapter, Media và completion signal. Token thường vẫn chỉ tạo đề xuất; token `--automation` có thể kích hoạt áp dụng qua policy `always_publish` do người có quyền cấu hình trên server. Mặc định buộc preview. Chưa triển khai Apps Script/lịch production hoặc điểm ngữ nghĩa đầy đủ.

- Kiểm kê và audit URL từ thực thể CMS/route travel hiện hữu; Google Sheet quản lý chiến lược keyword và kết quả audit, CMS tiếp tục sở hữu nội dung public.
- Phần mở rộng có Page Registry, rule score 100 điểm, proposal, approval, version/idempotency, queue và re-audit sau áp dụng; implementation phải qua các gate rollout trong design.
- Giữ public IA, schema/render coupling và nguồn facts thương mại hiện hành; không kích hoạt lại SEO AI construction hoặc dùng `seo_pages` legacy làm nguồn public.

---

## 5A. Travel inquiry contract

Public inquiry contract:

- public post route remains `/yeu-cau-tu-van`
- all frontsite inquiry submissions must write into `TravelInquiry`
- inquiry context for `tour`, `service`, and `general` must stay normalized through `source`, `context_title`, and optional `meta`
- extended frontsite fields such as inquiry type, adult guest count, address, and subject should be stored in `meta` unless a wider schema change is explicitly needed; child guest count continues to use `party_size`
- promotion voucher submissions may pass `voucher_campaign_slug`; the lead still writes to `TravelInquiry`, while voucher code assignment is stored in `meta.voucher`
- voucher campaign cookies must store only the current campaign/code version context and must be invalidated when the campaign `code_set_version` changes

Frontsite form contract:

- frontsite must not maintain parallel public inquiry handlers for homepage, service, contact, and tour pages
- a shared `TravelInquiry` contract remains the runtime source of truth for public inquiry capture; the popup modal is the default surface, while approved inline CTA variants must still post into the same contract
- validation errors from modal submission should preserve the modal-open state on redirect so the user returns to the same surface with feedback visible
- mail send failures must not discard previously captured inquiry meta fields
- when JavaScript is available, active frontsite write forms should submit through Ajax and expect JSON success/error payloads instead of relying on redirect-first UX
- field-level validation must be renderable inline from the JSON error bag without inventing a second validation contract for the same form
- redirect + session flash handling should remain as a no-JS fallback, not as the primary interaction model for frontsite form submission

Listing search contract:

- homepage and frontsite listing pages should prefer one shared GET search bar contract based on the query key `q`
- the default shared listing search surface is text query + submit only; taxonomy chips/links may still exist outside the search bar when needed
- `/tour-trong-nuoc` and `/tour-nuoc-ngoai` are the controlled exception: they may add one `Chủ đề` select inside the same search surface, submitted through the GET key `category`
- the `category` options on those 2 pages must resolve from published `TourCategory` records that still have at least one published `Tour` in the current scope
- if a listing page needs to keep a current taxonomy context while searching, preserve that context through the route path or hidden GET inputs instead of introducing a second filter UI
- for the domestic and international scope pages, the route path remains the scope context and the extra `Chủ đề` select must stay visually compact instead of turning into a full multi-filter bar
- homepage taxonomy browse-entry rails should remain outside that shared search bar and link directly to canonical taxonomy hubs such as `/danh-muc-tour/{slug}` and `/tour-{slug}`
- destination items exposed in the homepage browse-entry rail should have at least one published tour behind them

### Tour departure pricing contract

- `tour_departures` là source of truth cho dữ liệu thương mại theo từng ngày đi của `Tour`, bao gồm ít nhất `departure_date`, `standard_label`, `base_price`, `sale_price`, `available_slots`, `pricing_note`, `status`, và `sort_order`
- `App\Livewire\Admin\Cms\ToursManager` phải tiếp tục map `form.departures.*` 1:1 vào contract này; không tạo một contract giá song song khác chỉ cho frontsite
- frontsite `tour detail` phải ưu tiên render phần giá từ các departure đang public (`scheduled` hoặc `published`) khi các row này có dữ liệu usable về ngày đi hoặc giá
- trong mode giá theo departure, mỗi dòng hiển thị phải giữ đúng bộ dữ liệu cùng row: `Ngày khởi hành` + `Tiêu chuẩn` + `Giá`
- `pricing_table` của `Tour` là contract phụ cho phụ thu, ghi chú giá, hoặc fallback block khi tour chưa có departure usable; không phải primary source cho lịch giá theo ngày
- schema `Offer` / `AggregateOffer` của tour detail phải tiếp tục bám cùng tập departure public đó để UI giá và structured data không lệch nhau

### Public page families

- `/tour-trong-nuoc`
- `/tour-nuoc-ngoai`
- `/tour-doan`
- `/danh-muc-tour/{slug}`
- `/tour-{slug}`
- `/vung-mien/{slug}`
- `/tour-{slug}` for country root destinations
- `/chuong-trinh/{slug}`
- `/`
- `/ve-chung-toi`
- `/dich-vu`
- `/dich-vu/danh-muc/{slug}`
- `/dich-vu/{slug}`
- `/blog`
- `/danh-muc/{slug}` for blog category hubs
- `/{slug-category}/{slug-blog}`
- `/lien-he`
- `/{custom-landing-slug}`

### Schema mapping

- homepage/company schema must use `Theme Settings -> Schema trang chủ` as the source for `Organization.image`, structured `PostalAddress`, and optional `LocalBusiness.priceRange`
- `Organization.image` fallback order: `structured_data.organization.image_url` -> sitewide OG image -> logo
- `Organization.contactPoint` may be derived from the same hotline/phone/email values already visible on the frontsite
- `Organization.address` should emit `PostalAddress` when any structured address field exists, with `streetAddress` allowed to fall back from the plain `address` field
- `PostalAddress.addressCountry` should emit the ISO 3166-1 alpha-2 country code; Haidang Travel address data should render Vietnam as `VN`, including when older CMS data stores `Việt Nam` or a nested `Country` object
- homepage should emit a simple `BreadcrumbList` for `/`
- homepage should also emit a stable `WebPage` node for `/`, including `mainEntityOfPage`, `image`, and `primaryImageOfPage` when the homepage landing exposes a usable hero image
- homepage FAQ should emit `FAQPage` only from the same first 4 visible accordion items currently rendered on the page
- homepage schema should keep exactly one primary `mainEntity` `ItemList` to avoid page-intent ambiguity on `/`; current runtime prefers the visible `tour nổi bật` block and only falls back to `Chủ đề tour` rồi `Điểm đến nổi bật` when featured tours are unavailable
- homepage featured-tour `ItemList` should serialize visible tour cards as `Product` items with `Offer`, and may add `AggregateRating` only when the same homepage card visibly renders rating data
- homepage `tour_taxonomy_tabs`, `region_taxonomy_tabs`, and other secondary browse rails remain visible UI blocks but should not emit additional homepage `ItemList` nodes
- visible homepage `blog_preview` and dynamic `blog_list` widgets may emit secondary `ItemList` nodes with `BlogPosting` items, referenced from `WebPage.hasPart`, because they represent editorial posts actually rendered on the homepage and do not replace the tour/browse `mainEntity`
- tour scope pages, category hubs, region hubs, and country hubs: `CollectionPage` + `ItemList` + `BreadcrumbList`
- destination pages: `CollectionPage` + `ItemList` + `BreadcrumbList`
- category, destination, and region hub `CollectionPage` nodes should keep stable `@id = {canonical-url}#webpage` and expose page-level `image` + `mainEntityOfPage` when the CMS provides a usable taxonomy image
- category, destination, region, country, and scope listing pages should not attach `AggregateRating` or `Review` directly to `CollectionPage`; keep page-level reviews visible in HTML, and reserve rating schema for supported reviewed item types such as tour `Product`
- tour detail: `Product` + `Offer` or `AggregateOffer` + referenced per-departure `Offer` nodes + optional itinerary `ItemList` + supporting place nodes + `BreadcrumbList`
- tour detail `Product` must map at least `name`, `description`, `image`, `productID`, `sku`, `brand`, `category`, `keywords`, `slogan`, and visible `additionalProperty` facts from the tour content already rendered on-page
- current fallback contract: `productID = tours.id`, `sku = tour_departure_sync_states.tour_code` when synced from API, otherwise `HD{published_or_created_year}{tour_id}`, `brand = site_settings.company_name | site_name`, `slogan = site_settings.site_tagline`
- public tour `Offer.availability` and `AggregateOffer.availability` must render `https://schema.org/InStock` even when local departure slots/status are used for visible UX labels
- public tour merchant return policy schema must link to the canonical booking/cancellation/refund policy page via `merchantReturnLink = https://haidangtravel.com/chinh-sach-dat-tour-huy-doi-hoan-tien`
- service listing hubs should emit a detailed `CollectionPage` node with stable `@id = {canonical-url}#webpage`, `mainEntityOfPage`, and page-level image fallbacks from category/landing imagery
- service detail pages should keep a stable `Service.@id = {canonical-url}#service`, expose `mainEntityOfPage`, and reuse the sitewide organization node as `provider`
- blog listing hubs should emit a detailed `CollectionPage` node with stable `@id = /blog#webpage`, `mainEntityOfPage`, and page-level image fallbacks from landing/category imagery
- blog detail pages should now use `BlogPosting` with stable `@id = {canonical-url}#article`, `mainEntityOfPage`, and the sitewide organization node as `publisher`
- blog `BlogPosting` nodes rendered on detail pages, listing pages, homepage blog preview, or homepage/landing blog widgets should include a stable author URL (`/tac-gia/{author-slug}`) with a real author name or the shared author fallback
- FAQ content may add `FAQPage` only when the same Q/A is visible
- visible review content may add `Review` only when the page really renders that review block and the review attaches to a Google-supported reviewed item type such as tour `Product`
- `AggregateRating` may be emitted from the visible CMS rating summary (`rating_average` + `rating_count`) when detailed review items are absent or disabled, but when review items are enabled and valid it should be calculated from those real reviews first
- custom landing pages default to `WebPage`, and may upgrade to `CollectionPage` + `ItemList` when a visible query block defines the page intent
- custom landing pages with visible `tour_list` or `tour_taxonomy_tabs` blocks should additionally emit per-tour graph nodes for `Product` + linked `TouristTrip` + departure-backed `Offer` / `AggregateOffer`; `AggregateRating` is allowed only when the landing card visibly renders rating data
- custom landing pages with visible `topic_rail`, `region_rail`, or `region_taxonomy_tabs` blocks should emit taxonomy-first `ItemList` nodes that reference the same canonical hub pages already exposed in the visible carousel/tab UI
- custom landing pages should not emit schema they cannot visibly support, and admin `schema` JSON is not the default public source in the current frontsite runtime

### Canonical and crawl rules

- one canonical URL per indexable page
- front-controller variants such as `/index.php/{path}` are duplicate SEO URLs and must 301 to the clean canonical path before render/cache; middleware should inspect the raw request URI because some web servers expose `/index.php` as the Laravel base URL instead of route path info
- filtered listing variants canonicalize to the base listing and default to `noindex,follow`
- departure data stays embedded in the tour page by default
- sitemap must include only active, canonical travel routes
- `Theme Settings` phải hỗ trợ `ga_measurement_id` và `facebook_pixel_id` để frontsite public có baseline tracking bắt buộc cho launch, paid traffic, và đo lường SEO

Never emit:

- fake reviews,
- fake ratings,
- fake prices,
- fake departure dates,
- FAQ schema for invisible content.

---

## 6. Validation checklist

Run the smallest relevant checks for the changed scope:

### Runtime

- `composer dump-autoload -o`
- `php artisan route:list`
- `php artisan test`
- for cache-sensitive frontsite changes, also verify `php artisan frontsite:cache:clear`, `php artisan frontsite:cache:warm --limit=1`, and one public page cycle with `X-Frontsite-Cache: MISS` then `X-Frontsite-Cache: HIT`

### Focused travel CMS checks

- verify admin sidebar groups and actions match `AdminNavigationRegistry`
- verify role-based sidebar filtering and deep-link authorization
- verify sliders remain a separate admin group
- verify landing-page custom slugs do not collide with reserved routes
- verify block-based landing pages render hero/gallery/query blocks correctly
- verify public travel pages still expose one visible H1 and the expected schema stack

### Frontend build

- `npm run build`

Use targeted test files when a smaller check is enough, but do not skip route and authorization verification when the task touches CMS navigation or permissions.
