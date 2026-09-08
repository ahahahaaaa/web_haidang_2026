---
name: haidangtravel-cms-frontsite-system
description: >-
  Use when working anywhere in the Haidang Travel repo on the active travel CMS
  or frontsite: Laravel/Livewire admin, theme `haidangtravel`, tours,
  services, blog, landing pages, sliders, menus, settings, TravelInquiry,
  Spatie media, Quill image insertion, SEO metadata, sitemap, robots, canonical
  rules, JSON-LD schema, schema.org object selection, frontsite cache, or
  validation. Trigger for project-wide technical guidance, CMS changes, admin
  upload/image fields, frontsite UX, travel SEO/schema, and docs updates that
  must stay inside the current travel runtime.
---

# Haidang Travel CMS Frontsite System

Use this as the main project skill for Haidang Travel CMS/frontsite work. It is a routing and operating guide: load only the reference file that matches the task, then pull specialist skills when deeper implementation rules are needed.

## Required First Read

Always read:

- `docs/AGENTS.md`
- `docs/DOC_REFERENCE_MAP.md`

Then read the focused reference in this skill:

- `references/technical-runtime.md` for architecture, runtime boundaries, cache, inquiry, route families, and permissions.
- `references/admin-cms.md` for admin sidebar, list/edit screens, Livewire manager UX, roles, and CMS forms.
- `references/media-quill-spatie.md` for shared Media popup, Quill insert image, image upload fields, Spatie collections, conversions, and regeneration.
- `references/frontsite-seo-schema.md` for public IA, metadata, canonical, sitemap, JSON-LD, FAQ/schema coupling, and frontsite SEO.
- `references/landing-slider-blocks.md` for LandingPage blocks, visual slots, sliders, gallery/banner behavior, and query-backed lists.
- `references/validation-checklist.md` for the smallest useful validation set by task type.

## Runtime Boundaries

- Active theme is `haidangtravel`.
- Active public/admin domains are `Tour`, `TourCategory`, `Destination`, `Region`, `Country`, `Service`, `BlogPost`, `LandingPage`, `Slider`, `TravelInquiry`, `Menu`, and `SiteSetting`.
- Public conversion writes use `TravelInquiry`.
- Public render paths should use `resources/views/themes/haidangtravel`.
- Keep custom landing catch-all routes last in `routes/frontsite.php`.
- Keep controller logic thin; put business rules in services, actions, jobs, models, or Livewire managers as appropriate.

Do not reintroduce construction runtime, estimator flows, package pages, project pages, old SEO AI runtime, or the old `phong_thanh_dat` theme into active runtime. If older docs mention these, treat them as historical unless a direct user request explicitly asks for migration archaeology.

## Schema.org Object Contract

When a task touches JSON-LD/schema creation, editing, auditing, or mapping:

- Ask or confirm the primary schema.org object before implementing, using the exact `@type` name when possible, such as `Product`, `Service`, `CollectionPage`, `BlogPosting`, `FAQPage`, `Offer`, or `AggregateOffer`.
- Also confirm important nested objects when they affect structure, such as `Offer`, `PostalAddress`, `BreadcrumbList`, `ItemList`, `Organization`, `Review`, or `AggregateRating`.
- If the page type is already defined by this skill's references, state that default mapping and ask only whether the user wants to override it.
- Build schema according to schema.org structure and the current Haidang Travel documentation. Do not invent unsupported properties, fake values, or schema nodes that are not backed by visible page content.

## Companion Skill Routing

Load these skills when the task overlaps:

- `.agents/skills/media-spatie-library/SKILL.md` for Media popup, Quill image insertion, image-dropzone fields, library uploads, and Spatie sync.
- `.agents/skills/landingpage-creator/SKILL.md` for landing-page presets, blocks, custom slugs, query blocks, hero/gallery media, and SEO checks.
- `.agents/skills/tour-seo-system/SKILL.md` for tour/category/destination/region/country SEO, schema, offers, departures, canonical, and internal links.
- `.agents/skills/slider-runtime-animatecss/SKILL.md` for slider item runtime, banner-location, autoplay, overlay, video, and Animate.css behavior.
- `.agents/skills/laravel-best-practices/SKILL.md` for Laravel controllers, models, validation, policies, migrations, queues, caching, and tests.
- `.agents/skills/livewire-development/SKILL.md` for Livewire manager state, wire interactions, validation, navigation, and reactivity.
- `.agents/skills/fluxui-development/SKILL.md` for Flux UI admin components.
- `.agents/skills/tailwindcss-development/SKILL.md` for Tailwind utility and responsive layout work.

Use `seo-ai-maker` only for sitemap, robots, citation-ready public SEO architecture, or AI-search visibility. Use `seo-ai-pages-flow` only if the task explicitly touches the legacy SEO Pages admin flow; that flow is not active public runtime by default.

## Work Pattern

1. Confirm the task belongs to active travel runtime.
2. Read the smallest focused docs/reference set.
3. Inspect routes, controllers, models, migrations, seeders, Livewire managers, views, and theme files before editing.
4. Prefer small, reviewable diffs and existing project patterns.
5. Keep CMS writes authorized, validated, and publish-state aware.
6. Keep frontsite content HTML-first for SEO-critical facts, links, FAQ, prices/contact states, and schema-backed content.
7. Validate with the relevant checks from `references/validation-checklist.md`.

## Source Docs

Treat these as current source of truth:

- `docs/AGENTS.md`
- `docs/ADMIN_CMS.md`
- `docs/FRONTSITE_AGENT.md`
- `docs/DESIGN_SYSTEM.md`
- `docs/TECHNICAL_REQUIREMENTS.md`
- `docs/TOUR_SITEMAP_BLOCKS.md`
- `docs/SEO_SCHEMA_MAPPING.md`
- `docs/DOC_REFERENCE_MAP.md`
