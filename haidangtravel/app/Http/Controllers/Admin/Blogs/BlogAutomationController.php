<?php

namespace App\Http\Controllers\Admin\Blogs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Blogs\StoreBlogAutomationJobRequest;
use App\Jobs\Cms\RunBlogAutomationJob;
use Illuminate\Http\JsonResponse;

class BlogAutomationController extends Controller
{
    public function __invoke(StoreBlogAutomationJobRequest $request): JsonResponse
    {
        RunBlogAutomationJob::dispatch(
            payload: $request->validated(),
            actorId: $request->user()?->getKey(),
        )->onQueue(config('blog_automation.queue', 'seo'));

        return response()->json([
            'message' => 'Đã đưa yêu cầu crawl và biên tập blog vào hàng đợi.',
            'meta' => [
                'queued' => true,
                'reference_count' => count($request->validated('reference_urls', [])),
                'queue' => config('blog_automation.queue', 'seo'),
            ],
        ], 202);
    }
}
