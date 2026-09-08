<?php

namespace App\Services\Travel;

use App\Support\ContentGallery;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;

class HaidangTravelSnapshotCrawler
{
    protected const BASE_URL = 'https://haidangtravel.com';

    /**
     * @return array<string, mixed>
     */
    public function crawl(int $limit = 50): array
    {
        $homepage = $this->fetchHtml(self::BASE_URL);

        if ($homepage === null) {
            return [];
        }

        $tourContexts = [];

        foreach ($this->extractTourLinks($homepage) as $url => $context) {
            $tourContexts[$url] = $context;
        }

        foreach ($this->extractSeedPages($homepage) as $seed) {
            if (count($tourContexts) >= $limit * 2) {
                break;
            }

            $seedHtml = $this->fetchHtml($seed['url']);

            if ($seedHtml === null) {
                continue;
            }

            foreach ($this->extractTourLinks($seedHtml, $seed) as $url => $context) {
                $tourContexts[$url] = array_filter(array_merge($tourContexts[$url] ?? [], $context));
            }
        }

        $tourCategories = collect();
        $regions = collect();
        $destinations = collect();
        $tours = [];

        foreach ($tourContexts as $url => $context) {
            if (count($tours) >= $limit) {
                break;
            }

            $tour = $this->parseTourPage($url, $context);

            if ($tour === null) {
                continue;
            }

            $tourCategories->push($tour['tour_category']);
            $regions->push($tour['region']);
            $destinations->push($tour['destination']);
            $tours[] = $tour['tour'];
        }

        return [
            'destinations' => $destinations->filter()->unique('slug')->values()->all(),
            'regions' => $regions->filter()->unique('slug')->values()->all(),
            'tour_categories' => $tourCategories->filter()->unique('slug')->values()->all(),
            'tours' => $tours,
        ];
    }

    protected function cleanLabel(?string $value): string
    {
        $value = html_entity_decode(trim((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return trim((string) preg_replace('/^(Du lịch|TOUR DU LỊCH|Tour du lịch)\s+/iu', '', $value));
    }

    protected function defaultCategoryName(?TourScope $scope, ?string $regionName): string
    {
        return match ($scope) {
            TourScope::Domestic => $regionName ?: 'Tour trong nước',
            TourScope::International => $regionName ?: 'Tour nước ngoài',
            TourScope::Group => 'Tour đoàn',
            default => 'Tour nổi bật',
        };
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function extractSeedPages(string $html): array
    {
        $xpath = $this->xpath($html);

        if (! $xpath) {
            return [];
        }

        $seeds = [];

        foreach ($xpath->query('//a[@href]') as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $url = $this->normalizeUrl($link->getAttribute('href'));
            $label = $this->cleanLabel($link->textContent);
            $path = parse_url($url, PHP_URL_PATH) ?: '';

            if ($label === '') {
                continue;
            }

            if (str_starts_with($path, '/diem-den/') || preg_match('/^\/tour-[A-Za-z0-9-]+$/', $path) === 1) {
                $seeds[$url] = [
                    'destination_label' => $label,
                    'scope' => $this->inferScopeFromDestination($label)?->value,
                    'url' => $url,
                ];
            }

            if (in_array(Str::upper($label), ['TRONG NƯỚC', 'NƯỚC NGOÀI', 'TOUR ĐOÀN'], true)) {
                $scope = match (Str::upper($label)) {
                    'TRONG NƯỚC' => TourScope::Domestic,
                    'NƯỚC NGOÀI' => TourScope::International,
                    'TOUR ĐOÀN' => TourScope::Group,
                    default => null,
                };

                if ($scope) {
                    $seeds[$url] = ['scope' => $scope->value, 'url' => $url];
                }
            }
        }

        return array_values($seeds);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function extractTourLinks(string $html, array $context = []): array
    {
        $xpath = $this->xpath($html);

        if (! $xpath) {
            return [];
        }

        $links = [];

        foreach ($xpath->query('//a[@href]') as $link) {
            if (! $link instanceof DOMElement) {
                continue;
            }

            $url = $this->normalizeUrl($link->getAttribute('href'));
            $path = parse_url($url, PHP_URL_PATH) ?: '';

            if (! $this->isTourDetailPath($path)) {
                continue;
            }

            $links[$url] = array_filter([
                'destination_label' => $context['destination_label'] ?? null,
                'scope' => $context['scope'] ?? null,
            ]);
        }

        return $links;
    }

    protected function fetchHtml(string $url): ?string
    {
        $response = Http::timeout(25)
            ->withoutVerifying()
            ->retry(2, 400)
            ->withHeaders([
                'Accept-Language' => 'vi,en-US;q=0.9,en;q=0.8',
                'User-Agent' => 'Mozilla/5.0 (compatible; HaidangTravelSnapshotCrawler/1.0; +https://haidangtravel.com)',
            ])
            ->get($url);

        return $response->successful() ? $response->body() : null;
    }

    protected function inferRegionName(?TourScope $scope, string $destinationName): ?string
    {
        $value = Str::lower($destinationName);
        $map = [
            'Miền Bắc' => ['ha noi', 'hà nội', 'sapa', 'fansipan', 'ha giang', 'hà giang', 'hạ long', 'mộc châu', 'tây bắc'],
            'Miền Trung' => ['đà nẵng', 'huế', 'quy nhơn', 'phú yên', 'ninh chữ', 'phan thiết', 'nha trang', 'hội an'],
            'Miền Nam' => ['cà mau', 'phú quốc', 'cần thơ', 'bạc liêu', 'côn đảo', 'vũng tàu', 'châu đốc', 'đồng tháp', 'miền tây'],
            'Tây Nguyên' => ['tà đùng', 'đà lạt', 'măng đen', 'buôn mê thuột', 'kon tum', 'bảo lộc'],
            'Châu Á' => ['singapore', 'malaysia', 'thượng hải', 'trung quốc', 'hàn quốc', 'nhật bản', 'đài loan', 'thái lan', 'ấn độ', 'nepal', 'tây tạng', 'bhutan'],
            'Châu Âu' => ['đức', 'hà lan', 'thụy sĩ', 'pháp', 'ý'],
            'Châu Mỹ' => ['mỹ', 'canada', 'mexico'],
            'Châu Úc' => ['úc', 'new zealand', 'newzeland'],
        ];

        foreach ($map as $region => $keywords) {
            foreach ($keywords as $keyword) {
                if (Str::contains($value, Str::lower($keyword))) {
                    return $region;
                }
            }
        }

        return match ($scope) {
            TourScope::Domestic => 'Việt Nam',
            TourScope::International => 'Quốc tế',
            default => null,
        };
    }

    protected function inferScopeFromDestination(string $destinationLabel): ?TourScope
    {
        $destinationLabel = Str::lower($destinationLabel);

        foreach (['singapore', 'malaysia', 'trung quốc', 'hàn quốc', 'nhật bản', 'ấn độ', 'thái lan', 'đức', 'pháp', 'ý', 'úc'] as $keyword) {
            if (Str::contains($destinationLabel, $keyword)) {
                return TourScope::International;
            }
        }

        return TourScope::Domestic;
    }

    protected function isTourDetailPath(string $path): bool
    {
        $path = trim($path, '/');

        if ($path === '' || str_contains($path, '.')) {
            return false;
        }

        $first = Str::before($path, '/');
        $excluded = ['page', 'tin-tuc', 'dich-vu', 'diem-den', 'chu-de-tour', 'gioi-thieu', 'lien-he', 'yeu-cau-ho-tro'];

        if (in_array($first, $excluded, true)) {
            return false;
        }

        if (in_array($path, ['tour-doan', 'tour-trong-nuoc', 'tour-nuoc-ngoai'], true)) {
            return false;
        }

        return str_starts_with($path, 'du-lich-') || str_starts_with($path, 'tour-') || substr_count($path, '-') >= 3;
    }

    protected function normalizeUrl(?string $href): string
    {
        $href = trim((string) $href);

        if ($href === '' || str_starts_with($href, '#')) {
            return self::BASE_URL;
        }

        if (str_starts_with($href, '//')) {
            $href = 'https:'.$href;
        }

        if (str_starts_with($href, '/')) {
            $href = rtrim(self::BASE_URL, '/').$href;
        }

        return Str::before($href, '#');
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseTourPage(string $url, array $context): ?array
    {
        $html = $this->fetchHtml($url);
        $xpath = $html ? $this->xpath($html) : null;

        if (! $xpath) {
            return null;
        }

        $title = $this->firstText($xpath, '//h1');

        if ($title === '') {
            return null;
        }

        $metaDescription = $this->metaContent($xpath, 'description');
        $pageText = $this->visibleText($html);
        preg_match_all('/\b\d{2}\/\d{2}\/\d{4}\b/u', $pageText, $dateMatches);
        preg_match('/Phương tiện\s*:?\s*([^\n\r]{1,80})/iu', $pageText, $transportMatch);
        preg_match('/Khởi hành\s*:?\s*([^\n\r]{1,120})/iu', $pageText, $departureMatch);
        preg_match('/(\d+)\s*ngày\s*(\d+)\s*đêm/iu', $pageText, $durationMatch);
        preg_match('/Tiêu chuẩn\s*:?\s*([^\n\r]{1,120})/iu', $pageText, $standardMatch);
        preg_match_all('/(\d{1,3}(?:[\.,]\d{3})+(?:\s*đ| VNĐ))/u', $pageText, $priceMatches);

        $scope = TourScope::tryFrom((string) ($context['scope'] ?? '')) ?: $this->inferScopeFromDestination((string) ($context['destination_label'] ?? ''));
        $destinationName = $this->cleanLabel((string) ($context['destination_label'] ?? ''));
        $destinationName = $destinationName !== '' ? $destinationName : $this->guessDestinationFromTitle($title);
        $regionName = $this->inferRegionName($scope, $destinationName);
        $categoryName = $this->defaultCategoryName($scope, $regionName);
        $tourCategorySlug = Str::slug($categoryName);
        $destinationSlug = Str::slug($destinationName);
        $regionSlug = $regionName ? Str::slug($regionName) : null;
        $coverImage = $this->metaContent($xpath, 'og:image');
        $coverImage = $this->isUsableImageUrl($coverImage)
            ? $coverImage
            : collect($this->imageUrls($xpath))->first();
        $galleryImages = collect($this->imageUrls($xpath))->reject(fn ($image) => $image === $coverImage)->take(8)->values();
        $paragraphs = collect(iterator_to_array($xpath->query('//p')))
            ->filter(fn ($node) => $node instanceof DOMElement)
            ->map(fn (DOMElement $node) => $this->cleanLabel($node->textContent))
            ->filter(fn (string $text) => mb_strlen($text) >= 40)
            ->take(5)
            ->values();
        $excerpt = $this->truncate($metaDescription ?: (string) $paragraphs->first() ?: $this->cleanLabel($title), 500);
        $content = $paragraphs->map(fn (string $text) => '<p>'.e($text).'</p>')->implode('');
        $dates = collect($dateMatches[0] ?? [])->unique()->take(12)->values();
        $basePrice = $this->parseMoney($priceMatches[1][0] ?? null);
        $salePrice = $this->parseMoney($priceMatches[1][1] ?? null) ?: $basePrice;

        return [
            'tour_category' => $this->taxonomyPayload($categoryName, $scope),
            'destination' => $this->destinationPayload($destinationName, $scope, $regionName),
            'region' => $regionName ? $this->regionPayload($regionName, $scope) : null,
            'tour' => [
                'slug' => Str::slug(trim((string) parse_url($url, PHP_URL_PATH), '/')),
                'title' => $this->truncate($title, 255),
                'scope' => $scope?->value ?: TourScope::Domestic->value,
                'tour_category_slug' => $tourCategorySlug,
                'tour_category_slugs' => [$tourCategorySlug],
                'destination_slug' => $destinationSlug,
                'destination_slugs' => [$destinationSlug],
                'region_slug' => $regionSlug,
                'region_slugs' => $regionSlug ? [$regionSlug] : [],
                'excerpt' => $excerpt,
                'content' => $content !== '' ? $content : '<p>'.e($excerpt).'</p>',
                'status' => 'published',
                'transport' => $this->truncate($this->cleanLabel($transportMatch[1] ?? ''), 255),
                'departure_location' => $this->truncate($this->cleanLabel($departureMatch[1] ?? ''), 255),
                'duration_days' => isset($durationMatch[1]) ? (int) $durationMatch[1] : null,
                'duration_nights' => isset($durationMatch[2]) ? (int) $durationMatch[2] : null,
                'standard_label' => $this->truncate($this->cleanLabel($standardMatch[1] ?? ''), 255),
                'base_price' => $basePrice,
                'sale_price' => $salePrice,
                'cta_mode' => $salePrice ? 'book' : 'contact',
                'is_featured' => true,
                'sort_order' => 0,
                'published_at' => now()->toDateTimeString(),
                'cover_alt' => $this->truncate($title, 255),
                'cover_image_url' => $coverImage,
                'meta_title' => $this->truncate($title, 255),
                'meta_description' => $excerpt,
                'og_title' => $this->truncate($title, 255),
                'og_description' => $excerpt,
                'canonical_url' => $url,
                'robots_directive' => 'index,follow',
                'itinerary' => $paragraphs->take(3)->map(fn (string $text, int $index) => ['title' => 'Chặng '.($index + 1), 'content' => $text])->all(),
                'pricing_table' => $salePrice ? [['label' => 'Giá từ', 'price' => number_format($salePrice, 0, ',', '.').' đ']] : [],
                'departure_schedules' => $dates->all(),
                'inclusions' => [],
                'faq_items' => [],
                'gallery' => $galleryImages->map(function (string $imageUrl) use ($title): array {
                    $item = ContentGallery::defaultItem();
                    $item['image_alt'] = $title;
                    $item['image_url'] = $imageUrl;

                    return $item;
                })->all(),
                'departures' => $dates->map(function (string $date) use ($basePrice, $salePrice, $title, $transportMatch, $departureMatch, $standardMatch): array {
                    return [
                        'departure_date' => \DateTimeImmutable::createFromFormat('d/m/Y', $date)?->format('Y-m-d'),
                        'departure_location' => $this->truncate($this->cleanLabel($departureMatch[1] ?? ''), 255),
                        'transport_label' => $this->truncate($this->cleanLabel($transportMatch[1] ?? ''), 255),
                        'standard_label' => $this->truncate($this->cleanLabel($standardMatch[1] ?? '') ?: $title, 255),
                        'base_price' => $basePrice,
                        'sale_price' => $salePrice,
                        'status' => 'published',
                        'sort_order' => 0,
                    ];
                })->filter(fn (array $departure) => filled($departure['departure_date']))->values()->all(),
            ],
        ];
    }

    protected function xpath(string $html): ?DOMXPath
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>'.mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        return $loaded ? new DOMXPath($dom) : null;
    }

    protected function destinationPayload(string $name, ?TourScope $scope, ?string $regionName): array
    {
        return ['name' => $name, 'slug' => Str::slug($name), 'scope' => $scope?->value, 'region_slug' => $regionName ? Str::slug($regionName) : null, 'status' => 'published', 'sort_order' => 0];
    }

    protected function regionPayload(string $name, ?TourScope $scope): array
    {
        return ['name' => $name, 'slug' => Str::slug($name), 'scope' => $scope?->value, 'status' => 'published', 'sort_order' => 0];
    }

    protected function taxonomyPayload(string $name, ?TourScope $scope): array
    {
        return ['name' => $name, 'slug' => Str::slug($name), 'scope' => $scope?->value, 'status' => 'published', 'sort_order' => 0];
    }

    protected function firstText(DOMXPath $xpath, string $query): string
    {
        $node = $xpath->query($query)?->item(0);

        return $node ? $this->cleanLabel($node->textContent) : '';
    }

    protected function guessDestinationFromTitle(string $title): string
    {
        $title = preg_replace('/\b\d+N\d+Đ\b/iu', '', $title) ?: $title;
        $title = preg_replace('/^(TOUR DU LỊCH|Tour du lịch|Du lịch)\s*/iu', '', $title) ?: $title;
        $parts = collect(explode('-', $title))->map(fn ($part) => $this->cleanLabel($part))->filter()->values();

        return (string) $parts->take(2)->implode(' - ');
    }

    /**
     * @return array<int, string>
     */
    protected function imageUrls(DOMXPath $xpath): array
    {
        $images = [];

        foreach ($xpath->query('//img[@src or @data-src or @data-original or @data-lazy-src or @srcset or @data-srcset]') as $image) {
            if (! $image instanceof DOMElement) {
                continue;
            }

            foreach (['src', 'data-src', 'data-original', 'data-lazy-src', 'srcset', 'data-srcset'] as $attribute) {
                $value = trim((string) $image->getAttribute($attribute));

                if ($value === '') {
                    continue;
                }

                $src = $attribute === 'srcset' || $attribute === 'data-srcset'
                    ? $this->firstSrcsetUrl($value)
                    : $value;

                $src = $this->normalizeUrl($src);

                if (! $this->isUsableImageUrl($src)) {
                    continue;
                }

                $images[] = $src;
            }
        }

        return array_values(array_unique($images));
    }

    protected function metaContent(DOMXPath $xpath, string $property): ?string
    {
        $query = $property === 'description'
            ? '//meta[@name="description"]'
            : sprintf('//meta[@property="%s" or @name="%s"]', $property, $property);

        $node = $xpath->query($query)?->item(0);

        if (! $node instanceof DOMElement) {
            return null;
        }

        $content = trim((string) $node->getAttribute('content'));

        return $property === 'description'
            ? $content
            : $this->normalizeUrl($content);
    }

    protected function firstSrcsetUrl(string $srcset): string
    {
        $candidate = Str::of($srcset)
            ->before(',')
            ->before(' ')
            ->trim()
            ->value();

        return trim($candidate);
    }

    protected function isUsableImageUrl(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '') {
            return false;
        }

        if (preg_match('/facebook|logo|icon|user|placeholder|avatar-default|logo1-default/i', $url)) {
            return false;
        }

        return preg_match('/\.(jpe?g|png|webp|gif|avif)(\?|$)/i', $url) === 1
            || str_contains($url, '/thumb/');
    }

    protected function parseMoney(?string $value): ?int
    {
        $value = preg_replace('/[^\d]/', '', (string) $value) ?: '';

        return $value !== '' ? (int) $value : null;
    }

    protected function truncate(string $value, int $max): string
    {
        return Str::limit(trim($value), $max, '');
    }

    protected function visibleText(string $html): string
    {
        $html = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/is', ' ', $html) ?: $html;
        $html = preg_replace('/<(br|\/p|\/div|\/li|\/tr|\/section|\/article|\/h[1-6])[^>]*>/i', "$0\n", $html) ?: $html;
        $text = strip_tags($html);
        $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+/u", ' ', $text) ?: $text;
        $text = preg_replace("/\n{2,}/u", "\n", $text) ?: $text;

        return trim((string) $text);
    }
}
