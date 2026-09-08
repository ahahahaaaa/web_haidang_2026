# Landing Pages, Sliders, And Blocks

Use this reference for block-first landing pages, visual slots, sliders, galleries, query-backed lists, custom slugs, and frontsite block rendering.

Load `.agents/skills/landingpage-creator/SKILL.md` for landing-page implementation and `.agents/skills/slider-runtime-animatecss/SKILL.md` for slider runtime mechanics.

## Landing Page Contract

- Authoring is block-first.
- Manual `html` mode is an explicit escape hatch for blank custom pages that paste raw markup.
- System pages use `page_key`.
- Custom pages use unique root `slug`.
- `page_key` stays nullable for custom pages.
- Custom landing route must stay last in `routes/frontsite.php`.
- Disabled blocks keep configuration in CMS but do not render public HTML.
- Public rendering uses live runtime data, not copied snapshots.
- On the homepage system page, non-hero blocks may use `home_position` as fallback placement hints before anchors such as `home-tour-topics`, `featured-tours`, taxonomy tabs, destination slider, services, trust, process, blog preview, FAQ, and CTA.
- On the homepage, global post-hero order is `home_config.layout_order`; it mixes `section:{key}` hardcode sections and `block:{uuid}` dynamic blocks in one render list.
- Non-hero homepage blocks with default `home_position` must still receive type-based fallback anchors so the frontsite render order matches the CMS mixed layout.
- Fixed homepage sections after hero use `home_config.layout_order` for ordering and `home_config.{section}.is_enabled` for visibility; do not remove hardcoded sections from `home.blade.php` just to move or hide them.
- Hero blocks remain first-position only because they carry the homepage H1 and primary CTA.
- `html_widget` defaults to `before_featured_tours` on the homepage so campaign banners do not fall below the destination slider; editors can still move it through `home_config.layout_order` or choose another supported slot.

## Presets

Current template presets:

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

## Block Catalog

Current block families:

- `hero_slider`
- `hero_media`
- `hero_demo_landingpage`
- `gallery_slider`
- `gallery_media`
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

## Visual Slots

- Hero remains first visual block and must keep page `H1`, supporting copy, and CTA visible in server-rendered HTML.
- Gallery is optional and should sit after the main editorial body or browse intent.
- Hero and gallery source may be `none`, `slider`, or `media`.
- `slider` source prefers explicitly selected slider, then falls back to active slider matching `banner-location` such as `{page_key}-hero` or `{page_key}-gallery`.
- `media` source uses assets chosen from the shared Media popup.
- `none` keeps default editorial/static layout.
- Titles, descriptions, CTAs, and media wrappers render only when data exists.
- Homepage widget/banner placement should use `home_config.layout_order` instead of new one-off Blade insertion points; `home_position` only seeds fallback placement for new/unplaced blocks.
- Homepage fixed-section placement should use `home_config.layout_order`; `section_order` is compatibility data derived from that mixed order.

## Query Blocks

`tour_list` can query published tours by:

- category
- destination
- region
- country
- scope
- featured
- limit
- sort

`blog_list` can query published blog posts by:

- category
- featured
- limit
- sort

`region_rail` queries published `Region` hubs with published tours.

`topic_rail` queries featured `TourCategory` records and links to canonical `/danh-muc-tour/{slug}` hubs.

`region_taxonomy_tabs` auto-queries live `Region` hubs and switches between child `Destination` or `TourCategory` cards.

`tour_taxonomy_tabs` uses tabs targeting live `Region`, `Destination`, or `TourCategory` sources and renders server-side tour cards.

`hero_demo_landingpage` reuses the homepage demo hero layout for landing pages that need that stronger tour-entry hero. It is treated as a hero visual block, so it suppresses the default landing hero, uses live published tours for the right panel, and keeps the primary CTA as a Travel Inquiry trigger.

Avoid over-constraining query blocks into empty results.

## Slider Rules

Slider item fields may include:

- desktop image
- mobile image
- optional inner image
- optional video URL
- alt text
- title/subtitle/description
- destination URL
- primary and secondary CTA
- motion effect
- sort order
- active state

Rules:

- Use arrows/dots/carousel controls only when more than one item exists.
- Autoplay should pause on hover, focus, or manual interaction where applicable.
- Overlay and inner media panels collapse completely when disabled or unavailable.
- Persistent slider copy must not use exit-only animation effects.
- Slider/gallery media should follow shared conversion sizes from the media reference.

## Banner Count Matrix

For current landing banner/template blocks:

- Mobile: slider behavior from `1` item.
- Desktop `1` item: static one column.
- Desktop `2` items: static two columns.
- Desktop `3` items: static three columns.
- Desktop `4` items: static four columns.
- Desktop `>4` items: slider.

Keep static desktop variants stretched across the landing section width unless a task explicitly changes the design.

## FAQ Block

- Public FAQ blocks use one-column accordion.
- Use visible `+ / -` state icon and single-open behavior.
- Only visible FAQ items may be serialized into `FAQPage`.
- Hidden or disabled FAQ data must not drive schema.
