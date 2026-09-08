---
name: cost-estimator
description: Use this skill for construction cost estimator systems: frontsite calculator, admin formula/pricing config, JSON-driven estimate engine, level-based input flows, and estimate API/contracts. Do not use for unrelated pricing calculators.
---

# Cost Estimator Skill

Use this skill when the task is to design, implement, refactor, or document a construction cost estimator system with any of these scopes:

- frontsite estimate calculator
- admin configuration for formulas and price tables
- JSON-driven calculation engine
- API contracts for estimate preview or submission
- level-based estimation flows (Level 1, 2, 3)
- lead capture tied to estimate results

Do not activate this skill for unrelated e-commerce pricing, generic mortgage calculators, or one-off arithmetic unless the task is explicitly about the construction estimator domain.

## Objectives

This skill standardizes a calculator system that must:

1. separate formula rules from UI code
2. separate converted-area logic from package pricing logic
3. support versioned draft/published configuration
4. return a reproducible line-by-line breakdown
5. support different input levels over the same engine

## Core domain model

The estimator has three layers:

1. **Frontsite**
   - user enters dimensions and options
   - system shows converted-area breakdown and package pricing
   - optional lead capture and CRM submission

2. **Admin**
   - managers edit coefficients, labels, field visibility, package pricing
   - configs support preview, draft, publish, import/export, versioning

3. **Engine**
   - validates inputs by level
   - resolves effective formula set and price set
   - computes derived areas and converted area per component
   - computes pricing packages from the converted area

## Activation checklist

Activate this skill when the request includes one or more of:

- build a “dự toán”, “estimate”, or “calculator” page
- configure coefficients for roof, foundation, basement, terrace, balcony, mezzanine
- create formula.json / field schema / level schema
- create admin CRUD for estimator settings
- create API or service for estimate calculation
- create AGENTS.md-compatible documentation for estimator work

## Operating rules

- Never hard-code business coefficients in controllers.
- Prefer config-driven or database-driven rules.
- Keep machine keys stable even when labels change.
- Use decimals for coefficients internally, not formatted percentages.
- Always return detailed component rows, not just totals.
- Every result must include enough metadata to reproduce it later:
  - level
  - formula version
  - price version
  - effective date if applicable
- Separate visibility rules from validation rules.
- Treat Level 1, Level 2, and Level 3 as input profiles, not separate engines.

## Terrace Auto Mode Policy

When the estimator supports the checkbox `has_terrace`, implement it as a documented engine mode, not as a view-only convenience:

- `has_terrace = true` means the top counted level is no longer treated as a normal `upper_floors` component
- when `has_terrace = true`, frontsite should show manual `tum_area` and `terrace_area` inputs instead of hiding them
- the system should prefill those two inputs with default values from the active formula
- users may adjust either value manually, but the engine/frontsite must keep `tum_area + terrace_area = terrace_rooftop_area`
- use stable derived keys so admin/frontsite/docs stay aligned:
  - `typical_floor_count`
  - `terrace_rooftop_area`
  - `terrace_default_tum_area`
  - `terrace_default_area`
  - `tum_effective_area`
  - `terrace_effective_area`
  - `roof_effective_area`
- default terrace auto split:
  - `terrace_rooftop_area = base_area * 1.25`
  - `terrace_default_tum_area = terrace_rooftop_area * 0.3`
  - `terrace_default_area = terrace_rooftop_area * 0.7`
- uncovered terrace in this auto mode uses coefficient `0.5`
- roof area in this auto mode follows the tum-covered portion unless an explicit `roof_area` overrides it
- admin help text, frontsite guide popup, breakdown rows, and illustration must all describe the same auto-split behavior
- calculation should preserve exact decimal values internally; formatting/rounding for display belongs to the presentation layer
- when `has_terrace = false`, frontsite must not render `Tum` / `ST` helper labels or their illustration SVG block
- when `has_terrace = false`, `typical_floor_count` should behave as `floors - 1`, so a 2-storey house still renders `Tầng 2 (Lầu 1)` plus `Tầng Trệt`
- the no-terrace illustration should use a flat roof line aligned with the top floor box, not the terrace/tum roof composition

## Required outputs for implementation tasks

When using this skill, define all of the following unless the task explicitly narrows scope:

- input schema
- field visibility by level
- validation rules by level
- formula component mapping
- total converted area formula
- pricing package formula
- API response contract
- admin CRUD structure
- config versioning strategy
- sample input and output

## Estimation components

Model these components explicitly when relevant:

- ground floor
- front yard
- back yard
- garden / outdoor slab
- mezzanine
- mezzanine void
- upper floors
- terrace / rooftop / tum
- balcony
- roof system
- foundation
- ground concrete slab
- basement
- special structures

## Level policy

### Level 1 — Quick estimate
Purpose:
- lead generation
- fast consultation
- minimal inputs

Typical fields:
- length
- width
- floors
- mezzanine
- roof type
- foundation type
- basement yes/no
- finish package

### Level 2 — Advanced estimate
Purpose:
- sales consultation
- more realistic commercial estimate

Adds:
- terrace area
- tum area
- balcony area
- void area
- front/back yard area
- roof slope factor
- basement area
- building type
- location / access constraints

### Level 3 — Internal estimate
Purpose:
- internal pre-costing
- technical-commercial review

Adds:
- soil condition
- groundwater condition
- pile depth/type
- floor heights
- special structural spans
- detailed finish options
- contract scope flags
- regional pricing controls
- surcharge and discount controls

## Implementation guidance

### Laravel
Prefer a domain-oriented structure such as:

- `Domains/Estimator/DTOs`
- `Domains/Estimator/Enums`
- `Domains/Estimator/Services`
- `Domains/Estimator/Actions`
- `Domains/Estimator/Repositories`
- `Domains/Estimator/Models`
- `Domains/Estimator/Http/Controllers`
- `Domains/Estimator/Http/Requests`

### WordPress
Prefer:

- plugin bootstrap
- config repository
- admin menu for formulas and pricing
- AJAX endpoint for preview
- shortcode or Elementor widget for frontsite calculator

## Validation expectations

Always validate:

- required fields by level
- numeric ranges
- option key existence
- incompatible combinations
- presence of published config
- presence of active price set
- safe defaults for omitted optional inputs

## Output contract

Use a shape equivalent to:

```json
{
  "level": "level_1",
  "formula_version": "v1",
  "price_version": "v1",
  "input": {},
  "derived": {
    "base_area": 0
  },
  "components": [
    {
      "key": "ground_floor",
      "label": "Tổng Trệt",
      "base_area": 0,
      "effective_area": 0,
      "coefficient": 1.0,
      "converted_area": 0,
      "notes": []
    }
  ],
  "totals": {
    "converted_area": 0
  },
  "pricing": {
    "packages": []
  }
}
```

## File map

Use these references before implementing:

- `docs/business-rules.md`
- `docs/frontsite-spec.md`
- `docs/admin-spec.md`
- `docs/api-spec.md`
- `docs/data-model.md`
- `configs/field-catalog.json`
- `configs/estimation-levels.json`
- `configs/default-pricing.json`
- `examples/formula.json`
- `examples/sample-input.json`
- `examples/sample-output.json`
