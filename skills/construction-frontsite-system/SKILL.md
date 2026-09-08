---
name: construction-frontsite-system
description: Build, refactor, document, and operate a production-oriented construction-company website system with explicit frontend/backend boundaries, SEO-safe content architecture, lead-generation UX, and document-routed agent behavior.
---

# SKILL.md

## Purpose

Use this skill when working on a construction-company website or demo template that requires:
- homepage composition,
- services / project / blog page architecture,
- quotation/contact conversion flows,
- backend content and lead APIs,
- SEO generation and QA,
- Laravel domain-driven SEO system support,
- documentation-driven implementation.

This skill is document-routed. The agent must consult the relevant document before editing.

---

## Document routing rules

### Always read first
- `docs/AGENTS.md`

### Frontend/UI tasks
Consult:
- `docs/FRONTSITE_AGENT.md`
- `docs/DESIGN_SYSTEM.md`
- `references/code.html` when visual composition or section patterns matter

Examples:
- homepage sections
- service cards
- project listing/detail
- blog/article layout
- CTA placement
- color/spacing/typography consistency

### Backend/API/SEO-engine tasks
Consult:
- `docs/BACKEND_AGENT.md`
- `docs/TECHNICAL_REQUIREMENTS.md`
- `docs/SEO_PHASE_SUMMARY.md`
- `examples/*.json` when implementing AI draft/brief contracts

Examples:
- models
- validation
- routes
- admin content flows
- queue jobs
- OpenAI integration
- publish workflow
- SEO metadata/schema generation

### Setup/run/install tasks
Consult:
- `docs/PROJECT_START_GUIDE.md`

### Requirement ambiguity
Consult:
- `docs/DOC_REFERENCE_MAP.md`

---

## Execution workflow

### Step 1 — Inspect
Identify:
- framework and version,
- app structure,
- route loading,
- current frontend stack,
- whether `src/Domains/*` exists,
- whether `Src\\` autoload is configured,
- existing build/test commands.

### Step 2 — Classify
Classify the request into:
- docs
- frontsite UI
- backend/API
- SEO engine
- setup/ops
- refactor
- integration

### Step 3 — Resolve document set
Before changing anything, load the document subset:
- all tasks → `docs/AGENTS.md`
- UI tasks → `docs/FRONTSITE_AGENT.md`, `docs/DESIGN_SYSTEM.md`
- backend tasks → `docs/BACKEND_AGENT.md`, `docs/TECHNICAL_REQUIREMENTS.md`
- SEO-engine tasks → `docs/SEO_PHASE_SUMMARY.md`, `examples/*.json`
- startup/debug tasks → `docs/PROJECT_START_GUIDE.md`

### Step 4 — Plan minimally
State:
- files to create/update,
- constraints,
- validation steps,
- migration or queue risks if any.

### Step 5 — Implement incrementally
Rules:
- keep diffs reviewable,
- do not invent business rules,
- keep controllers thin,
- keep UI consistent with red/blue construction brand,
- preserve conversion-first layout and SEO-safe hierarchy,
- keep queue-first behavior for heavy generation.

### Step 6 — Validate
Depending on task:
- `composer dump-autoload -o`
- `php artisan optimize:clear`
- `php artisan migrate:status`
- `php artisan route:list`
- `php artisan test`
- `npm run build` or `npm run dev`
- verify one H1 per page
- verify metadata/schema/internal links for SEO tasks

### Step 7 — Report
Return:
- what changed,
- docs consulted,
- files affected,
- validation performed,
- remaining risks.

---

## Frontsite rules

- Follow strong section hierarchy.
- Keep CTA visible above the fold.
- Prefer construction-relevant visuals and proof.
- Avoid decorative noise.
- Maintain consistent card/button systems.
- Keep responsive behavior intentional.

---

## Backend rules

- Validate every write.
- Enforce publish status separation.
- Keep admin-safe operations explicit.
- Use services/actions/jobs for business logic.
- Queue notifications and generation.
- Do not hide failures.

---

## SEO rules

- one H1 per page
- explicit slug/meta/schema support
- internal linking strategy
- lead-generation CTA on commercial pages
- human review before publish
- avoid duplicate/cannibalizing pages

---

## Success criteria

A task using this skill is successful when:
- the right docs were consulted,
- output matches repository architecture,
- frontend/backend responsibilities are explicit,
- brand system remains consistent,
- setup and validation are concrete,
- the implementation is realistic for production evolution.
