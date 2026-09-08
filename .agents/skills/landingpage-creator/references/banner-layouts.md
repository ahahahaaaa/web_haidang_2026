# Banner Layouts

Use this file when a landing-page task needs count-based banner behavior for hero, gallery, or other template-led visual blocks.

## Count matrix

- Mobile:
  - from `1` item, use slider behavior

- Desktop:
  - `1` item -> static `1` column, full viewport section width
  - `2` items -> static `2` columns, full viewport section width
  - `3` items -> static `3` columns, full viewport section width
  - `4` items -> static `4` columns, full viewport section width
  - `>4` items -> slider

## Apply this matrix to

- current landing banner templates
- landing hero/gallery blocks when the section is acting like a banner rail
- count-driven landing visual sections that should stay editorial first on desktop

## Do not use this file for

- shared slider runtime mechanics such as autoplay, nav buttons, slide effects, or `SliderItem` admin behavior
- non-landing shared carousels such as taxonomy rails unless the task explicitly converts them into a landing banner pattern

For those cases, read `../../slider-runtime-animatecss/SKILL.md`.

## Runtime files to inspect before patching

- `resources/views/themes/haidangtravel/partials/landing-hero.blade.php`
- `resources/views/themes/haidangtravel/partials/landing-gallery.blade.php`
- `resources/views/themes/haidangtravel/partials/landing-gallery-card.blade.php`
- `resources/js/front/service-detail.js`

## Decision rule

- Default to static desktop when the count is `4` or fewer.
- Default to slider on mobile even for a single item so the component family stays behaviorally consistent.
- Only override this matrix when the task includes an explicit runtime or UX requirement that conflicts with it.
