<?php

namespace App\Http\Resources\Shipping;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodResource extends JsonResource
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
            'code' => $this->code,
            'description' => $this->description,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'min_order_amount' => $this->min_order_amount ? (float) $this->min_order_amount : null,
            'max_order_amount' => $this->max_order_amount ? (float) $this->max_order_amount : null,
            'estimated_delivery_days' => $this->estimated_delivery_days,
            'min_delivery_days' => $this->min_delivery_days,
            'max_delivery_days' => $this->max_delivery_days,
            'delivery_estimate' => $this->getDeliveryEstimate(),
            'formatted_price' => $this->getFormattedPrice(),
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'zones' => ShippingZoneResource::collection($this->whenLoaded('zones')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
