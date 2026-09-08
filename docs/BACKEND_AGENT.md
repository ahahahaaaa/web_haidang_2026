# BACKEND_AGENT.md

Backend Architecture & Integration Agent Specification  
Project type: Construction company website / lead-generation backend / CMS-supporting API

---

## 1. Purpose

This document defines how the agent should design and maintain backend behavior for the construction-company website.

The backend must support:
- content delivery,
- service and project management,
- blog/article publishing,
- SEO metadata,
- lead capture,
- quotation/contact requests,
- admin-safe operations,
- clean integration with the frontsite.

Language rule:
- any Vietnamese text generated, seeded, transformed, or exposed by backend/admin flows must use full Vietnamese diacritics,
- do not leave non-accented Vietnamese in seeded data, validation messages, flash messages, labels, placeholders, or default content,
- the application default locale and fallback locale are `vi`; Faker uses `vi_VN` for Vietnam-specific seeded data.

---

## 2. Primary backend responsibilities

Explicitly support these domains:
- company/site settings,
- services,
- projects,
- blog posts,
- categories/taxonomies,
- testimonials,
- banners/hero blocks,
- contact submissions,
- quotation requests,
- SEO metadata,
- media references.

---

## 3. Architectural principles

Optimize for:
- explicit data contracts,
- thin controllers,
- validated inputs,
- service/action business logic,
- maintainable admin operations,
- predictable API responses,
- safe content publication workflows.

Do not bury business rules in routes or views.

---

## 4. Recommended layers

Typical split:
- Routes
- Controllers
- Requests / validators
- Services or Actions
- Models / Repositories
- Policies / authorization
- Transformers / Resources
- Jobs / queues

Preferred rule:
- controllers coordinate,
- requests validate,
- services execute logic,
- resources shape output.

---

## 5. Suggested domain model

### Site settings
- company_name,
- logo,
- hotline,
- email,
- address,
- social_links,
- about_summary,
- SEO defaults.

### Service
- title,
- slug,
- excerpt,
- body,
- cover_image,
- icon,
- category,
- featured,
- status,
- sort_order,
- SEO fields.

### Project
- title,
- slug,
- excerpt,
- body,
- location,
- area,
- service_type,
- timeline,
- completion_date,
- gallery,
- featured,
- status,
- SEO fields.

### Blog post
- title,
- slug,
- excerpt,
- body,
- cover_image,
- author,
- published_at,
- category,
- tags,
- status,
- SEO fields.

### Testimonial
- customer_name,
- role_or_company,
- quote,
- avatar,
- rating,
- featured,
- status.

### Contact lead
- name,
- phone,
- email,
- service_interest,
- project_location,
- message,
- source_page,
- utm data,
- status,
- assignee,
- notes.

### Quotation request
- contact fields,
- service type,
- estimated budget,
- timeline target,
- property type,
- area,
- message,
- attachments if supported,
- source_page,
- status.

---

## 6. API design rules

### Public API goals
- stable,
- cache-aware,
- minimal but sufficient,
- shaped for frontend consumption,
- resistant to malformed input.

### Admin/API goals
- authentication required,
- authorization enforced,
- validate all writes,
- record meaningful errors.

Prefer predictable JSON envelopes:
```json
{
  "data": {},
  "meta": {},
  "errors": []
}
```

---

## 7. Routing guidance

Suggested public routes:
- `GET /api/site-settings`
- `GET /api/services`
- `GET /api/services/{slug}`
- `GET /api/projects`
- `GET /api/projects/{slug}`
- `GET /api/posts`
- `GET /api/posts/{slug}`
- `POST /api/contact`
- `POST /api/quotation`

---

## 8. Validation and security

Every write endpoint must validate explicitly.
Consider:
- phone normalization,
- email validity,
- message bounds,
- enum validation,
- file validation,
- anti-spam controls,
- rate limiting,
- safe uploads,
- auditability for critical changes.

Database safety rule:
- seeders and seed-like maintenance commands must be additive or idempotent and must never hide a `migrate:fresh`, `db:wipe`, truncate, or implicit reset behind a normal bootstrap or seed flow.

Do not expose draft/private content by default.

---

## 9. Publishing workflow

Recommended statuses:
- draft
- review
- published
- archived

Rules:
- only `published` content appears publicly,
- preview behavior is controlled,
- publication timestamps are intentional.

---

## 10. SEO metadata support

Support:
- meta_title,
- meta_description,
- canonical_url,
- og_title,
- og_description,
- og_image,
- robots_directive,
- schema fragments if applicable.

Use sane fallbacks from title/excerpt/site defaults.

---

## 11. Lead and quotation flows

### Contact flow
- validate input,
- store lead,
- optionally notify,
- return clear success/error state,
- preserve campaign/source context.

### Quotation flow
Capture enough for follow-up:
- contact identity,
- requested service,
- scope,
- budget/timeline if known,
- free-form note.

Queue notifications when appropriate.

---

## 12. Caching and performance

For read-heavy public content:
- use intentional caching,
- invalidate on publish/update,
- avoid N+1,
- paginate or scope lists,
- precompute lightweight summaries if needed.

---

## 13. Observability and testing

Do not fail silently.
Preserve or implement:
- structured logging,
- actionable validation errors,
- safe exception handling,
- admin-visible lead status where possible.

Prefer tests for:
- form validation,
- content visibility rules,
- slug retrieval,
- authorization boundaries,
- API response shape,
- publishing behavior.

---

## 14. Output contract

When the backend agent generates changes, report:
- domain/module touched,
- routes/endpoints affected,
- validation/authorization added,
- response contract assumptions,
- migration/data risks,
- validation or test steps.
