<?php

namespace Src\Domains\Seo\Support;

use Src\Domains\Seo\Enums\SeoPageType;
use Src\Domains\Seo\Models\SeoPage;

class SeoQaValidator
{
    public function validate(SeoPage $page, int $outgoingLinksCount = 0): array
    {
        $contentText = $this->plainText($page->content);
        $wordCount = $this->wordCount($contentText);
        $pageType = $page->page_type instanceof SeoPageType
            ? $page->page_type
            : SeoPageType::tryFrom((string) $page->page_type);
        $minWordCount = match ($pageType) {
            SeoPageType::Blog => (int) config('seo_ai.quality_gates.blog_word_count_min', 1200),
            SeoPageType::ServiceCategory => (int) config('seo_ai.quality_gates.service_category_word_count_min', 650),
            SeoPageType::Service => (int) config('seo_ai.quality_gates.service_word_count_min', 900),
            SeoPageType::Contact => (int) config('seo_ai.quality_gates.contact_word_count_min', 300),
            SeoPageType::TourCategory => (int) config('seo_ai.quality_gates.tour_category_word_count_min', 650),
            SeoPageType::Destination => (int) config('seo_ai.quality_gates.destination_word_count_min', 850),
            SeoPageType::Region => (int) config('seo_ai.quality_gates.region_word_count_min', 750),
            SeoPageType::Project => (int) config('seo_ai.quality_gates.project_word_count_min', 800),
            SeoPageType::LocationLanding => (int) config('seo_ai.quality_gates.location_landing_word_count_min', 700),
            default => 500,
        };
        $metaTitleLength = mb_strlen(trim((string) $page->meta_title));
        $metaDescriptionLength = mb_strlen(trim((string) $page->meta_description));
        $schemaTypes = $this->schemaTypes($page->schema ?? []);
        $requiredSchemaTypes = $this->requiredSchemaTypes($pageType);
        $location = $this->resolveLocation($page);

        $errors = [];
        if (!trim((string) $page->title)) {
            $errors[] = 'Missing title';
        }
        if (!trim((string) $page->slug)) {
            $errors[] = 'Missing slug';
        }
        if (!trim((string) $page->h1)) {
            $errors[] = 'Missing H1';
        }
        if (!trim((string) $page->meta_title)) {
            $errors[] = 'Missing meta title';
        }
        if (!trim((string) $page->meta_description)) {
            $errors[] = 'Missing meta description';
        }
        if (empty($page->schema)) {
            $errors[] = 'Missing schema';
        }
        if ($metaTitleLength > 0 && $metaTitleLength < (int) config('seo_ai.quality_gates.meta_title_min', 45)) {
            $errors[] = 'Meta title too short';
        }
        if ($metaTitleLength > (int) config('seo_ai.quality_gates.meta_title_max', 60)) {
            $errors[] = 'Meta title too long';
        }
        if ($metaDescriptionLength > 0 && $metaDescriptionLength < (int) config('seo_ai.quality_gates.meta_description_min', 120)) {
            $errors[] = 'Meta description too short';
        }
        if ($metaDescriptionLength > (int) config('seo_ai.quality_gates.meta_description_max', 160)) {
            $errors[] = 'Meta description too long';
        }
        if ($wordCount < $minWordCount) {
            $errors[] = 'Word count below threshold';
        }
        if ($outgoingLinksCount < (int) config('seo_ai.quality_gates.min_internal_links', 1)) {
            $errors[] = 'Not enough internal links';
        }

        foreach ($requiredSchemaTypes as $requiredSchemaType) {
            if (!in_array($requiredSchemaType, $schemaTypes, true)) {
                $errors[] = "Missing schema type: {$requiredSchemaType}";
            }
        }

        foreach ($this->pageTypeErrors($pageType, $page, $contentText, $location) as $error) {
            $errors[] = $error;
        }

        return [
            'status' => empty($errors) ? 'pass' : 'fail',
            'word_count' => $wordCount,
            'min_word_count' => $minWordCount,
            'meta_title_length' => $metaTitleLength,
            'meta_description_length' => $metaDescriptionLength,
            'outgoing_links_count' => $outgoingLinksCount,
            'schema_types' => $schemaTypes,
            'required_schema_types' => $requiredSchemaTypes,
            'resolved_location' => $location,
            'errors' => $errors,
        ];
    }

    protected function pageTypeErrors(?SeoPageType $pageType, SeoPage $page, string $contentText, ?string $location): array
    {
        return match ($pageType) {
            SeoPageType::Contact => $this->contactErrors($contentText),
            SeoPageType::ServiceCategory => $this->serviceCategoryErrors($page, $contentText),
            SeoPageType::Service => $this->serviceErrors($page, $contentText),
            SeoPageType::TourCategory => $this->tourCategoryErrors($page, $contentText),
            SeoPageType::Destination,
            SeoPageType::Region => $this->geoHubErrors($page, $contentText, $location),
            SeoPageType::Project => $this->projectErrors($page, $contentText),
            SeoPageType::LocationLanding => $this->locationLandingErrors($page, $contentText, $location),
            default => [],
        };
    }

    protected function projectErrors(SeoPage $page, string $contentText): array
    {
        $errors = [];

        if (!trim((string) $page->excerpt)) {
            $errors[] = 'Missing project excerpt';
        }

        if (! $this->containsAny($contentText, ['quy mô', 'phạm vi', 'triển khai', 'thi công', 'hạng mục'])) {
            $errors[] = 'Project page lacks scope/execution signals';
        }

        return $errors;
    }

    protected function serviceCategoryErrors(SeoPage $page, string $contentText): array
    {
        $errors = [];

        if (!trim((string) $page->excerpt)) {
            $errors[] = 'Missing service category intro';
        }

        if (! $this->containsAny($contentText, ['dịch vụ', 'visa', 'vé máy bay', 'sim', 'du học', 'tư vấn', 'hồ sơ', 'lịch trình'])) {
            $errors[] = 'Service category page lacks travel service signals';
        }

        return $errors;
    }

    protected function serviceErrors(SeoPage $page, string $contentText): array
    {
        $errors = [];

        if (!trim((string) $page->excerpt)) {
            $errors[] = 'Missing service intro';
        }

        if (! $this->containsAny($contentText, ['dịch vụ', 'tư vấn', 'hỗ trợ', 'visa', 'vé máy bay', 'sim', 'du học', 'hồ sơ', 'khách'])) {
            $errors[] = 'Service page lacks travel service scope signals';
        }

        return $errors;
    }

    protected function tourCategoryErrors(SeoPage $page, string $contentText): array
    {
        $errors = [];

        if (!trim((string) $page->excerpt)) {
            $errors[] = 'Missing category intro';
        }

        if (! $this->containsAny($contentText, ['tour', 'lịch khởi hành', 'giá', 'điểm đến'])) {
            $errors[] = 'Tour category page lacks commercial travel signals';
        }

        return $errors;
    }

    protected function geoHubErrors(SeoPage $page, string $contentText, ?string $location): array
    {
        $errors = [];
        $focus = $location ?: trim((string) ($page->h1 ?: $page->title ?: $page->primary_keyword));

        if ($focus === '') {
            $errors[] = 'Missing geography context';

            return $errors;
        }

        $haystacks = [
            mb_strtolower(trim((string) $page->h1)),
            mb_strtolower(trim((string) $page->meta_title)),
            mb_strtolower($contentText),
        ];
        $needle = mb_strtolower($focus);

        $mentioned = collect($haystacks)
            ->contains(static fn ($value) => $value !== '' && str_contains($value, $needle));

        if (! $mentioned) {
            $errors[] = 'Geo hub page does not mention the target place clearly';
        }

        if (! $this->containsAny($contentText, ['tour', 'điểm đến', 'lịch khởi hành', 'hành trình'])) {
            $errors[] = 'Geo hub page lacks travel intent signals';
        }

        return $errors;
    }

    protected function contactErrors(string $contentText): array
    {
        if ($this->containsAny($contentText, ['liên hệ', 'tư vấn', 'hotline', 'email', 'zalo', 'tour', 'visa', 'đặt tour'])) {
            return [];
        }

        return ['Contact page lacks clear travel CTA'];
    }

    protected function locationLandingErrors(SeoPage $page, string $contentText, ?string $location): array
    {
        $errors = [];

        if ($location === null) {
            $errors[] = 'Missing location context';

            return $errors;
        }

        $haystacks = [
            mb_strtolower(trim((string) $page->h1)),
            mb_strtolower(trim((string) $page->meta_title)),
            mb_strtolower($contentText),
        ];
        $needle = mb_strtolower($location);

        $mentioned = collect($haystacks)
            ->contains(static fn ($value) => $value !== '' && str_contains($value, $needle));

        if (! $mentioned) {
            $errors[] = 'Location landing does not mention target location clearly';
        }

        return $errors;
    }

    protected function requiredSchemaTypes(?SeoPageType $pageType): array
    {
        return match ($pageType) {
            SeoPageType::Service => ['Service', 'BreadcrumbList'],
            SeoPageType::ServiceCategory => ['CollectionPage', 'BreadcrumbList', 'ItemList'],
            SeoPageType::TourCategory,
            SeoPageType::Destination,
            SeoPageType::Region => ['CollectionPage', 'BreadcrumbList', 'ItemList'],
            SeoPageType::Contact => ['ContactPage', 'Organization', 'BreadcrumbList'],
            SeoPageType::Project => ['CreativeWork', 'BreadcrumbList'],
            SeoPageType::LocationLanding => ['Service', 'BreadcrumbList'],
            default => [],
        };
    }

    protected function schemaTypes(array $schema): array
    {
        return collect($schema)
            ->map(function ($item) {
                $type = data_get($item, '@type');

                if (is_array($type)) {
                    return array_values(array_filter($type));
                }

                return $type ? [$type] : [];
            })
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    protected function plainText(?string $content): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $content)) ?? '');
    }

    protected function wordCount(string $content): int
    {
        if ($content === '') {
            return 0;
        }

        preg_match_all('/[\p{L}\p{N}]+/u', $content, $matches);

        return count($matches[0]);
    }

    protected function containsAny(string $content, array $needles): bool
    {
        $haystack = mb_strtolower($content);

        foreach ($needles as $needle) {
            if (str_contains($haystack, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    protected function resolveLocation(SeoPage $page): ?string
    {
        $location = trim((string) data_get($page->cluster?->context, 'location'));

        return $location !== '' ? $location : null;
    }
}
