<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTourReviewRequest;
use App\Http\Requests\UnlockTourReviewRequest;
use App\Support\FrontsiteMedia;
use App\Support\ReviewContent;
use App\Support\RichText;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Src\Domains\Cms\Models\Tour;
use Src\Domains\Cms\Models\TourReviewBatch;

class TourReviewSubmissionController extends Controller
{
    public function show(Request $request, Tour $tour, string $token): View
    {
        $reviewBatch = $this->authorizeReviewLink($tour, $token);

        $tour->loadMissing(['primaryCategory', 'destination', 'region']);
        $tour->load([
            'publishedReviews' => fn ($query) => $query->limit(6),
        ]);

        $reviewItems = ReviewContent::fromModels($tour->publishedReviews);
        $reviewSummary = ReviewContent::aggregate(
            $reviewItems,
            filled($tour->rating_average) ? (float) $tour->rating_average : null,
            filled($tour->rating_count) ? (int) $tour->rating_count : null,
        );
        $reviewUrl = route('tour-reviews.public.show', ['tour' => $tour, 'token' => $token]);

        return view('themes.haidangtravel.pages.tours.review-submit', [
            'reviewBatch' => $reviewBatch,
            'reviewItems' => $reviewItems,
            'reviewSummary' => $reviewSummary,
            'reviewUnlocked' => ! $reviewBatch->requiresPassword() || $this->hasReviewAccess($request, $reviewBatch),
            'reviewUrl' => $reviewUrl,
            'seo' => [
                'canonical' => $reviewUrl,
                'description' => 'Gửi đánh giá sau chuyến đi cho tour '.$tour->title.'.',
                'og_image' => FrontsiteMedia::modelUrl($tour, 'cover', FrontsiteMedia::SIZE_SMALL, 'cover_image_url'),
                'robots' => 'noindex,nofollow',
                'title' => 'Đánh giá tour '.$tour->title,
            ],
            'token' => $token,
            'tour' => $tour,
        ]);
    }

    public function unlock(UnlockTourReviewRequest $request, Tour $tour, string $token): RedirectResponse|JsonResponse
    {
        $reviewBatch = $this->authorizeReviewLink($tour, $token);

        $password = (string) $request->validated('review_password');

        if ($reviewBatch->requiresPassword() && ! hash_equals((string) $reviewBatch->password, $password)) {
            throw ValidationException::withMessages([
                'review_password' => 'Mật khẩu đánh giá chưa đúng.',
            ]);
        }

        $request->session()->put($this->sessionKey($reviewBatch), true);

        $message = 'Mật khẩu hợp lệ. Bạn có thể gửi đánh giá cho tour này.';
        $redirectUrl = route('tour-reviews.public.show', ['tour' => $tour, 'token' => $token]).'#tour-review-form';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => $redirectUrl,
            ]);
        }

        return redirect($redirectUrl)->with('tour_review_status', $message);
    }

    public function store(StoreTourReviewRequest $request, Tour $tour, string $token): RedirectResponse|JsonResponse
    {
        $reviewBatch = $this->authorizeReviewLink($tour, $token);

        if ($reviewBatch->requiresPassword() && ! $this->hasReviewAccess($request, $reviewBatch)) {
            $message = 'Phiên nhập mật khẩu đã hết hạn. Vui lòng nhập lại mật khẩu đánh giá.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()
                ->route('tour-reviews.public.show', ['tour' => $tour, 'token' => $token])
                ->withErrors(['review_password' => $message]);
        }

        $this->createDraftReview($request, $tour, $reviewBatch, 'public_qr', route('tour-reviews.public.show', ['tour' => $tour, 'token' => $token]));

        $message = 'Cảm ơn bạn đã gửi đánh giá. Nội dung sẽ hiển thị sau khi đội ngũ Hải Đăng Travel kiểm duyệt.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        return back()->with('tour_review_status', $message);
    }

    public function storeFromTourDetail(StoreTourReviewRequest $request, Tour $tour): RedirectResponse|JsonResponse
    {
        abort_unless(config('travel_reviews.enabled', true), 404);
        abort_unless(Tour::query()->published()->whereKey($tour->getKey())->exists(), 404);

        $validated = $request->validated();
        $reviewBatch = null;

        if (filled($validated['tour_review_batch_id'] ?? null)) {
            $reviewBatch = $tour->reviewBatches()
                ->enabled()
                ->whereKey((int) $validated['tour_review_batch_id'])
                ->first();

            if (! $reviewBatch) {
                throw ValidationException::withMessages([
                    'tour_review_batch_id' => 'Lượt đánh giá không hợp lệ.',
                ]);
            }
        }

        $this->createDraftReview($request, $tour, $reviewBatch, 'public_web', route('tours.show', $tour).'#tour-review-form');

        $message = 'Cảm ơn bạn đã gửi đánh giá. Nội dung sẽ hiển thị sau khi đội ngũ Hải Đăng Travel kiểm duyệt.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        return back()->with('tour_review_status', $message);
    }

    protected function createDraftReview(
        StoreTourReviewRequest $request,
        Tour $tour,
        ?TourReviewBatch $reviewBatch,
        string $source,
        string $reviewUrl,
    ): void {
        $validated = $request->validated();

        $tour->reviews()->create([
            'author_email' => $validated['author_email'] ?? null,
            'author_name' => Str::limit(RichText::normalizePlain($validated['author_name']), 255, ''),
            'author_phone' => $validated['author_phone'] ?? null,
            'author_title' => Str::limit(RichText::normalizePlain($validated['author_title'] ?? ''), 255, ''),
            'content' => RichText::normalizePlain($validated['content']),
            'metadata' => [
                'departure_date' => $reviewBatch?->departure_date?->toDateString(),
                'ip' => $request->ip(),
                'review_batch_label' => $reviewBatch?->label,
                'review_url' => $reviewUrl,
                'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            ],
            'published_at' => null,
            'rating_value' => $validated['rating_value'],
            'sort_order' => 0,
            'source' => $source,
            'status' => 'draft',
            'submitted_at' => now(),
            'title' => Str::limit(RichText::normalizePlain($validated['title'] ?? ''), 255, ''),
            'tour_review_batch_id' => $reviewBatch?->getKey(),
        ]);
    }

    protected function authorizeReviewLink(Tour $tour, string $token): TourReviewBatch
    {
        abort_unless(config('travel_reviews.enabled', true), 404);

        $reviewBatch = $tour->reviewBatches()
            ->with('departure')
            ->enabled()
            ->where('token', $token)
            ->first();

        abort_unless($reviewBatch, 404);
        abort_unless(
            Tour::query()->published()->whereKey($tour->getKey())->exists(),
            404,
        );

        return $reviewBatch;
    }

    protected function hasReviewAccess(Request $request, TourReviewBatch $reviewBatch): bool
    {
        return (bool) $request->session()->get($this->sessionKey($reviewBatch), false);
    }

    protected function sessionKey(TourReviewBatch $reviewBatch): string
    {
        return 'tour_review_access_'.$reviewBatch->getKey().'_'.sha1((string) $reviewBatch->token);
    }
}
