<?php

namespace App\Http\Resources\Shipping;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'countries' => $this->countries ?? [],
            'country_count' => $this->getCountryCount(),
            'regions' => $this->regions ?? [],
            'postal_code_patterns' => $this->postal_code_patterns ?? [],
            'is_active' => $this->is_active,
            'methods' => ShippingMethodResource::collection($this->whenLoaded('methods')),
            'methods_with_pricing' => $this->when(
                $this->relationLoaded('methods'),
                function () {
                    return $this->methods->map(function ($method) {
                        return [
                            'id' => $method->id,
                            'name' => $method->name,
                            'base_price' => (float) $method->price,
                            'price_override' => $method->pivot->price_override ? (float) $method->pivot->price_override : null,
                            'effective_price' => $method->getPriceForZone($this->resource),
                            'is_active' => $method->is_active,
                        ];
                    });
                }
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
