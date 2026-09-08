# Frontsite SEO And Schema

Use this reference for public IA, theme UX, metadata, canonical, sitemap, robots, schema, FAQ coupling, and visible-content SEO.

Also load `.agents/skills/tour-seo-system/SKILL.md` for tour-specific SEO and `.agents/skills/seo-ai-maker/SKILL.md` for sitemap/robots/citation-readiness work.

## Frontsite Rules

- Public views should use `resources/views/themes/haidangtravel`.
- Vietnamese public copy must use full Vietnamese diacritics.
- Keep one visible `H1` per public page.
- SEO-critical content must be visible HTML: primary tour facts, price/contact state, itinerary, FAQ, breadcrumbs, internal links, and schema-backed facts.
- Avoid hiding key content only in JS, sliders, tabs, or collapsed widgets.
- CTAs should reuse the shared inquiry modal unless a scoped inline variant is explicitly approved.
- Shared frontsite forms submit via Ajax when JavaScript is available.

## Public IA

Core pages:

- homepage
- about
- domestic tour landing
- international tour landing
- group tour landing
- tour category hub
- destination hub
- region hub
- country hub
- tour detail
- service listing/detail
- blog listing/detail
- contact
- custom landing pages

Homepage should orient users, narrow intent, compare options, build trust, remove friction, and convert. It should expose crawlable taxonomy rails for `Chủ đề tour` and `Điểm đến nổi bật` when data exists.

## Search And Filters

- Homepage and listing pages share a compact GET search bar using query key `q`.
- Default search surface is one text input plus one submit action.
- `/tour-trong-nuoc` and `/tour-nuoc-ngoai` may add one `Chủ đề` select bound to `category`.
- That select uses published `TourCategory` records that have at least one published tour in the current scope.
- Filtered variants canonicalize to the base listing and default to `noindex,follow`.

## Sitemap And Canonical

Include only:

- published pages
- active travel routes
- canonical public URLs
- taxonomy hubs with at least one published tour

Do not include deprecated construction/estimator/package/project/old SEO AI routes, filtered URLs, sort URLs, or departure parameter URLs.

Each indexable page resolves to one canonical URL. Custom landing pages publish at `/{slug}` only after reserved-slug validation.

## Schema Core Rule

Schema follows visible rendered content, not CMS-only data.

Before creating, editing, or auditing schema:

- Confirm the primary schema.org `@type` object for the page or entity.
- Confirm nested schema objects when they affect the JSON-LD graph, especially `Offer`, `AggregateOffer`, `PostalAddress`, `BreadcrumbList`, `ItemList`, `Organization`, `Review`, `AggregateRating`, and `FAQPage`.
- Use the page-type mapping in this reference as the default when the user has not supplied a different schema.org object.
- If the requested object conflicts with visible page intent or current Haidang Travel docs, explain the conflict and keep the schema aligned with visible content unless the user explicitly changes the page intent.

Never emit:

- fake reviews
- fake ratings
- fake prices
- fake departure dates
- fake certifications or awards
- `FAQPage` for invisible FAQ content
- schema for related-question prompts that are only internal links

## Schema By Page Type

Homepage:

- `Organization`
- `WebSite`
- `LocalBusiness` when structured address or price range supports it
- `BreadcrumbList`
- `WebPage`
- `FAQPage` only from visible FAQ items
- one primary `ItemList`, preferring visible featured tours, then visible topic/destination fallback

About:

- `AboutPage`
- `BreadcrumbList`

Service listing:

- `CollectionPage`
- `BreadcrumbList`
- `FAQPage` if visible landing FAQ exists

Service detail:

- `Service`
- `BreadcrumbList`
- `FAQPage` only if service FAQ is visible
- Provider comes from sitewide organization settings

Tour scope/category/destination/region/country hubs:

- `CollectionPage`
- `ItemList`
- `BreadcrumbList`
- optional `FAQPage` if visible
- page-level review blocks may remain visible HTML, but do not attach `Review` or `AggregateRating` directly to `CollectionPage`; reserve rating schema for supported item types such as tour `Product`

Tour detail:

- `Product`
- `Offer` or `AggregateOffer`
- referenced per-departure `Offer` nodes when multiple published departures exist
- optional itinerary `ItemList` when visible itinerary exists
- supporting place nodes only from linked visible entities
- `BreadcrumbList`
- optional `FAQPage`

Blog listing:

- `CollectionPage`
- `ItemList`
- `BreadcrumbList`
- optional `FAQPage` from visible selected category FAQ

Blog detail:

- `BlogPosting`
- `BreadcrumbList`
- optional `FAQPage`
- Table of contents is generated from rendered `h2` headings and is not its own schema node

Custom landing pages:

- `WebPage` by default
- `CollectionPage` plus `ItemList` when a visible query block defines the primary intent
- visible tour-card blocks may emit `Product`, linked `TouristTrip`, and departure-backed offers
- visible taxonomy rails emit lightweight `CollectionPage` references to canonical hubs

## Tour Detail Commercial Contract

- `tour_departures` is the source of truth for per-date commercial data.
- When published/scheduled departures exist, visible pricing and schema offers must use the same rows.
- A visible row must keep `Ngày khởi hành`, `Tiêu chuẩn`, and `Giá` from the same departure row.
- `pricing_table` is supplemental for phụ thu and notes, not the primary departure price source.
- If no price exists, show truthful contact/consultation state.

## Accessibility Baseline

Public pages and forms target WCAG 2.2 AA:

- keyboard navigation
- visible focus
- no hidden focus behind sticky UI
- semantic landmarks
- sufficient contrast
- no meaning by color alone
- mobile reflow without core horizontal scrolling
- touch-friendly controls
- reduced motion support
- associated validation errors
