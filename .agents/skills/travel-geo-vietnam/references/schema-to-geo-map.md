# Schema To GEO Map

Use this reference when converting Haidang Travel-style schema SEO into GEO-ready architecture for Vietnamese travel pages.

## GEO Output Model

Every GEO-ready page should answer four machine-readable questions in visible HTML:

1. What is this page about?
2. Who is it for?
3. Which concrete travel facts can be cited?
4. What should the reader do next?

Schema reinforces those answers; it does not replace them.

## Runtime Feature Flag

GEO is controlled by `FRONTSITE_GEO_ENABLED`.

- `true`: frontsite can render GEO panels from `FrontsiteGeoPresenter`, and CMS can show/edit `geo_config` plus landing `geo_answer` blocks.
- `false`: frontsite must not render GEO panels, backend/CMS must not render GEO controls, landing builder must not show or offer `geo_answer` blocks, and save actions must not overwrite existing `geo_config`.

The flag is global. Entity-level `geo_config.is_enabled` only matters when `FRONTSITE_GEO_ENABLED=true`.

## Page Family Map

| Page family | Primary intent | Schema stack | GEO blocks |
| --- | --- | --- | --- |
| Homepage | Orient and route demand | `Organization`, `WebSite`, `WebPage`, optional `LocalBusiness`, `BreadcrumbList`, one primary `ItemList` | brand answer, featured tours/topics, destination rail, service entry, FAQ, inquiry CTA |
| Tour detail | Convert a specific package tour | `Product`, `Offer` or `AggregateOffer`, per-departure `Offer`, optional itinerary `ItemList`, place support nodes, `BreadcrumbList`, optional `FAQPage` | direct tour answer, quick facts, departure/price table, itinerary, includes/excludes, policy, FAQ, related tours |
| Tour category | Compare tours by theme | `CollectionPage`, `ItemList`, `BreadcrumbList`, optional `FAQPage` | category answer, selection criteria, visible tour list, major destinations, FAQ, related guides |
| Destination | Plan around a place hub | `CollectionPage`, `ItemList`, `BreadcrumbList`, optional `FAQPage` | destination answer, best time/route notes when backed, featured tours, parent region/country links, FAQ |
| Region/Country | Group destinations and tours | `CollectionPage`, `ItemList`, `BreadcrumbList`, optional `FAQPage` | regional answer, destination clusters, tour groups, practical planning notes, internal links |
| Service detail | Explain a support service | `Service`, provider `Organization`, `BreadcrumbList`, optional `FAQPage` | service answer, who needs it, required inputs, process, related services/tours, inquiry CTA |
| Blog detail | Explain a travel topic | `BlogPosting`, `BreadcrumbList`, optional `FAQPage` | concise answer, sections from H2s, practical tips, links to tours/services, FAQ when visible |
| Custom landing | Capture a specific demand cluster | `WebPage` or `CollectionPage` + `ItemList`; visible tour cards may add `Product`, `TouristTrip`, offers | cluster answer, curated lists, comparison/decision block, FAQ, CTA |

## GEO Block Patterns

### Direct Answer

Use one short block near the top. It should be self-contained enough for AI systems to quote:

- define the topic in one sentence
- name the company/site context when useful
- mention location, tour/service type, and next step
- avoid hype that cannot be verified

### Quick Facts

Use field-backed facts only:

- duration
- departure location
- destinations
- region/country
- transport
- from price or contact state
- next departure
- service category

If the value is absent, omit it or show a truthful consultation/contact state.

### Commercial Evidence

For tour pages, keep visible commercial facts synchronized with schema:

- departure date
- departure location
- standard
- base/sale price
- availability/status
- CTA anchor to `#tour-departures` or the shared inquiry flow

Use `Offer` for one priced departure and `AggregateOffer` plus child `Offer` nodes for multiple real priced departures.

### FAQ

Only serialize `FAQPage` from the exact Q/A items visible on the page. Do not turn related search prompts, internal link labels, or hidden CMS notes into FAQ schema.

### Internal Links

Add links that clarify entity relationships:

- tour -> category, destination, region, related tours, related guides, services
- destination -> featured tours, parent region/country, practical guides
- category -> featured tours, major destinations, related blog guides
- service -> service category, related services, contact/inquiry
- blog -> relevant tour/service/destination pages

## Citation Readiness Checklist

- One clear H1 matches the canonical intent.
- Canonical URL represents one intent family.
- First viewport or early body names the entity and travel context.
- Key facts are in semantic visible HTML, not image-only or JS-only UI.
- Schema `@type` and `@id` nodes match the visible content.
- `Organization` data comes from Theme Settings.
- Price/departure schema comes from `tour_departures` or truthful fallback fields.
- Reviews/ratings are rendered visibly before schema uses them.
- FAQ is visible before `FAQPage`.
- CTA uses active `TravelInquiry` or active contact route.
- Filter/sort/search URLs do not compete with canonical hubs.

## Common Mistakes

- Using `TouristDestination` as the main schema for a destination hub that is actually a listing page.
- Creating `FAQPage` from "related questions" that are only link prompts.
- Emitting multiple primary `ItemList` nodes on homepage without a clear main entity.
- Publishing thin region/country hubs without visible tour or destination links.
- Copying schema into admin JSON while the frontsite still auto-generates from visible content.
- Adding old SEO AI runtime routes instead of strengthening current travel pages.
