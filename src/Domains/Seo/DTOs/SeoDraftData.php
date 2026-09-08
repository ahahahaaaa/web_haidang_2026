<?php

namespace Src\Domains\Seo\DTOs;

class SeoDraftData
{
    public function __construct(
        public readonly string $content,
        public readonly array $meta,
        public readonly array $schema,
        public readonly array $rawResponse,
    ) {}
}
