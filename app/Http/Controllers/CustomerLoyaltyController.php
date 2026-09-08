<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerLoyaltyLookupRequest;
use App\Http\Requests\RedeemCustomerGiftRequest;
use App\Services\Travel\CustomerLoyaltyApi;
use App\Services\Travel\CustomerLoyaltyApiException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Src\Domains\Cms\Models\Tour;

class CustomerLoyaltyController extends Controller
{
    public function index(CustomerLoyaltyLookupRequest $request, CustomerLoyaltyApi $loyaltyApi): View
    {
        $lookup = null;
        $lookupError = null;
        $ordersPaginator = null;
        $showHistorySections = (bool) config('customer_loyalty.history_enabled', false);
        $giftCatalog = [
            'gifts' => [],
            'sections' => ['gifts' => false],
            'error' => null,
        ];

        if ($request->hasLookup()) {
            try {
                $lookup = $loyaltyApi->lookup($request->phone());
                $this->storeLookupToken($request->phone(), $lookup['lookup_token'] ?? null);
                $ordersPaginator = $showHistorySections
                    ? $this->paginateLookupItems($lookup['orders'] ?? [], $request, 'orders_page', 10)
                    : null;
                $giftCatalog = [
                    'gifts' => $lookup['gifts'] ?? [],
                    'sections' => [
                        'gifts' => (bool) ($lookup['sections']['gifts'] ?? false),
                    ],
                    'error' => null,
                ];
            } catch (CustomerLoyaltyApiException $exception) {
                $lookupError = $exception->getMessage();
            }
        }

        if (! $lookup) {
            $giftCatalog = $this->safeGiftCatalog($loyaltyApi);
        }

        $seoDescription = $showHistorySections
            ? 'Tra cứu điểm thưởng, đơn hàng, lịch sử đổi quà và gửi yêu cầu đổi quà dành cho khách hàng Hải Đăng Travel.'
            : 'Tra cứu điểm thưởng và gửi yêu cầu đổi quà dành cho khách hàng Hải Đăng Travel.';

        return view('themes.haidangtravel.pages.customer-loyalty', [
            'apiConfigured' => $loyaltyApi->configured(),
            'lookup' => $lookup,
            'lookupError' => $lookupError,
            'ordersPaginator' => $ordersPaginator,
            'phone' => $request->phone(),
            'showHistorySections' => $showHistorySections,
            'giftCatalog' => $giftCatalog,
            'featuredTours' => $this->featuredTours(),
            'seo' => [
                'title' => 'Kiểm tra điểm và đổi quà | Hải Đăng Travel',
                'description' => $seoDescription,
                'canonical' => route('customer-loyalty.index'),
                'robots' => 'noindex,follow',
            ],
        ]);
    }

    public function redeem(RedeemCustomerGiftRequest $request, CustomerLoyaltyApi $loyaltyApi): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['lookup_token'] = $validated['lookup_token'] ?? $this->lookupToken($validated['phone']);

        try {
            $result = $loyaltyApi->requestRedemption($validated);
        } catch (CustomerLoyaltyApiException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => [
                        'redemption' => [$exception->getMessage()],
                    ],
                ], 422);
            }

            return back()
                ->withErrors(['redemption' => $exception->getMessage()])
                ->withInput();
        }

        $message = $result['message'];

        if (filled($result['reference'])) {
            $message .= ' Mã tham chiếu: '.$result['reference'].'.';
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'refresh_url' => route('customer-loyalty.index', ['phone' => $validated['phone']]),
            ]);
        }

        return redirect()
            ->route('customer-loyalty.index', ['phone' => $validated['phone']])
            ->with('customer_loyalty_status', $message);
    }

    protected function storeLookupToken(string $phone, mixed $token): void
    {
        if (blank($token)) {
            return;
        }

        session()->put($this->lookupTokenSessionKey($phone), (string) $token);
    }

    protected function lookupToken(string $phone): ?string
    {
        $token = session($this->lookupTokenSessionKey($phone));

        return filled($token) ? (string) $token : null;
    }

    protected function lookupTokenSessionKey(string $phone): string
    {
        return 'customer_loyalty.lookup_tokens.'.hash('sha256', $phone);
    }

    /**
     * @param  array<int, mixed>  $items
     */
    protected function paginateLookupItems(array $items, CustomerLoyaltyLookupRequest $request, string $pageName, int $perPage): LengthAwarePaginator
    {
        $collection = Collection::make($items)->values();
        $lastPage = max(1, (int) ceil($collection->count() / $perPage));
        $currentPage = min(max(1, (int) $request->query($pageName, 1)), $lastPage);

        return (new LengthAwarePaginator(
            $collection->forPage($currentPage, $perPage)->values()->all(),
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
            ],
        ))
            ->appends($request->except($pageName))
            ->fragment('customer-loyalty-orders');
    }

    /**
     * @return array{gifts: array<int, mixed>, sections: array{gifts: bool}, error: string|null}
     */
    protected function safeGiftCatalog(CustomerLoyaltyApi $loyaltyApi): array
    {
        if (! $loyaltyApi->configured()) {
            return [
                'gifts' => [],
                'sections' => ['gifts' => false],
                'error' => null,
            ];
        }

        try {
            $catalog = $loyaltyApi->giftCatalog();

            return [
                'gifts' => $catalog['gifts'] ?? [],
                'sections' => [
                    'gifts' => (bool) ($catalog['sections']['gifts'] ?? false),
                ],
                'error' => null,
            ];
        } catch (CustomerLoyaltyApiException $exception) {
            return [
                'gifts' => [],
                'sections' => ['gifts' => false],
                'error' => $exception->getMessage(),
            ];
        }
    }

    protected function featuredTours(int $limit = 8): Collection
    {
        $baseQuery = Tour::query()
            ->published()
            ->with([
                'media',
                'departures' => fn ($departureQuery) => $departureQuery->published()->orderBy('departure_date'),
                'destination.media',
                'primaryCategory.media',
                'region.media',
            ])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest('updated_at')
            ->limit($limit);

        $featuredTours = (clone $baseQuery)
            ->where('is_featured', true)
            ->get();

        if ($featuredTours->isNotEmpty()) {
            return $featuredTours;
        }

        return $baseQuery->get();
    }
}
