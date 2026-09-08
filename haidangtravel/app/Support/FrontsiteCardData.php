<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\BlogPost;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourDeparture;

class FrontsiteCardData
{
    protected static ?array $blogFallbackTourImages = null;

    public static function tour(Tour $tour): array
    {
        /** @var TourDeparture|null $nextDeparture */
        $nextDeparture = $tour->departures
            ->first(fn (TourDeparture $departure) => in_array($departure->status, ['scheduled', 'published'], true))
            ?: $tour->departures->first();

        $destinationLabel = trim((string) ($tour->destination?->name ?: $tour->region?->name));
        $transportLabel = trim((string) ($tour->transport ?: $nextDeparture?->transport_label ?: 'Liên hệ'));
        $departurePlace = trim((string) ($nextDeparture?->departure_location ?: $tour->departure_location ?: 'Liên hệ'));
        $nextDepartureLabel = $nextDeparture?->departure_date?->format('d/m/Y')
            ?: collect($tour->departure_schedules ?? [])->filter()->first()
            ?: 'Liên hệ';
        $durationLabel = trim(sprintf(
            '%s ngày %s đêm',
            $tour->duration_days ?: '?',
            $tour->duration_nights ?: 0,
        ));
        $standardLabel = trim((string) ($nextDeparture?->standard_label ?: $tour->standard_label ?: 'Liên hệ'));
        $priceValue = $nextDeparture?->sale_price ?: $nextDeparture?->base_price ?: $tour->sale_price ?: $tour->base_price;
        $basePriceValue = $nextDeparture?->base_price ?: $tour->base_price;
        $statusValue = Str::lower(trim((string) ($nextDeparture?->status ?: '')));
        $statusLabel = match ($statusValue) {
            'scheduled', 'published' => 'Đang nhận khách',
            'sold_out' => 'Đã kín chỗ',
            'closed' => 'Tạm khóa',
            default => $statusValue !== '' ? Str::headline(str_replace('_', ' ', $statusValue)) : 'Tư vấn thêm',
        };
        $primaryTopicLabel = trim((string) (self::loadedRelation($tour, 'primaryCategory')?->name ?: ''));
        $slotsValue = $nextDeparture?->available_slots;
        $slotLabel = is_numeric($slotsValue) && (int) $slotsValue > 0
            ? ((int) $slotsValue).' chỗ còn'
            : ($statusLabel ?: 'Tư vấn lịch đi');

        $fitAudience = match ($tour->scope) {
            TourScope::Domestic => 'gia đình, nhóm bạn hoặc khách muốn chốt lịch nhanh',
            TourScope::International => 'khách cần so sánh ngày đi, chi phí và hồ sơ trước chuyến quốc tế',
            TourScope::Group => 'doanh nghiệp, đoàn đông người hoặc nhóm cần chốt brief rõ ràng',
            default => 'khách đang tìm hành trình phù hợp',
        };

        $valueHook = $destinationLabel !== ''
            ? 'Điểm nổi bật là '.$destinationLabel.' với lộ trình '.$durationLabel.' và phương tiện '.$transportLabel.'.'
            : 'Bạn có thể đối chiếu nhanh lịch gần nhất, phương tiện và mức giá khởi hành trước khi mở chi tiết.';

        $summary = Str::limit(
            trim('Phù hợp cho '.$fitAudience.'. '.$valueHook.' Mở chi tiết để xem tiêu chuẩn, giá và tình trạng chỗ.'),
            190
        );
        $ratingValue = filled($tour->rating_average) ? number_format((float) $tour->rating_average, 1, ',', '.') : null;
        $ratingCount = is_numeric($tour->rating_count) && (int) $tour->rating_count > 0
            ? (int) $tour->rating_count
            : null;

        return [
            'base_price_value' => is_numeric($basePriceValue) ? (int) $basePriceValue : null,
            'cta_label' => $tour->cta_mode === 'contact' ? 'Tư vấn' : 'Xem',
            'destination_label' => $destinationLabel !== '' ? $destinationLabel : 'Theo tư vấn',
            'detail_url' => route('tours.show', $tour),
            'duration_label' => $durationLabel,
            'image_alt' => trim((string) ($tour->cover_alt ?: $tour->title)),
            'image_url' => self::tourImageUrl($tour),
            'next_departure_label' => $nextDepartureLabel,
            'departure_place' => $departurePlace,
            'price_label' => is_numeric($priceValue) && (int) $priceValue > 0
                ? number_format((int) $priceValue, 0, ',', '.').' đ'
                : 'Liên hệ',
            'primary_topic_label' => $primaryTopicLabel !== '' ? $primaryTopicLabel : null,
            'scope_label' => $tour->scope?->label() ?: 'Tour',
            'rating_count' => $ratingCount,
            'rating_label' => $ratingValue,
            'slot_label' => $slotLabel,
            'slot_status_label' => $statusLabel,
            'standard_label' => $standardLabel,
            'summary' => $summary,
            'title' => trim((string) $tour->title),
            'transport_label' => $transportLabel,
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function tourGallerySlides(Tour $tour): array
    {
        $coverUrl = self::tourCoverUrl($tour, FrontsiteMedia::SIZE_MEDIUM);
        $coverThumbUrl = self::tourCoverUrl($tour, FrontsiteMedia::SIZE_SMALL);
        $coverAlt = trim((string) ($tour->cover_alt ?: $tour->title));
        $coverTitle = trim((string) $tour->title);

        $coverSlides = collect();

        if ($coverUrl !== '') {
            $coverSlides->push([
                'alt' => $coverAlt !== '' ? $coverAlt : $coverTitle,
                'description' => 'Ảnh đại diện của tour.',
                'icon' => 'fa-regular fa-image',
                'kind' => ContentGallery::TYPE_IMAGE,
                'label' => 'Ảnh đại diện',
                'src' => $coverUrl,
                'thumbnail_url' => $coverThumbUrl !== '' ? $coverThumbUrl : $coverUrl,
                'title' => $coverTitle,
            ]);
        }

        $gallerySlides = collect(ContentGallery::resolveForFrontsite(
            $tour,
            $tour->gallery,
            fn (string $uuid) => ContentGallery::tourCollection($uuid),
            $coverUrl !== '' ? $coverUrl : null,
            $coverAlt !== '' ? $coverAlt : $coverTitle,
            $coverThumbUrl !== '' ? $coverThumbUrl : ($coverUrl !== '' ? $coverUrl : null),
        ))
            ->filter(fn (array $item) => ($item['lightbox_kind'] ?? null) === ContentGallery::TYPE_IMAGE)
            ->map(fn (array $item) => [
                'alt' => trim((string) ($item['resolved_alt'] ?? '')) ?: $coverAlt ?: $coverTitle,
                'description' => trim((string) ($item['resolved_description'] ?? '')),
                'icon' => 'fa-regular fa-image',
                'kind' => ContentGallery::TYPE_IMAGE,
                'label' => 'Gallery tour',
                'src' => trim((string) ($item['image_url'] ?? '')),
                'thumbnail_url' => trim((string) (($item['thumbnail_url'] ?? '') ?: ($item['small_image_url'] ?? '') ?: ($item['image_url'] ?? ''))),
                'title' => trim((string) ($item['resolved_title'] ?? '')) ?: $coverTitle,
            ])
            ->filter(fn (array $item) => $item['src'] !== '');

        return $coverSlides
            ->merge($gallerySlides)
            ->unique(fn (array $item) => $item['src'])
            ->values()
            ->all();
    }

    protected static function tourImageUrl(Tour $tour): ?string
    {
        $coverUrl = self::tourCoverUrl($tour, FrontsiteMedia::SIZE_SMALL);

        if ($coverUrl !== '') {
            return $coverUrl;
        }

        $galleryUrl = self::tourGalleryImageUrl($tour, FrontsiteMedia::SIZE_SMALL);

        if ($galleryUrl !== null) {
            return $galleryUrl;
        }

        foreach ([
            self::loadedRelation($tour, 'destination'),
            self::loadedRelation($tour, 'region'),
            self::loadedRelation($tour, 'primaryCategory'),
        ] as $relatedModel) {
            $relatedImage = self::modelImageUrl($relatedModel, 'avatar', FrontsiteMedia::SIZE_SMALL);

            if ($relatedImage !== null) {
                return $relatedImage;
            }
        }

        return null;
    }

    protected static function tourCoverUrl(Tour $tour, string $size = FrontsiteMedia::SIZE_FULL): string
    {
        return trim((string) FrontsiteMedia::modelUrl($tour, 'cover', $size));
    }

    protected static function tourGalleryImageUrl(Tour $tour, string $size = FrontsiteMedia::SIZE_FULL): ?string
    {
        foreach (ContentGallery::prepare($tour->gallery) as $item) {
            if (($item['type'] ?? null) !== ContentGallery::TYPE_IMAGE) {
                continue;
            }

            $imageUrl = trim((string) (
                FrontsiteMedia::modelUrl(
                    $tour,
                    ContentGallery::tourCollection((string) ($item['uuid'] ?? '')),
                    $size,
                    null,
                )
                ?: FrontsiteMedia::validatedUrl((string) ($item['image_url'] ?? ''))
            ));

            if ($imageUrl !== '') {
                return $imageUrl;
            }
        }

        return null;
    }

    protected static function modelImageUrl(mixed $model, string $collection, string $size = FrontsiteMedia::SIZE_FULL): ?string
    {
        if ($collection === 'avatar') {
            return FrontsiteMedia::taxonomyAvatarUrl($model, $size, 'cover_image_url', false);
        }

        return FrontsiteMedia::modelUrl($model, $collection, $size);
    }

    protected static function loadedRelation(Tour $tour, string $relation): mixed
    {
        return $tour->relationLoaded($relation) ? $tour->getRelation($relation) : null;
    }

    public static function blog(BlogPost $post, Collection|array|null $serviceCategories = null): array
    {
        $serviceCategories = collect($serviceCategories ?? []);
        $signals = self::normalizedText(
            implode(' ', array_filter([
                (string) $post->title,
                (string) $post->excerpt,
                ContentCategoryTree::pathLabel($post->category),
            ]))
        );

        $categoryLabel = trim((string) (ContentCategoryTree::pathLabel($post->category) ?: 'Cẩm nang du lịch'));
        $publishedLabel = optional($post->published_at)->format('d/m/Y') ?: optional($post->created_at)->format('d/m/Y');
        $cover = FrontsiteMedia::modelUrl($post, 'cover', FrontsiteMedia::SIZE_SMALL);

        $visaSignals = ['visa', 'ho so', 'thu tuc', 'passport'];
        $groupSignals = ['tour doan', 'doan', 'mice', 'team building', 'doanh nghiep', 'company trip'];
        $seasonSignals = ['mua', 'mua dep', 'thoi tiet', 'he', 'thu', 'dong', 'xuan', 'tet', 'le'];
        $checklistSignals = ['checklist', 'chuan bi', 'kinh nghiem', 'luu y', 'cam nang'];

        $intentLabel = $categoryLabel;
        $bridgeLabel = 'Tìm tour phù hợp';
        $bridgeUrl = route('tours.search');
        $summary = 'Giúp bạn rút ngắn thời gian tìm tour bằng các lưu ý thực tế trước khi chốt hành trình.';

        $visaCategory = $serviceCategories->first(function ($category) {
            $text = self::normalizedText((string) data_get($category, 'slug').' '.(string) data_get($category, 'name'));

            return Str::contains($text, 'visa');
        });

        if (Str::contains($signals, $visaSignals)) {
            $intentLabel = 'Visa';
            $bridgeLabel = 'Xem dịch vụ visa';
            $bridgeUrl = $visaCategory
                ? route('service-categories.show', ['category' => data_get($visaCategory, 'slug')])
                : route('services.index');
            $summary = 'Giúp bạn rà soát hồ sơ, mốc thời gian và các lưu ý trước khi chốt tour quốc tế.';
        } elseif (Str::contains($signals, $groupSignals)) {
            $intentLabel = 'Kinh nghiệm tour đoàn';
            $bridgeLabel = 'Xem tour đoàn';
            $bridgeUrl = route('tours.group');
            $summary = 'Giúp bạn chốt brief đoàn, ngân sách và các hạng mục cần hỏi trước khi gửi yêu cầu.';
        } elseif (Str::contains($signals, $seasonSignals)) {
            $intentLabel = 'Mùa đẹp';
            $bridgeLabel = 'Xem tour trong nước';
            $bridgeUrl = route('tours.domestic');
            $summary = 'Giúp bạn chọn thời điểm đi phù hợp để không lệch mùa trải nghiệm và ngân sách.';
        } elseif (Str::contains($signals, $checklistSignals)) {
            $intentLabel = 'Checklist';
            $bridgeLabel = 'Xem tour nước ngoài';
            $bridgeUrl = route('tours.international');
            $summary = 'Giúp bạn biết cần chuẩn bị gì trước khi chọn tour, dịch vụ đi kèm hoặc lịch khởi hành.';
        }

        $excerpt = trim(strip_tags((string) $post->excerpt));

        if ($excerpt !== '') {
            $summary = Str::limit($summary.' '.$excerpt, 185);
        }

        if (blank($cover)) {
            $cover = self::blogFallbackTourImageUrl(
                isVisa: Str::contains($signals, $visaSignals),
                isGroup: Str::contains($signals, $groupSignals),
                isSeason: Str::contains($signals, $seasonSignals),
                isChecklist: Str::contains($signals, $checklistSignals),
            );
        }

        return [
            'author_label' => trim((string) ($post->author_name ?: config('app.name'))),
            'bridge_label' => $bridgeLabel,
            'bridge_url' => $bridgeUrl,
            'category_label' => $categoryLabel,
            'detail_label' => 'Xem thêm',
            'detail_url' => route('blog.show', $post),
            'image_alt' => trim((string) ($post->cover_alt ?: $post->title)),
            'image_url' => $cover,
            'intent_label' => $intentLabel,
            'published_label' => $publishedLabel,
            'reading_time_label' => $post->reading_time_minutes ? $post->reading_time_minutes.' phút đọc' : null,
            'summary' => $summary,
            'title' => trim((string) $post->title),
        ];
    }

    protected static function blogFallbackTourImageUrl(bool $isVisa, bool $isGroup, bool $isSeason, bool $isChecklist): ?string
    {
        $images = self::blogFallbackTourImages();

        if ($isGroup) {
            return $images['group'] ?? $images['default'];
        }

        if ($isVisa || $isChecklist) {
            return $images['international'] ?? $images['default'];
        }

        if ($isSeason) {
            return $images['domestic'] ?? $images['default'];
        }

        return $images['default']
            ?? $images['domestic']
            ?? $images['international']
            ?? $images['group'];
    }

    protected static function blogFallbackTourImages(): array
    {
        if (self::$blogFallbackTourImages !== null) {
            return self::$blogFallbackTourImages;
        }

        $images = [
            'default' => null,
            'domestic' => null,
            'international' => null,
            'group' => null,
        ];

        $tours = Tour::query()
            ->published()
            ->with(['destination', 'region', 'primaryCategory', 'media'])
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->limit(60)
            ->get();

        foreach ($tours as $tour) {
            $imageUrl = self::tourImageUrl($tour);

            if (blank($imageUrl)) {
                continue;
            }

            $images['default'] ??= $imageUrl;

            $scopeKey = match ($tour->scope) {
                TourScope::Domestic => 'domestic',
                TourScope::International => 'international',
                TourScope::Group => 'group',
                default => null,
            };

            if ($scopeKey !== null && blank($images[$scopeKey])) {
                $images[$scopeKey] = $imageUrl;
            }

            if (filled($images['default']) && filled($images['domestic']) && filled($images['international']) && filled($images['group'])) {
                break;
            }
        }

        return self::$blogFallbackTourImages = $images;
    }

    protected static function normalizedText(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->value();
    }
}
