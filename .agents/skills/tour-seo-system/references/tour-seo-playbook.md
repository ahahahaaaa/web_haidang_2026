# Tour SEO Playbook

## 1. Entity Map

| Entity | SEO role | Page type | Main schema | Index rule |
|---|---|---|---|---|
| `Tour` | Money page and conversion target | Tour detail | `Product` or `TouristTrip` + `Offer` or `AggregateOffer` + `BreadcrumbList` + optional `FAQPage` | Index |
| `Tour Category` | Commercial or thematic listing hub | Listing page | `CollectionPage` + `BreadcrumbList` + `ItemList` | Index |
| `Destination` | Place hub with guide and booking intent | Destination page | `CollectionPage` or `WebPage` + `BreadcrumbList` + `ItemList` + optional `FAQPage` | Index |
| `Region` | Grouping hub for destinations and tours | Region page | `CollectionPage` or `WebPage` + `BreadcrumbList` + optional `ItemList` | Index |
| `Country` | Top-level hub for national tour clusters; in Haidang Travel this is a root `Destination` record | Country page | `CollectionPage` or `WebPage` + `BreadcrumbList` + optional `ItemList` | Index |
| `Departure` | Sales variation by date | Embedded block or API field | `Offer` | Usually no dedicated index |
| `Offer` | Price and availability layer | Embedded in tour detail | `Offer` or nested inside `AggregateOffer` | Not a standalone page by default |

## 2. Core Relationships

- One `Tour` can belong to many `Tour Category` records.
- One `Tour` can go through many regular `Destination` records.
- One regular `Destination` belongs to exactly one country root through `country_id`.
- Country is implemented as a root `Destination` with `is_country_root = true` and `country_id = null`; do not reintroduce a separate country runtime model for this repo.
- One regular `Destination` can also belong to one `Region`; current region records group geography but do not replace the country root relation.
- Blog support clusters can belong to a country root and a regular destination; if both are set, they must match the same country hierarchy.
- One `Tour` has many `Departure` records.
- Each `Departure` carries pricing, transport, and availability signals.

## 3. Hub And Cluster Architecture

Use hub/cluster structure to keep crawl intent, visible content, schema, and internal links aligned.

| Layer | Page role | Default URLs | SEO job | Required link behavior |
|---|---|---|---|---|
| Authority hub | Brand and top-level travel entry | `/`, `/tour-trong-nuoc`, `/tour-nuoc-ngoai`, `/tour-doan` | Explain the main travel offer and route users into the right intent family | Link to scope, category, destination, service, and guide clusters |
| Taxonomy hub | Commercial or geographic browse hub | `/danh-muc-tour/{slug}`, `/tour-{slug}`, `/vung-mien/{slug}` | Own one category, destination, region, or country intent | Link down to tour detail pages and across/up to related taxonomy hubs |
| Money cluster | Purchasable tour detail | `/chuong-trinh/{slug}` | Convert users and prove the offer with dates, price/contact state, itinerary, inclusions, and policies | Link back to category, destination, region, country, and related tours |
| Support cluster | Editorial or service support page | `/{slug-category}/{slug-blog}`, `/dich-vu/{slug}`, custom landing slugs | Answer supporting questions and reinforce trust, logistics, or seasonal intent | Link to the most relevant hub and selected tour detail pages |
| Embedded commercial data | Departure and offer data | no standalone indexable URL by default | Provide price, availability, date, and sales variation context | Render visibly inside tour detail and feed `Offer` / `AggregateOffer` schema |

Hub compliance requires all of the following:

- a stable canonical URL that owns one intent family
- one visible H1 and useful intro/body copy for that intent
- crawlable HTML links to child cluster pages
- crawlable HTML links back up from child pages
- `CollectionPage` or appropriate page schema plus `ItemList` when a visible list exists
- sitemap inclusion when indexable and backed by live published content
- `noindex,follow` or canonicalization for filtered, sorted, search, and duplicate variants

Cluster compliance requires:

- a clear parent hub relation
- visible commercial or answer content that is not a thin duplicate of the hub
- internal links back to the parent hub and to relevant sibling pages
- schema that matches visible content only

Do not treat a redirect-only route, legacy compatibility route, or empty listing as a compliant hub. It may support old URLs, but it does not satisfy the hub role until it has data, visible content, schema, and sitemap treatment.

Default Haidang Travel cluster map:

- `/` links into the 3 scope hubs, featured tour category hubs, featured destination hubs, services, and blog guides.
- `/tour-trong-nuoc` clusters domestic categories, regions, destinations, and domestic tour details.
- `/tour-nuoc-ngoai` clusters international categories, destinations, visa/service support, and international tour details.
- `/tour-doan` clusters group-tour categories, MICE/team-building support content, and group tour details.
- `/danh-muc-tour/{slug}` clusters tour detail pages within one thematic/commercial intent.
- `/tour-{slug}` clusters tour detail pages by destination and should link to parent region/country when those hubs exist; the same family renders country hubs when the record is a country root.
- `/vung-mien/{slug}` clusters destination hubs and tour detail pages by broader geography.
- `/tour-{slug}` clusters region or destination hubs for one country when the destination record is a country root.
- `/{slug-category}/{slug-blog}` supports discovery by answering trip planning, visa, seasonal, destination, or policy questions and linking to the relevant hub.

Country-root implementation rules for Haidang Travel:

- `/tour-{slug}` must be backed by a published `Destination`; country hubs use records where `is_country_root = true`.
- Regular destination hubs use records where `is_country_root` is false or null and `country_id` points to a country root.
- Country hubs must render crawlable links to their child destination hubs.
- Destination, tour detail, and blog pages should link back to the country hub when country context exists.

## 4. URL Families

- `Country` -> `/tour-{country-slug}`
- `Region` -> `/vung-mien/{region-slug}`
- `Destination` -> `/tour-{destination-slug}`
- `Tour Category` -> `/danh-muc-tour/{category-slug}`
- `Tour` -> `/tour/{tour-slug}` or `/tour/{primary-category-slug}/{tour-slug}`

Guidance:

- Keep slugs lowercase and hyphenated.
- Use one canonical tour URL only.
- Prefer stable paths that do not depend on temporary filters.

## 5. Required SEO Fields By Entity

### Country

- `id`
- `name`
- `slug`
- `is_country_root = true`
- `country_id = null`
- `seo_title`
- `seo_description`
- `h1`
- `intro_content`
- `canonical_url`

### Region

- `id`
- `country_id`
- `name`
- `slug`
- `seo_title`
- `seo_description`
- `h1`
- `intro_content`
- `faq_content`
- `canonical_url`

### Destination

- `id`
- `country_id` pointing to a country root
- `is_country_root = false` or `null`
- `region_id`
- `name`
- `slug`
- `type`
- `short_description`
- `long_description`
- `highlights`
- `best_time_to_visit`
- `seo_title`
- `seo_description`
- `h1`
- `faq_content`
- `canonical_url`

### Blog support cluster

- `id`
- `title`
- `slug`
- `content_category_id`
- `country_destination_id` when the guide belongs to a country intent
- `destination_id` when the guide belongs to a destination intent
- `seo_title`
- `seo_description`
- `h1`
- `intro_content`
- `faq_content`
- `canonical_url`

### Tour Category

- `id`
- `name`
- `slug`
- `parent_id`
- `short_description`
- `long_description`
- `seo_title`
- `seo_description`
- `h1`
- `canonical_url`

### Tour

- `id`
- `code`
- `name`
- `slug`
- `short_description`
- `overview_content`
- `itinerary_content`
- `included_content`
- `excluded_content`
- `policy_content`
- `duration_text`
- `duration_days`
- `duration_nights`
- `primary_country_id`
- `primary_region_id`
- `departure_place`
- `thumbnail`
- `gallery`
- `seo_title`
- `seo_description`
- `h1`
- `canonical_url`
- `is_published`
- `categories[]`
- `destinations[]`
- `departures[]`
- `related_tours[]`

### Departure

- `id`
- `tour_id`
- `start_date`
- `end_date`
- `booking_open_until`
- `available_slots`
- `status`
- `departure_place`
- `transport_mode`
- `transport_label`
- `transport_provider`

### Offer or Pricing

- `id`
- `departure_id`
- `currency`
- `list_price`
- `sale_price`
- `price_adult`
- `price_child`
- `price_infant`
- `valid_from`
- `valid_through`
- `availability`

## 6. Tour Detail Content Contract

Recommended section order:

1. H1 with the full tour name
2. Short summary
3. From-price plus nearest departure
4. Quick info: duration, departure place, transport, destinations
5. Highlights
6. Day-by-day itinerary
7. Departure and pricing table
8. Included and excluded services
9. Booking or cancellation policy
10. FAQ
11. Related tours
12. Destination or guide links

Required meta outputs:

- `title`
- `meta_description`
- `canonical`
- `og:title`
- `og:description`
- `og:image`
- `robots`
- JSON-LD

## 7. Metadata Templates

### Tour detail

Title:

```text
{Tour Name} | Giá từ {From Price} | Khởi hành {Departure Place}
```

Meta description:

```text
Đặt {Tour Name} lịch khởi hành mới nhất, đi {Main Destinations}, thời lượng {Duration}, phương tiện {Transport Summary}, giá từ {From Price}. Xem lịch trình và ưu đãi mới nhất.
```

### Destination page

Title:

```text
Tour {Destination Name} giá tốt, lịch khởi hành mới nhất
```

### Category page

Title:

```text
{Category Name} | Danh sách tour, lịch khởi hành và giá mới nhất
```

## 8. Schema Composition Rules

### Tour detail

- Main type: `Product` for this repo's commercial tour detail runtime, with `TouristTrip` remaining a valid generic travel alternative
- Support types: `Offer` or `AggregateOffer`, `BreadcrumbList`, optional `FAQPage`, optional `Organization`
- When using `Product`, map `productID`, `sku`, `brand`, `category`, `keywords`, `slogan`, and visible tour facts through `additionalProperty`
- Map ordered destinations to itinerary support nodes or ordered list schema only when the same sequence is visible on-page
- Derive `lowPrice`, `highPrice`, and `offerCount` from departures
- Use visible transport wording in `Offer.name` or nearby content

### Category page

- `CollectionPage`
- `BreadcrumbList`
- `ItemList`

### Destination, Region, Country

- `CollectionPage` or `WebPage`
- `BreadcrumbList`
- `ItemList`
- optional `FAQPage` only when visible Q/A exists

Repo-specific destination note:

- on the current Haidang Travel frontsite, canonical `/tour-{slug}` pages stay listing-first and should use `CollectionPage` as the sole page entity
- `/diem-den/{slug}` is a legacy redirect path and must not be treated as the canonical destination hub
- do not emit an additional `TouristDestination` node for the same destination hub page unless the product intent or visible content structure changes away from a listing hub

## 9. Transport Mapping

- Repo default: keep the main tour page as `Product` when the page behaves like a purchasable package tour with departure-driven offers
- Generic travel default may still keep the main tour page as `TouristTrip`
- Highlight transport in copy, quick facts, filters, and offer names
- Only switch to `Flight`, `BusTrip`, `BoatTrip`, or `TrainTrip` when the page truly represents that single transport trip and not a bundled package tour

## 10. Internal Linking Matrix

### On tour pages

Link to:

- the primary category
- each destination
- the parent region
- the parent country
- related blog or guide content
- related tours by region, theme, or transport

Breadcrumb order:

- International tour and destination pages: `Tour nước ngoài -> Region/continent -> Country root -> Destination -> current page`
- Other scopes may keep the local hierarchy that best matches the data, but must not put country after destination.

### On destination pages

Link to:

- featured tours
- tours by transport or season
- the parent region
- the parent country
- matching category hubs when relevant

### On region pages

Link to:

- major destination hubs in that region
- featured category hubs that have live tours in the region
- representative tour details
- the parent country hub when it exists

### On country pages

Link to:

- major destination hubs that have `country_id` pointing to the country root
- region hubs when they help users browse the country intent
- scope or category hubs that fit the country intent
- representative tour details

### On blog guide pages

Link to:

- the country hub when `country_destination_id` exists
- the destination hub when `destination_id` exists
- selected tour detail pages that satisfy the guide intent
- service pages that solve the same planning problem, such as visa support

### On category pages

Link to:

- best-selling tours
- major destinations
- visible FAQ content

### On blog or service support pages

Link to:

- the primary related hub
- selected tour detail pages
- service or guide pages that solve the same planning problem

## 11. Canonical and Robots Rules

Index by default:

- tour detail pages
- major destination pages
- major category pages
- major region pages
- country pages

Handle carefully:

- sort and filter URLs with many parameters
- thin paginated pages
- departure-only URLs
- duplicate URLs that differ only by date or price filters

Canonical rules:

- canonical every tour to the main tour URL
- do not let `?departure=` self-compete with the main URL
- only index a departure-specific page when it has materially unique content

## 12. Vietnam Default Rules

- If `country_id` is missing and the tour is domestic, default to `Vietnam`
- Default country root slug: `du-lich-viet-nam`; strip the `du-lich-` prefix only when inferring the country name from a slug
- Regular destinations should not remain countryless after import, seeding, or manual admin save
- Common domestic regions may include `mien-bac`, `mien-trung`, `mien-nam`, `tay-nguyen`, `mien-tay`
- Domestic tours roll into the `tour trong nuoc` cluster
- International tours roll into the `tour nuoc ngoai` cluster

## 13. API and Rendering Checklist

Backend should be able to provide:

- `from_price`
- `next_departure`
- `transport_summary`
- tour to category relations
- tour to destination relations
- region and country context

Frontend should:

- render JSON-LD server-side where possible
- render visible breadcrumb plus `BreadcrumbList`
- render visible FAQ plus `FAQPage`
- render departure or price tables from real data
- keep internal links aligned with taxonomy
- expose parent and child hub links in server-rendered HTML
- keep support clusters linked to their primary commercial hub

## 14. Minimal JSON-LD Shape For Tour Detail

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Tour Đà Nẵng - Hội An - Huế 3N2Đ",
  "url": "https://example.com/tour/tour-da-nang-hoi-an-hue-3n2d",
  "description": "Tour Đà Nẵng - Hội An - Huế 3 ngày 2 đêm, khởi hành định kỳ và có bảng giá theo ngày.",
  "image": "https://example.com/uploads/tours/da-nang-1.jpg",
  "productID": "1234",
  "sku": "tour-da-nang-hoi-an-hue-3n2d",
  "brand": {
    "@type": "Brand",
    "name": "Hai Dang Travel"
  },
  "category": "Tour trong nước",
  "keywords": "Tour trong nước, Đà Nẵng, Huế, Hội An",
  "additionalProperty": [
    {
      "@type": "PropertyValue",
      "name": "Điểm khởi hành",
      "value": "TP. Hồ Chí Minh"
    }
  ],
  "subjectOf": {
    "@type": "ItemList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "item": {
          "@type": "Place",
          "name": "Đà Nẵng"
        }
      }
    ]
  },
  "offers": {
    "@type": "AggregateOffer",
    "priceCurrency": "VND",
    "lowPrice": "3590000",
    "highPrice": "4990000",
    "offerCount": 2
  }
}
```
