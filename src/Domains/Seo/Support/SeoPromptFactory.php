<?php

namespace Src\Domains\Seo\Support;

use Src\Domains\Seo\Models\ContentCluster;
use Src\Domains\Seo\Models\SeoPage;

class SeoPromptFactory
{
    public function brief(ContentCluster $cluster): string
    {
        $secondary = implode(', ', $cluster->secondary_keywords ?? []);
        $lsi = implode(', ', $cluster->lsi_keywords ?? []);
        $targetPageType = $cluster->target_page_type?->value ?? $cluster->target_page_type;
        $location = trim((string) data_get($cluster->context, 'location'));
        $cta = trim((string) data_get($cluster->context, 'cta'));
        $notes = trim((string) data_get($cluster->context, 'notes'));

        return <<<PROMPT
You are an SEO strategist for a Vietnamese travel website.
Return valid JSON only.

Cluster:
- name: {$cluster->name}
- primary_keyword: {$cluster->primary_keyword}
- secondary_keywords: {$secondary}
- lsi_keywords: {$lsi}
- intent: {$cluster->intent}
- target_page_type: {$targetPageType}
- location_context: {$location}
- cta: {$cta}
- notes: {$notes}

Output keys:
- title
- slug_suggestion
- h1
- faq
- outline
- trust_signals
- cta

Rules:
- do not invent awards, addresses, airline policies, visa guarantees, or unavailable tour prices
- keep Vietnam travel context natural
- match the page type exactly
PROMPT;
    }

    public function draft(SeoPage $page): string
    {
        $secondary = implode(', ', $page->secondary_keywords ?? []);
        $pageType = $page->page_type->value;
        $location = trim((string) data_get($page->cluster?->context, 'location'));
        $cta = trim((string) data_get($page->cluster?->context, 'cta'));
        $typeGuidance = match ($pageType) {
            'category_service' => '- build a service-category hub with concise intro, service-selection guidance, when to use this category, internal links to real service detail pages, FAQs, and a consultation CTA',
            'service' => '- focus on service scope, traveler pain points, process, benefits, FAQs, and a clear consultation CTA',
            'blog' => '- keep the structure educational, practical, and scannable with useful subheadings and internal links to relevant tours or services',
            'contact' => '- keep it concise, trust-building, action-oriented, and centered on clear tour inquiry channels',
            'tour_category' => '- build a commercial listing-style page with intro, buying guidance, featured tour angles, FAQs, and internal links to destinations or tours',
            'destination' => '- combine destination guide context with clear travel-buying intent, itinerary expectations, best time, related tour links, and FAQ',
            'region' => '- position the page as a hub for multiple destinations and tour routes within the same region',
            'project' => '- legacy type: emphasize project context, scope, execution highlights, deliverables, and proof-oriented storytelling',
            'location_landing' => '- legacy type: make the page locally relevant and action-oriented',
            'homepage' => '- keep the page broad, trust-oriented, and conversion-focused across main travel scopes',
            default => '- keep the content factual, structured, and conversion-oriented',
        };

        return <<<PROMPT
You are a Vietnamese SEO writer for a travel website.
Write markdown only for a {$pageType} page.

Primary keyword: {$page->primary_keyword}
Secondary keywords: {$secondary}
H1: {$page->h1}
Location context: {$location}
Primary CTA: {$cta}

Rules:
- no code fences
- natural Vietnamese
- persuasive but factual
- no fake awards, fake guarantees, fake departure schedules, or fabricated promotions
- keep one clear search intent per page
- service and category_service pages should stay grounded in real travel services such as visa, vé máy bay, sim du lịch, du học, tour đoàn support, or related trip-planning help
{$typeGuidance}
PROMPT;
    }
}
