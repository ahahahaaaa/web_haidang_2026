<?php

namespace App\Support;

use Illuminate\Support\Str;

class GeoContent
{
    public const MAX_SUMMARY_LENGTH = 600;

    public const MAX_DECISION_NOTES = 5;

    public const MAX_DECISION_NOTE_LENGTH = 180;

    public static function defaultConfig(): array
    {
        return [
            'is_enabled' => true,
            'answer_summary' => '',
            'decision_notes' => [],
            'updated_label' => '',
        ];
    }

    public static function form(?array $config = null): array
    {
        return static::normalize($config);
    }

    public static function normalize(mixed $config): array
    {
        $config = is_array($config) ? $config : [];

        return [
            'is_enabled' => (bool) ($config['is_enabled'] ?? true),
            'answer_summary' => static::plainText($config['answer_summary'] ?? '', static::MAX_SUMMARY_LENGTH),
            'decision_notes' => static::normalizeDecisionNotes($config['decision_notes'] ?? []),
            'updated_label' => static::plainText($config['updated_label'] ?? '', 120),
        ];
    }

    public static function normalizeDecisionNotes(mixed $notes): array
    {
        return collect(is_array($notes) ? $notes : [])
            ->map(function (mixed $note): string {
                if (is_array($note)) {
                    $note = $note['text'] ?? $note['value'] ?? '';
                }

                return static::plainText($note, static::MAX_DECISION_NOTE_LENGTH);
            })
            ->filter()
            ->take(static::MAX_DECISION_NOTES)
            ->values()
            ->all();
    }

    public static function plainText(mixed $value, int $limit): string
    {
        $text = RichText::normalizePlain((string) $value);
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = trim($text);

        return $text === '' ? '' : Str::limit($text, $limit, '');
    }
}
