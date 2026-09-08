<?php

namespace Src\Domains\Seo\Support;

use RuntimeException;
use Src\Domains\Seo\Enums\SeoPageStatus;
use Src\Domains\Seo\Models\SeoPage;

class SeoPublisher
{
    public function publish(SeoPage $page): SeoPage
    {
        if (($page->qa_report['status'] ?? null) !== 'pass') {
            throw new RuntimeException('SEO QA must pass before publish.');
        }

        if (!in_array($page->status, [SeoPageStatus::Approved, SeoPageStatus::PendingReview], true)) {
            throw new RuntimeException('Page must be approved or pending review before publish.');
        }

        $page->status = SeoPageStatus::Published;
        $page->published_at = now();
        $page->save();

        return $page;
    }
}
