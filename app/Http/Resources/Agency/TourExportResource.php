<?php

namespace App\Http\Resources\Agency;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Src\Domains\Cms\Enums\TourScope;
use Src\Domains\Cms\Models\TourDeparture;

class TourExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sellingPrice = $this->sellingPrice();

        return [
            'tour_id' => (int) $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'scope' => $this->scope instanceof TourScope ? $this->scope->value : $this->scope,
            'status' => $this->status,
            'transport' => $this->transport,
            'standard_label' => $this->standard_label,
            'duration_days' => $this->duration_days,
            'duration_nights' => $this->duration_nights,
            'price' => $sellingPrice,
            'base_price' => $sellingPrice,
            'sale_price' => $sellingPrice,
            'manager_email' => $this->manager?->email,
            'manager' => $this->managerPayload($this->manager),
            'published_at' => optional($this->published_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
            'departures' => $this->whenLoaded('departures', function () use ($request): array {
                return $this->departures
                    ->map(fn (TourDeparture $departure): array => (new TourDepartureExportResource($departure))->toArray($request))
                    ->values()
                    ->all();
            }, []),
        ];
    }

    protected function managerPayload(?User $manager): ?array
    {
        if (! $manager) {
            return null;
        }

        return [
            'cms_user_id' => (int) $manager->getKey(),
            'name' => $manager->name,
            'email' => $manager->email,
            'phone' => $manager->phone,
            'is_active' => (bool) $manager->is_active,
            'roles' => method_exists($manager, 'getRoleNames') ? $manager->getRoleNames()->values()->all() : [],
        ];
    }

    protected function sellingPrice(): ?int
    {
        return $this->sale_price !== null
            ? (int) $this->sale_price
            : ($this->base_price !== null ? (int) $this->base_price : null);
    }
}
