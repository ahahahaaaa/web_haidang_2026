---
name: frontsite-reveal-animatecss
description: >-
  Use when building or adjusting frontsite text, section, div, or hero reveal
  animations with Animate.css, IntersectionObserver, reduced-motion fallback,
  and the shared data-reveal contract in the Haidang Travel theme.
---

# Frontsite Reveal AnimateCSS

Use this skill when the task is about shared frontsite reveal animation behavior rather than slider-specific effects.

## Always Read First

- `docs/AGENTS.md`
- `docs/FRONTSITE_AGENT.md`
- `docs/DESIGN_SYSTEM.md`

## Read When Needed

- `references/reveal-contract.md`

## Use This Skill When

- the user wants text, section, or `div` reveal animations,
- the user wants to add or tune `data-reveal` markup,
- the user wants to change hero text reveal behavior,
- the user wants to adjust reduced-motion or intersection-observer behavior,
- the user wants to reuse the current Animate.css reveal contract on another frontsite page.

## Core Rules

- Reuse the existing shared reveal contract before adding a new animation system.
- The source of truth is currently `resources/js/front/service-detail.js`, even though the file name is narrower than its actual usage.
- Keep motion progressive: content must stay readable without JS.
- Respect `prefers-reduced-motion`.
- Prefer existing reveal kinds such as `meta`, `title`, `body`, `cta`, `card`, `copy`, and `panel` before adding new ones.
- Hero text reveal uses `data-hero-text` and may override the effect through `data-hero-effect`.

## Workflow

### 1. Identify the animation family

Choose one:

- general reveal via `data-reveal`
- hero text reveal via `data-hero-text`
- explicit effect override via `data-reveal-effect` or `data-hero-effect`

### 2. Reuse the current markup contract

For normal reveals:

- add `data-reveal`
- optionally add `data-reveal-delay`

For hero text:

- add `data-hero-text`
- optionally add `data-hero-effect`

### 3. Extend JS only when the preset is missing

- prefer existing presets first
- if a new preset is necessary, update the shared reveal preset map instead of hand-animating one page

### 4. Verify shared behavior

Check:

- reduced-motion fallback
- intersection-triggered reveal
- page load and `livewire:navigated` re-init behavior

## Deliverables

The task is complete when:

- the page uses the shared reveal contract,
- motion remains readable without JS,
- reduced-motion behavior is respected,
- the new markup or preset fits the current frontsite animation family.
