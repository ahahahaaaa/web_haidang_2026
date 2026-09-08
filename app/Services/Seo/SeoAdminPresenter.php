<?php

namespace App\Services\Seo;

use Src\Domains\Seo\Models\SeoPage;

class SeoAdminPresenter
{
    public function statusBadgeClass(SeoPage $page): string
    {
        return match ($page->status->value) {
            'published' => 'bg-green-100 text-green-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'qa_failed' => 'bg-red-100 text-red-800',
            'pending_review' => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-700',
        };
    }
}
