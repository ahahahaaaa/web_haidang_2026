---
name: slider-runtime-animatecss
description: >-
  Use when working on the shared slider runtime or admin slider manager:
  Slider, SliderItem, banner-location resolution, autoplay, media panel
  behavior, and Animate.css effect whitelists in the Haidang Travel frontsite.
---

# Slider Runtime AnimateCSS

Use this skill when the task is about shared slider data, admin slider authoring, or frontsite slider runtime behavior.

## Always Read First

- `docs/AGENTS.md`
- `docs/TECHNICAL_REQUIREMENTS.md`
- `docs/TOUR_SITEMAP_BLOCKS.md`

## Read When Needed

- `references/admin-and-runtime.md`

## Use This Skill When

- the user wants to edit `Slider` or `SliderItem`,
- the user wants to change `banner-location` resolution,
- the user wants to add or validate an Animate.css slide effect,
- the user wants to fix autoplay, nav, overlay, video, or inner-media slider behavior,
- the user wants to change shared frontsite slider runtime outside landing template layout rules.

## Core Rules

- This skill owns shared slider mechanics, not landing template count-layout decisions.
- Landing-page template decisions such as `1..4 static desktop, >4 desktop slider` belong to `../landingpage-creator/SKILL.md`.
- Effect values must come from `SliderAnimationEffects::values()`.
- Frontsite should resolve an explicit slider id first, then fallback to active `banner-location`.
- Desktop image, mobile image, and inner image each have separate roles and should not be collapsed casually.
- If the task changes slider payload shape, update both admin authoring and runtime consumers.

## Workflow

### 1. Choose the layer

Decide whether the task is:

- admin slider authoring
- runtime slider resolution
- shared effect catalog

### 2. Update the admin contract when needed

For admin work:

- inspect `SlidersManager`
- keep `Slider` and `SliderItem` fields aligned with the runtime payload
- preserve image selection flows that already use the shared media popup

### 3. Update the runtime contract when needed

For frontsite work:

- inspect `FrontsiteController` slider resolve methods
- inspect the relevant hero or gallery partial
- keep autoplay and nav behavior aligned with the resolved payload

### 4. Keep effect values safe

- add new effect values to `SliderAnimationEffects`
- keep admin validation and select options in sync
- do not emit raw effect strings that bypass the whitelist

### 5. Verify both sides

Check:

- admin create/edit still saves valid slider items
- frontsite still resolves the correct active slider
- autoplay, overlay, CTA, and optional media panel still behave as intended

## Deliverables

The task is complete when:

- the shared slider runtime remains coherent,
- admin and frontsite payload contracts stay aligned,
- Animate.css effects remain validated,
- landing template layout rules were not mixed into this skill by accident.
