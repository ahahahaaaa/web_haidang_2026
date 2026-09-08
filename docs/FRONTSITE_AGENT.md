# FRONTSITE_AGENT.md

Frontend Architecture & UI Agent Specification  
Project type: Haidang Travel frontsite / travel CMS website

---

## 1. Purpose

This document instructs the agent how to design, generate, and maintain the public-facing website.

The frontsite must:
- reflect a trustworthy travel brand,
- convert visitors into leads,
- present tours, services, blog content, and contact paths clearly,
- be SEO-friendly,
- remain modular and easy to extend.
- stay strictly within the travel domain; public navigation and conversion surfaces should not expose legacy construction, project, estimator, package, or interior-design content.

This document is normative for the active runtime:
- all future frontsite code, IA decisions, and UI rebuild work for theme `haidangtravel` must follow this file,
- if an existing Blade/demo/layout conflicts with this document, this document wins for future implementation work.

Language rule:
- any Vietnamese text shown on the public site must use full Vietnamese diacritics,
- do not ship non-accented Vietnamese in headings, paragraphs, buttons, labels, placeholders, CTA copy, or seeded frontsite defaults,
- the public frontsite default language is Vietnamese; render technical language metadata consistently as `vi`,
- Open Graph locale on public pages must render `<meta property="og:locale" content="vi">` instead of deriving a hyphenated locale such as `vi-VN`.

### Audit baseline (reviewed 2026-04-20)

Reference inputs used for this version of the frontsite rules:
- `https://haidangtravel.com/`
- `https://travel.com.vn/`
- Google Search Central SEO Starter Guide
- Google Search Central structured data documentation
- W3C WCAG 2.2
- W3C WAI-ARIA overview and APG direction

---

## 2. Design direction

Reference style:
- modern travel service UI,
- structured layout,
- large visual blocks,
- strong CTA visibility,
- minimal clutter,
- balanced whitespace,
- image-led destination storytelling.

The visual direction may take inspiration from premium travel websites, but must remain distinct to the Haidang Travel brand.

Block heading rule:
- không dùng text chú thích, eyebrow, badge heading, hoặc overline text đứng riêng phía trên tiêu đề block ở frontsite,
- mỗi block phải đi thẳng vào heading chính hoặc nội dung cốt lõi, chỉ giữ lại label khi đó là dữ liệu chức năng thực sự như form field hoặc thông số tour.

---

## 3. Brand system

### Core colors
- Primary orange: `#FF6A00`
- Primary orange hover: `#E65F00`
- Primary gradient: `linear-gradient(135deg, #FF6A00, #FF8C00)`
- Secondary blue: `#004A99`
- Accent dark: `#222222`
- Background main: `#F8FAFC`
- Background soft: `#FDFAF7`
- Primary text: `#1E293B`
- Secondary text: `#475569`
- Muted text: `#64748B`
- Border soft: `#E2E8F0`
- Border muted: `#F1F5F9`
- Border strong: `#CBD5E1`
- Success: `#10B981`
- Danger: `#EF4444`
- Price highlight: `#E31C25`
- Rating / warning: `#FFB800`
- Zalo green: `#25D366`
- Zalo blue: `#0068FF`
- White: `#FFFFFF`

### Usage rules
- Orange is for primary CTA, emphasis, badges, key actions.
- Blue is for headings, navigation accents, trust cues, and brand anchors.
- Do not let orange dominate large surfaces; keep it under roughly 20% of visible page area.
- Text should not use pure black `#000000`.
- Backgrounds should stay bright and airy.
- Avoid unrelated accent palettes.

---

## 4. Typography

Preferred pairings:
- Heading: Poppins or Montserrat
- Body: Inter or Roboto

Scale:
- H1: 40–56px desktop, 30–36px mobile
- H2 shared `.frontsite-h2`: `clamp(1.875rem, 1.76rem + .58vw, 1.25rem)` / 30px baseline
- H3: 22–28px
- Body: 16–18px

---

## 5. Layout system

### Grid
- Desktop: 12-column grid
- Mobile: single-column stacking

### Container
- Max width: 1200–1280px
- Side padding: 16px mobile, 24px tablet, 32px desktop

### Spacing
Use an 8px system.
- Với frontsite runtime hiện tại, các block section chính phải dùng nhịp spacing gọn hơn bản trước; mặc định giảm khoảng 50% vertical padding ở section wrapper thay vì giữ nhịp whitespace quá rộng.
- Quy ước ưu tiên:
  - section chuẩn: `py-8`, desktop `lg:py-10`
  - hero hoặc intro lớn: `py-10`, desktop `lg:py-12`
  - detail wrapper cần nhấn lớp: `py-9`, desktop `lg:py-11`
- Không áp dụng máy móc rule này lên card, form, modal, chip/filter nhỏ, breadcrumb bar, hoặc footer inner padding; các khối đó giữ padding theo readability và usability.

---

## 6. Core page inventory

At minimum support:
- homepage
- about/company
- services listing
- service detail
- tours listing by scope
- tour category pages
- destination / region / country hub pages
- tour detail
- blog listing
- blog detail
- contact
- inquiry / consultation surfaces

### Reference analysis: `haidangtravel.com`

What is worth preserving:
- strong split between `Tour trong nước`, `Tour nước ngoài`, and `Tour đoàn`,
- dense destination taxonomy that helps users browse by intent and location,
- tour cards expose practical fields users actually compare: transport, departure place, duration, price, and next CTA,
- services such as visa, vé máy bay, thuê xe, sim du lịch support cross-sell and trust,
- hotline and local-travel trust cues are visible early.

What should not be copied directly:
- navigation density is too high and can overwhelm first-time users,
- many destination links are exposed at once, which weakens scannability and semantic grouping,
- filters and content blocks compete visually instead of building one clear conversion path,
- home page information scent is strong, but hierarchy and section pacing can be cleaner.

### Reference analysis: `travel.com.vn`

What is worth preserving:
- very clear product segmentation: domestic, international, tour lines, and supporting services,
- visible booking lookup and support utilities,
- strong enterprise trust signals: contact data, legal information, payment methods, policies,
- category-first IA works well for users who already know the type of trip they want.

What should not be copied directly:
- corporate IA can feel tool-heavy and light on destination storytelling,
- search/lookup utilities can dominate above-the-fold attention if not balanced with a clear hero,
- large option sets need stronger visual prioritization on mobile,
- SEO value is reduced if primary tour selling points are hidden behind tabs, widgets, or JS-heavy blocks.

### Mandatory rebuild direction

The rebuilt Haidang Travel frontsite should:
- keep the 3-scope tour model: domestic, international, group,
- combine category clarity from `travel.com.vn` with destination storytelling from `haidangtravel.com`,
- reduce menu density and expose only the highest-value browse paths in the first layer,
- use one clear primary CTA per viewport cluster, with hotline/contact as the secondary support path,
- make tour comparison possible from listing cards without opening every detail page,
- prefer semantic, HTML-first sections over JS-first tabs/sliders for critical SEO content.

---

## 7. Homepage rules

Homepage goal:
- help first-time users understand Haidang Travel in under 10 seconds,
- let ready-to-buy users jump quickly into tour discovery,
- keep the page usable both as a branded landing page and as an SEO entry page,
- balance inspiration, comparison, and conversion instead of acting like only a brochure or only a search tool.

Required section order:
1. Header / navigation
2. Hero
3. Quick trust bar / hotline / booking support
4. Tour intent chooser / light search
5. Tour scope discovery
6. Seasonal campaigns / featured tours
7. Destination / region discovery
8. Tour styles / customer segments
9. Core services
10. Why choose us / trust metrics
11. Booking process / workflow
12. Reviews / testimonials / partner proof
13. Travel guide / blog preview
14. Homepage FAQ
15. Final CTA
16. Footer
17. ui desktop document html `docs/front_end/demo/home_page/code.html`
18. ui mobile document html `docs/front_end/demo/home_page_mobile/code.html`

### Homepage block inventory

The homepage should not be a random stack of sections. It should move users through this sequence:
- orient,
- narrow intent,
- compare options,
- build trust,
- remove friction,
- convert.

#### 1. Header / navigation

Must include:
- logo,
- primary nav for `Tour trong nước`, `Tour nước ngoài`, `Tour đoàn`, `Dịch vụ`, `Blog`, `Liên hệ`,
- visible hotline,
- one high-priority CTA such as `Tư vấn tour` or `Gửi yêu cầu`,
- responsive mobile menu,
- optional sticky behavior only if it does not hide focus or primary content.

Header CTA & Navigation rules:
- hotline remains a direct `tel:` action,
- the main conversion CTA such as `Gửi yêu cầu`, `Nhận tư vấn`, or `Liên hệ ngay` should open the shared frontsite inquiry modal instead of pushing users to a separate page,
- the hotline button should stay visually simpler than the primary conversion CTA: phone icon + number only.
- **Desktop Submenu Flyout Interaction (Phân cấp Menu Frontsite Desktop)**:
  - Menu Cấp 1 nằm ngang trên thanh Header Navigation.
  - Menu Cấp 2 xuất hiện trong bảng Dropdown ngay bên dưới Cấp 1 khi hover.
  - Các cấp con (Cấp 3+) **không gom tất cả vào một div lớn**. Khi hover vào một danh mục Cấp 2 có con, một `div` bảng con riêng biệt sẽ xuất hiện mở rộng theo **chiều ngang sang phía bên phải (`left-full top-0 pl-2`)** để hiển thị danh sách các item Cấp 3 tương ứng.
  - Đảm bảo trải nghiệm menu phân cấp ngang (Cascading Flyout Navigation) mượt mà, dễ rà chuột và có hiệu ứng chuyển cảnh mượt.
  - Mobile Menu: Giữ nguyên cấu trúc cây xổ dọc linh hoạt (`<details>` / `<summary>`) trên thiết bị cảm ứng.

#### 2. Hero

The hero is the homepage poster, not a dashboard.

### Hero requirements
- clear H1,
- short supporting paragraph,
- one primary CTA,
- one optional secondary CTA,
- travel-related imagery/background,
- trust cue if possible,
- one narrow support strip or compact utility cluster only if it helps booking intent,
- no overloaded multi-filter experience in the first viewport.

Hero may include:
- one featured destination image/video,
- one compact tab or toggle for `trong nước / nước ngoài / tour đoàn`,
- one trust line such as hotline, years of operation, or licensed-travel cue,
- one compact inquiry shortcut.

#### 3. Quick trust bar / booking support

This block should sit immediately below the hero and reduce hesitation.

Recommended content:
- hotline,
- giờ hỗ trợ,
- địa phương khởi hành chính,
- giấy phép / pháp lý / company trust cue,
- booking lookup or quick support link if it is genuinely useful.

Homepage implementation note:
- the support/search surface directly below the hero should use the same shared search-bar language as the public listing pages,
- keep it to one clear tour query input plus one submit action inside a single rounded surface,
- do not reintroduce quick-intent rails, date, budget, or multi-step filter controls into this homepage block unless a later task explicitly changes the product rule.

Inquiry UX note:
- if a quick support block offers an inquiry action, it should reuse the same shared inquiry modal as the header CTA,
- do not introduce a second frontsite contact form style in these support strips.
- when a frontsite write form is submitted with JavaScript available, it should resolve inline on the same surface via Ajax instead of forcing a full-page reload just to show feedback,
- validation errors should appear directly under the affected field and be paired with a short local summary inside the same modal/panel,
- success states should also render inside that same modal/panel; redirect remains only as a graceful no-JS fallback.

#### 4. Tour intent chooser / light search

This block helps users who know their need but not the exact tour.

Recommended inputs:
- one text query for the destination, route, or tour intent the user is looking for.

Rules:
- keep it lighter than a full travel portal search engine,
- homepage filtering should push to index/listing pages, not trap users inside a heavy widget,
- fields must be keyboard-usable and mobile-usable,
- every state should map cleanly to a crawl-safe listing URL.

Homepage search bar contract:
- use one prominent tour search text field as the primary field, phrased in plain travel language such as `Bạn muốn đi đâu?`,
- the homepage search surface should use the same shared search bar partial and visual contract as listing pages,
- the homepage search surface should stay aligned with the shared listing search pattern: one text field plus one submit action inside the same rounded surface,
- the homepage search field should behave like a true text query for tours, not like a disguised category select,
- the search action should resolve to an existing travel browse URL such as a tour listing page instead of inventing a second homepage-only results state,
- do not reintroduce date, budget, or quick-intent rails into this homepage search bar unless a later task explicitly changes the product rule.

Frontsite form control contract:
- all public-facing `select` fields in theme `haidangtravel` should use the shared `Tom Select` enhancement layer for one consistent dropdown/search surface,
- other frontsite forms that still need `select` inputs should stay on this same `Tom Select` system instead of mixing native selects and one-off plugins,
- desktop select surfaces should stay visually compact with a reasonable max-width instead of stretching unnecessarily wide across their full grid cell,
- when a frontsite date/select control is still needed elsewhere, keep the shared control stack `Tom Select` for selects and `Flatpickr` for date fields unless a future task explicitly replaces them repo-wide.

Listing search contract:
- homepage and public listing pages such as `Blog`, `Dịch vụ`, `Tour`, `Danh mục tour`, `Điểm đến`, `Vùng miền`, and `Quốc gia` should share one simple search bar pattern,
- the default shared listing search bar should contain only one text query field and one submit action grouped in the same surface,
- `/tour-trong-nuoc` and `/tour-nuoc-ngoai` may extend that same bar with one `Chủ đề` select so users can narrow tours by thematic intent without leaving the current scope page,
- this `Chủ đề` select should use the same `Tom Select` control family, stay inside the same rounded search surface, and remain clearly secondary to the main text query,
- do not reintroduce extra select/date/budget controls into other listing search bars unless a later task explicitly redefines that listing UX.

#### 5. Tour scope discovery

This block is mandatory and should explain the 3 core business lanes:
- `Tour trong nước`
- `Tour nước ngoài`
- `Tour đoàn`

Each scope block should include:
- a strong image,
- a short positioning statement,
- 2 to 4 useful sub-links,
- one primary CTA into the relevant listing page.

#### 6. Seasonal campaigns / featured tours

This is the main sales block on the homepage.

Recommended content:
- seasonal promotions such as lễ, hè, tết, team building, visa season,
- featured tours selected by real business priority,
- clear price/contact state,
- departure summary,
- visible CTA.

Rules:
- prioritize 4 to 8 strong tours instead of a crowded wall,
- cards must be comparable at a glance,
- do not hide all featured tours inside a carousel only.
- the homepage featured-tour tablist (`home-featured-tab-international`, `home-featured-tab-domestic`, `home-featured-tab-group`) should use the same mobile horizontal-scroll treatment as taxonomy tablists, then wrap normally from tablet/desktop upward.

#### 7. Destination / region discovery

Purpose:
- support exploratory users,
- strengthen internal linking,
- expose destination breadth without making the header too dense.

Recommended variants:
- region clusters,
- destination chips,
- curated destination collections,
- editorial destination cards with one image and one short insight.

Current homepage contract:
- the primary discovery zone should expose two crawlable taxonomy rails when data exists: `Chủ đề tour` and `Điểm đến nổi bật`
- `Chủ đề tour` links directly to `/danh-muc-tour/{category-slug}` hubs and acts as the thematic browse layer
- `Điểm đến nổi bật` links directly to `/tour-{destination-slug}` hubs and acts as the place-first browse layer
- these rails are browse-entry components, not search/filter components; do not add select, date, budget, or multi-step controls into them
- destination items should prefer featured destinations first, but every visible item must still have at least one published tour behind it
- both rails should remain server-rendered HTML links for crawlability and predictable mobile interaction
- taxonomy tablists such as `tour-taxonomy-tab-*` and `region-taxonomy-tab-*` should use horizontal scrolling on mobile instead of stretching buttons into cramped fixed grids or widening the page
- mobile card-carousel panels under those taxonomy tabs should preview the next item, roughly `1` full card plus `1/5` of the following card, so users can read the rail as scrollable without breaking the viewport

#### 8. Tour styles / customer segments

This block is missing on many generic travel homepages but matters for conversion.

Support common travel intents such as:
- gia đình,
- cặp đôi,
- khách lẻ,
- khách công ty,
- MICE / team building,
- tour thiết kế riêng,
- hành hương / chuyên đề if the business truly serves them.

Each segment block should explain:
- who it is for,
- what kind of experience it fits,
- where the user should click next.

### Services section
Each service card should include:
- icon or visual,
- service title,
- short value-driven description,
- detail link.

#### 9. Core services

Use this section for cross-sell and trust support, not as the main hero substitute.

Recommended service set:
- visa,
- vé máy bay,
- thuê xe,
- sim du lịch,
- hỗ trợ đoàn / MICE when relevant.

### Featured tours
Homepage destination or featured-tour cards should support:
- image,
- destination or route name,
- travel scope / region,
- departure location,
- duration summary,
- price or contact state,
- direct CTA to tour detail.

#### 10. Why choose us / trust metrics

This block should convert uncertainty into confidence.

Recommended content:
- years of operation,
- supported destinations,
- team/guide expertise,
- support responsiveness,
- transparent process,
- legal/company legitimacy.

Rules:
- every metric must be real,
- if numbers are weak or unavailable, use proof-based statements instead of fake counters.
- default visual treatment should not be a flat row of equally weighted long-text cards; prefer one featured proof card plus shorter supporting proof cards.
- each proof card should read in this order: short highlight label, decision-oriented title, then one concise supporting sentence.
- proof cards should support Font Awesome icon classes from CMS, with frontsite fallbacks for older saved data.
- mobile trust proof rendering should stay compact and low-noise; large decorative background icons are desktop/tablet-only.
- landing-page `trust_proof` blocks may render optional trust metrics only when the editor enters real value/label/icon rows.
- homepage `trust` block and landing-page `trust_proof` block should share the same visual language so the brand proof system feels consistent across campaign pages.

#### 11. Booking process / workflow

The homepage should explain the buying journey, not only showcase tours.

### Process
Preferred as 4–6 steps:
1. Nhận nhu cầu
2. Gợi ý hành trình
3. Chốt lịch trình
4. Chuẩn bị dịch vụ đi kèm
5. Khởi hành / đồng hành
6. Chăm sóc sau chuyến đi

Implementation note:
- each workflow step on the homepage may include one optional supporting image selected from CMS Media popup
- the image should strengthen scannability and trust, but the step title and description remain the primary information layer
- if a step has no image, the card must still look complete and balanced without leaving empty media placeholders

#### 12. Reviews / testimonials / partner proof

Recommended proof formats:
- customer testimonials,
- company/group client proof,
- event/team-building recap,
- partner or airline/service logos when legitimate,
- UGC/photo proof if rights are available.

Rules:
- no fake testimonials,
- avoid anonymous low-trust quotes if stronger proof exists,
- keep this block scannable and image-supported.

#### 13. Travel guide / blog preview

This block supports SEO and helps hesitant users self-educate.

Recommended article types:
- kinh nghiệm đi điểm đến,
- checklist trước chuyến đi,
- visa guidance,
- seasonal travel recommendations,
- group travel planning tips.

#### 14. Homepage FAQ

This block is strongly recommended for both conversion and SEO.

Homepage FAQ should answer:
- cách chọn tour phù hợp,
- tour có khởi hành từ đâu,
- giá tour đã gồm những gì,
- khi nào nên đặt tour,
- có hỗ trợ tour đoàn / tour riêng không,
- cách gửi yêu cầu nhanh.

Rules:
- FAQ must be visible HTML content if schema is emitted,
- questions should reduce pre-contact friction, not repeat generic marketing copy.

FAQ block contract:
- all public-facing FAQ blocks in theme `haidangtravel` must use a one-column accordion; do not render FAQ as 2-column cards or mixed-width masonry rows,
- each FAQ item should span the full available row width so question scanning and answer reading stay stable on both desktop and mobile,
- every FAQ trigger must show a visible `+ / -` state indicator for closed/open status,
- default state should open only the first FAQ item; all remaining items stay collapsed on first render,
- when one item is opened, other items should collapse so the block behaves like a single-focus accordion instead of a multi-open dump,
- keep the question itself always visible in HTML and keep the answer in the DOM even when collapsed so SEO and accessibility stay aligned with the visible UI,
- frontsite FAQ sections must not render a supporting description paragraph under the FAQ heading,
- shared `Điều khoản tour` sections on tour detail pages must follow the same rule and render only the heading plus accordion items, without a description paragraph,
- FAQ and shared `Điều khoản tour` accordions use compact spacing; internal trigger/panel padding should stay at roughly 50% of the older roomy version.

#### 15. Final CTA

This block should close the page with one decisive next action.

Recommended CTA choices:
- gửi yêu cầu tư vấn,
- gọi hotline,
- xem tour nổi bật,
- đặt tour đoàn / tour thiết kế riêng.

#### 16. Footer

The footer must finish trust and discovery, not act as a dump of random links.

Recommended footer columns:
- company intro,
- tour links,
- service links,
- policy/help links,
- contact/legal info,
- social links.

Footer contract for theme `haidangtravel`:
- footer navigation should support 2 independent CMS-managed menu groups when content exists, not only one fixed quick-link column,
- the primary footer menu title such as `Đi nhanh` must be editable from CMS instead of being hard-coded in the Blade template,
- if a second footer menu group exists, mobile layout should use that second column to avoid leaving a large empty area beside the first menu list,
- footer menu groups should remain crawlable HTML link lists and should not depend on slider, tabs, or JS-only toggles,
- `Zalo` is a first-class support/social channel in the footer and should render alongside the other brand social links whenever `zalo_url` exists in site settings,
- footer support/social icons should remain compact touch-friendly actions and still expose an accessible text label.

### Homepage composition rules

- Each homepage block should have one dominant job only.
- Avoid stacking more than 2 highly promotional sections back to back.
- Home should alternate between inspiration blocks and decision-support blocks.
- Critical SEO text must be visible in HTML, not only inside tabs, accordions, or sliders.
- At least one tour-sales block must appear before the fold boundary on large desktop layouts.
- On mobile, the first 3 blocks after hero should be: trust, scope, featured tours.

### Homepage SEO and conversion rules

- The homepage H1 should state the primary brand + travel offer clearly.
- Hero copy and first support block should mention the site’s core offer in plain language.
- Internal links from homepage should point to the 3 tour scopes, key services, selected destination clusters, and featured blog guides.
- When homepage renders the visible `Chủ đề tour` browse-entry rail, the page schema should include `WebPage` + `ItemList` for that rail and keep the list items pointed at canonical `/danh-muc-tour/{slug}` hubs.
- Homepage should expose enough crawlable text to explain business scope without becoming article-heavy.
- The primary CTA should repeat at least twice on long pages.
- Homepage FAQ and trust blocks should reduce the need for users to leave the homepage just to understand the business.

---

## 8. Service pages

### Listing page
- top banner
- concise intro
- service grid
- optional FAQ
- CTA

### Detail page
1. Hero/banner
2. Service overview
3. Included scope / deliverables
4. Related services or support blocks
5. FAQ or inquiry CTA
6. CTA
- ui desktop html `docs/front_end/demo/service_detail/code.html`
---

## 9. Tour pages

Detailed sitemap structure, page family mapping, and required block order for the full tour runtime live in `docs/TOUR_SITEMAP_BLOCKS.md`.

### Listing page
- scope heading
- optional region filters
- optional destination shortcuts
- tour grid
- visible result count
- sort/filter state that can be linked and indexed safely
- pagination

Filter behavior:
- `departure_date`, `transport`, `category`, and `destination` filters should canonicalize back to the base listing URL,
- filtered variants should not compete with the base listing page for indexation.

### Tour object contract

Every future frontsite implementation must treat `Tour` as a first-class money page object, not a generic card.

Listing card minimum fields:
- `title`
- `slug`
- `scope`
- `destination` and/or `region`
- `transport`
- `departure_location`
- `duration_days` and `duration_nights`
- `sale_price` or explicit contact state
- `excerpt`
- `cover` / `cover_image_url`
- `cover_alt`
- `cta_mode`

Detail page fields that should be rendered when present:
- `content`
- `departures`
- `standard_label`
- `itinerary`
- `pricing_table`
- `departure_schedules`
- `inclusions`
- `tour_terms_items`
- `faq_items`
- `gallery`
- `rating_average` and `rating_count` only when real
- `meta_title`, `meta_description`, `og_title`, `og_description`, `canonical_url`, `robots_directive`, `schema`

Behavior rules:
- never fabricate ratings, review counts, urgency, or departure dates,
- if `sale_price` is empty, show a truthful contact/consultation state instead of fake pricing,
- card and detail CTA labels must match `cta_mode`,
- image alt text must describe the destination/tour context, not repeat meaningless file names,
- when published `departures` exist, tour-detail pricing surfaces must prefer `departure_date`, `standard_label`, and `sale_price` / `base_price` from those departure rows instead of inventing a separate summary source,
- one visible pricing row must stay internally consistent: the `Ngày khởi hành`, `Tiêu chuẩn`, and `Giá` shown together must come from the same departure row, not from mixed fallback rows,
- `pricing_table` on tour detail should be treated as supplemental pricing notes such as phụ thu or ghi chú thêm, and should render as its own notes block instead of repeating departure-backed pricing rows,
- `tour_terms_items` on the current `Tour` should be rendered ahead of FAQ when present; if a tour has no own term items, the frontsite should fallback to the shared template from `SiteSetting`,
- schema and metadata must be generated from visible tour content and real CMS fields only.

### Detail page
1. Hero banner
2. Tour summary facts
3. Tour overview
4. Itinerary
5. Pricing / inclusions / schedules
6. Tour terms
7. FAQ
8. Related tours
9. Inquiry CTA

Detail-page presentation refinements:
- all visible price strings on the tour detail page must use a no-wrap treatment such as `whitespace-nowrap` or an equivalent CSS rule; this applies to departure tables, departure cards, pricing rows, sticky/sidebar price clusters, and related-tour price labels so the currency string never breaks across lines,
- the supplemental pricing block on tour detail should be titled `Phụ thu và ghi chú thêm`; departure-backed prices stay in the departure schedule section and should not be repeated again as a second `Ngày khởi hành / Tiêu chuẩn / Giá` table,
- the `Lịch trình chi tiết` block on tour detail uses the shared single-open accordion behavior, but its trigger row should read like editorial content instead of a generic FAQ card,
- itinerary item titles should sit flush at the start of the content line; do not prepend a separate orange day chip, day badge, or left-side label block in the trigger row by default,
- the visible itinerary title text itself should carry the orange emphasis color at `1.2rem`, while the `+ / -` affordance remains a smaller secondary control on the right,
- itinerary item spacing stays compact: only a small gap between cards, and trigger/panel padding reduced by about 50% from the older roomy version,
- the expanded `tour-itinerary-panel-*` content surface should stay clean and borderless; do not add an inner outline or ring treatment around the itinerary content panel,
- when a `tour-itinerary-trigger-*` item is opened, the page should scroll that accordion item back to the top of the viewport below the sticky header so the expanded content starts in an easy reading position,
- the main media/gallery section on a tour detail page represents the album of the current tour being viewed, but frontsite gallery sliders should go straight into the media shell; do not render a separate section heading, eyebrow, or helper intro copy above the gallery by default,
- gallery cards that open a lightbox must expose an explicit zoom/open cue such as `Phóng to` with a magnifying-glass style icon; do not rely on image hover alone to imply the action,
- thumbnail rails in frontsite gallery sliders should show image previews only; do not render titles, descriptions, numbering, badges, or helper text above or inside the thumbnail strip by default,
- on mobile, tour-detail gallery sliders should let the primary media stage span the full available content width while the thumbnail rail stays underneath as a horizontal scroll surface for browsing more media,
- the `Related tours` block may start with an image-led gallery strip or visual preview rail above the shared tour-card grid when that improves browsing intent and visual storytelling,
- any related-tour gallery strip must reuse real cover or gallery imagery from the related tours themselves, keep price/contact state truthful, and still preserve direct links into the actual related tour detail pages,
- when both the current-tour album and a related-tour preview/gallery strip appear on the same page, each block must stay visually distinct by container, source data, and surrounding section context; do not rely on duplicated gallery heading/copy to separate them,
- after the main `Tour liên quan` section heading, the block should enter directly into the related-tour grid or approved preview rail; do not add a second helper header row, counter badge, or duplicate intro copy such as `Danh sách tour liên quan`,
- when `Tour liên quan` has 4 or more tours, render it as the shared card carousel instead of a static grid: desktop uses 3 tour cards per view, and mobile exposes about `1 + 1/5` cards per viewport like the homepage tour rail,
- the shared frontsite gallery lightbox should support `prev / next`, a visible item counter, close by `Esc` or backdrop, and left/right keyboard navigation,
- when a page contains more than one independent gallery set, the triggers should be scoped by a shared collection wrapper such as `data-gallery-collection` so lightbox navigation stays inside the intended gallery instead of mixing media from different sections.
---

## 10. Blog pages

### Listing page
- article grid/list
- categories
- search/archive if supported
- `/blog` không có yêu cầu bắt buộc phải render một featured article riêng phía trên danh sách bài viết; listing có thể đi thẳng từ intro/category rail vào article grid nếu layout hiện tại không cần stage featured
- khối intro + `Danh mục` của blog listing nên đi theo full width của content container hiện tại; không bó hẹp wrapper này bằng một `max-width` nhỏ hơn khi grid danh mục đang hiển thị
- `Danh mục` ở blog listing chỉ hiển thị danh mục blog cấp 1
- danh mục blog dùng canonical route `/danh-muc/{slug}`; `/blog?category={slug}` chỉ là URL cũ cần redirect về route canonical này
- cụm `Danh mục` này dùng visual family gần với `Chủ đề tour` ở homepage: card ảnh gọn, nhấn vào root category để lọc branch blog tương ứng
- cụm `Danh mục` ở `/blog` không dùng heading riêng hay `theme-panel` bọc ngoài; cụm này bắt đầu trực tiếp bằng filter row + category rail
- trên mobile, cụm `Danh mục` dùng grid `2` cột khi có tối đa `2` root category; nếu nhiều hơn `2` thì chuyển sang slider với `2` card mỗi lượt nhìn thấy
- trên desktop, cụm `Danh mục` hiển thị tối đa `6` card trên một hàng; nếu ít hơn `6` thì cả hàng card phải được canh giữa, và nếu nhiều hơn `6` thì desktop chuyển sang slider
- không render thêm dải chip category con bên trong card
- summary phân trang của blog listing phải dùng tiếng Việt theo format `Hiển thị X đến Y trong tổng số Z kết quả`, với nhãn điều hướng `Trước` và `Tiếp theo`
- ui desktop html `docs/front_end/demo/blog/code.html`

### Detail page
- H1 title
- publish date
- cover image
- generated table of contents from content `h2`
- structured body
- related posts
- optional CTA
- wrapper nội dung chính của blog detail gồm TOC, body, `Thông tin bài viết`, FAQ nên đi theo full width của content container hiện tại; không bó lại bằng `max-width` nhỏ hơn kiểu `max-w-5xl`

Blog purpose:
- SEO growth,
- authority building,
- internal linking to tours and services.
- no separate summary card on blog detail when TOC is used.
- ui desktop html `docs/front_end/demo/blog_detail/code.html`
---

## 11. Header/footer rules

### Header
Must support:
- logo,
- main navigation,
- visible CTA or hotline,
- responsive mobile menu.

### Footer
Should include:
- company summary,
- quick links,
- tour or service links,
- contact information,
- social links,
- map/address reference if appropriate.

Footer CMS rules:
- menu location `footer` is the primary footer quick-link group,
- menu location `footer_secondary` is the secondary footer link group and should be available for both desktop and mobile footer layouts,
- the menu `description` field of `footer` and `footer_secondary` should be treated as the visible column heading on the frontsite,
- copy like `Đi nhanh` may remain the default seed/fallback label, but editors must be able to rename it without code changes,
- if only one footer menu group has content, the layout may collapse back to a single link column without rendering an empty placeholder block.

---

## 12. Component rules

Prefer reusable components:
- `HeroSection`
- `SectionHeading`
- `ServiceCard`
- `TourCard`
- `InquiryForm`
- `DestinationCard`
- `MetricCard`
- `ProcessSteps`
- `TestimonialCard`
- `ArticleCard`
- `CTASection`
- `ContactCard`

## 13. Frontsite motion system

### Required motion hooks
- Use shared hooks `frontsite-text-reveal`, `data-reveal`, and `data-hero-text`.
- Do not hardcode one-off animation classes in Blade unless there is no shared hook available.
- Frontsite pages should import and rely on the shared JS motion controller bundled through `resources/js/app.js`.
- Slider items may additionally pass `data-reveal-effect` or `data-hero-effect` so the shared motion controller can apply the configured animate.css effect without creating a second animation system.

### Reveal behavior
- General frontsite sections keep restrained `animate.css` presets: `fadeInDown`, `fadeIn`, `fadeInUp`.
- Slider items may opt into the broader non-exit animate.css catalog exposed by CMS, but exit effects must not be used for persistent slider copy.
- Hidden reveal state should combine `opacity: 0` with a slight downward offset.
- Sections should reveal on scroll via IntersectionObserver when possible.
- Hero text should replay reveal animation when hero slides change.

### Slider visual contract
- Slider items may define a main/desktop image, an optional mobile image, and an optional inner mini image for the hero side panel.
- The hero overlay is item-level and must disappear completely when turned off; do not leave invisible overlay wrappers behind.
- The hero inner mini panel is item-level and must collapse entirely when disabled or when no asset is available.
- Gallery renderers should honor the same responsive image contract so slider content stays consistent across hero and gallery surfaces.

### Hover behavior
- Hover effects should feel premium and light, not bouncy.
- Icons and images may scale slightly on hover.
- Card headings and linked text may shift subtly on the X axis.
- Avoid aggressive text scaling or exaggerated parallax.

### Reduced motion
- When `prefers-reduced-motion` is enabled, reveal and hover transforms must degrade gracefully.
- Content must remain readable with no dependence on animation for comprehension.

Avoid one-off component fragmentation.

---

## 14. UX rules

### Conversion priorities
- visible primary CTA above the fold,
- repeated CTA on long pages,
- contact/hotline access in header,
- trust sections before hard conversion asks.

### Mobile priorities
- thumb-friendly buttons,
- legible text,
- clean card stacking,
- simple menus.

---

## 15. SEO, AI discovery, and accessibility

Baseline target:
- SEO baseline follows current Google Search Central guidance,
- accessibility baseline for public frontsite work is WCAG 2.2 AA,
- ADA-facing implementation should treat WCAG 2.2 AA as the default engineering floor for public pages and inquiry flows.

### Search and crawl rules

Preserve or implement:
- one visible H1 per page,
- meaningful H2/H3 hierarchy with no skipped structure for core sections,
- descriptive `<title>`, meta description, canonical, Open Graph, and index directives,
- HTML-first rendering for primary tour facts, price/contact state, itinerary, FAQ, breadcrumbs, and internal links,
- sitemap entries only for active travel routes,
- `robots.txt` must advertise the sitemap and must not block intended public travel pages,
- internal links between home, landing pages, tour detail, services, blog, and contact must reflect real user intent,
- images should have descriptive alt text, stable dimensions, and optimized formats,
- avoid shipping key content only inside collapsed widgets, sliders, or JS-injected blocks.

### AI-friendly / citation-ready SEO rules

Important money pages should:
- answer the main user intent near the top of the page in plain language,
- expose key comparison facts in scannable HTML blocks,
- keep destination, duration, departure, pricing/contact state, and inclusions visible without requiring interaction,
- use clear internal links to related tours, related services, and supporting blog guides,
- include concise trust/context blocks that AI and search systems can parse without ambiguity.

### Schema rules for current travel runtime

Recommended schema:
- `Organization`
- `WebSite`
- `LocalBusiness`
- `AboutPage`
- `CollectionPage`
- `Service`
- `Product`
- `Article`
- `FAQPage`
- `BreadcrumbList`

Schema coupling rules:
- only emit schema that matches the visible page intent and visible content,
- only emit `FAQPage` when the page visibly renders the same FAQ items,
- blog detail TOC should be built from rendered `h2` anchors,
- current repo tour detail should prefer `Product` plus departure-driven `Offer` / `AggregateOffer` and `BreadcrumbList`,
- listing pages should prefer `CollectionPage`,
- service detail should prefer `Service`,
- related-question or related-tour blocks are for internal linking and intent expansion, not extra fake schema nodes,
- never fake reviews, ratings, awards, certifications, or prices in schema.

### ADA / WCAG 2.2 implementation rules

Public pages and forms must support:
- full keyboard navigation with no keyboard trap,
- visible focus states on all interactive controls,
- focus that is not obscured by sticky headers, floating chat buttons, or cookie bars,
- skip link or equivalent bypass path for repeated navigation,
- correct landmarks: `header`, `nav`, `main`, `footer`, search/form regions when relevant,
- sufficient color contrast for text, icons, input borders, and focus indicators,
- meaning that does not rely on color alone,
- responsive reflow at narrow widths without horizontal scrolling for core content,
- controls that are large enough to operate on touch devices; minimum WCAG 2.2 target-size thinking applies, and mobile CTA targets should generally prefer `44x44px` or larger,
- alternatives for drag/swipe-only interactions; carousels and galleries need buttons and keyboard access,
- reduced-motion support for reveal, hover, autoplay, and parallax-like behavior,
- error states and validation messages that are programmatically associated with the relevant field,
- autocomplete / input purpose hints where appropriate for inquiry fields such as name, email, phone, and departure-related inputs,
- no redundant re-entry in multi-step inquiry flows when the system already knows the value.

### ARIA rules

- prefer native HTML elements before ARIA roles,
- use ARIA only when semantic HTML is insufficient,
- do not add decorative or incorrect ARIA that conflicts with visible behavior,
- accordions, dialogs, tabs, and menus must follow WAI-ARIA Authoring Practices when custom behavior is required.

---

## 16. Performance rules

- optimize images,
- lazy-load non-critical media,
- keep JS bundle controlled,
- avoid unnecessary animation libraries,
- avoid slider overuse,
- prefer SSR/SSG-compatible patterns.

---

## 17. Output contract

When generating frontsite code/docs, return:
- page/component breakdown,
- files to create or modify,
- why the structure supports conversion,
- how the change respects the tour object contract and the SEO/ADA baseline,
- what should be validated in browser/build.
