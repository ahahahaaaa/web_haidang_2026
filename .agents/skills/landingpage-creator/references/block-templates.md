# Current Block Templates

Use this file when the task is about current landing-page presets, block inventory, or runtime entry points.

## Source of truth

- `app/Support/LandingPageBlocks.php`
- `app/Livewire/Admin/Cms/LandingPagesManager.php`
- `app/Http/Controllers/FrontsiteController.php`
- `resources/views/themes/haidangtravel/pages/landing/show.blade.php`

## Current preset templates

- `home`
  - `hero_slider`
  - `gallery_slider`
  - `topic_rail`
  - `tour_taxonomy_tabs`
  - `region_taxonomy_tabs`
  - `tour_list`
  - `blog_list`
  - `faq`
  - `cta`

- `about`
  - `hero_media`
  - `gallery_media`
  - `rich_text`
  - `cta`

- `contact`
  - `hero_media`
  - `rich_text`
  - `cta`

- `services`
  - `hero_media`
  - `gallery_slider`
  - `rich_text`
  - `cta`

- `blog`
  - `hero_media`
  - `gallery_media`
  - `rich_text`
  - `blog_list`
  - `cta`

- `domestic_tours`
  - `hero_slider`
  - `gallery_media`
  - `rich_text`
  - `tour_list`
  - `cta`

- `international_tours`
  - `hero_slider`
  - `gallery_media`
  - `rich_text`
  - `tour_list`
  - `cta`

- `group_tours`
  - `hero_slider`
  - `gallery_media`
  - `rich_text`
  - `tour_list`
  - `cta`

- `blank`
  - no default blocks

- `generic`
  - `hero_media`
  - `rich_text`
  - `cta`

## Current block catalog

- `hero_slider`
- `hero_media`
- `hero_demo_landingpage`
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

## Runtime notes

- `blocks` is the primary landing authoring contract.
- `visual_config` still exists as a compatibility layer and fallback source.
- `home_config` belongs to the landing-page manager and remains part of this skill's scope.
- Homepage blocks may set `home_position` as a fallback placement hint around fixed homepage anchors such as `home-tour-topics`, `featured-tours`, taxonomy tabs, destination slider, services, trust, process, blog preview, FAQ, and CTA.
- Homepage post-hero order is `home_config.layout_order`, a single mixed list of `section:{key}` hardcode sections and `block:{uuid}` dynamic blocks. The CMS block stack edits block content; the mixed layout order controls public homepage position.
- Homepage non-hero blocks with default `home_position` must still enter `home_config.layout_order` through type-based fallback anchors.
- Homepage fixed sections after hero are ordered by `home_config.layout_order`; keep these hardcoded sections in Blade and hide them with `home_config.{section}.is_enabled` instead of deleting section markup for layout changes.
- Hero blocks stay locked to the first homepage visual position. Use `home_position` only for post-hero blocks.
- Homepage `html_widget` blocks default to `before_featured_tours` so campaign/banner widgets do not fall below the destination slider; they can still choose `before_topic_rail` or any other supported homepage slot.
- Public rendering for custom landing pages flows through `FrontsiteController::landingShow()` and `resources/views/themes/haidangtravel/pages/landing/show.blade.php`.
- `hero_demo_landingpage` reuses the homepage demo hero layout as an optional landing hero block. It queries live published tours across domestic, international, and group scopes, hides the default landing hero when enabled, and keeps the primary CTA wired to Travel Inquiry.
- `region_taxonomy_tabs` currently uses the newest visual contract: image-first cards, white bold `H3` title inside a translucent bottom overlay, the overlay clamps to title height at rest and expands with motion on hover to reveal the description, and the description stays out of normal flow so no blank gap appears under the title before hover; desktop `3-up` grid until the tab has more than `3` items, then desktop switches to `3-up` slider.
