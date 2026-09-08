# SEO Schema Mapping

## Core rule
Company schema is generated from Theme Settings, while public page schemas are generated from the actual entity or landing-page content being rendered. Schema must follow visible content, not the other way around.

## Canonical production SEO baseline (2026-06-27)
- Canonical production origin is `https://haidangtravel.com`; do not index `www`, HTTP, `/index.php/...`, or `haidangtravel.dtaa-tech.com` variants.
- Production must set `FRONTSITE_CANONICAL_URL=https://haidangtravel.com`; `FRONTSITE_CANONICAL_REDIRECT_ENABLED` should be true in production, and redirect hosts must include `haidangtravel.com`, `www.haidangtravel.com`, and `haidangtravel.dtaa-tech.com`.
- Canonical tags, `og:url`, sitemap entries, internal CMS snippets, menu links, frontsite CTAs, and media URLs must pass through the frontsite URL normalizer before rendering. Internal links should use a clean path or the configured canonical origin; external links keep their original host.
- Apache/LiteSpeed deployments must keep the `.htaccess` canonical rewrite before the Laravel front-controller rule: strip `/index.php`, redirect `www` and staging host to the non-www canonical host, and force HTTPS for `haidangtravel.com`.
- Laravel canonical middleware is the second defense for `/index.php/...` variants and must normalize from the raw request URI, not only route path info, because some server setups expose `/index.php` as the request base URL before routing.
- `/sitemap.xml` must list only canonical, active travel URLs. It must not contain staging hosts, `www`, `/index.php`, tracking parameters, filtered URLs, or inactive/non-published entities.
- `/robots.txt` must advertise the canonical sitemap URL and block only low-value technical/filter paths such as admin, auth, cart/checkout, search, and tracking/filter query patterns. Do not disallow the core travel IA.
- Tour departures are SEO-visible only when they are published/scheduled and current or upcoming in the app timezone. Past departures must not appear in frontsite departure lists, card next-departure data, or `Offer`/`AggregateOffer` JSON-LD.
- Travel legal data has one source of truth in `site_settings.structured_data.company`: `legal_name`, `business_license`, `international_travel_license`, and `tax_code`. Footer legal copy and Organization JSON-LD must use these fields, with `COMPANY_INTERNATIONAL_TRAVEL_LICENSE` only as a deployment fallback.
- Before launch or after domain changes, verify with `curl -I` for HTTP, `www`, staging, and `/index.php/...`; then inspect page source for canonical/OG URL, sitemap, robots, and absence of staging URLs.

## ImageObject contract
- Any schema field that represents an image, including `image`, `logo`, and `primaryImageOfPage`, should serialize as an `ImageObject` when a usable CMS/media/setting URL exists instead of falling back to a bare URL string.
- `ImageObject` should include `@type = ImageObject`, `url`, `contentUrl`, and a real caption/name when the source entity provides one; stable `@id` values may be added for page-level primary images.
- This applies across existing frontsite schema objects such as `Organization`, `LocalBusiness`, `WebPage`, `AboutPage`, `CollectionPage`, `Service`, `Product`, `TouristTrip`, `BlogPosting`, lightweight listing/taxonomy item references, and country/region/destination support nodes.
- Supplemental image galleries or additional article/category images should serialize through `associatedMedia` as an array of `ImageObject` nodes when those images come from real CMS media, visible rich-text images, landing media, or visible listing items.
- Do not invent placeholder image objects. If no existing image URL is available from CMS media, entity fields, landing hero/OG image, rendered content, or Theme Settings fallback, omit the image field.

---

## 1. Homepage

### Schema types
- `Organization`
- `WebSite`
- `LocalBusiness`
- `BreadcrumbList`
- `WebPage`
- `FAQPage` when the homepage FAQ block is visible
- one primary `ItemList` when the visible homepage featured-tour block or fallback browse-entry rail is chosen as the page's main entity
- secondary `ItemList` for visible homepage blog preview / `blog_list` widgets when they contain published posts

### Company source fields
- `company_name`
- `site_name`
- `logo`
- `structured_data.organization.image_url`
- `structured_data.company.legal_name`
- `structured_data.company.business_license`
- `structured_data.company.international_travel_license`
- `structured_data.company.tax_code`
- `phone` / `hotline`
- `email`
- `structured_data.address.street_address`
- `structured_data.address.address_locality`
- `structured_data.address.address_region`
- `structured_data.address.postal_code`
- `structured_data.address.address_country` (ISO 3166-1 alpha-2; Việt Nam phải render là `VN`)
- `structured_data.local_business.price_range`
- social/profile links when available

### Render notes
- `Organization.address` should serialize as `PostalAddress` when Theme Settings contains structured address fields; `streetAddress` may fall back to the plain `address` field when needed
- `PostalAddress.addressCountry` must render as an ISO 3166-1 alpha-2 country code; legacy values such as `Việt Nam`, `Vietnam`, `Viet Nam`, `VNM`, or a nested `Country.name` value are normalized to `VN`
- `Organization.image` should serialize as `ImageObject` and prefer `structured_data.organization.image_url`, then fall back to sitewide OG image, then logo
- `Organization.contactPoint` may serialize from the same hotline/phone/email values already shown on the frontsite
- `Organization.legalName`, `taxID`, and `identifier` should serialize only from Theme Settings `structured_data.company` or the deployment license fallback; do not hard-code legal/license values in Blade templates
- homepage may emit `LocalBusiness` when Theme Settings provides a usable `price_range` or structured address payload
- homepage should emit a simple `BreadcrumbList` rooted at `Trang chủ`
- homepage should emit a stable `WebPage` node for `/`, include `image` and `primaryImageOfPage` as `ImageObject` when a usable landing hero exists, include `mainEntityOfPage`, and keep `mainEntity` pointed at the visible homepage `featured tours` `ItemList` when that block has live tours
- when the fixed homepage `blog_preview` section or dynamic homepage `blog_list` block visibly renders published posts, serialize it as a secondary `ItemList` whose items are `BlogPosting` nodes, and reference those lists from `WebPage.hasPart` instead of replacing the primary homepage `mainEntity`
- homepage featured-tour `ItemList` should serialize visible tour cards as `Product` items with `Offer`, and may add `AggregateRating` only when the same homepage card visibly renders rating data
- if the homepage featured-tour block has no live tours, the single homepage `ItemList` may fall back to the visible `Chủ đề tour` rail, whose items serialize as lightweight `CollectionPage` references to the canonical `/danh-muc-tour/{slug}` hubs
- homepage `FAQPage` must serialize exactly the FAQ items rendered in the visible accordion; current runtime caps this homepage set to the first 4 visible items
- homepage should keep only one primary `mainEntity` `ItemList` in schema to avoid ambiguous page intent on `/`; secondary browse rails and taxonomy tabs remain visible UI and keep their own canonical destination/category/region pages, while visible blog preview/widgets may emit secondary `ItemList` nodes through `WebPage.hasPart` because they describe editorial content actually rendered on the homepage

--- 

## 2. About page

### Schema types
- `AboutPage`
- `BreadcrumbList`

### Content source
- about landing page content
- Theme Settings company description
- SEO overrides from landing-page meta fields

---

## 3. Service listing

### Schema types
- `CollectionPage`
- `BreadcrumbList`
- `FAQPage` if landing FAQ exists and is visible

### Content source
- `landing_pages` record with `page_key = services`
- `landing_pages.faq_items`

### Render notes
- FAQ on the listing page should answer group-selection and next-step questions
- FAQ content must be rendered on-page before `FAQPage` is emitted
- service listing hubs should keep a stable `CollectionPage.@id = {canonical-url}#webpage` and expose `mainEntityOfPage`, `image`, and `primaryImageOfPage` as `ImageObject` when the landing/category has a usable image

---

## 4. Service detail

### Schema types
- `Service`
- `BreadcrumbList`
- `FAQPage` if service FAQ exists and is visible

### Content source
- `services.title`
- `services.excerpt` / `services.content`
- `services.faq_items`
- `services.cover`
- service category as `category`
- site/company name as `provider`

### Render notes
- Service detail pages should keep one visible H1
- AI answer summary is a visible UX/SEO aid block, not a schema node
- `services.related_questions` should render as internal-link prompts only
- `related_questions` must not be serialized into `FAQPage`
- the primary `Service` node should keep a stable `@id = {canonical-url}#service`, expose `mainEntityOfPage`, and reuse the sitewide organization node as `provider`

---

## 5. Tour scope, category, region, and country listings

### Schema types
- `CollectionPage`
- `ItemList`
- `BreadcrumbList`
- `FAQPage` if page FAQ exists and is visible

### Content source
- system landing pages backing `/tour-trong-nuoc`, `/tour-nuoc-ngoai`, `/tour-doan`
- `tour_categories`, `regions`, `countries`
- filtered published `tours`
- page `excerpt`, `content`, `faq_items`, and visible review content

### Render notes
- listing pages should keep `CollectionPage` as the primary page type and point `mainEntity` to the visible `ItemList`
- canonical hub pages should keep a stable `CollectionPage.@id` at `{canonical-url}#webpage` and include `image`, `mainEntityOfPage`, and `primaryImageOfPage` as `ImageObject` when the page has a usable hero or taxonomy image; current runtime applies this richer page identity to category, destination, and region hubs
- listing and taxonomy hub pages may visibly render review blocks, but should not serialize `AggregateRating` or `Review` directly on `CollectionPage` because Google review rich results only accept ratings on supported reviewed item types such as `Product`, not category/list pages
- `ItemList` items should serialize lightweight `Product` references for tours with `name`, `url`, `productID`, `sku`, `category`, and visible comparison data when available
- filtered listing variants stay canonical to the base listing and default to `noindex,follow`
- category, region, and country hubs remain listing-first travel pages; do not force `Place` / `TouristDestination` unless the page is truly about one destination entity

---

## 6. Destination page

### Schema types
- `CollectionPage`
- `ItemList`
- `BreadcrumbList`
- supporting `Product` with rating and visible reviews
- `FAQPage` if destination FAQ exists and is visible

### Content source
- `destinations.name`
- `destinations.excerpt` / `destinations.content`
- `destinations.faq_items`
- `destinations.rating_average` / `destinations.rating_count`
- visible destination reviews
- related `region` and `country`
- published tours linked to the destination

### Render notes
- destination page should stay listing-first and use `CollectionPage` as the only page entity for the canonical `/tour-{slug}` URL
- destination page `BreadcrumbList` must follow the same visible browse trail as the UI: `Trang chủ` -> tour scope -> parent region -> current destination. When the current destination is a child place, do not insert the country as an intermediate breadcrumb; for example `Trang chủ` -> `Tour nước ngoài` -> `Châu Á` -> `Bắc Kinh`. Country root pages still show the country as the current node.
- destination page `CollectionPage` should carry the canonical page identity directly through `@id`, `image`, `mainEntityOfPage`, and `primaryImageOfPage` as `ImageObject`
- destination `CollectionPage.about` references a supporting `Product`; its `AggregateRating` serializes from real visible reviews when enabled, otherwise from `destinations.rating_average` and `destinations.rating_count`, while `Review` nodes serialize only from the visible review grid; its `AggregateOffer` uses the price range of paid tour cards currently rendered on the page
- in the current frontsite runtime, do not emit an extra `TouristDestination` node for the same destination hub page unless the page intent changes away from listing-first behavior
- do not invent coordinates, attractions, or place metadata that the CMS does not store and the page does not visibly support

---

## 7. Tour detail

### Schema types
- `Product`
- `Offer` or `AggregateOffer`
- per-departure `Offer` nodes when multiple published departures exist
- `ItemList` for visible itinerary content when the day-by-day sequence is rendered on-page
- supporting `TouristDestination` / `AdministrativeArea` / `Country` nodes when linked entities are present
- `BreadcrumbList`
- `FAQPage` if tour FAQ exists and is visible

### Content source
- `tours.title`
- `tours.excerpt` / `tours.content`
- `tours.cover`
- `tours.scope`
- `tours.slug`
- `tours.departure_location`
- `tours.transport`
- `tours.duration_*`
- `tours.itinerary`
- `tours.faq_items`
- `tours.rating_*`
- visible tour reviews
- published `tour_departures`
- related `destination`, `region`, and `country`

### Render notes
- `Product` should keep a stable `@id` and map the tour as the main commercial entity with `name`, `description`, `image` as `ImageObject`, `productID`, `sku`, `brand`, `category`, `keywords`, `slogan`, and `mainEntityOfPage`
- tour detail `BreadcrumbList` must match the visible breadcrumb trail: `Trang chủ` -> tour scope -> parent region -> destination -> current tour. When the destination is a child place, skip the country level, for example `Trang chủ` -> `Tour nước ngoài` -> `Châu Á` -> `Bắc Kinh` -> `{Tên tour}`
- current runtime maps `productID` from `tours.id`, `sku` from `tour_departure_sync_states.tour_code` when the tour sync API has a code, otherwise fallback to `HD{published_or_created_year}{tour_id}`, `brand` from Theme Settings company/site name, and `slogan` from `site_settings.site_tagline`
- visible quick facts such as scope, chủ đề tour, điểm khởi hành, điểm đến, vùng miền, phương tiện, thời lượng, và tiêu chuẩn should emit through `additionalProperty`
- if there is one valid current/upcoming departure-backed price, emit one primary `Offer`; if there are multiple valid current/upcoming departures, emit an `AggregateOffer` plus referenced child `Offer` nodes
- per-departure offers must come from real departure rows only and should reuse visible values such as date, departure place, standard, status, and price
- past departures must be excluded from visible departure lists, card next-departure state, and JSON-LD offer graphs even when the old row remains published in the database
- `Offer` should map real commercial fields already visible on the page, including numeric `price`, `priceCurrency`, `availability`, `itemCondition`, `seller`, and `url`
- public tour `Offer.availability` and `AggregateOffer.availability` must serialize as `https://schema.org/InStock`; seat counts and departure status stay in visible UX/business logic, not as schema stock downgrades
- tour `Offer.hasMerchantReturnPolicy` / `AggregateOffer.hasMerchantReturnPolicy` should include `merchantReturnLink = https://haidangtravel.com/chinh-sach-dat-tour-huy-doi-hoan-tien` through the canonical URL helper, alongside `applicableCountry = VN` and `returnPolicyCategory = https://schema.org/MerchantReturnNotPermitted`
- in the current repo contract, `seller` may serialize as a lightweight nested `Organization` summary while the page graph still contains the sitewide `Organization` node
- `AggregateRating` may serialize from the visible CMS rating summary fields `tours.rating_average` and `tours.rating_count`; this is independent from detailed review item visibility
- when `TRAVEL_REVIEWS_ENABLED=true` and valid published tour reviews exist, `AggregateRating` should prefer the average/count calculated from those real reviews before falling back to the CMS rating summary fields
- `review` should stay grounded in visible tour reviews; if the page renders one primary review it may serialize as a single `Review` object, otherwise it may remain a list
- when `TRAVEL_REVIEWS_ENABLED=false`, keep `AggregateRating` if its rating summary is visible, but omit detailed `review` schema and the public review grid
- itinerary schema is allowed only when the same order and content are visibly rendered on the tour page

---

## 8. Blog listing

### Schema types
- `CollectionPage`
- `ItemList`
- `BreadcrumbList`
- `FAQPage` if the selected visible blog category FAQ is rendered on the page

### Content source
- `landing_pages` record with `page_key = blog`
- published `blog_posts`
- selected `content_categories` for blog taxonomy

### Render notes
- the base canonical URL remains `/blog`
- query-string category and search variants are filter views, not standalone canonical entity pages
- do not create a separate category schema stack unless the project later ships a dedicated canonical blog category route
- blog listing hubs should keep a stable `CollectionPage.@id = /blog#webpage` and expose `mainEntityOfPage`, `image`, and `primaryImageOfPage` as `ImageObject` when the landing/category has a usable image
- visible blog `ItemList` entries on blog listing, homepage `blog_preview`, and dynamic homepage/landing `blog_list` widgets should serialize each `BlogPosting.author` with `@type = Person`, `name`, and a stable `url = /tac-gia/{author-slug}` when the post has an author name, or the shared fallback author when it does not
- base blog and selected blog-category `CollectionPage` nodes may expose `associatedMedia` as a compact gallery of real `ImageObject` nodes sourced from the landing image, selected category avatar, parent category avatar, and visible listed blog-post images

---

## 9. Blog detail

### Schema types
- `BlogPosting`
- `BreadcrumbList`
- `FAQPage` if blog FAQ exists and is visible

### Content source
- `blog_posts.title`
- `blog_posts.excerpt`
- `blog_posts.content`
- `blog_posts.cover`
- `blog_posts.faq_items`
- author/publisher fallback from site settings

### Render notes
- blog title must render as the page H1
- `BlogPosting` should keep a stable `@id = {canonical-url}#article`, expose `mainEntityOfPage`, and reuse the sitewide organization node as `publisher`
- `BlogPosting.author` should resolve to a stable `Person` entity with `url = /tac-gia/{author-slug}`; detail pages may reference the same author node by `@id`, while list/homepage item entries may inline the compact Person object
- `BlogPosting.image` should serialize as `ImageObject` and prefer the blog cover, then the first visible image inside rendered content, then the sitewide OG image as the final fallback so frontsite schema does not emit an empty image field by accident
- `BlogPosting.associatedMedia` should serialize real article image galleries as `ImageObject` arrays from the blog cover and visible rich-text images; do not include sitewide fallback images in this gallery unless they are also visible entity media
- the table of contents is generated from visible `h2` headings inside the rendered content and is a UI aid, not a separate schema node
- do not emit a TOC if the body has no usable `h2`

---

## 10. Custom landing pages

### Schema types
- `WebPage` by default
- `CollectionPage` when a visible query block defines the page's primary intent
- `ItemList` for visible `tour_list` / `tour_taxonomy_tabs` / `blog_list` / `topic_rail` / `region_rail` / `region_taxonomy_tabs` blocks
- supporting `Product`, `TouristTrip`, `Offer`, `AggregateOffer`, and `AggregateRating` nodes for visible tour cards inside `tour_list` / `tour_taxonomy_tabs`
- `FAQPage` if a visible `faq` block is rendered
- `BreadcrumbList`

### Content source
- custom `landing_pages` record with `page_key = null`
- normalized `blocks` JSON
- resolved query data for `tour_list`, `tour_taxonomy_tabs`, and `blog_list`
- visible FAQ block items

### Render notes
- block-based landing pages must stay visible-content-first: hero/gallery-only pages remain `WebPage`
- when multiple list blocks exist, pick one primary visible list as the `CollectionPage.mainEntity`; additional lists remain secondary graph nodes
- visible tour-card blocks on landing pages should emit each tour as a graph-backed `Product` with a linked `TouristTrip` node and a real `Offer` / `AggregateOffer` node sourced from published departures
- visible taxonomy rails on landing pages should emit lightweight `CollectionPage` item references that point to the canonical region, destination, or tour-category hub pages already shown in the carousel/tab UI
- `AggregateRating` on landing-page tour items is allowed only when the landing card itself visibly renders the corresponding rating summary
- `FAQPage` may only serialize the FAQ items rendered by the visible accordion block
- in phase 1 travel runtime, schema is generated from visible landing blocks; admin `schema` JSON is not the default public source unless the frontsite renderer explicitly opts in later

---

## 11. Fallback rules

### If page-level SEO data is missing
Fallback order:
1. entity meta fields such as `meta_title`, `meta_description`, `og_*`, `canonical_url`
2. landing-page defaults for the route
3. Theme Settings site-wide defaults

### Current public schema generation priority
1. auto-generated schema from the entity and visible page content
2. auto-generated schema from resolved landing-page blocks and query-backed block data
3. site-wide organization defaults from Theme Settings

---

## 12. Production rules
- Never fake ratings, reviews, awards, certifications, prices, departure dates, or availability
- Do not output `FAQPage` without a visible FAQ block on the same page
- Do not serialize `related_questions` into `FAQPage`
- Keep one canonical schema set per page intent
- Keep one visible H1 per public page
- Always include `BreadcrumbList` on listing/detail pages where breadcrumbs are shown
- When `image`, `logo`, or `primaryImageOfPage` is emitted, keep it as an `ImageObject` backed by an existing image URL rather than a placeholder or bare string
- Keep stable `@id` references for reusable nodes such as `Organization`, `Product`, `CollectionPage`, `TouristDestination`, and `ItemList`
- Keep schema aligned with rendered headings, CTAs, filters, and visible content blocks

---

## 13. SEO measurement baseline
- `Theme Settings` must store a sitewide `GA4 Measurement ID` and `Facebook Pixel ID` for the public frontsite
- tracking snippets are a launch-critical SEO measurement baseline and must render on public pages, not inside admin CMS
- schema rollout is not a substitute for measurement; when SEO work ships, confirm both entity/schema coverage and tracking coverage
