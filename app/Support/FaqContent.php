<?php

namespace App\Support;

class FaqContent
{
    public static function blankItem(): array
    {
        return [
            'question' => '',
            'answer' => '',
        ];
    }

    public static function normalizeItems(?array $items, array $fallback = []): array
    {
        $normalized = collect(is_array($items) ? array_values($items) : [])
            ->filter(fn ($item) => is_array($item))
            ->map(fn (array $item) => [
                'question' => self::questionValue(data_get($item, 'question')),
                'answer' => self::answerValue(data_get($item, 'answer')),
            ])
            ->filter(fn (array $item) => filled($item['question']) && filled($item['answer']))
            ->values()
            ->all();

        if ($normalized !== [] || $fallback === []) {
            return $normalized;
        }

        return self::normalizeItems($fallback);
    }

    public static function normalizeQuestions(?array $questions, array $fallback = []): array
    {
        $normalized = collect(is_array($questions) ? array_values($questions) : [])
            ->map(fn ($question) => self::questionValue($question))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($normalized !== [] || $fallback === []) {
            return $normalized;
        }

        return self::normalizeQuestions($fallback);
    }

    public static function prepareItems(?array $items, array $fallback = []): array
    {
        return self::normalizeItems($items, $fallback);
    }

    public static function prepareQuestions(?array $questions, array $fallback = []): array
    {
        return self::normalizeQuestions($questions, $fallback);
    }

    protected static function answerValue(mixed $value): string
    {
        return RichText::sanitize((string) $value);
    }

    protected static function questionValue(mixed $value): string
    {
        return RichText::normalizePlain((string) $value);
    }
}
