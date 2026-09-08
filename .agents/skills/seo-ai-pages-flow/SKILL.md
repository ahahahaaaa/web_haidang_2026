---
name: seo-ai-pages-flow
description: Use when working on this repo's SEO AI admin flow for creating, generating, editing, QA-ing, approving, publishing, or extending `seo_pages` and `content_clusters`. Trigger on requests about `/admin/seo/pages`, SEO AI, SEO Pages, SEO cluster creation, prompt/meta/schema/QA rules, page-type support (`service`, `blog`, `project`, `contact`, `location_landing`), or queue-based SEO generation.
---

# SEO AI Pages Flow

## Overview

Use this skill for the repository's SEO AI system centered on `content_clusters`, `seo_pages`, queue-based draft generation, internal-link suggestions, QA gating, and manual publish review.

This skill is for implementation and maintenance of the SEO admin flow, not general frontsite SEO copywriting.

## Always Read First

- `docs/AGENTS.md`
- `docs/BACKEND_AGENT.md`
- `docs/TECHNICAL_REQUIREMENTS.md`
- `docs/SEO_PHASE_SUMMARY.md`
- `examples/seo_brief_example.json`
- `examples/seo_draft_example.json`

Read [references/file-map.md](references/file-map.md) when you need exact route/component/service entry points.

Read [references/page-type-rules.md](references/page-type-rules.md) when the task involves `project`, `contact`, `location_landing`, schema, meta, or QA tuning.

## Use This Skill When

- The user asks to add or optimize anything under `/admin/seo/pages`
- The task involves creating a SEO page manually or via cluster
- The task involves `content_clusters`, `seo_pages`, `seo_links`
- The task involves queue-based SEO generation or QA flow
- The task involves page-type support for `service`, `blog`, `project`, `contact`, or `location_landing`
- The task involves prompt, meta, schema, or validation rules for SEO AI pages
- The task involves admin UX copy or forms for SEO AI in this repo

## Core Flow

### 1. Inspect the current entry path

Confirm which path the user needs:

- Admin UI list/editor: `routes/admin_seo.php`
- Admin API generate/publish endpoints: `routes/api_v1/seo.php`
- AI preview endpoint: `routes/ai.php`
- Route loading: `bootstrap/app.php`

Do not assume a route file is active just because it exists. Verify it is loaded.

### 2. Choose the creation mode

There are two supported creation modes:

- Manual page creation
  Use when the user wants a page record immediately and will edit content directly.
- Cluster-first creation
  Use when the user wants the full AI workflow: cluster -> brief -> draft -> internal links -> QA.

For manual page work:

- Keep creation logic in an action, not the controller or Blade
- Generate slug/canonical deterministically
- Redirect to the page editor after create when appropriate

For cluster-first work:

- Validate cluster payload with a request or equivalent validation
- Mark cluster status intentionally
- Dispatch generation via queue, not synchronously in the request

### 3. Respect the queue-first generation chain

The expected sequence is:

1. create cluster or page
2. generate brief if cluster-driven
3. generate draft
4. build internal links
5. run QA
6. manual approval
7. publish

Heavy AI work must remain queued. Do not move generation into synchronous admin requests.

### 4. Extend page-type support completely

When adding or repairing a page type, update all relevant layers together:

- enum / allowed values
- create forms and validation
- prompt guidance
- fallback meta generation
- schema generation
- QA requirements
- admin labels/help text
- config thresholds if word-count or meta rules differ

If you only update schema or only update QA, the system will drift and pages will fail unexpectedly.

### 5. Admin UI rules

- Use fully accented Vietnamese for every admin-facing label, hint, placeholder, and flash message
- Keep the admin flow explicit: create, generate, QA, approve, publish
- Use the shared Quill editor rules from `docs/AGENTS.md`
- Keep list pages operational and scannable
- Keep editor pages focused on title, slug, H1, content, meta, QA report, and workflow actions

### 6. SEO rules for this system

- One H1 per page
- Slug must stay short, lowercase, and hyphenated
- Meta and schema must match page type
- Commercial pages need CTA clarity
- Location pages must carry clear location context
- Project pages need proof/scope signals
- Contact pages need obvious contact intent
- Internal links are part of QA, not optional polish

## Files to Touch by Task

### Add or change creation flow

- `app/Livewire/Admin/Seo/SeoPagesIndex.php`
- `resources/views/livewire/admin/seo/pages-index.blade.php`
- `src/Domains/Seo/Actions/CreateSeoPageAction.php`
- `app/Http/Controllers/Admin/Seo/SeoClusterController.php`
- `app/Http/Requests/Admin/Seo/StoreContentClusterRequest.php`

### Add or change routes

- `bootstrap/app.php`
- `routes/admin_seo.php`
- `routes/api_v1/seo.php`
- `routes/ai.php`

### Add or change generation behavior

- `src/Domains/Seo/Jobs/GenerateSeoBriefJob.php`
- `src/Domains/Seo/Jobs/GenerateSeoDraftJob.php`
- `src/Domains/Seo/Jobs/BuildSeoLinksJob.php`
- `src/Domains/Seo/Jobs/ValidateSeoPageJob.php`
- `src/Domains/Seo/Support/SeoPromptFactory.php`

### Add or change page-type optimization

- `src/Domains/Seo/Support/SeoMetaFactory.php`
- `src/Domains/Seo/Support/SeoSchemaFactory.php`
- `src/Domains/Seo/Support/SeoQaValidator.php`
- `config/seo_ai.php`
- `src/Domains/Seo/Enums/SeoPageType.php`

## Validation

Run the smallest relevant set first, then broader checks when the task touches infrastructure:

- `php -l <changed-php-file>`
- `composer dump-autoload -o`
- `php artisan route:list | Select-String "admin.seo|api/v1/seo|ai/seo"`
- `php artisan view:cache`

When route-loading changed, always verify the routes actually appear.

When page-type logic changed, sanity-check:

- meta title/description are generated
- schema contains the expected `@type`
- QA errors reflect the new rules

## Success Criteria

The task is complete when:

- the chosen create flow works end-to-end
- page-type support is updated across prompt/meta/schema/QA together
- admin copy is accented and operationally clear
- route loading is verified, not assumed
- queue-first behavior is preserved
- validation commands were run and reported
