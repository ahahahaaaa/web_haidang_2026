---
name: tour-seo-system
description: >-
  Use when working on SEO architecture for travel tour websites: entity mapping
  for tours, categories, destinations, regions, countries, departures, and
  offers; URL design, metadata, canonical rules, robots, internal linking, and
  JSON-LD schema such as `Product`, `TouristTrip`, `Offer`, `AggregateOffer`,
  `BreadcrumbList`, and `FAQPage`; hub/cluster structures for tour scope,
  taxonomy, destination, region, country, tour detail, blog guide, and service
  support pages. Trigger on requests about chuan hoa SEO tour, schema tour,
  destination hub pages, travel taxonomy, SEO hub/cluster, internal linking,
  tour CMS SEO fields, or departure pricing schema.
---

# Tour SEO System

## Overview

Use this skill to standardize SEO for travel tour websites and tour CMSs with an entity-first model: `Tour`, `Tour Category`, `Destination`, `Region`, `Country`, `Departure`, and `Offer`. It turns travel data into stable page types, URL rules, metadata, JSON-LD, canonical behavior, and internal linking that match real booking intent.

The default architecture is a crawlable hub and cluster system:

- authority hubs: homepage and the 3 tour-scope pages
- taxonomy hubs: tour category, destination, region, and country pages
- cluster pages: tour detail pages, supporting blog guides, service cross-sell pages, and custom landing pages when they target a unique campaign or intent
- commercial data: departures and offers embedded inside tour detail pages instead of becoming thin indexable pages

## Always Read First

- `docs/AGENTS.md`
- `docs/FRONTSITE_AGENT.md` when the task changes public templates, page sections, or internal-link blocks
- `docs/TECHNICAL_REQUIREMENTS.md` when the task changes backend contracts, schema render, API payloads, or SEO admin logic
- [references/tour-seo-playbook.md](references/tour-seo-playbook.md)

Load these companion skills when the task overlaps:

- `.agents/skills/seo-ai-maker/SKILL.md`
  Use when the task also involves sitemap, robots, citation-ready sections, answer-summary blocks, entity clarity for AI search, or phased SEO rollout.
- `.agents/skills/seo-ai-pages-flow/SKILL.md`
  Use when the task touches this repo's SEO admin flow, `seo_pages`, `content_clusters`, schema/meta/QA generation rules, or `/admin/seo/pages`.

## Use This Skill When

- The user asks to standardize SEO for tour detail, destination, category, region, or country pages.
- The task involves `Product`, `TouristTrip`, `Offer`, `AggregateOffer`, `BreadcrumbList`, `FAQPage`, or travel schema design.
- The task involves tour taxonomy, URL design, canonical, robots, or internal links for a travel website.
- The task involves SEO fields for a tour CMS model or API contract such as `from_price`, `next_departure`, or `transport_summary`.
- The task involves deciding whether departures should be indexed or embedded as offers.
- The task involves converting travel business data into SEO-first landing pages or travel entities.

## Core Rules

- Model SEO around travel entities, not around a generic product page.
- One intent family should map to one primary indexable URL.
- Every indexable hub must own a distinct intent family and must not duplicate another hub's keyword target.
- Cluster pages must link back to their primary hub and onward to sibling or parent hubs when those links help real users browse.
- A hub is not compliant just because a route exists; it needs visible hub copy, crawlable child links, canonical metadata, schema, and sitemap inclusion when indexable.
- `Tour` is the commercial money page and the central SEO entity.
- `Departure` is sales data by default, not a standalone indexable page.
- Generic travel default may use `TouristTrip`, but this repo currently allows tour detail to render as `Product` when the page is positioned as a commercial purchasable tour with departure-driven offers.
- Do not replace the page with `Flight`, `BusTrip`, or `TrainTrip` unless the page truly represents that single transport trip.
- `Offer` and `AggregateOffer` must be derived from departures and visible pricing context.
- Do not emit schema that the page cannot visibly support.
- Do not emit `FAQPage` unless the same Q/A pairs are rendered on the page.
- Keep filter and sort URLs non-indexable unless they have unique editorial value.
- If country is missing and the tour is domestic, default country to `Vietnam`.
- In this repo, `Country` is implemented as a root `Destination` record: `is_country_root = true` and `country_id = null`. Do not restore a separate `Country` runtime model or table.
- Every regular `Destination` must belong to exactly one country root through `country_id`; a destination without a country is not SEO-compliant.
- Blog support clusters may carry both `country_destination_id` and `destination_id`; when a blog has a destination, its country must match the destination's country root.
- In this repo's current runtime, destination hub pages are listing-first and should use `CollectionPage` as the canonical page entity; do not add a second `TouristDestination` node for the same canonical `/tour-{slug}` page unless the page intent changes materially. Treat `/diem-den/{slug}` as a legacy redirect path, not the canonical hub.
- In this repo, stay inside the active travel runtime and do not reintroduce deprecated construction or old SEO AI runtime paths.
- Treat sitewide measurement as part of the SEO baseline: `Theme Settings` should expose `GA4 Measurement ID` and `Facebook Pixel ID` for all public tour pages.

## Workflow

### 1. Map the hub/cluster role

Classify the page before writing code:

- `authority_hub`: `/`, `/tour-trong-nuoc`, `/tour-nuoc-ngoai`, `/tour-doan`
- `taxonomy_hub`: `/danh-muc-tour/{slug}`, `/tour-{slug}`, `/vung-mien/{slug}`, with country roots sharing `/tour-{slug}`
- `money_cluster`: `/chuong-trinh/{slug}`
- `support_cluster`: blog guide, service page, or custom landing page with a unique supporting intent
- `embedded_commercial_data`: departure and offer rows inside a tour page

Then assign:

- one primary target keyword or user intent
- the parent hub it should link up to
- the child or sibling pages it should link down/across to
- the canonical URL that owns this intent
- whether the page belongs in the sitemap

For geography, enforce the chain `Country root -> Destination hub -> Tour detail / Blog guide`. Country hubs must expose crawlable destination links; destination hubs and tour/blog clusters must link back up to the relevant country when the relation exists.

Do not create or index another page for the same intent unless the content, audience, and conversion path are materially different.

### 2. Map the entity and its intent

Determine whether the task concerns:

- `Tour` for the main conversion page
- `Tour Category` for a commercial or thematic listing
- `Destination` for a place hub with guide plus money intent
- `Region` for a grouping hub
- `Country` for a top-level hub
- `Departure` or `Offer` for embedded commercial data

### 3. Choose the public page type

Default public page map:

- `Tour` -> `tour detail`
- `Tour Category` -> `listing page`
- `Destination` -> `destination page`
- `Region` -> `region page`
- `Country` -> `country page`
- `Departure` -> embedded offer block or API response

### 4. Lock the URL and indexability

Use the path family from [references/tour-seo-playbook.md](references/tour-seo-playbook.md):

- `/tour/{tour-slug}` or `/tour/{primary-category-slug}/{tour-slug}`
- `/danh-muc-tour/{category-slug}`
- `/tour-{destination-slug}`
- `/vung-mien/{region-slug}`
- `/tour-{country-slug}` for country roots

Canonical rules:

- one canonical URL per tour
- `?departure=` stays canonical to the main tour URL by default
- filtered and sorted duplicates should be `noindex` or canonicalized
- departure-only pages stay non-indexed unless they carry materially unique content

### 5. Define the field contract before writing UI or schema

Ensure the model or payload includes the required SEO fields for the chosen entity. Use the playbook reference for exact field lists and relations. Do not guess fields later in Blade, controllers, or schema factories.

At minimum, every indexable page needs:

- slug
- H1
- SEO title
- SEO description
- canonical URL
- intro or descriptive content that matches the page's intent

### 6. Compose the schema stack

Default schema bundles:

- `Tour detail` -> `Product` or `TouristTrip` + `Offer` or `AggregateOffer` + `BreadcrumbList` + optional `FAQPage` + `Organization`
- `Tour Category` -> `CollectionPage` + `BreadcrumbList` + `ItemList`
- `Destination`, `Region`, `Country` -> `CollectionPage` or `WebPage` + `BreadcrumbList` + `ItemList` + optional `FAQPage`

Repo note:

- current Haidang Travel frontsite uses `CollectionPage` as the only page entity on canonical destination hub URLs; reserve `TouristDestination` for supporting nodes on other page types, such as tour detail, when the linked destination is referenced visibly.

For tour pages:

- map itinerary from ordered destinations
- derive low and high price plus offer count from departures
- expose transport as text and offer naming unless the page is a pure transport trip
- when using `Product`, map `productID`, `sku`, `brand`, `category`, `keywords`, `slogan`, and visible quick facts through `additionalProperty`

### 7. Align visible content blocks and metadata

Tour detail pages should expose, in visible HTML:

- H1
- short summary
- from price and nearest departure
- quick facts: duration, departure place, transport, destinations
- highlights
- day-by-day itinerary
- departure and price table
- included and excluded services
- booking or cancellation policies
- FAQ
- related tours and destination links

Use the title and meta templates from the playbook and keep metadata grounded in real data such as destinations, duration, departure place, and from-price.

### 8. Wire internal linking as part of the architecture

At minimum:

- `Tour` links to category, destinations, region, country, related tours, and relevant blog guides
- `Destination` links to featured tours, transport or season clusters, parent region, and parent country
- `Category` links to best-selling tours, major destinations, and FAQ blocks
- `Region` links to its destination hubs, featured category hubs, and active tour clusters
- `Country` links to its region or destination hubs and the main scope/category hubs
- International breadcrumbs must follow `Tour nước ngoài -> Region/continent -> Country root -> Destination -> current page`.
- `Blog` guide pages link to the most relevant hub and selected tour detail pages, not only to other posts
- `Service` support pages link to the tour hubs where the service matters commercially, such as visa support from international-tour pages

Internal linking is required work, not optional polish.

### 9. Pull in supplemental repo skills when needed

Use `.agents/skills/seo-ai-maker/SKILL.md` after the entity and page map is clear and the task extends to:

- sitemap or robots
- citation blocks or answer-summary sections
- AI-friendly entity clarity across public pages
- phased SEO rollout

Use `.agents/skills/seo-ai-pages-flow/SKILL.md` after the entity and page map is clear and the task extends to:

- `seo_pages`, `content_clusters`, or `seo_links`
- queue-based metadata and schema generation
- prompt, meta, schema, or QA rules in admin
- manual review, approval, or publish flow

Recommended sequence for mixed tasks:

1. Use this skill to lock travel SEO entities, URLs, schema, and linking.
2. Use `seo-ai-maker` to strengthen crawlability, citation readiness, and rollout order.
3. Use `seo-ai-pages-flow` only if the implementation touches this repo's SEO admin workflow.

## Repo Extension Rules

When adapting this repo's SEO admin system for travel pages, introduce travel page types completely instead of patching only one layer. The typical public SEO page family is:

- `tour`
- `tour_category`
- `destination`
- `region`
- `country`

For these page types, update together:

- allowed enum values
- create and edit validation
- prompt guidance
- meta fallback rules
- schema generation
- QA requirements
- admin labels and help text

Do not create a dedicated `departure` page type unless the business explicitly needs unique landing pages for individual departures.

## Validation

Check the smallest relevant set first:

- required SEO fields exist for the chosen entity
- exactly one visible `H1`
- canonical resolves to the intended primary URL
- `Product` or `TouristTrip`, `CollectionPage`, `BreadcrumbList`, and `FAQPage` match visible content
- departures produce consistent `Offer` or `AggregateOffer`
- listing pages have unique intros, not thin duplicate copy
- destination, category, region, and country links form a usable hub path
- every hub has crawlable links to child clusters and at least one route back up from child clusters
- every regular destination has a country root and country hubs expose crawlable links to their destination clusters
- blog posts with destination context also carry the matching country context
- sitemap includes every indexable hub that has live content, and excludes legacy redirect shims
- redirect-only routes are treated as legacy support, not compliant hub pages
- filter and sort URLs do not compete with primary URLs
- public tour pages keep the required sitewide GA4 and Facebook Pixel snippets unless the task explicitly documents why tracking is deferred
- if the task touches repo code, run the smallest relevant Laravel, route, or build checks for changed files

## Success Criteria

The task is complete when:

- travel entities map cleanly to public page types
- every indexable page has a stable URL, metadata, and visible intent-matching content
- tour pages use the repo-approved primary entity, currently `Product`, with departure-driven offers
- hub pages connect category, destination, region, and country logically
- canonical, robots, and internal links prevent duplicate intent pages
- repo-specific admin or AI SEO changes are handed off to the appropriate supplemental skill
