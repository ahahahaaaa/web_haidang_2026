---
name: landingpage-creator
description: >-
  Use when building or refactoring current Haidang Travel landing-page block
  templates, including preset selection, hero/gallery/banner blocks, slider vs
  static banner layout by item count, homepage landing config, query-driven
  lists, reserved root slugs, and publish/SEO checks.
---

# Landing Page Creator

Use this skill when the task is to create, clone, restructure, or QA a travel landing page in the Haidang Travel CMS.

## Always Read First

- `docs/AGENTS.md`
- `docs/TOUR_SITEMAP_BLOCKS.md`
- `docs/TECHNICAL_REQUIREMENTS.md`
- `docs/DESIGN_SYSTEM.md`

## Read When Needed

- `references/block-templates.md`
- `references/banner-layouts.md`

## Use This Skill When

- the user wants a new landing page in CMS,
- the user wants to convert a page into reusable blocks,
- the user wants hero/gallery/banner blocks to come from `slider` or `media popup`,
- the user wants a landing page to include dynamic `tour_list` or `blog_list` blocks,
- the user wants a landing page to include a tabbed tour block backed by `Region`, `Destination`, or `TourCategory`,
- the user wants a custom root slug such as `/{slug}` for a campaign page,
- the user wants to decide whether a banner block should render as static columns or slider by item count.

## Core Rules

- The landing-page editor is `block-first`; use manual `html` mode only when the user explicitly wants a blank custom landing page and will paste HTML directly.
- System pages use `page_key`; custom pages use a unique root `slug`.
- Reject reserved root slugs before saving or publishing.
- `hero_*` and `gallery_*` blocks may source only from `slider` or `media popup`.
- `tour_list` and `blog_list` must query published runtime content instead of copying titles or snapshots into the page.
- `region_rail` must query published `Region` hubs that still have published tours behind them.
- `tour_taxonomy_tabs` must query published tours from live `Region`, `Destination`, or `TourCategory` sources selected per tab.
- Use block `is_enabled` when the user wants to hide a landing block without deleting its configuration.
- The current landing-page contract is `blocks` first; `visual_config` is a compatibility layer and not the primary authoring target for new work.
- Homepage-specific config stays inside this manager through `home_config`; do not split homepage landing authoring into a separate runtime.
- Homepage blocks can use `home_position` as a fallback placement hint around fixed homepage anchors; use it to seed widgets/banners near `home-tour-topics` or `featured-tours` instead of adding hard-coded Blade insertion points.
- On the homepage, global post-hero order is `home_config.layout_order`, a mixed token list of `section:{key}` hardcode sections and `block:{uuid}` dynamic blocks.
- Homepage non-hero blocks with default `home_position` still need type-based fallback anchors; do not let editable dynamic blocks disappear from the public homepage just because no explicit slot was selected.
- Homepage fixed sections after hero move through `home_config.layout_order`, and `home_config.{section}.is_enabled` hides them without deleting the Blade section.
- Template-level decisions for landing hero/gallery/banner blocks live here. Shared slider runtime mechanics belong to `../slider-runtime-animatecss/SKILL.md`.
- If the change introduces a new CMS surface, update both `App\Support\Admin\AdminNavigationRegistry` and the design docs in the same patch.

## Current Template Scope

Current preset templates and block defaults are documented in `references/block-templates.md`.

Current block families:

- `hero_slider`
- `hero_media`
- `gallery_slider`
- `gallery_media`
- `html_widget`
- `rich_text`
- `region_rail`
- `region_taxonomy_tabs`
- `topic_rail`
- `tour_taxonomy_tabs`
- `trust_proof`
- `cta`
- `faq`
- `tour_list`
- `blog_list`

## Banner Count Matrix

Use this rule for current landing banner/template blocks before inventing custom JS:

- Mobile: from `1` item, use slider behavior.
- Desktop `1` item: static `1` column, full viewport section width.
- Desktop `2` items: static `2` columns, full viewport section width.
- Desktop `3` items: static `3` columns, full viewport section width.
- Desktop `4` items: static `4` columns, full viewport section width.
- Desktop `>4` items: switch to slider.

Read `references/banner-layouts.md` when the task changes runtime markup or count-based layout behavior.

## Workflow

### 1. Choose the page type

Decide whether the page is:

- a system landing page backed by `page_key`, or
- a custom campaign page backed by a root `slug`.

For custom pages, check the reserved route list in `App\Support\LandingPageBlocks::reservedSlugs()`.

### 2. Pick a template preset

Use the closest preset first:

- `home`
- `about`
- `contact`
- `services`
- `blog`
- `domestic_tours`
- `international_tours`
- `group_tours`
- `blank`
- `generic`

Preset blocks are a starting point. Reorder, remove, or duplicate blocks as needed.

### 3. Build the block stack

Available blocks:

- `hero_slider`
- `hero_media`
- `gallery_slider`
- `gallery_media`
- `html_widget`
- `rich_text`
- `region_rail`
- `region_taxonomy_tabs`
- `topic_rail`
- `tour_taxonomy_tabs`
- `trust_proof`
- `cta`
- `faq`
- `tour_list`
- `blog_list`

Recommended flow:

1. Choose one hero block.
2. Add editorial `rich_text` only where the page needs real body context.
3. Add `tour_list` or `blog_list` only when the page should surface live content.
4. Add `tour_taxonomy_tabs` when the page should compare tour groups through live region/destination/topic tabs.
5. Add gallery and FAQ only when they improve the page's decision support.
6. Finish with a `cta` block unless the page already has a stronger built-in conversion surface.

### 4. Configure hero, gallery, and banner layouts

For slider-backed visuals:

- choose an explicit slider when campaign-specific art matters,
- otherwise let the runtime fallback use the matching `banner-location`.

For media-backed visuals:

- use the shared Media popup flow,
- provide alt text for image-led media,
- keep titles, descriptions, and CTA labels empty when they should not render.

For banner count layout:

- apply the `1/2/3/4 static desktop, >4 desktop slider, mobile slider from 1` rule,
- keep desktop static variants stretched across the landing section width,
- only bypass this matrix when the runtime requirement explicitly calls for another layout.

### 5. Configure dynamic query blocks

`tour_list` supports:

- `category_slug`
- `destination_slug`
- `region_slug`
- `country_slug`
- `scope`
- `featured`
- `limit`
- `sort`

`blog_list` supports:

- `category_slug`
- `featured`
- `limit`
- `sort`

`region_rail` supports:

- `scope`
- `featured`
- `limit`
- `title`
- `description`
- `card_cta_label`

`tour_taxonomy_tabs` supports:

- shared block fields: `title`, `description`, `cta_label`, `scope`, `featured`, `limit`, `sort`
- per-tab fields: `source_type = region | destination | tour_category`
- per-tab fields: `source_slug`, `label`, `title`, `description`

`region_taxonomy_tabs` supports:

- shared block fields: `title`, `description`, `cta_label`, `card_cta_label`, `scope`, `featured`, `card_source_type`, `tab_limit`, `limit`
- `card_source_type = destination | tour_category`
- tablist is auto-generated from live `Region` hubs that still resolve published child cards for the chosen scope and source type

Use the smallest filter set that matches the page intent. Avoid over-constraining a page into an empty list.

### 6. Set publish and SEO fields

Before saving:

- confirm `is_active`,
- confirm title and slug,
- set `meta_title`, `meta_description`, canonical, robots, and schema only when the page has enough visible content to support them.

### 7. Verify the public result

Check:

- the page renders the blocks in the intended order,
- hero/gallery text collapses cleanly when fields are empty,
- dynamic lists show published items only,
- the custom slug does not collide with existing travel routes,
- the page still has one visible H1 and valid CTA flow.

## Deliverables

The task is complete when:

- the landing page uses the correct current preset or custom block stack,
- media and slider-backed blocks are wired through supported sources,
- banner blocks follow the current count-based desktop/mobile layout rule,
- query blocks return the intended live content,
- reserved slug and publish rules are respected,
- the public page matches the CMS configuration without legacy field drift.
