# Database Schema Blueprint

## Important architectural note
This system does **not** require a `companies` table in the first production version.
Company/site/business identity is loaded from Theme Settings.

---

## Tables

### service_categories
- id
- name
- slug
- description nullable
- sort_order default 0
- is_active boolean default true
- timestamps

### services
- id
- category_id nullable
- name
- slug unique
- service_code nullable
- icon nullable
- short_description text
- description text nullable
- content longtext
- featured_image nullable
- gallery_json json nullable
- pricing_type nullable
- starting_price decimal(15,2) nullable
- price_note nullable
- duration_note nullable
- benefits_json json nullable
- process_steps_json json nullable
- faq_json json nullable
- cta_title nullable
- cta_description nullable
- is_featured boolean default false
- sort_order integer default 0
- publish_status string index
- published_at nullable
- timestamps

### projects
- id
- primary_service_id
- name
- slug unique
- project_code nullable
- short_description text
- description text nullable
- content longtext nullable
- featured_image nullable
- gallery_json json nullable
- project_type nullable
- style nullable
- location_text nullable
- district nullable
- city nullable
- province nullable
- area_land_m2 decimal(10,2) nullable
- area_floor_m2 decimal(10,2) nullable
- floors integer nullable
- bedrooms integer nullable
- bathrooms integer nullable
- budget_min decimal(15,2) nullable
- budget_max decimal(15,2) nullable
- construction_time_days integer nullable
- start_date nullable
- completion_date nullable
- client_name nullable
- materials_json json nullable
- scope_of_work_json json nullable
- before_after_json json nullable
- project_facts_json json nullable
- is_featured boolean default false
- sort_order integer default 0
- publish_status string index
- published_at nullable
- timestamps

### blog_categories
- id
- name
- slug unique
- description nullable
- sort_order integer default 0
- is_active boolean default true
- timestamps

### blog_posts
- id
- category_id
- author_id nullable
- title
- slug unique
- excerpt text
- summary text nullable
- content longtext
- featured_image nullable
- cover_image nullable
- reading_time integer nullable
- source_type nullable
- content_type nullable
- intent_type nullable
- outline_json json nullable
- faq_json json nullable
- references_json json nullable
- cta_service_id nullable
- canonical_url nullable
- is_featured boolean default false
- sort_order integer default 0
- publish_status string index
- published_at nullable
- timestamps

### tags
- id
- name
- slug unique
- timestamps

### blog_post_tag
- blog_post_id
- tag_id

### blog_post_service
- blog_post_id
- service_id

### blog_post_project
- blog_post_id
- project_id

### project_service
Only needed if one Project can map to multiple Services.
- project_id
- service_id

### leads
- id
- service_id nullable
- project_id nullable
- source nullable
- full_name
- phone
- email nullable
- message text nullable
- budget_range nullable
- construction_location nullable
- lead_status string index
- assigned_to nullable
- contacted_at nullable
- timestamps

### testimonials
- id
- service_id nullable
- project_id nullable
- client_name
- client_role nullable
- content text
- rating tinyint nullable
- avatar nullable
- is_featured boolean default false
- publish_status string default 'published'
- timestamps

### faqs
- id
- entity_type
- entity_id
- question
- answer text
- sort_order integer default 0
- is_active boolean default true
- timestamps

### seo_meta
- id
- entity_type
- entity_id
- meta_title nullable
- meta_description nullable
- meta_keywords nullable
- canonical_url nullable
- robots nullable
- og_title nullable
- og_description nullable
- og_image nullable
- twitter_title nullable
- twitter_description nullable
- twitter_image nullable
- timestamps

### seo_schema
- id
- entity_type
- entity_id
- schema_type
- schema_json longtext
- is_auto_generated boolean default true
- timestamps

### internal_links
- id
- source_type
- source_id
- target_type
- target_id
- anchor_text
- link_position nullable
- is_auto_generated boolean default true
- timestamps

### settings or theme_settings
Use one of:
- `settings` key-value table
- `theme_settings` single-row / JSON document table
