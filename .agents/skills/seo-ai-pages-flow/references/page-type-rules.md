# Page Type Rules

## Supported page types

- `service`
- `category_service`
- `blog`
- `project`
- `contact`
- `location_landing`
- `homepage`

## What must be updated when adding or changing a page type

1. `SeoPageType` enum or accepted values
2. create-form options and validation
3. prompt guidance in `SeoPromptFactory`
4. fallback meta in `SeoMetaFactory`
5. schema in `SeoSchemaFactory`
6. QA thresholds and page-specific checks in `SeoQaValidator`
7. config thresholds in `config/seo_ai.php` if needed
8. admin labels/help text in SEO Pages UI

## Current expectations by page type

### `project`

- Meta should frame the page as proof/case-study style content
- Schema should include `CreativeWork`
- QA should expect scope/proof/execution signals
- Excerpt is useful and should not be empty

### `contact`

- Meta should prioritize contact intent and fast consultation
- Schema should include `ContactPage` and `Organization`
- QA should require clear contact CTA language
- If phone/email/address are available in config, use them in schema

### `category_service`

- Meta should frame the page as a service-category hub for travel support intent
- Schema should include `CollectionPage`, `ItemList`, and `BreadcrumbList`
- QA should require travel service signals such as dịch vụ, visa, vé máy bay, sim, du học, tư vấn, hồ sơ, or lịch trình
- Internal links should point to real service detail pages or closely related travel-support pages

### `location_landing`

- Meta should include local intent where possible
- Schema should include `Service` and explicit `areaServed`
- QA should require location context
- H1, meta, or body should mention the target location clearly

## QA heuristics to preserve

- Do not rely only on word count
- Validate required schema types per page type
- Validate meta lengths, not just presence
- Keep internal links part of QA
- Favor simple deterministic string checks over fragile heuristics

## Safe defaults

- If location exists, read it from `cluster.context.location`
- If excerpt exists, use it to seed meta description before generic fallback copy
- Keep schema arrays small and explicit
- Avoid inventing facts not present in content or config
