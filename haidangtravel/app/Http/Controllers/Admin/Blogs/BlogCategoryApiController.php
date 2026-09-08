<?php

namespace App\Http\Controllers\Admin\Blogs;

use App\Http\Controllers\Controller;
use App\Services\Cms\BlogPostManager;
use App\Support\ContentCategoryTree;
use Illuminate\Http\JsonResponse;

class BlogCategoryApiController extends Controller
{
    public function __invoke(BlogPostManager $manager): JsonResponse
    {
        return response()->json([
            'data' => $manager->categories()->map(fn ($category) => [
                'id' => $category->getKey(),
                'name' => $category->name,
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'path' => ContentCategoryTree::pathLabel($category),
                'depth' => (int) ($category->tree_depth ?? 0),
                'description' => $category->description,
                'sort_order' => $category->sort_order,
                'parent' => $category->parent ? [
                    'id' => $category->parent->getKey(),
                    'name' => $category->parent->name,
                    'slug' => $category->parent->slug,
                ] : null,
            ])->values(),
        ]);
    }
}
