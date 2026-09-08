<?php

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use Src\Domains\Seo\Jobs\GenerateSeoDraftJob;
use Src\Domains\Seo\Jobs\ValidateSeoPageJob;
use Src\Domains\Seo\Models\SeoPage;

class SeoGenerationController extends Controller
{
    public function regenerate(SeoPage $page)
    {
        $this->authorize('update', $page);
        GenerateSeoDraftJob::dispatch($page->getKey())->onQueue(config('seo_ai.queue', 'seo'));
        return response()->json(['message' => 'Generation dispatched.']);
    }

    public function qa(SeoPage $page)
    {
        $this->authorize('update', $page);
        ValidateSeoPageJob::dispatch($page->getKey())->onQueue(config('seo_ai.queue', 'seo'));
        return response()->json(['message' => 'QA dispatched.']);
    }
}
