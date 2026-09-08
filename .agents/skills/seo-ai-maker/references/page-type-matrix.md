# Page Type Matrix

Use this matrix to keep schema aligned with page intent.

## Core mappings

- Home: `Organization`, optional `LocalBusiness`, `WebSite`, `WebPage`, primary `ItemList`
- Tour scope hub: `CollectionPage`, `ItemList`, `BreadcrumbList`
- Country hub: `CollectionPage`, `ItemList`, `BreadcrumbList`
- Region hub: `CollectionPage`, `ItemList`, `BreadcrumbList`
- Destination hub: `CollectionPage`, `ItemList`, `BreadcrumbList`
- Tour category hub: `CollectionPage`, `ItemList`, `BreadcrumbList`
- Tour detail: `Product` or `TouristTrip`, `Offer` or `AggregateOffer`, `BreadcrumbList`, optional visible `FAQPage`
- Service index: `CollectionPage`, `BreadcrumbList`
- Service category: `CollectionPage`, `ItemList`, `BreadcrumbList`
- Service detail: `Service`, `BreadcrumbList`
- Blog index: `CollectionPage`, `BreadcrumbList`
- Blog detail: `BlogPosting`, `BreadcrumbList`, optional visible `FAQPage`
- Travel landing page: `WebPage` by default, or `CollectionPage` plus `ItemList` when a visible tour/list block owns the intent

## Haidang Travel geography rules

- Country is a root `Destination` (`is_country_root = true`, `country_id = null`)
- Country root slugs use `du-lich-{slug}`, for example `du-lich-viet-nam`
- Regular destinations must point to a country root through `country_id`
- Country hubs should link to destination hubs; destination, tour, and blog pages should link back up when country context exists
- Blog posts with `destination_id` should also carry the matching `country_destination_id`

## Rule of thumb

- Match the main visible purpose of the page
- Do not add entities that the page does not substantively support
- Prefer one clear primary entity over many weak ones
