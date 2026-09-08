<?php

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use Src\Domains\Seo\Actions\PublishSeoPageAction;
use Src\Domains\Seo\Models\SeoPage;

class SeoPublishController extends Controller
{
    public function __invoke(SeoPage $page, PublishSeoPageAction $publishSeoPage)
    {
        $this->authorize('publish', $page);
        $publishSeoPage->execute($page);
        return response()->json(['message' => 'Publish dispatched.']);
    }
}
