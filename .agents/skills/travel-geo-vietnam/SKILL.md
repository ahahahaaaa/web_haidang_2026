---
name: travel-geo-vietnam
description: Build GEO (Generative Engine Optimization) and AI-search citation architecture for Vietnam travel websites, especially Haidang Travel-style Laravel CMS pages. Use when planning, implementing, auditing, or documenting GEO-ready tour, destination, region, country, service, blog, landing page, schema.org JSON-LD, answer-summary blocks, entity hubs, internal linking, sitemap/robots, or citation-ready content for Vietnamese tourism queries.
---

# Travel GEO Vietnam

## Overview

Use this skill to turn Vietnam travel SEO pages into GEO-ready pages that AI search systems can understand, quote, and route to conversion. It extends the current Haidang Travel schema contract: visible content first, schema second, and no fabricated commercial or trust signals.

## Required Reads

Always read:

- `docs/AGENTS.md`
- `docs/DOC_REFERENCE_MAP.md`
- `docs/SEO_SCHEMA_MAPPING.md`
- `.agents/skills/haidangtravel-cms-frontsite-system/references/frontsite-seo-schema.md`

Then read the narrow companion:

- `.agents/skills/tour-seo-system/SKILL.md` for tour/category/destination/region/country schema, offers, canonical, and internal links.
- `.agents/skills/seo-ai-maker/SKILL.md` for sitemap, robots, answer-summary sections, citation readiness, and rollout planning.
- `.agents/skills/landingpage-creator/SKILL.md` when GEO pages are block-based landing pages.
- `references/schema-to-geo-map.md` when designing or auditing the full GEO structure for a page family.

## Core Contract

- Keep active runtime in the `haidangtravel` theme and current travel domains only.
- Build GEO from visible HTML: H1, intro answer, quick facts, itinerary, pricing/departures, FAQ, breadcrumbs, internal links, trust/contact data, and page-specific comparisons.
- Keep schema aligned with rendered content. Do not emit schema for facts hidden only in CMS, JS-only widgets, disabled blocks, or internal prompts.
- Do not fake prices, departure dates, availability, ratings, reviews, licenses, awards, author credentials, or coordinates.
- Use Vietnamese with full diacritics for public copy and answer blocks.
- Treat `TravelInquiry` as the public lead/conversion path.
- Respect the global `FRONTSITE_GEO_ENABLED` flag. When it is `false`, frontsite GEO panels must not render, backend/CMS GEO controls must be hidden, `geo_answer` blocks must not be visible or addable from CMS, and save actions must preserve existing `geo_config` without writing new GEO changes.
- Do not reintroduce construction runtime, estimator pages, package/project pages, or legacy SEO AI runtime into active public pages.

## Current Schema Anchors

Use the repo's existing schema structure as the default:

- Head render: `resources/views/themes/haidangtravel/partials/head.blade.php` outputs `$seo['schema']` as one JSON-LD graph.
- Schema factory surface: `app/Http/Controllers/FrontsiteController.php` builds current frontsite schema graph nodes.
- Homepage: `Organization`, optional `LocalBusiness`, `WebSite`, `WebPage`, `BreadcrumbList`, optional `FAQPage`, and one primary `ItemList`.
- Service detail: `Service`, provider `Organization`, optional visible `FAQPage`, and `BreadcrumbList`.
- Tour detail: `Product` as the commercial entity, `Offer` or `AggregateOffer`, per-departure `Offer` nodes, optional itinerary `ItemList`, supporting place nodes, `BreadcrumbList`, optional visible `FAQPage`, and visible reviews only.
- Tour/category/destination/region/country hubs: `CollectionPage`, `ItemList`, `BreadcrumbList`, optional visible `FAQPage`.
- Destination hubs stay listing-first: use `CollectionPage` as the canonical page entity, not an extra `TouristDestination` for the same URL.
- Blog detail: `BlogPosting`, `BreadcrumbList`, optional visible `FAQPage`.
- Custom landing pages: `WebPage` by default, or `CollectionPage` plus `ItemList` when a visible query/list block defines the primary intent; visible tour cards may emit `Product`, linked `TouristTrip`, and departure-backed offers.

## Workflow

1. Map the user request to a travel entity: `Tour`, `TourCategory`, `Destination`, `Region`, `Country`, `Service`, `BlogPost`, or `LandingPage`.
2. Identify the query intent family: informational, comparison, commercial tour selection, destination planning, service support, or conversion/contact.
3. Lock one canonical URL and one primary page entity before writing content or schema.
4. Design the visible GEO block set:
   - direct answer summary
   - quick facts from real fields
   - decision criteria or comparison block
   - price/departure/contact state when commercial
   - itinerary or timing guidance when relevant
   - FAQ from visible Q/A only
   - internal links to canonical tours, hubs, services, and guides
5. Compose schema from the current page mapping and reference real rendered facts by stable `@id` nodes.
6. Add citation-ready prose that states who, what, where, when, price/contact state, and next step in short self-contained paragraphs.
7. Validate that metadata, canonical, robots, schema, visible blocks, and internal links all describe the same page intent.

## Vietnam Travel GEO Rules

- Prefer query language used by Vietnamese travelers: "tour", "du lịch", "lịch khởi hành", "giá tour", "điểm khởi hành", "tour đoàn", "visa", "vé máy bay", "khách sạn", "MICE", "dịch vụ du lịch".
- Use `VND` for prices and keep visible table values synced with schema offers.
- For domestic pages, default country context to `Việt Nam` only when the entity/data supports it.
- For destination pages, connect destination -> region -> country -> featured tours -> related guides.
- For tour pages, connect tour -> category -> destination/region -> departures -> policies -> inquiry CTA.
- For service pages, connect service -> service category -> applicable travel scenario -> required customer inputs -> inquiry CTA.
- Mention local departure context such as TP. Hồ Chí Minh, Hà Nội, Đà Nẵng only when data or page copy supports it.

## Implementation Touchpoints

Before editing code, inspect the relevant route, controller method, model fields, migration/seed data, and Blade template.

Common files:

- `routes/frontsite.php`
- `app/Http/Controllers/FrontsiteController.php`
- `resources/views/themes/haidangtravel/partials/head.blade.php`
- `resources/views/themes/haidangtravel/pages/tours/show.blade.php`
- `resources/views/themes/haidangtravel/pages/tours/listing.blade.php`
- `resources/views/themes/haidangtravel/pages/services/show.blade.php`
- `resources/views/themes/haidangtravel/pages/blog/show.blade.php`
- `resources/views/themes/haidangtravel/pages/landing/show.blade.php`
- `docs/SEO_SCHEMA_MAPPING.md`

## Validation

For planning/docs-only work, confirm the skill or document follows the current schema map and travel runtime boundaries.

For code changes, choose the smallest useful checks:

- `php artisan route:list`
- inspect rendered page source for one JSON-LD graph and expected `@type`
- verify one visible H1 and visible FAQ before `FAQPage`
- verify canonical/robots behavior for filters and query URLs
- verify departure offer rows and schema offers come from the same data
- `npm run build` when Blade/CSS/JS changes affect frontsite assets

## Handoff

When finishing a GEO task, summarize:

- entity and intent mapped
- canonical URL family
- visible GEO blocks added or required
- schema graph types used
- internal links and conversion path
- validation run and remaining risks
