---
name: seo-ai-maker
description: Build and operate AI-friendly SEO systems for websites that want stronger citation visibility in ChatGPT, Claude, Gemini, and search-driven AI experiences. Use when planning or implementing sitemap, robots, schema/entity mapping, answer-summary blocks, topic clusters, internal linking, citation-ready page structures, and phased SEO rollouts.
---

# SEO AI Maker

## Overview

Use this skill when the goal is not only classic SEO ranking, but also making pages easier for AI-assisted search and answer engines to crawl, understand, and cite.

This skill is for implementation and rollout work. It should result in concrete crawl, entity, content-structure, and linking improvements, not just strategy notes.

## Always Read First

- `docs/AGENTS.md`
- `docs/FRONTSITE_AGENT.md`
- `docs/BACKEND_AGENT.md`
- `docs/TECHNICAL_REQUIREMENTS.md`

Read these references when needed:

- [references/phase-rollout.md](references/phase-rollout.md)
- [references/page-type-matrix.md](references/page-type-matrix.md)
- [references/citation-blocks.md](references/citation-blocks.md)

If the repo already has SEO admin flows, also read:

- `.agents/skills/seo-ai-pages-flow/SKILL.md`

## Use This Skill When

- The user asks to make the website more AI-friendly or citation-ready
- The task involves ChatGPT, Claude, Gemini, Google AI, or answer-engine visibility
- The task involves sitemap, robots, schema completeness, or page-type entity mapping
- The task involves building answer-summary blocks, FAQ, comparison tables, or intent-focused page sections
- The task involves topic clusters, internal linking, or phased SEO rollout plans

## Core Principles

- Optimize for crawlability first, then entity clarity, then answer quality
- Every important keyword should have one clear destination URL
- Important pages should answer the main question early and plainly
- Schema supports understanding, but content structure drives citation value
- Internal links are part of the system, not optional polish
- Hub/cluster work must declare parent hubs, child clusters, crawlable links in both directions, sitemap inclusion rules, and duplicate-intent canonical rules before code is changed.
- Keep changes incremental and measurable
- Treat sitewide measurement as mandatory for launch: verify `GA4 Measurement ID` and `Facebook Pixel ID` coverage on the public frontsite or document the gap explicitly
- In the Haidang Travel repo, stay inside the travel runtime: tour scope, tour category, country root, destination, region, tour detail, service, blog, and landing page. Do not revive construction, package, estimate, or project page assumptions.

## Implementation Order

### 1. Audit crawl and index foundations

- Verify public routes, canonical behavior, and robots access
- Add or repair `sitemap.xml`
- Ensure `robots.txt` advertises the sitemap
- Avoid shipping citation-focused content while crawl basics are missing

### 2. Map page types to clear entities

- Use [references/page-type-matrix.md](references/page-type-matrix.md)
- Confirm each public page has the right schema shape for its job
- Keep schema aligned with the visible content on the page

### 3. Add citation-ready content blocks

- Add short answer-summary blocks near the top of money pages and evergreen pages
- Include 3 to 5 concrete bullets, internal links, and an updated signal when available
- Use [references/citation-blocks.md](references/citation-blocks.md) to keep blocks concise

### 4. Strengthen topical architecture

- Build hub and cluster relationships between travel authority hubs, tour category hubs, country roots, destination hubs, region hubs, tour detail pages, service support pages, blog guides, and landing pages
- Prefer clusters around one intent family instead of isolated articles
- Keep anchor text natural and specific

For Haidang Travel geography:

- Treat country as a root `Destination` (`is_country_root = true`, `country_id = null`)
- Require every regular destination to have a country root through `country_id`
- Treat country pages as hubs, destination pages as geographic hubs or clusters, tour details as money clusters, and blog posts as support clusters
- When a blog has a destination, it should also carry the matching country context and link to both where useful

### 5. Roll out in phases

- Use [references/phase-rollout.md](references/phase-rollout.md)
- Implement the smallest high-leverage phase first
- Validate after each phase before widening scope

## Output Rules

- Always return a concrete implementation plan, not only theory
- Prefer reusable partials, services, and helpers over duplicated markup
- Keep controllers thin
- Do not introduce schema or SEO changes that contradict visible page content
- When adding summaries or citation blocks, make them useful to humans first

## Validation

Run the smallest relevant validation set first:

- `php -l <changed-php-file>`
- `php artisan route:list`
- `php artisan test --filter=FrontsitePagesTest`
- verify public HTML still emits required sitewide tracking snippets when SEO rollout depends on measurement
- verify country hubs expose crawlable destination cluster links and destination/tour/blog pages link back up when country context exists

If routes or rendered output changed, verify both the endpoint and the HTML/XML response body.

## Success Criteria

The task is complete when:

- crawl foundations are in place
- key public page types have correct entities
- important pages expose a concise answer-summary block
- internal navigation supports topic discovery
- the rollout is documented by phase, not left as ad hoc edits
