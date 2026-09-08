# TOUR_SITEMAP_BLOCKS.md

## 1. Purpose

This document records the canonical sitemap structure, page families, and block inventory for the active Haidang Travel tour runtime.

Use this file when the task touches:
- travel IA,
- tour sitemap,
- tour taxonomy pages,
- tour detail page structure,
- listing filters and indexability,
- internal linking between `Tour`, `TourCategory`, `Destination`, `Region`, and `Country`,
- shared landing page hero/gallery routing,
- slider-to-landing visual contracts,
- CMS sidebar grouping,
- admin permission mapping,
- landing-page block-builder contracts.

This file extends:
- `docs/FRONTSITE_AGENT.md`
- `docs/SEO_SCHEMA_MAPPING.md`
- `docs/TECHNICAL_REQUIREMENTS.md`

If a future implementation conflicts with this document for the tour runtime, this document wins.

---

## 2. Public sitemap structure

### Core public routes

Always treat the following URLs as the primary travel page family:

- `/tour-trong-nuoc`
- `/tour-nuoc-ngoai`
- `/tour-doan`
- `/danh-muc-tour/{category-slug}`
- `/tour-{destination-slug}`
- `/vung-mien/{region-slug}`
- `/tour-{country-slug}` for country root destinations
- `/chuong-trinh/{tour-slug}`

Supporting travel routes remain:

- `/`
- `/ve-chung-toi`
- `/dich-vu`
- `/dich-vu/{service-slug}`
- `/blog`
- `/{blog-category-slug}/{post-slug}`
- `/lien-he`

### URL intent map

- `Tour scope` pages are the top browse entry for domestic, international, and group-tour intent.
- `Tour category` pages are thematic or commercial listing hubs.
- `Destination` pages are place-first hubs with browse + decision support intent.
- `Region` pages are grouping hubs for broader discovery.
- `Country` pages are top-level geographic hubs.
- `Tour detail` is the main commercial money page.
- `Departure` stays embedded inside the tour detail page and must not become a standalone indexable URL by default.

### Sitemap inclusion rules

Include only:

- published pages,
- active travel routes,
- tour taxonomy hubs that currently have at least one published tour,
- canonical public URLs only.

Do not include:

- deprecated construction, estimator, package, project, or old SEO AI runtime routes,
- filtered listing URLs,
- sort URLs,
- departure parameter URLs such as `?departure=...`.

### Canonical and robots rules

- Each indexable page must resolve to exactly one canonical URL.
- Listing filters such as `departure_date`, `transport`, `category`, and `destination` must canonicalize back to the base listing URL.
- Filtered listing URLs should default to `noindex,follow`.
- Pagination may stay indexable only when the page remains part of the main listing sequence and not a filter-only variant.

---

## 3. Frontsite page families and block inventory

### 3.1 Tour scope pages

Applies to:

- `/tour-trong-nuoc`
- `/tour-nuoc-ngoai`
- `/tour-doan`

Required block order:

1. Hero with scope headline and supporting copy
2. Breadcrumbs
3. Intro / editorial context block
4. Primary filter bar, placed directly above the tour-count / tour-grid area
5. Active-filter summary block
6. Tour grid with result count
7. Pagination
8. Final CTA / inquiry support block

Required search contract:

- all scope pages keep one primary GET search surface anchored to the query key `q`
- `/tour-trong-nuoc` and `/tour-nuoc-ngoai` extend that same surface with one additional `Chủ đề` select bound to the query key `category`
- the `Chủ đề` select resolves live `TourCategory` options that still have at least one published `Tour` in the current scope
- the selected topic must filter inside the current route scope only; it does not replace the scope route or create a cross-scope search state
- `/tour-doan` stays on the default text-query-only search bar unless a later task explicitly changes that product rule

### 3.2 Tour category pages

Applies to:

- `/danh-muc-tour/{category-slug}`

Required block order:

1. Hero with category identity
2. Breadcrumbs
3. Fixed context chips for the current category
4. Category intro / body copy block
5. Primary filter bar, placed directly above the tour-count / tour-grid area
6. Active-filter summary block
7. Tour grid
8. Pagination
9. CTA / contact block

Notes:

- category context must stay visible even when filters are applied,
- category pages should help users compare tours inside one commercial intent family,
- do not render a separate generated helper heading such as `Danh sách tour thuộc ...`; the hero title and editorial content already carry that context,
- destination filter remains available unless the page is intentionally locked to a destination,
- when real `rating_average` and `rating_count` exist on the current `TourCategory`, show a compact rating summary in the hero/meta strip using the same trust rhythm as tour detail,
- if the current category has published reviews, render the review block after pagination and before FAQ or final CTA instead of hiding all social proof inside schema only.

### 3.3 Destination pages

Applies to:

- `/tour-{destination-slug}`

Required block order:

1. Hero with destination identity
2. Breadcrumbs
3. Fixed context chips
4. Destination intro / body copy
5. Primary filter bar, placed directly above the tour-count / tour-grid area
6. Supporting place context block if available
7. Active-filter summary block
8. Tour grid
9. Pagination
10. CTA / inquiry support block

Internal-link requirements:

- link to the parent region when available,
- link to the parent country when available,
- link to matching category hubs where relevant.

Presentation notes:

- when real `rating_average` and `rating_count` exist on the current `Destination`, show a compact rating summary in the hero/meta strip using the same trust rhythm as tour detail,
- if the current destination has published reviews, render the review block after pagination and before FAQ or final CTA instead of hiding all social proof inside schema only.

Schema contract:

- destination pages stay listing-first and should use `CollectionPage` as the canonical page entity
- visible reviews and aggregate rating on destination pages should remain visible HTML content, but should not attach directly to `CollectionPage` in JSON-LD
- không emit thêm `TouristDestination` node cho chính trang điểm đến nếu page intent hiện tại vẫn là hub/listing

### 3.4 Region pages

Applies to:

- `/vung-mien/{region-slug}`

Required block order:

1. Hero with region identity
2. Breadcrumbs
3. Fixed context chips
4. Region intro / body copy
5. Primary filter bar, placed directly above the tour-count / tour-grid area
6. Major destination shortcuts when available
7. Active-filter summary block
8. Tour grid
9. Pagination
10. CTA block

### 3.5 Country pages

Applies to:

- `/tour-{country-slug}` when the destination record is a country root

Required block order:

1. Hero with country identity
2. Breadcrumbs
3. Fixed context chips
4. Country intro / body copy
5. Primary filter bar, placed directly above the tour-count / tour-grid area
6. Region or destination bridge block when available
7. Active-filter summary block
8. Tour grid
9. Pagination
10. CTA block

### 3.6 Tour detail

Applies to:

- `/chuong-trinh/{tour-slug}`

Required block order:

1. Hero with H1, excerpt, CTA, and summary facts
2. Breadcrumb strip
3. Tour overview / main body copy
4. Departure offers block
5. Itinerary block
6. Gallery / media block
7. Pricing table and inclusions block
8. FAQ block when data exists
9. Related tours block
10. Inquiry form / CTA block

Pricing / inclusions presentation rule:

- the pricing + inclusions area on tour detail should not render an extra outer section heading such as `Giá tour và quyền lợi đi kèm`,
- let the inner pricing and inclusions cards carry their own headings so the block enters directly into useful commercial information without repeating a generic wrapper title.
- when published departures exist, the primary visible `Bảng giá` block should mirror those departure rows with the commercial trio `Ngày khởi hành`, `Tiêu chuẩn`, and `Giá`
- `pricing_table` may still render below that primary departure-backed table as a supplemental list for phụ thu, ghi chú thêm, hoặc các mức giá không gắn với một ngày đi cụ thể

Visible facts that should stay above the fold when available:

- departure location,
- duration,
- transport,
- accommodation standard,
- main CTA,
- contact shortcut.

Internal-link requirements:

- link from the tour detail page to its category,
- link to destination,
- link to region,
- link to country,
- link to related tours.

Schema contract:

- the primary entity is `Product` with a stable `@id`
- the tour `Product` should map `name`, `description`, `image`, `productID`, `sku`, `brand`, `category`, `keywords`, `slogan`, and visible `additionalProperty` facts from the hero / summary block
- current repo fallback contract: `productID = tours.id`, `sku = tours.slug`, `brand = Theme Settings company/site name`, `slogan = Theme Settings site_tagline`
- the pricing graph should emit one `Offer` for one valid departure-backed price, or an `AggregateOffer` plus referenced child `Offer` nodes when multiple valid departures are shown
- tour offers should reuse real departure-backed fields already shown on-page such as `price`, `priceCurrency`, `availability`, `itemCondition`, `seller`, và anchor URL của block giá
- itinerary schema may use `ItemList` only when the same ordered itinerary content is visibly rendered on-page
- supporting `TouristDestination`, `AdministrativeArea`, and `Country` nodes should be emitted only from linked published entities already represented in the UI

### 3.7 Shared landing pages and visual slots

Applies to:

- `/`
- `/ve-chung-toi`
- `/dich-vu`
- `/blog`
- `/lien-he`
- shared `LandingPage` records that back `/tour-trong-nuoc`, `/tour-nuoc-ngoai`, and `/tour-doan`

Homepage browse-entry rules:

- homepage may expose compact taxonomy discovery rails as part of its browse-entry layer
- the current preferred pair is `Chủ đề tour` -> `/danh-muc-tour/{category-slug}` and `Điểm đến nổi bật` -> `/tour-{destination-slug}`
- these homepage rails are direct internal-link blocks, not alternative search widgets and not pseudo-index pages
- `Điểm đến nổi bật` should include only destination hubs that currently have at least one published tour
- these rails may use the shared homepage card-carousel treatment on mobile and desktop, but the canonical link target stays the taxonomy hub itself and the underlying rendered HTML remains direct taxonomy links
- when the shared card-carousel treatment is enabled, autoplay should remain supportive only: advance while overflowing, then pause on hover, focus, or manual interaction

Required visual-slot contract:

1. Hero remains the first visual block and must keep the page H1, supporting copy, and CTA visible in server-rendered HTML
2. Gallery is optional and should sit after the main editorial body, not before the page's primary browse intent
3. Hero and gallery may resolve from `none`, `slider`, or `media`
4. `slider` source should use the explicitly selected slider when present, otherwise fallback to the active slider whose `banner-location` matches `{page_key}-hero` or `{page_key}-gallery`
5. `media` source should render the asset or assets chosen from the shared Media popup flow
6. `none` should keep the page on its default editorial/static layout
7. Homepage may keep its bespoke hero until a landing-page visual override is explicitly enabled
8. Titles, descriptions, CTA buttons, and media wrappers should render only when the underlying data exists

Schema contract:

- homepage keeps `WebPage` as the page container and may emit one primary `ItemList` for the visible featured tours or fallback browse-entry rail, with `mainEntity` pointing to that list when present
- each homepage topic-rail item should serialize as a lightweight `CollectionPage` reference to the canonical `/danh-muc-tour/{category-slug}` hub, not as a fake standalone entity page
- shared system landings and custom landing pages default to `WebPage` unless a visible query-backed list block clearly defines the page's primary browse intent
- visible `tour_list`, `tour_taxonomy_tabs`, and `blog_list` blocks may emit `ItemList`; one primary visible list may promote custom landing pages to `CollectionPage`
- on the homepage specifically, visible fixed `blog_preview` and dynamic `blog_list` widgets emit secondary `ItemList` nodes with `BlogPosting` items and attach through `WebPage.hasPart`, while the homepage remains `WebPage` and keeps its tour/browse list as the primary `mainEntity`
- visible FAQ accordion content may emit `FAQPage`, but hidden or CMS-only FAQ data must not be serialized
- current travel runtime generates landing-page schema from visible resolved blocks rather than treating admin `schema` JSON as the default public source

Hero slot requirements when source = `slider`:

- each slide may contain image or video media,
- subtitle / eyebrow,
- title,
- description,
- primary CTA,
- secondary CTA,
- alt text and destination URL when relevant.

Gallery slot requirements:

- gallery may render image cards, embedded video cards, or MP4 cards,
- carousel behavior should appear only when the item count justifies it,
- small sets should fall back to a static grid,
- item title, subtitle, description, and link remain optional.

---

## 4. CMS navigation and admin page structure

### 4.1 CMS sidebar registry

Source of truth:

- `App\Support\Admin\AdminNavigationRegistry`

Hard rules:

- every CMS entry in the sidebar must be modeled as `group -> action`,
- each action must declare label, icon family, route, active route patterns, and permission key,
- desktop sidebar, mobile sidebar, page-local submenus, seeded permissions, and account permission editor must all read from the same registry contract,
- every new CMS model or management screen must update both the registry and this document in the same change,
- removing or renaming a CMS model/action must remove or rename the matching route, permission key, and doc entry together.

Active travel CMS groups:

- `Quản lý tour`
- `Quản lý dịch vụ`
- `Quản lý blog`
- `Landing Pages`
- `Sliders`
- `Travel Inquiries`
- `Media`
- `Menus & Theme`
- `Tài khoản`

Sidebar UX rules:

- each group renders as an expandable card with a travel-appropriate icon,
- expand/collapse state is remembered per browser via local storage,
- a group auto-hides when the current user cannot access any child action,
- the current route's group must render open in server-side HTML before Alpine hydrates,
- sidebar state restore must tolerate malformed or stale local-storage JSON and fall back safely,
- `Slider` is always its own group and must not be nested under `Landing Pages`.

### 4.2 Permission matrix and account roles

Permission rules:

- permissions follow the child-action naming contract such as `admin.tours.index`, `admin.tours.edit`, `admin.blogs.categories.index`, `admin.accounts.edit`,
- list/index routes use the matching `.index` permission,
- create/edit/delete write flows use the matching `.edit` permission,
- route middleware must enforce the same permission key used by the sidebar, not a separate hidden contract.

Account types:

- `Admin` has full access to every action declared in `AdminNavigationRegistry`,
- `Content` has exactly four default permissions: `admin.blogs.index`, `admin.blogs.edit`, `admin.blogs.categories.index`, `admin.blogs.categories.edit`
- `super_admin` remains a compatibility role in the database and is treated as full access,
- admins may grant extra permissions to a content account per child action from the sidebar matrix,
- the sidebar must reflect granted extra permissions immediately by showing only the allowed groups/actions.

### 4.3 Admin list pages

Applies to:

- `/admin/tours`
- `/admin/tours/categories`
- `/admin/tours/destinations`
- `/admin/tours/regions`
- `/admin/tours/countries`
- `/admin/services`
- `/admin/services/categories`
- `/admin/blogs`
- `/admin/blogs/categories`
- `/admin/sliders`
- `/admin/accounts`

Required structure:

1. Page header with registry-driven travel submenu
2. Filter toolbar
3. Result table
4. Primary create action
5. Row actions for edit and delete

### 4.4 Tour create/edit

Required admin blocks:

1. Core identity and publish state
2. Scope and taxonomy mapping
3. Pricing and commercial fields
4. Avatar / cover
5. Gallery content
6. Overview / content
7. Itinerary
8. Pricing table and inclusions
9. Departure repeater
10. FAQ
11. SEO fields

Departure editor must support:

- add/remove rows,
- departure date,
- return date,
- departure location,
- transport,
- accommodation standard,
- base price,
- sale price,
- slots,
- pricing note,
- publish status,
- featured flag,
- sort order.

Departure contract note:

- the repeater is the primary admin source for per-date commercial data that surfaces again on the frontsite pricing block
- editor scan order should prioritize `departure_date`, `sale_price`, `base_price`, and `standard_label` before secondary operational fields

### 4.5 Taxonomy create/edit

Applies to:

- category,
- destination,
- region,
- country.

Required admin blocks:

1. Identity
2. Publish state
3. Avatar / cover
4. Gallery content
5. Intro / body copy
6. SEO fields

### 4.6 Slider manager

Required admin blocks:

1. Slider index list with name, banner-location, item count, and active state
2. Slider detail editor
3. Slider item editor
4. Media picker integration
5. Save and delete actions

Slider-level fields:

- name,
- banner-location,
- description,
- autoplay delay,
- active state.

Slider item fields:

- image from media library or upload,
- optional video URL,
- alt text,
- destination link,
- title,
- subtitle,
- description,
- primary button label and URL,
- secondary button label and URL,
- motion effect,
- sort order,
- active state.

Rendering rules:

- the primary button is the high-emphasis CTA,
- the secondary button is the lighter support CTA,
- title, description, and buttons stay hidden when the value is empty.

### 4.7 Accounts manager

Required admin blocks:

1. Account list with search and current role
2. Identity editor: name, email, password
3. Role type selector with only `Admin` and `Content`
4. Permission matrix grouped by the same sidebar groups
5. Save action

Account-management rules:

- switching a user to `Admin` must clear direct extra permissions and rely on the full-access role,
- switching a user to `Content` must restore the four default blog permissions and then apply any additional granted child-action permissions,
- the permission editor should explain permissions in business language, not raw technical jargon,
- accounts must never receive permissions for legacy estimator, package, project, or old SEO AI runtime screens.

### 4.8 Landing page create/edit

Landing page editor is block-first, with an explicit manual-HTML mode for blank custom campaign pages.

Do not use legacy field-centric authoring as the primary editing contract:

- no separate hero form,
- no separate gallery form,
- no separate intro/body/CTA tabs as the source of truth,
- legacy fields may remain in storage only for compatibility and fallback rendering,
- raw pasted HTML is allowed only when the editor is switched to manual HTML mode on the landing-page manager.

Required admin blocks:

1. Identity, slug, and publish state
2. Template preset selector
3. Editor-mode selector (`blocks` or manual `html`)
4. Clone-from-page utility
5. Block picker
6. Block stack editor with reorder / duplicate / delete actions
7. Manual HTML textarea when HTML mode is active
8. SEO and schema/meta fields
9. Save actions

Landing page manager must support:

- on/off visibility via `is_active`,
- system pages identified by `page_key`,
- custom pages identified by root-level `slug`,
- `page_key` remaining nullable for custom pages,
- clone content from another landing page without changing the current `page_key` or target slug,
- template presets:
  - `home`
  - `about`
  - `contact`
  - `services`
  - `blog`
  - `domestic_tours`
  - `international_tours`
  - `group_tours`
  - `blank`
  - `generic`,
- root-slug validation that rejects reserved paths such as `ve-chung-toi`, `tour-trong-nuoc`, `chuong-trinh`, `tour`, `dich-vu`, `blog`, `admin`, `login`, `robots.txt`, and `sitemap.xml`,
- manual HTML mode that renders the pasted markup directly inside the frontsite layout for the custom landing page,
- preserving visual media collections when cloning pages that use block media sources.

Block catalog v1:

- `hero_slider`
- `hero_media`
- `hero_demo_landingpage`
- `gallery_slider`
- `gallery_media`
- `html_widget`
- `rich_text`
- `topic_rail`
- `region_taxonomy_tabs`
- `tour_taxonomy_tabs`
- `trust_proof`
- `cta`
- `faq`
- `tour_list`
- `blog_list`

Block rules:

- `hero_*` and `gallery_*` may source only from `slider` or `media popup`,
- `hero_demo_landingpage` reuses the homepage demo hero section for landing pages that need that tour-entry layout; it queries live published tours, suppresses the default landing hero, and opens the primary CTA through `TravelInquiry`,
- `html_widget` starts as an empty block and renders pasted HTML directly in the landing block stack for widget/snippet needs,
- mỗi block có `home_position` cho riêng system page `home`; trường này là fallback để block mới được đưa vào gần các mốc homepage như `home-tour-topics`, `featured-tours`, taxonomy tabs, destination slider, services, trust, process, blog preview, FAQ hoặc CTA,
- trên homepage, thứ tự toàn cục sau hero lấy từ `home_config.layout_order`, nơi có thể trộn `section:{key}` của hardcode section và `block:{uuid}` của dynamic block trong cùng một danh sách,
- stack `Blocks` trên homepage chỉ quản lý nội dung block; move vị trí public của dynamic block phải cập nhật `home_config.layout_order` để giữ đúng thứ tự frontsite,
- mọi dynamic block không phải hero phải được đưa vào render order bằng token `block:{uuid}`; nếu editor chưa chọn vị trí, runtime dùng fallback anchor theo type thay vì bỏ qua block,
- các section hardcode của homepage sau hero phải đổi thứ tự bằng `home_config.layout_order`; không xoá markup khỏi `home.blade.php` chỉ để thay vị trí hoặc ẩn block,
- mỗi section hardcode trong `layout_order` dùng `home_config.{section}.is_enabled` để ẩn/hiện; `section_order` chỉ là compatibility field được derive từ layout mới,
- hero blocks không được rời khỏi vị trí đầu trang chủ vì hero giữ H1, thông điệp chính và CTA đầu tiên; các slot tuỳ biến chỉ áp dụng cho block sau hero,
- `html_widget` homepage mặc định neo trước `featured_tours`; nội dung giữ theo `uuid`, và khi có nhiều widget trên cùng trang thì vị trí frontsite phải theo token `block:{uuid}` trong `home_config.layout_order` để tránh lẫn nội dung,
- `trust_proof` uses the shared homepage trust visual contract: one featured proof card, up to two supporting proof cards, Font Awesome icons from CMS with fallbacks, optional real trust metrics, and a simplified mobile presentation,
- `topic_rail` reuses the homepage `Chủ đề tour` icon-carousel family with live featured `TourCategory` data; `eyebrow`, `title`, `description`, and navigator controls are optional and any empty heading part must collapse cleanly on the public page,
- `region_taxonomy_tabs` is a region-first compare block: regions become the live tablist, each active panel resolves `Destination` or `TourCategory` cards through `card_source_type`, mobile keeps the tablist above the content as a horizontal scroll rail with no page-level overflow, desktop moves it to a left column, and the cards remain server-rendered taxonomy links,
- homepage featured-tour tabs (`home-featured-tab-international`, `home-featured-tab-domestic`, `home-featured-tab-group`) define the scope-tab family: mobile tablist scrolls horizontally with no page-level overflow, then wraps normally from tablet/desktop upward,
- `tour_taxonomy_tabs` reuses that homepage featured-tour tablist family; each tab targets one live `Region`, `Destination`, or `TourCategory`, the mobile tablist scrolls horizontally, and the active summary + CTA must switch with the tab while the tour cards remain server-rendered HTML,
- mobile card-carousel panels inside `region_taxonomy_tabs` and `tour_taxonomy_tabs` should expose about `1 + 1/5` items per viewport to signal horizontal browsing without stretching the page width,
- `faq` must render as the shared frontsite accordion contract: full-width rows, visible `+ / -` state icon, single-open behavior, and only the first item open by default,
- `tour_list` is query-driven and may filter published tours by `category`, `destination`, `region`, `country`, `scope`, `featured`, `limit`, and `sort`,
- `blog_list` is query-driven and may filter published posts by `category`, `featured`, `limit`, and `sort`,
- frontsite rendering must use live runtime data for `tour_list`, `tour_taxonomy_tabs`, and `blog_list`, not copied snapshots,
- custom landing pages publish at `/{slug}` only after passing reserved-slug validation,
- the catch-all custom landing route must stay at the end of `routes/frontsite.php`.

Workflow standardization:

- landing-page creation and editing should follow the repo-local skill `[$landingpage-creator](../.agents/skills/landingpage-creator/SKILL.md)` whenever a user asks to build a new landing page from blocks, sliders, media, and query-driven lists.

---

## 5. Schema coupling

Default schema mapping for the tour family:

- scope listing, category, destination, region, country -> `CollectionPage` + `ItemList` + `BreadcrumbList`
- tour detail -> `Product` + `Offer` or `AggregateOffer` + `BreadcrumbList`
- FAQ data may add `FAQPage` only when the same Q/A is visibly rendered

Never emit:

- fake reviews,
- fake ratings,
- fake departure dates,
- fake prices,
- schema for invisible FAQ content.

---

## 6. Delivery checklist

When changing the tour runtime, verify:

- route family still matches this sitemap map,
- sitemap only exposes canonical travel pages,
- filtered URLs are not competing with canonical pages,
- sidebar groups and child actions still match `AdminNavigationRegistry`,
- account permissions and route middleware still match the sidebar contract,
- each page type still renders the required block sequence,
- landing pages still render from ordered blocks and custom root slugs do not collide with reserved routes,
- taxonomy pages and tour detail preserve internal linking,
- schema matches the visible blocks on the page.
