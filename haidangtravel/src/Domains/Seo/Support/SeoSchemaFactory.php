<?php

namespace Src\Domains\Seo\Support;

use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Models\SeoPage;

class SeoSchemaFactory
{
    public function forPage(SeoPage $page): array
    {
        $page->loadMissing('outgoingLinks.targetPage.cluster');

        $title = $page->h1 ?: $page->title;
        $description = $page->meta_description ?: $page->excerpt;
        $url = $page->canonical_url;
        $pageType = $page->page_type instanceof SeoPageType
            ? $page->page_type
            : SeoPageType::tryFrom((string) $page->page_type);
        $schemas = match ($pageType) {
            SeoPageType::Homepage => array_values(array_filter([
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => config('seo_ai.business_name'),
                    'url' => config('app.url'),
                ],
                $this->organizationSchema(withContext: true),
            ])),
            SeoPageType::Service => array_values(array_filter([
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => $title,
                    'description' => $description,
                    'provider' => $this->organizationSchema(),
                    'url' => $url,
                ],
                $this->breadcrumbSchema($page, SeoPageType::Service->sectionLabel()),
            ])),
            SeoPageType::ServiceCategory => array_values(array_filter([
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $url,
                    'mainEntityOfPage' => $url,
                ],
                $this->breadcrumbSchema($page, SeoPageType::ServiceCategory->sectionLabel()),
                $this->itemListSchema($page),
            ])),
            SeoPageType::Blog => array_values(array_filter([
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Article',
                    'author' => $this->articleAuthorSchema(),
                    'headline' => $page->title ?: $page->h1,
                    'description' => $description,
                    'url' => $url,
                ],
                $this->breadcrumbSchema($page, SeoPageType::Blog->sectionLabel()),
            ])),
            SeoPageType::Contact => array_values(array_filter([
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'ContactPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $url,
                    'mainEntity' => $this->organizationSchema(),
                ],
                $this->organizationSchema(withContext: true),
                $this->breadcrumbSchema($page, SeoPageType::Contact->sectionLabel()),
            ])),
            SeoPageType::TourCategory,
            SeoPageType::Destination,
            SeoPageType::Region => array_values(array_filter([
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $title,
                    'description' => $description,
                    'url' => $url,
                ],
                $this->breadcrumbSchema($page, $pageType?->sectionLabel() ?? 'SEO page'),
                $this->itemListSchema($page),
            ])),
            SeoPageType::Project => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CreativeWork',
                    'name' => $title,
                    'description' => $description,
                    'creator' => $this->organizationSchema(),
                    'url' => $url,
                ],
                $this->breadcrumbSchema($page, 'Dự án'),
            ],
            SeoPageType::LocationLanding => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Service',
                    'name' => $title,
                    'description' => $description,
                    'areaServed' => $this->resolveLocation($page) ?: $page->primary_keyword,
                    'provider' => $this->organizationSchema(),
                    'url' => $url,
                ],
                $this->breadcrumbSchema($page, 'Khu vực'),
            ],
            default => [],
        };

        if ($page->faq_items) {
            $faqSchema = $this->faqSchema($page->faq_items);

            if ($faqSchema !== null) {
                $schemas[] = $faqSchema;
            }
        }

        return array_values(array_filter($schemas));
    }

    protected function breadcrumbSchema(SeoPage $page, string $sectionName): array
    {
        $baseUrl = rtrim((string) config('app.url'), '/');
        $currentUrl = $page->canonical_url ?: $baseUrl.'/seo-pages/'.$page->slug;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Trang chủ',
                    'item' => $baseUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $sectionName,
                    'item' => $currentUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $page->h1 ?: $page->title,
                    'item' => $currentUrl,
                ],
            ],
        ];
    }

    protected function itemListSchema(SeoPage $page): ?array
    {
        $items = collect($page->outgoingLinks ?? [])
            ->sortByDesc('priority')
            ->map(function ($link, int $index) {
                $target = $link->targetPage;

                if (! $target) {
                    return null;
                }

                return [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => $target->canonical_url ?: url('/seo-pages/'.$target->slug),
                    'name' => $link->anchor_text ?: ($target->h1 ?: $target->title ?: $target->primary_keyword),
                ];
            })
            ->filter()
            ->take(8)
            ->values()
            ->all();

        if ($items === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'itemListElement' => $items,
        ];
    }

    protected function faqSchema(array $faqItems): ?array
    {
        $entities = collect($faqItems)
            ->map(function ($item) {
                $question = trim((string) (is_array($item) ? ($item['question'] ?? $item['title'] ?? '') : $item));
                $answer = trim((string) (is_array($item) ? ($item['answer'] ?? '') : ''));

                if ($question === '') {
                    return null;
                }

                return [
                    '@type' => 'Question',
                    'name' => $question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $answer !== '' ? $answer : 'Liên hệ để được tư vấn chi tiết theo nhu cầu lịch trình thực tế.',
                    ],
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($entities === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    protected function organizationSchema(bool $withContext = false): ?array
    {
        $organization = [
            '@type' => 'Organization',
            'name' => config('seo_ai.business_name'),
            'url' => rtrim((string) config('app.url'), '/'),
        ];

        if ($phone = trim((string) config('seo_ai.contact_phone'))) {
            $organization['telephone'] = $phone;
            $organization['contactPoint'] = [[
                '@type' => 'ContactPoint',
                'telephone' => $phone,
                'contactType' => 'customer service',
            ]];
        }

        if ($email = trim((string) config('seo_ai.contact_email'))) {
            $organization['email'] = $email;
        }

        $address = array_filter([
            '@type' => 'PostalAddress',
            'addressLocality' => trim((string) config('seo_ai.address_locality')),
            'addressRegion' => trim((string) config('seo_ai.address_region')),
            'addressCountry' => trim((string) config('seo_ai.address_country')),
        ], static fn ($value) => $value !== '');

        if (count($address) > 1) {
            $organization['address'] = $address;
        }

        if ($withContext) {
            $organization['@context'] = 'https://schema.org';
        }

        return $organization['name'] ? $organization : null;
    }

    protected function articleAuthorSchema(): ?array
    {
        return $this->organizationSchema();
    }

    protected function resolveLocation(SeoPage $page): ?string
    {
        $location = trim((string) data_get($page->cluster?->context, 'location'));

        return $location !== '' ? $location : null;
    }
}
