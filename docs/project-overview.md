# Project Overview

This Codex pack standardizes a cost-estimator feature that can be embedded into:

- Laravel applications
- WordPress / Elementor projects
- Vue or Nuxt frontends
- API-first systems

## Architectural principle

The estimator must be split into:

- **UI layer**: forms, tables, summary cards, lead forms
- **engine layer**: formula resolution, coefficient application, breakdown generation
- **admin/config layer**: editable field metadata, formulas, price sets, publish workflow

## Repository rule

Configs and docs are the source of truth. Generated UI or backend code should align with the contracts already defined under `skills/cost-estimator/`.
