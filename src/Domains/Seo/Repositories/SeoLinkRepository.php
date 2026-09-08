<?php

namespace Src\Domains\Seo\Repositories;

use Src\Domains\Seo\Models\SeoLink;
use Src\Domains\Seo\Models\SeoPage;

class SeoLinkRepository
{
    public function suggest(SeoPage $source, SeoPage $target, string $anchor): SeoLink
    {
        return SeoLink::query()->firstOrCreate([
            'source_page_id' => $source->getKey(),
            'target_page_id' => $target->getKey(),
            'anchor_text' => $anchor,
        ], [
            'link_type' => 'related',
            'priority' => 50,
            'status' => 'suggested',
        ]);
    }
}
