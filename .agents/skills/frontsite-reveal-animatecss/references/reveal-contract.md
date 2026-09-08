# Reveal Contract

Use this file when you need the current shared frontsite reveal behavior.

## Source of truth

- `resources/js/front/service-detail.js`

Even though the file is named `service-detail.js`, it currently owns shared frontsite behavior such as:

- hero sliders
- text reveal presets
- general `data-reveal` intersection logic
- re-init on `livewire:navigated`

## Current reveal kinds

- `eyebrow`
- `meta`
- `title`
- `body`
- `cta`
- `stat`
- `card`
- `copy`
- `panel`

## Current data attributes

- `data-reveal`
- `data-reveal-delay`
- `data-reveal-effect`
- `data-hero-text`
- `data-hero-effect`

## Runtime rules

- `IntersectionObserver` reveals non-hero elements when they enter view
- hero slide text is reset and replayed when slides change
- `prefers-reduced-motion` disables animation while keeping content visible
- the runtime re-syncs on `DOMContentLoaded`, `load`, `pageshow`, and `livewire:navigated`

## Representative view usage

- `resources/views/themes/haidangtravel/partials/landing-hero.blade.php`
- `resources/views/themes/haidangtravel/partials/section-heading.blade.php`
- `resources/views/themes/haidangtravel/partials/tour-card.blade.php`
- `resources/views/themes/haidangtravel/pages/home.blade.php`
- `resources/views/themes/haidangtravel/pages/blog/show.blade.php`
