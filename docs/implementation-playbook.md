# Implementation Playbook

## When integrating into Laravel

Recommended structure:

- `Domains/Estimator/DTOs`
- `Domains/Estimator/Enums`
- `Domains/Estimator/Services`
- `Domains/Estimator/Actions`
- `Domains/Estimator/Repositories`
- `Domains/Estimator/Models`
- `Domains/Estimator/Http/Controllers`
- `Domains/Estimator/Http/Requests`
- `tests/Feature/Estimator`
- `tests/Unit/Estimator`

## When integrating into WordPress

Recommended structure:

- plugin bootstrap
- estimator option/custom-table repository
- admin page(s) for formulas and pricing
- AJAX preview endpoint
- shortcode or Elementor widget
- mail/CRM hook for estimate capture

## Expected implementation order

1. field catalog
2. level visibility/validation
3. formula config
4. engine
5. package pricing
6. frontsite UI
7. admin CRUD
8. tests
