<?php

namespace App\Http\Controllers\Admin\Seo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Src\Domains\Seo\Models\SeoPage;
use Src\Domains\Seo\Support\SeoPromptFactory;

class SeoAiController extends Controller
{
    public function previewPrompt(Request $request, SeoPage $page, SeoPromptFactory $promptFactory)
    {
        $this->authorize('update', $page);
        return response()->json([
            'type' => 'draft',
            'prompt' => $promptFactory->draft($page),
        ]);
    }
}
