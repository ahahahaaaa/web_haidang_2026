<?php

namespace Src\Domains\Seo\Support;

use Illuminate\Support\Str;

class SeoSlugGenerator
{
    public function generate(string $input): string
    {
        return mb_substr(
            Str::of($input)->ascii()->lower()
                ->replaceMatches('/[^a-z0-9\s-]/', '')
                ->replaceMatches('/\s+/', '-')
                ->replaceMatches('/-+/', '-')
                ->trim('-')
                ->value(),
            0, 80
        );
    }
}
