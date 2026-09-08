# Admin And Runtime

Use this file when the task touches the shared slider system.

## Admin authoring

- `app/Livewire/Admin/Cms/SlidersManager.php`
- `resources/views/livewire/admin/cms/sliders-manager.blade.php`

Current authoring includes:

- slider name
- `location` as `banner-location`
- `autoplay_delay`
- item title, subtitle, description
- desktop image, mobile image, inner image
- primary and secondary CTA
- `video_url`
- `effect`
- `show_overlay`
- `show_inner_media`

## Effect whitelist

- `app/Support/SliderAnimationEffects.php`

Use this file whenever:

- adding a new Animate.css option
- changing labels or grouped options
- validating effect input in admin

## Frontsite resolution

- `app/Http/Controllers/FrontsiteController.php`

Important methods:

- `resolveLandingSlider()`
- `resolveSliderGalleryItems()`
- `resolveSliderHeroSlides()`
- `landingSliderLocation()`

## Frontsite render surfaces

- `resources/views/themes/haidangtravel/partials/landing-hero.blade.php`
- `resources/views/themes/haidangtravel/partials/landing-gallery.blade.php`
- `resources/js/front/service-detail.js`

## Rules

- explicit slider id wins when active and populated
- otherwise fallback to active slider by `location`
- hero and gallery payloads may carry image, mobile image, inner image, video, overlay, CTA, and effect
