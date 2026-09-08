# Entity Schema Guide

## Scope
This guide defines the production data structure for a construction company website with these core public content entities:

- Company (virtual entity from Theme Settings)
- Service
- Project
- BlogPost
- Lead
- FAQ
- Testimonial
- SeoMeta
- SeoSchema
- InternalLink

The main architectural rule is:

> Company data is **not** stored as a standard CRUD table. It is sourced from the global Theme Settings / site configuration.

---

## 1. Root architecture

```text
ThemeSettings (root config source)
├── Company profile
├── Contact info
├── Social links
├── Brand media
├── Trust metrics
├── Default SEO
└── Mail settings

Public entities
├── Service
├── Project
├── BlogPost
├── Lead
├── FAQ
├── Testimonial
├── SeoMeta
├── SeoSchema
└── InternalLink
```

---

## 2. Company as config-based entity

### Company data must come from Theme Settings
Based on the current settings UI, Company information should be loaded from one central config source.

### Recommended config sections

#### System information
- site_name
- tagline
- active_theme
- short_description
- company_summary

#### Main media
- logo
- favicon
- default_share_image (recommended)

#### Contact and social
- company_name
- address
- phone
- hotline
- email_main
- email_support
- email_sales
- mail_from_name
- mail_from_address
- contact_receiver_email
- facebook_url
- youtube_url
- tiktok_url
- zalo_url
- linkedin_url

#### Trust metrics and site SEO defaults
- years_experience
- completed_projects_count
- team_size
- quality_badge
- copyright_text
- default_seo_title
- default_seo_keywords
- default_robots
- default_seo_description

### Consequence
There is no mandatory `companies` table unless the system later becomes multi-tenant.

---

## 3. Core public entities

## 3.1 Service
Commercial / conversion entity.

### Purpose
Represents business offerings such as:
- xây nhà trọn gói
- sửa chữa cải tạo
- thi công nội thất
- thiết kế kiến trúc
- thi công showroom / văn phòng / nhà xưởng

### Required fields
- id
- category_id nullable
- name
- slug
- short_description
- content
- featured_image
- publish_status
- published_at nullable
- created_at
- updated_at

### Recommended optional fields
- service_code
- icon
- description
- gallery_json
- pricing_type
- starting_price
- price_note
- duration_note
- benefits_json
- process_steps_json
- faq_json
- cta_title
- cta_description
- is_featured
- sort_order

---

## 3.2 Project
Proof / portfolio entity.

### Purpose
Represents real work delivered by the company.

### Required fields
- id
- primary_service_id
- name
- slug
- short_description
- featured_image
- location_text
- publish_status
- published_at nullable
- created_at
- updated_at

### Recommended optional fields
- project_code
- description
- content
- gallery_json
- project_type
- style
- district
- city
- province
- area_land_m2
- area_floor_m2
- floors
- bedrooms
- bathrooms
- budget_min
- budget_max
- construction_time_days
- start_date
- completion_date
- client_name
- materials_json
- scope_of_work_json
- before_after_json
- project_facts_json
- is_featured
- sort_order

---

## 3.3 BlogPost
SEO acquisition / education entity.

### Purpose
Pulls organic traffic and routes users toward Service / Project / quotation.

### Required fields
- id
- category_id
- author_id nullable
- title
- slug
- excerpt
- content
- featured_image
- publish_status
- published_at nullable
- created_at
- updated_at

### Recommended optional fields
- summary
- cover_image
- reading_time
- source_type
- content_type
- intent_type
- outline_json
- faq_json
- references_json
- cta_service_id nullable
- canonical_url
- is_featured
- sort_order

---

## 3.4 Lead
Conversion entity.

### Purpose
Captures inquiry and quotation requests.

### Fields
- id
- service_id nullable
- project_id nullable
- source
- full_name
- phone
- email nullable
- message nullable
- budget_range nullable
- construction_location nullable
- lead_status
- assigned_to nullable
- contacted_at nullable
- created_at
- updated_at

---

## 3.5 FAQ
Trust and structured content entity.

### Design
Use polymorphic relation.

### Fields
- id
- entity_type
- entity_id
- question
- answer
- sort_order
- is_active
- created_at
- updated_at

### Supported entity_type
- site
- service
- project
- blog_post

---

## 3.6 Testimonial
Trust proof entity.

### Fields
- id
- service_id nullable
- project_id nullable
- client_name
- client_role nullable
- content
- rating nullable
- avatar nullable
- is_featured
- publish_status
- created_at
- updated_at

---

## 3.7 SEO entities

### seo_meta
Per-entity metadata.

Fields:
- id
- entity_type
- entity_id
- meta_title
- meta_description
- meta_keywords nullable
- canonical_url nullable
- robots nullable
- og_title nullable
- og_description nullable
- og_image nullable
- twitter_title nullable
- twitter_description nullable
- twitter_image nullable
- created_at
- updated_at

### seo_schema
Stores structured data payloads.

Fields:
- id
- entity_type
- entity_id
- schema_type
- schema_json
- is_auto_generated
- created_at
- updated_at

### internal_links
Stores internal linking graph.

Fields:
- id
- source_type
- source_id
- target_type
- target_id
- anchor_text
- link_position nullable
- is_auto_generated
- created_at
- updated_at

---

## 4. Relationships

```text
ThemeSettings
  └── provides Company data for whole site

Service
  1 ── n Project
  n ── n BlogPost
  1 ── n Testimonial (optional)
  1 ── n FAQ (polymorphic)

Project
  n ── 1 Service
  n ── n BlogPost
  1 ── n Testimonial (optional)
  1 ── n FAQ (polymorphic)

BlogPost
  n ── 1 BlogCategory
  n ── n Service
  n ── n Project
  n ── n Tag
  1 ── n FAQ (polymorphic)
```

### Important note
Do **not** add `company_id` to every content table when the application is single-site and Company is already derived from Theme Settings.

---

## 5. URL structure

```text
/                       -> homepage
/gioi-thieu             -> company/about page
/dich-vu                -> service listing
/dich-vu/{slug}         -> service detail
/du-an                  -> project listing
/du-an/{slug}           -> project detail
/blog                   -> blog listing
/{slug-category}/{slug-blog} -> blog detail
/lien-he                -> contact page
/bao-gia                -> quotation page
```

---

## 6. Publish workflow

Use shared statuses for Service / Project / BlogPost:

- draft
- review
- published
- archived

```text
draft -> review -> published -> archived
```

Only `published` content is visible on the public site.

---

## 7. Internal linking rules

### Blog links to
- related services
- related projects
- quotation/contact page

### Service links to
- relevant projects
- supporting blog posts
- quotation/contact page

### Project links to
- primary service
- related blog posts
- quotation/contact page

---

## 8. Implementation guidance

### Use config/root settings for:
- organization schema
- header/footer contact block
- social links
- default SEO fallback
- contact email routing
- brand identity/media
- trust metrics

### Use DB entities for:
- services
- projects
- blog posts
- leads
- FAQ / testimonials
- SEO overrides

---

## 9. Future upgrade path

If the system later becomes multi-tenant or SaaS:
- introduce `companies` table
- add `company_id` to content entities
- move theme settings to per-company scope

Until then, a config-based Company source is the cleaner design.
