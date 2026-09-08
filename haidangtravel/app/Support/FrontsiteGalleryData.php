<?php

namespace App\Support;

use Spatie\MediaLibrary\HasMedia;
use Src\Domains\Cms\Models\Tour;

class FrontsiteGalleryData
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function tour(Tour $tour): array
    {
        $coverFull = trim((string) FrontsiteMedia::modelUrl($tour, 'cover', FrontsiteMedia::SIZE_FULL));
        $coverMedium = trim((string) (FrontsiteMedia::modelUrl($tour, 'cover', FrontsiteMedia::SIZE_MEDIUM) ?: $coverFull));
        $coverSmall = trim((string) (FrontsiteMedia::modelUrl($tour, 'cover', FrontsiteMedia::SIZE_SMALL) ?: $coverMedium));

        return self::build(
            $tour,
            $tour->gallery,
            fn (string $uuid): string => ContentGallery::tourCollection($uuid),
            $coverFull,
            $coverMedium,
            $coverSmall,
            trim((string) ($tour->cover_alt ?: $tour->title)),
            trim((string) $tour->title),
            'Ảnh đại diện',
            'Ảnh đại diện của tour.',
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function taxonomy(
        HasMedia $taxonomy,
        string $taxonomyType,
        ?string $title = null,
        ?string $alt = null,
        bool $includePrimaryImage = true,
        bool $fullStageForImages = false,
    ): array
    {
        $avatarFull = trim((string) FrontsiteMedia::modelUrl($taxonomy, 'avatar', FrontsiteMedia::SIZE_FULL, 'cover_image_url'));
        $avatarMedium = trim((string) (FrontsiteMedia::modelUrl($taxonomy, 'avatar', FrontsiteMedia::SIZE_MEDIUM, 'cover_image_url') ?: $avatarFull));
        $avatarSmall = trim((string) (FrontsiteMedia::modelUrl($taxonomy, 'avatar', FrontsiteMedia::SIZE_SMALL, 'cover_image_url') ?: $avatarMedium));
        $resolvedTitle = trim((string) ($title ?: data_get($taxonomy, 'name') ?: data_get($taxonomy, 'title') ?: 'Hình ảnh'));
        $resolvedAlt = trim((string) ($alt ?: data_get($taxonomy, 'cover_alt') ?: $resolvedTitle));

        $slides = self::build(
            $taxonomy,
            is_array(data_get($taxonomy, 'gallery')) ? data_get($taxonomy, 'gallery') : [],
            fn (string $uuid): string => ContentGallery::taxonomyCollection($taxonomyType, $uuid),
            $avatarFull,
            $avatarMedium,
            $avatarSmall,
            $resolvedAlt,
            $resolvedTitle,
            'Ảnh đại diện',
            match ($taxonomyType) {
                'category', 'tour_category' => 'Ảnh đại diện của chủ đề tour.',
                'destination' => 'Ảnh đại diện của điểm đến.',
                'region' => 'Ảnh đại diện của vùng miền.',
                default => 'Ảnh đại diện.',
            },
            $includePrimaryImage,
        );

        return $fullStageForImages
            ? self::preferFullStageForImages($slides)
            : $slides;
    }

    /**
     * @param  HasMedia  $model
     * @param  callable(string): string  $collectionResolver
     * @return array<int, array<string, mixed>>
     */
    protected static function build(
        HasMedia $model,
        ?array $items,
        callable $collectionResolver,
        ?string $fallbackFullImageUrl,
        ?string $fallbackMediumImageUrl,
        ?string $fallbackSmallImageUrl,
        ?string $fallbackAlt,
        ?string $fallbackTitle,
        string $primaryLabel,
        string $primaryDescription,
        bool $includePrimaryImage = true,
    ): array {
        $fallbackFullImageUrl = trim((string) $fallbackFullImageUrl);
        $fallbackMediumImageUrl = trim((string) ($fallbackMediumImageUrl ?: $fallbackFullImageUrl));
        $fallbackSmallImageUrl = trim((string) ($fallbackSmallImageUrl ?: $fallbackMediumImageUrl ?: $fallbackFullImageUrl));
        $fallbackAlt = trim((string) $fallbackAlt);
        $fallbackTitle = trim((string) $fallbackTitle);

        $slides = collect();

        if ($includePrimaryImage && ($fallbackMediumImageUrl !== '' || $fallbackFullImageUrl !== '')) {
            $slides->push([
                'embed_url' => null,
                'full_image_url' => $fallbackFullImageUrl !== '' ? $fallbackFullImageUrl : $fallbackMediumImageUrl,
                'image_url' => $fallbackMediumImageUrl !== '' ? $fallbackMediumImageUrl : $fallbackFullImageUrl,
                'lightbox_kind' => ContentGallery::TYPE_IMAGE,
                'media_label' => $primaryLabel,
                'resolved_alt' => $fallbackAlt !== '' ? $fallbackAlt : $fallbackTitle,
                'resolved_description' => $primaryDescription,
                'resolved_title' => $fallbackTitle,
                'small_image_url' => $fallbackSmallImageUrl,
                'thumbnail_url' => $fallbackSmallImageUrl !== '' ? $fallbackSmallImageUrl : $fallbackMediumImageUrl,
                'video_url' => null,
            ]);
        }

        $slides = $slides->merge(ContentGallery::resolveForFrontsite(
            $model,
            $items,
            $collectionResolver,
            $fallbackMediumImageUrl !== '' ? $fallbackMediumImageUrl : null,
            $fallbackAlt !== '' ? $fallbackAlt : $fallbackTitle,
            $fallbackSmallImageUrl !== '' ? $fallbackSmallImageUrl : ($fallbackMediumImageUrl !== '' ? $fallbackMediumImageUrl : null),
        ));

        $galleryThumbFallback = $slides
            ->map(fn (array $item): string => trim((string) (($item['thumbnail_url'] ?? '') ?: ($item['small_image_url'] ?? '') ?: ($item['image_url'] ?? '') ?: ($item['full_image_url'] ?? ''))))
            ->first(fn (string $url): bool => $url !== '') ?: ($fallbackSmallImageUrl !== '' ? $fallbackSmallImageUrl : $fallbackMediumImageUrl);

        return $slides
            ->map(fn (array $item): array => self::normalizeItem(
                $item,
                $fallbackMediumImageUrl,
                $fallbackSmallImageUrl,
                $galleryThumbFallback,
                $fallbackTitle,
                $fallbackAlt,
            ))
            ->filter(fn (array $item): bool => self::hasRenderableSource($item))
            ->unique(fn (array $item): string => self::uniqueKey($item))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected static function normalizeItem(
        array $item,
        string $fallbackMediumImageUrl,
        string $fallbackSmallImageUrl,
        string $galleryThumbFallback,
        string $fallbackTitle,
        string $fallbackAlt,
    ): array {
        $kind = trim((string) ($item['lightbox_kind'] ?? ContentGallery::TYPE_IMAGE));
        $thumbnailImage = trim((string) (($item['thumbnail_url'] ?? '') ?: ($item['small_image_url'] ?? '')));
        $stageSource = $kind === ContentGallery::TYPE_MP4
            ? trim((string) ($item['video_url'] ?? ''))
            : trim((string) (($item['image_url'] ?? '') ?: ($item['full_image_url'] ?? '')));
        $lightboxSource = $kind === ContentGallery::TYPE_MP4
            ? trim((string) ($item['video_url'] ?? ''))
            : trim((string) (($item['full_image_url'] ?? '') ?: ($item['image_url'] ?? '')));
        $previewImage = $thumbnailImage !== '' ? $thumbnailImage : ($fallbackSmallImageUrl !== '' ? $fallbackSmallImageUrl : $galleryThumbFallback);
        $stagePoster = $thumbnailImage !== '' ? $thumbnailImage : ($fallbackMediumImageUrl !== '' ? $fallbackMediumImageUrl : ($fallbackSmallImageUrl !== '' ? $fallbackSmallImageUrl : $galleryThumbFallback));
        $resolvedTitle = trim((string) ($item['resolved_title'] ?? '')) ?: $fallbackTitle;
        $resolvedAlt = trim((string) ($item['resolved_alt'] ?? '')) ?: ($fallbackAlt !== '' ? $fallbackAlt : $resolvedTitle);

        return [
            ...$item,
            'lightbox_src' => $lightboxSource,
            'media_icon' => self::mediaIcon($kind),
            'media_label' => trim((string) ($item['media_label'] ?? '')) !== '' ? trim((string) $item['media_label']) : self::mediaLabel($kind),
            'resolved_alt' => $resolvedAlt,
            'resolved_title' => $resolvedTitle,
            'stage_poster' => $stagePoster,
            'stage_src' => $stageSource,
            'thumbnail_image' => $previewImage,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected static function hasRenderableSource(array $item): bool
    {
        $kind = trim((string) ($item['lightbox_kind'] ?? ContentGallery::TYPE_IMAGE));

        return match ($kind) {
            ContentGallery::TYPE_YOUTUBE => trim((string) ($item['embed_url'] ?? '')) !== '',
            ContentGallery::TYPE_MP4 => trim((string) ($item['lightbox_src'] ?? '')) !== '',
            default => trim((string) (($item['stage_src'] ?? '') ?: ($item['lightbox_src'] ?? ''))) !== '',
        };
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected static function uniqueKey(array $item): string
    {
        $kind = trim((string) ($item['lightbox_kind'] ?? ContentGallery::TYPE_IMAGE));
        $source = $kind === ContentGallery::TYPE_YOUTUBE
            ? trim((string) ($item['embed_url'] ?? ''))
            : trim((string) (($item['lightbox_src'] ?? '') ?: ($item['stage_src'] ?? '')));

        return $kind.'|'.$source;
    }

    protected static function mediaIcon(string $kind): string
    {
        return match ($kind) {
            ContentGallery::TYPE_YOUTUBE => 'fa-brands fa-youtube',
            ContentGallery::TYPE_MP4 => 'fa-solid fa-video',
            default => 'fa-regular fa-image',
        };
    }

    protected static function mediaLabel(string $kind): string
    {
        return match ($kind) {
            ContentGallery::TYPE_YOUTUBE => 'YouTube',
            ContentGallery::TYPE_MP4 => 'Video MP4',
            default => 'Ảnh',
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $slides
     * @return array<int, array<string, mixed>>
     */
    protected static function preferFullStageForImages(array $slides): array
    {
        return collect($slides)
            ->map(function (array $item): array {
                if (($item['lightbox_kind'] ?? ContentGallery::TYPE_IMAGE) !== ContentGallery::TYPE_IMAGE) {
                    return $item;
                }

                $fullStageSource = trim((string) (($item['lightbox_src'] ?? '') ?: ($item['stage_src'] ?? '')));

                if ($fullStageSource === '') {
                    return $item;
                }

                return [
                    ...$item,
                    'stage_src' => $fullStageSource,
                ];
            })
            ->values()
            ->all();
    }
}
