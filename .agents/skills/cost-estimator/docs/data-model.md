# Data Model

## Tables

### estimator_formula_sets
- id
- code
- name
- description
- level_profile
- version
- status
- effective_from
- published_at
- published_by
- created_at
- updated_at

### estimator_formula_items
- id
- formula_set_id
- section
- key
- label
- input_dependency_json
- coefficient
- expression
- area_source
- option_key
- visibility_levels_json
- sort_order
- notes
- created_at
- updated_at

### estimator_price_sets
- id
- code
- name
- version
- status
- effective_from
- published_at
- published_by
- created_at
- updated_at

### estimator_price_items
- id
- price_set_id
- package_key
- package_label
- unit_price
- currency
- visibility_levels_json
- sort_order
- created_at
- updated_at

### estimator_requests
- id
- customer_name
- customer_email
- customer_phone
- customer_message
- level
- formula_version
- price_version
- raw_input_json
- raw_result_json
- total_converted_area
- created_at
- updated_at

