<?php

namespace App\Http\Resources\Agency;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourDepartureExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sellingPrice = $this->sellingPrice();

        return [
            'departure_id' => (int) $this->id,
            'tour_id' => (int) $this->tour_id,
            'departure_date' => optional($this->departure_date)?->toDateString(),
            'return_date' => optional($this->return_date)?->toDateString(),
            'departure_location' => $this->departure_location,
            'transport_label' => $this->transport_label,
            'standard_label' => $this->standard_label,
            'adult_price' => $sellingPrice,
            'price' => $sellingPrice,
            'base_price' => $sellingPrice,
            'sale_price' => $sellingPrice,
            'available_slots' => $this->available_slots,
            'pricing_note' => $this->pricing_note,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'sort_order' => (int) $this->sort_order,
            'created_at' => optional($this->created_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }

    protected function sellingPrice(): ?int
    {
        return $this->sale_price !== null
            ? (int) $this->sale_price
            : ($this->base_price !== null ? (int) $this->base_price : null);
    }
}
