<?php

namespace App\Support;

use Illuminate\Support\Collection;

class ReviewContent
{
    public static function aggregate(?array $items, ?float $fallbackAverage = null, ?int $fallbackCount = null): ?array
    {
        $prepared = self::prepareItems($items);

        if ($fallbackAverage !== null) {
            return [
                'average_value' => round($fallbackAverage, 1),
                'best_rating' => 5,
                'count' => $fallbackCount ?? count($prepared),
                'worst_rating' => 1,
            ];
        }

        if ($prepared !== []) {
            $count = count($prepared);
            $average = round(collect($prepared)->avg('rating_value') ?: 0, 1);

            return [
                'average_value' => $average,
                'best_rating' => 5,
                'count' => $count,
                'worst_rating' => 1,
            ];
        }

        return null;
    }

    public static function aggregateRatingSchema(?array $summary): ?array
    {
        $count = data_get($summary, 'count');
        $average = data_get($summary, 'average_value');

        if (! is_numeric($count) || (int) $count < 1 || ! is_numeric($average)) {
            return null;
        }

        return [
            '@type' => 'AggregateRating',
            'bestRating' => (string) data_get($summary, 'best_rating', 5),
            'ratingCount' => (int) $count,
            'ratingValue' => number_format((float) $average, 1, '.', ''),
            'reviewCount' => (int) $count,
            'worstRating' => (string) data_get($summary, 'worst_rating', 1),
        ];
    }

    public static function blankItem(): array
    {
        return [
            'author_name' => '',
            'author_title' => '',
            'content' => '',
            'published_at' => '',
            'rating_value' => 5,
            'title' => '',
        ];
    }

    public static function editorItems(?array $items): array
    {
        $prepared = self::prepareItems($items);

        return $prepared !== [] ? $prepared : [self::blankItem()];
    }

    public static function fromModels(Collection|iterable $items): array
    {
        return collect($items)
            ->map(function (mixed $item): ?array {
                if (! is_object($item)) {
                    return null;
                }

                return [
                    'author_name' => self::plainValue(data_get($item, 'author_name')),
                    'author_title' => self::plainValue(data_get($item, 'author_title')),
                    'content' => self::plainValue(data_get($item, 'content')),
                    'published_at' => self::dateValue(data_get($item, 'published_at')),
                    'rating_value' => self::ratingValue(data_get($item, 'rating_value')),
                    'title' => self::plainValue(data_get($item, 'title')),
                ];
            })
            ->filter(fn (?array $item) => is_array($item) && $item['author_name'] !== '' && $item['content'] !== '' && $item['rating_value'] !== null)
            ->values()
            ->all();
    }

    public static function normalizeItems(?array $items, array $fallback = []): array
    {
        $normalized = collect(is_array($items) ? array_values($items) : [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item): ?array {
                $authorName = self::plainValue(data_get($item, 'author_name'));
                $content = self::plainValue(data_get($item, 'content'));
                $ratingValue = self::ratingValue(data_get($item, 'rating_value'));

                if ($authorName === '' || $content === '' || $ratingValue === null) {
                    return null;
                }

                return [
                    'author_name' => $authorName,
                    'author_title' => self::plainValue(data_get($item, 'author_title')),
                    'content' => $content,
                    'published_at' => self::dateValue(data_get($item, 'published_at')),
                    'rating_value' => $ratingValue,
                    'title' => self::plainValue(data_get($item, 'title')),
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($normalized !== [] || $fallback === []) {
            return $normalized;
        }

        return self::normalizeItems($fallback);
    }

    public static function prepareItems(?array $items, array $fallback = []): array
    {
        return self::normalizeItems($items, $fallback);
    }

    public static function reviewSchema(?array $items): ?array
    {
        $reviews = collect(self::prepareItems($items))
            ->map(function (array $item): array {
                $review = [
                    '@type' => 'Review',
                    'author' => [
                        '@type' => 'Person',
                        'name' => $item['author_name'],
                    ],
                    'reviewBody' => $item['content'],
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'bestRating' => '5',
                        'ratingValue' => number_format((float) $item['rating_value'], 1, '.', ''),
                        'worstRating' => '1',
                    ],
                ];

                if ($item['title'] !== '') {
                    $review['name'] = $item['title'];
                }

                if ($item['published_at'] !== '') {
                    $review['datePublished'] = $item['published_at'];
                }

                return $review;
            })
            ->values()
            ->all();

        return $reviews !== [] ? $reviews : null;
    }

    protected static function dateValue(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }

    protected static function plainValue(mixed $value): string
    {
        $value = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', (string) $value) ?? (string) $value;
        $value = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $value) ?? $value;

        return RichText::normalizePlain($value);
    }

    protected static function ratingValue(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $rating = round((float) $value, 1);

        if ($rating < 1 || $rating > 5) {
            return null;
        }

        return $rating;
    }
}
