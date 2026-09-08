<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Http\Resources\Agency\TourExportResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Src\Domains\Cms\Models\Tour;

class TourExportController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $maxPerPage = max(1, (int) config('agency_export.max_per_page', 500));
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.$maxPerPage],
            'tour_id' => ['nullable', 'integer', 'min:1'],
            'updated_since' => ['nullable', 'date'],
        ]);
        $perPage = min(
            max(1, (int) ($validated['per_page'] ?? config('agency_export.per_page', 100))),
            $maxPerPage,
        );

        $query = Tour::query()
            ->with([
                'manager.roles',
                'departures' => fn ($departureQuery) => $departureQuery
                    ->orderBy('departure_date')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('id');

        if (filled($validated['tour_id'] ?? null)) {
            $query->whereKey((int) $validated['tour_id']);
        }

        if (filled($validated['updated_since'] ?? null)) {
            $updatedSince = Carbon::parse((string) $validated['updated_since']);

            $query->where(function (Builder $changedQuery) use ($updatedSince): void {
                $changedQuery
                    ->where('updated_at', '>=', $updatedSince)
                    ->orWhereHas('departures', fn (Builder $departureQuery) => $departureQuery
                        ->where('updated_at', '>=', $updatedSince));
            });
        }

        $tours = $query->paginate($perPage);

        return response()->json([
            'data' => $tours->getCollection()
                ->map(fn (Tour $tour): array => (new TourExportResource($tour))->toArray($request))
                ->values(),
            'meta' => [
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
                'per_page' => $tours->perPage(),
                'total' => $tours->total(),
            ],
        ]);
    }
}
