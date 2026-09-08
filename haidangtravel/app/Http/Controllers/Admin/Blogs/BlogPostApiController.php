<?php

namespace App\Http\Controllers\Admin\Blogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blogs\StoreBlogPostRequest;
use App\Http\Requests\Admin\Blogs\UpdateBlogPostRequest;
use App\Http\Resources\Admin\Blogs\BlogPostResource;
use App\Services\Cms\BlogPostManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Domains\Cms\Models\BlogPost;

class BlogPostApiController extends Controller
{
    public function index(Request $request, BlogPostManager $manager): JsonResponse
    {
        $posts = $manager->paginateForAdmin([
            'q' => $request->string('q')->trim()->toString(),
            'status' => $request->string('status')->trim()->toString(),
            'category_slug' => $request->string('category_slug')->trim()->toString(),
            'per_page' => $request->integer('per_page') ?: 15,
        ]);

        return response()->json([
            'data' => $posts->getCollection()
                ->map(fn (BlogPost $post) => (new BlogPostResource($post))->toArray($request))
                ->values(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function show(Request $request, BlogPost $post): JsonResponse
    {
        $post->load('category.parent');

        return response()->json([
            'data' => (new BlogPostResource($post))->toArray($request),
        ]);
    }

    public function store(StoreBlogPostRequest $request, BlogPostManager $manager): JsonResponse
    {
        $post = $manager->save(
            payload: $request->validated(),
            actor: $request->user(),
        );

        return response()->json([
            'data' => (new BlogPostResource($post))->toArray($request),
            'message' => 'Đã tạo bài viết blog.',
        ], 201);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $post, BlogPostManager $manager): JsonResponse
    {
        $post = $manager->save(
            payload: $request->validated(),
            post: $post,
            actor: $request->user(),
        );

        return response()->json([
            'data' => (new BlogPostResource($post))->toArray($request),
            'message' => 'Đã cập nhật bài viết blog.',
        ]);
    }

    public function destroy(BlogPost $post, BlogPostManager $manager): JsonResponse
    {
        $manager->delete($post);

        return response()->json([
            'message' => 'Đã xóa bài viết blog.',
        ]);
    }
}
