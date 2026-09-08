# SEO_PHASE_SUMMARY.md

## Phase 3
Introduced the practical Laravel repository binding for the SEO engine:
- `content_clusters`
- `seo_pages`
- `seo_links`
- sample models
- queue jobs
- admin API/controller stubs
- slug/meta/schema rules

## Phase 4
Added repo-operational Laravel/Codex elements:
- service provider
- artisan commands
- queue chain orchestration
- Livewire admin starter
- OpenAI Responses API wrapper
- QA-gated publish workflow

## Phase 5
Shifted into domain-driven repo structure:
- `src/Domains/Seo/*`
- domain models / enums / DTOs / repositories / actions / jobs / policies
- `routes/api_v1/*`
- `routes/ai.php`
- Livewire admin
- queue-first generation
- repo binding docs

## Phase 6
Hardened public SEO rendering on the Laravel frontsite:
- FAQ fields for `services`, `projects`, and `landing_pages`
- related-question fields for service/project detail pages
- estimate-page default FAQ items with step-by-step usage guidance
- blog detail H1 enforcement and auto-generated table of contents from visible `h2` headings
- visible-content-first schema coupling for `FAQPage`, `HowTo`, `OfferCatalog`, `Service`, `CreativeWork`, and `WebApplication`
- feature-test coverage for public schema output and blog-detail semantics

## Current recommended direction
Use:
- domain-driven backend structure,
- queue-first AI generation,
- human approval before publish,
- explicit metadata/schema rules,
- visible FAQ content before emitting `FAQPage`,
- one H1 per public page,
- blog detail TOC generated from content `h2` elements,
- service/project related questions as internal-link surfaces, not schema nodes,
- JSON examples for brief/draft contracts,
- doc-routed agent behavior.
