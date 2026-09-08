<?php

namespace Src\Domains\Seo\DTOs;

class SeoBriefData
{
    public function __construct(
        public readonly string $title,
        public readonly string $slugSuggestion,
        public readonly string $h1,
        public readonly array $faq,
        public readonly array $outline,
        public readonly array $trustSignals,
        public readonly string $cta,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            title: (string) ($data['title'] ?? ''),
            slugSuggestion: (string) ($data['slug_suggestion'] ?? ''),
            h1: (string) ($data['h1'] ?? ''),
            faq: (array) ($data['faq'] ?? []),
            outline: (array) ($data['outline'] ?? []),
            trustSignals: (array) ($data['trust_signals'] ?? []),
            cta: (string) ($data['cta'] ?? ''),
        );
    }
}
