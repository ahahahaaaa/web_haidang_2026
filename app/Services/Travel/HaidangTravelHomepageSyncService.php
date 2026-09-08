<?php

namespace App\Services\Travel;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\Process\Process;
use Src\Domains\Cms\Models\Slider;
use Src\Domains\Cms\Models\SliderItem;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourCategory;
use Throwable;

class HaidangTravelHomepageSyncService
{
    protected const BASE_URL = 'https://haidangtravel.com';

    protected const HOME_HERO_LOCATION = 'home-hero';

    public function sync(?string $htmlPath = null): array
    {
        $html = $this->resolveHomepageHtml($htmlPath);

        if ($html === '') {
            throw new RuntimeException('Không tải được HTML trang chủ từ haidangtravel.com.');
        }

        $storedHtmlPath = $this->storeHomepageHtml($html);
        $heroSlides = $this->extractHeroSlides($html);
        $tourCategories = $this->extractTourCategories($html);

        if ($heroSlides === []) {
            throw new RuntimeException('Không tìm thấy dữ liệu slider `bannertop` trên trang chủ nguồn.');
        }

        if ($tourCategories === []) {
            throw new RuntimeException('Không tìm thấy dữ liệu `.tour.owl-carousel` trên trang chủ nguồn.');
        }

        $heroSync = $this->syncHeroSlider($heroSlides);
        $categorySync = $this->syncTourCategories($tourCategories);

        return [
            'categories_parsed' => count($tourCategories),
            'categories_synced' => $categorySync['categories_synced'],
            'hero_items_synced' => $heroSync['items_synced'],
            'hero_slides_parsed' => count($heroSlides),
            'html_path' => $storedHtmlPath,
            'tours_recategorized' => $categorySync['tours_recategorized'],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    protected function extractHeroSlides(string $html): array
    {
        $xpath = $this->xpath($html);

        if (! $xpath) {
            return [];
        }

        $slides = [];

        foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' bannertop ')]/a") as $anchor) {
            if (! $anchor instanceof DOMElement) {
                continue;
            }

            $image = $xpath->query('.//img', $anchor)->item(0);

            if (! $image instanceof DOMElement) {
                continue;
            }

            $imageUrl = $this->normalizeUrl($image->getAttribute('src') ?: $image->getAttribute('data-src'));

            if ($imageUrl === '') {
                continue;
            }

            $alt = $this->cleanText($image->getAttribute('alt'));
            $href = $this->normalizeUrl($anchor->getAttribute('href'));
            $title = $this->bannerTitle($alt, $href);

            $slides[] = [
                'description' => '',
                'image_alt' => $title !== '' ? $title : $alt,
                'image_url' => $imageUrl,
                'primary_label' => $this->bannerPrimaryLabel($alt, $href),
                'primary_url' => $this->bannerPrimaryUrl($alt, $href),
                'secondary_label' => '',
                'secondary_url' => '',
                'subtitle' => $this->bannerSubtitle($alt, $href),
                'title' => $title,
            ];
        }

        return $slides;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    protected function extractTourCategories(string $html): array
    {
        $xpath = $this->xpath($html);

        if (! $xpath) {
            return [];
        }

        $categories = [];

        foreach ($xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' tour ') and contains(concat(' ', normalize-space(@class), ' '), ' owl-carousel ')]/a") as $anchor) {
            if (! $anchor instanceof DOMElement) {
                continue;
            }

            $href = $this->normalizeUrl($anchor->getAttribute('href'));
            $path = parse_url($href, PHP_URL_PATH) ?: '';

            if (! str_starts_with((string) $path, '/chu-de-tour/')) {
                continue;
            }

            $slug = trim((string) Str::afterLast((string) $path, '/'));
            $image = $xpath->query('.//img', $anchor)->item(0);
            $labelNode = $xpath->query('.//p', $anchor)->item(0);
            $label = $this->cleanText($labelNode?->textContent ?: $image?->getAttribute('alt'));
            $imageUrl = $image instanceof DOMElement
                ? $this->normalizeUrl($image->getAttribute('data-src') ?: $image->getAttribute('src'))
                : '';

            if ($slug === '' || $label === '' || $imageUrl === '') {
                continue;
            }

            $categories[] = [
                'image_alt' => $this->cleanText($image?->getAttribute('alt') ?: $label),
                'image_url' => $imageUrl,
                'name' => $label,
                'slug' => $slug,
                'source_url' => $href,
            ];
        }

        return $this->hydrateCategoryMetadata($categories);
    }

    protected function resolveHomepageHtml(?string $htmlPath = null): string
    {
        if (filled($htmlPath)) {
            $resolvedPath = $this->resolvePath($htmlPath);

            if (File::exists($resolvedPath)) {
                return (string) File::get($resolvedPath);
            }
        }

        return $this->fetchHtml(self::BASE_URL);
    }

    protected function storeHomepageHtml(string $html): string
    {
        $directory = storage_path('app/source-sync');
        File::ensureDirectoryExists($directory);

        $path = $directory.DIRECTORY_SEPARATOR.'haidangtravel-homepage.html';
        File::put($path, $html);

        return $path;
    }

    protected function syncHeroSlider(array $slides): array
    {
        $slider = Slider::query()->firstOrCreate(
            ['location' => self::HOME_HERO_LOCATION],
            ['name' => 'Home Hero Slider'],
        );

        $slider->fill([
            'autoplay_delay' => 5500,
            'description' => 'Đồng bộ từ bannertop của haidangtravel.com',
            'is_active' => true,
            'name' => 'Home Hero Slider',
        ])->save();

        $existingItems = $slider->items()->orderBy('order')->get()->values();
        $activeIds = [];

        foreach ($slides as $index => $slide) {
            $item = $existingItems->get($index) ?: new SliderItem();
            $item->slider()->associate($slider);
            $item->fill([
                'cta_label' => null,
                'cta_url' => null,
                'description' => $slide['description'],
                'effect' => 'animate__fadeInUp',
                'image_alt' => $slide['image_alt'],
                'is_active' => true,
                'order' => $index + 1,
                'primary_label' => $slide['primary_label'] ?: null,
                'primary_url' => $slide['primary_url'] ?: null,
                'secondary_label' => $slide['secondary_label'] ?: null,
                'secondary_url' => $slide['secondary_url'] ?: null,
                'subtitle' => $slide['subtitle'],
                'title' => $slide['title'],
                'video_url' => null,
            ]);
            $item->save();

            $this->syncRemoteImage(
                $item,
                'image',
                $slide['image_url'],
                $slide['image_alt'],
                'home-hero-slide-'.$item->getKey(),
            );

            $activeIds[] = (int) $item->getKey();
        }

        SliderItem::query()
            ->where('slider_id', $slider->getKey())
            ->when($activeIds !== [], fn ($query) => $query->whereNotIn('id', $activeIds))
            ->update(['is_active' => false]);

        return [
            'items_synced' => count($activeIds),
        ];
    }

    protected function syncTourCategories(array $categories): array
    {
        $synced = [];

        foreach ($categories as $index => $payload) {
            $category = $this->findExistingCategoryForSourceSlug((string) $payload['slug']) ?: new TourCategory();

            $category->fill([
                'canonical_url' => null,
                'content' => $this->categoryContent((string) ($payload['description'] ?? '')),
                'cover_alt' => $payload['image_alt'],
                'cover_image_url' => $payload['image_url'],
                'excerpt' => $payload['description'] ?: $payload['name'],
                'is_featured' => true,
                'meta_description' => $payload['description'] ?: $payload['name'],
                'meta_title' => $payload['title'] ?: $payload['name'],
                'name' => $payload['title'] ?: $payload['name'],
                'og_description' => $payload['description'] ?: $payload['name'],
                'og_title' => $payload['title'] ?: $payload['name'],
                'published_at' => $category->published_at ?: now(),
                'robots_directive' => 'index,follow',
                'scope' => $this->categoryScope((string) $payload['slug']),
                'slug' => (string) $payload['slug'],
                'sort_order' => $index + 1,
                'status' => 'published',
            ]);
            $category->save();

            $this->syncRemoteImage(
                $category,
                'avatar',
                (string) $payload['image_url'],
                (string) $payload['image_alt'],
                'tour-category-'.$category->slug,
            );

            $synced[(string) $payload['slug']] = $category;
        }

        $toursRecategorized = $this->syncTourAssignments(collect($synced));

        return [
            'categories_synced' => count($synced),
            'tours_recategorized' => $toursRecategorized,
        ];
    }

    protected function findExistingCategoryForSourceSlug(string $slug): ?TourCategory
    {
        $candidateSlugs = array_values(array_unique(array_filter([
            $slug,
            ...match ($slug) {
                'tour-dong-nam-a' => ['chau-a'],
                'tour-mien-bac' => ['mien-bac'],
                'tour-mien-trung' => ['mien-trung'],
                'tour-mien-nam' => ['mien-nam'],
                'tour-my-chau-au-canada' => ['chau-au'],
                default => [],
            },
        ])));

        return TourCategory::query()
            ->whereIn('slug', $candidateSlugs)
            ->first();
    }

    protected function syncTourAssignments(Collection $categories): int
    {
        if ($categories->isEmpty()) {
            return 0;
        }

        $categoryIds = $categories->mapWithKeys(fn (TourCategory $category) => [$category->slug => (int) $category->getKey()]);
        $updated = 0;

        $tours = Tour::query()
            ->published()
            ->with(['destination:id,name', 'region:id,name'])
            ->get();

        foreach ($tours as $tour) {
            $matchedSlug = $this->guessTourCategorySlug($tour, $categoryIds->keys()->all());

            if (! $matchedSlug) {
                continue;
            }

            $targetCategoryId = $categoryIds->get($matchedSlug);

            if (! $targetCategoryId || (int) $tour->tour_category_id === (int) $targetCategoryId) {
                continue;
            }

            $tour->forceFill(['tour_category_id' => $targetCategoryId])->save();
            $tour->syncTaxonomyLinks();
            $updated++;
        }

        return $updated;
    }

    protected function guessTourCategorySlug(Tour $tour, array $allowedSlugs): ?string
    {
        $haystack = Str::ascii(Str::lower(implode(' ', array_filter([
            $tour->title,
            $tour->slug,
            $tour->destination?->name,
            $tour->region?->name,
        ]))));

        $contains = function (array $keywords) use ($haystack): bool {
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, Str::ascii(Str::lower($keyword)))) {
                    return true;
                }
            }

            return false;
        };

        $priorityMap = [
            'tour-du-lich-30-4' => ['30/4', '30-4', '30 4', '30.4', 'le 30', 'lễ 30', '30 tháng 4', '30 thang 4'],
            'tour-dong-nam-a' => ['singapore', 'malaysia', 'thai lan', 'thailand', 'campuchia', 'lao', 'dong nam a', 'bali', 'indonesia'],
            'tour-han-nhat-dai' => ['han quoc', 'seoul', 'busan', 'nhat ban', 'tokyo', 'osaka', 'tai loan', 'dai bac', 'han nhat dai'],
            'tour-trung-quoc' => ['trung quoc', 'thuong hai', 'o tran', 'bac kinh', 'tay tang', 'lhasa', 'le giang', 'shangrila', 'con minh', 'cap nhi tan', 'trung khanh', 'li giang'],
            'chum-tour-an-do' => ['an do', 'india', 'himalaya', 'bhutan', 'nepal', 'kashmir', 'mumbai'],
            'tour-my-chau-au-canada' => ['chau au', 'my ', 'usa', 'canada', 'phap', 'duc', 'ha lan', 'thuy si', 'y ', 'italy', 'rome', 'paris'],
            'tour-uc-newzeland' => ['australia', 'newzeland', 'new zealand', 'sydney', 'melbourne', 'tour uc', 'nuoc uc'],
            'tour-mien-bac' => ['mien bac', 'ha noi', 'ha giang', 'sapa', 'fansipan', 'moc chau', 'ninh binh', 'ha long', 'cao bang'],
            'tour-mien-trung' => ['mien trung', 'da nang', 'hue', 'hoi an', 'nha trang', 'phu yen', 'quy nhon', 'ninh chu', 'cam ranh', 'vinh hy', 'binh hung'],
            'tour-mien-nam' => ['mien nam', 'mien tay', 'phu quoc', 'vung tau', 'can tho', 'sai gon', 'my tho', 'ben tre', 'con dao'],
        ];

        foreach ($priorityMap as $slug => $keywords) {
            if (in_array($slug, $allowedSlugs, true) && $contains($keywords)) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, string|null>>  $categories
     * @return array<int, array<string, string|null>>
     */
    protected function hydrateCategoryMetadata(array $categories): array
    {
        return collect($categories)
            ->map(function (array $category): array {
                $html = $this->fetchHtml((string) $category['source_url']);
                $metadata = $html !== '' ? $this->extractPageMetadata($html) : [];

                return [
                    ...$category,
                    'description' => $this->cleanText((string) ($metadata['description'] ?? '')),
                    'title' => $this->cleanText((string) ($metadata['title'] ?? $category['name'])),
                ];
            })
            ->all();
    }

    /**
     * @return array{description?: string, title?: string}
     */
    protected function extractPageMetadata(string $html): array
    {
        $xpath = $this->xpath($html);

        if (! $xpath) {
            return [];
        }

        $title = $this->cleanText($xpath->evaluate('string(//title)'));
        $description = $this->cleanText(
            (string) $xpath->evaluate("string(//meta[translate(@name, 'DESCRIPTION', 'description')='description']/@content)")
        );
        $h1 = $this->cleanText($xpath->evaluate('string(//h1[1])'));

        return [
            'description' => $description,
            'title' => $h1 !== '' ? $h1 : $title,
        ];
    }

    protected function bannerPrimaryLabel(string $alt, string $href): string
    {
        return match (true) {
            $this->bannerMatches($alt, $href, ['30/4', '30-4', '30 4', '30 tháng 4', '30 thang 4']) => 'Xem chủ đề 30 tháng 4',
            $this->bannerMatches($alt, $href, ['trong nuoc']) => 'Xem tour trong nước',
            $this->bannerMatches($alt, $href, ['nuoc ngoai']) => 'Xem tour nước ngoài',
            $this->bannerMatches($alt, $href, ['doan']) => 'Xem tour đoàn',
            $this->bannerMatches($alt, $href, ['visa']) => 'Xem dịch vụ visa',
            default => '',
        };
    }

    protected function bannerPrimaryUrl(string $alt, string $href): string
    {
        return match (true) {
            $this->bannerMatches($alt, $href, ['30/4', '30-4', '30 4', '30 tháng 4', '30 thang 4']) => '/danh-muc-tour/tour-du-lich-30-4',
            $this->bannerMatches($alt, $href, ['trong nuoc']) => '/tour-trong-nuoc',
            $this->bannerMatches($alt, $href, ['nuoc ngoai']) => '/tour-nuoc-ngoai',
            $this->bannerMatches($alt, $href, ['doan']) => '/tour-doan',
            $this->bannerMatches($alt, $href, ['visa']) => '/dich-vu/visa',
            default => $this->relativeUrl($href),
        };
    }

    protected function bannerSubtitle(string $alt, string $href): string
    {
        return match (true) {
            $this->bannerMatches($alt, $href, ['30/4', '30-4', '30 4', '30 tháng 4', '30 thang 4']) => 'Chủ đề tour theo mùa',
            $this->bannerMatches($alt, $href, ['trong nuoc']) => 'Tour trong nước',
            $this->bannerMatches($alt, $href, ['nuoc ngoai']) => 'Tour nước ngoài',
            $this->bannerMatches($alt, $href, ['doan']) => 'Tour đoàn',
            $this->bannerMatches($alt, $href, ['visa']) => 'Dịch vụ hỗ trợ',
            default => '',
        };
    }

    protected function bannerTitle(string $alt, string $href): string
    {
        return match (true) {
            $this->bannerMatches($alt, $href, ['30/4', '30-4', '30 4', '30 tháng 4', '30 thang 4']) => 'Du lịch 30 tháng 4',
            $this->bannerMatches($alt, $href, ['trong nuoc']) => 'Tour du lịch trong nước giá rẻ',
            $this->bannerMatches($alt, $href, ['nuoc ngoai']) => 'Tour du lịch nước ngoài giá rẻ',
            $this->bannerMatches($alt, $href, ['doan']) => 'Tour du lịch đoàn',
            $this->bannerMatches($alt, $href, ['visa']) => 'Dịch vụ làm visa',
            default => $this->cleanText($alt),
        };
    }

    protected function bannerMatches(string $alt, string $href, array $keywords): bool
    {
        $haystack = Str::ascii(Str::lower(trim($alt.' '.$href)));

        foreach ($keywords as $keyword) {
            if (str_contains($haystack, Str::ascii(Str::lower($keyword)))) {
                return true;
            }
        }

        return false;
    }

    protected function categoryContent(string $description): ?string
    {
        $description = trim($description);

        return $description !== '' ? '<p>'.e($description).'</p>' : null;
    }

    protected function categoryScope(string $slug): ?string
    {
        return match ($slug) {
            'tour-dong-nam-a',
            'tour-han-nhat-dai',
            'tour-trung-quoc',
            'chum-tour-an-do',
            'tour-uc-newzeland',
            'tour-my-chau-au-canada' => 'international',
            'tour-mien-bac',
            'tour-mien-trung',
            'tour-mien-nam' => 'domestic',
            default => null,
        };
    }

    protected function fetchHtml(string $url): string
    {
        try {
            $response = Http::withoutVerifying()
                ->connectTimeout(10)
                ->retry(2, 400)
                ->timeout(25)
                ->withHeaders([
                    'Accept-Language' => 'vi,en-US;q=0.9,en;q=0.8',
                    'User-Agent' => 'Mozilla/5.0 (compatible; HaidangTravelHomepageSync/1.0; +https://haidangtravel.com)',
                ])
                ->get($url);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (Throwable) {
        }

        return $this->fetchHtmlViaPowerShell($url);
    }

    protected function cleanText(?string $value): string
    {
        $value = html_entity_decode(trim((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?: '';

        return trim(strip_tags($value));
    }

    protected function normalizeUrl(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '' || str_starts_with($value, '#')) {
            return '';
        }

        if (str_starts_with($value, '//')) {
            return 'https:'.$value;
        }

        if (str_starts_with($value, '/')) {
            return rtrim(self::BASE_URL, '/').$value;
        }

        return $value;
    }

    protected function relativeUrl(?string $value): string
    {
        $normalized = $this->normalizeUrl($value);

        if ($normalized === '') {
            return '';
        }

        $path = parse_url($normalized, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $normalized;
        }

        return $path;
    }

    protected function resolvePath(string $path): string
    {
        if (Str::startsWith($path, ['/', '\\']) || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    protected function xpath(string $html): ?DOMXPath
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $loaded = $dom->loadHTML('<?xml encoding="utf-8"?>'.$html);

        if (! $loaded) {
            return null;
        }

        return new DOMXPath($dom);
    }

    /**
     * @param  Model&HasMedia  $model
     */
    protected function syncRemoteImage(
        Model $model,
        string $collection,
        string $url,
        string $alt,
        string $name,
    ): void {
        if ($url === '') {
            return;
        }

        $currentMedia = $model->getFirstMedia($collection);
        $currentSource = (string) data_get($currentMedia?->custom_properties, 'source_url', '');

        if ($currentMedia instanceof Media && $currentSource === $url) {
            return;
        }

        $download = $this->downloadImage($url);

        if ($download === null) {
            return;
        }

        try {
            $model->clearMediaCollection($collection);
            $model
                ->addMedia($download['path'])
                ->usingName($name)
                ->usingFileName($download['file_name'])
                ->withCustomProperties([
                    'alt' => $alt,
                    'source_url' => $url,
                ])
                ->toMediaCollection($collection, config('media-library.disk_name', 'public'));
        } finally {
            if (File::exists($download['path'])) {
                File::delete($download['path']);
            }
        }
    }

    /**
     * @return array{file_name: string, path: string}|null
     */
    protected function downloadImage(string $url): ?array
    {
        $extension = $this->extensionFromUrl($url) ?? 'jpg';
        $directory = storage_path('app/tmp/homepage-sync');
        File::ensureDirectoryExists($directory);

        try {
            $response = Http::accept('image/*')
                ->withoutVerifying()
                ->connectTimeout(10)
                ->retry(2, 400)
                ->timeout(25)
                ->get($url);
            if (! $response->failed()) {
                $contentType = Str::before(strtolower((string) $response->header('Content-Type')), ';');

                if (Str::startsWith($contentType, 'image/')) {
                    $extension = $this->extensionFromContentType($contentType) ?? $extension;
                    $path = $directory.DIRECTORY_SEPARATOR.Str::uuid().'.'.$extension;
                    File::put($path, $response->body());

                    return [
                        'file_name' => Str::slug(pathinfo(parse_url($url, PHP_URL_PATH) ?: 'homepage-image', PATHINFO_FILENAME)).'-'.substr(sha1($url), 0, 10).'.'.$extension,
                        'path' => $path,
                    ];
                }
            }
        } catch (Throwable) {
        }

        $path = $directory.DIRECTORY_SEPARATOR.Str::uuid().'.'.$extension;

        if (! $this->downloadImageViaPowerShell($url, $path)) {
            return null;
        }

        return [
            'file_name' => Str::slug(pathinfo(parse_url($url, PHP_URL_PATH) ?: 'homepage-image', PATHINFO_FILENAME)).'-'.substr(sha1($url), 0, 10).'.'.$extension,
            'path' => $path,
        ];
    }

    protected function extensionFromContentType(string $contentType): ?string
    {
        return match ($contentType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
            default => null,
        };
    }

    protected function extensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = is_string($path) ? strtolower((string) pathinfo($path, PATHINFO_EXTENSION)) : '';

        return $extension !== '' ? $extension : null;
    }

    protected function fetchHtmlViaPowerShell(string $url): string
    {
        $command = "(Invoke-WebRequest -Uri ".$this->powerShellLiteral($url)." -UseBasicParsing -TimeoutSec 30).Content";
        $process = new Process(['powershell', '-NoProfile', '-Command', $command], base_path(), ['NO_COLOR' => '1']);
        $process->setTimeout(45);

        try {
            $process->run();
        } catch (Throwable) {
            return '';
        }

        if (! $process->isSuccessful()) {
            return '';
        }

        return trim($process->getOutput());
    }

    protected function downloadImageViaPowerShell(string $url, string $path): bool
    {
        $command = "Invoke-WebRequest -Uri ".$this->powerShellLiteral($url)." -OutFile ".$this->powerShellLiteral($path)." -UseBasicParsing -TimeoutSec 45";
        $process = new Process(['powershell', '-NoProfile', '-Command', $command], base_path(), ['NO_COLOR' => '1']);
        $process->setTimeout(60);

        try {
            $process->run();
        } catch (Throwable) {
            return false;
        }

        return $process->isSuccessful() && File::exists($path);
    }

    protected function powerShellLiteral(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
}
