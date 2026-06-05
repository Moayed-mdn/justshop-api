<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'sku'              => $this->sku,
            'price'            => (float) $this->price,
            'compare_at_price' => $this->compare_at_price
                ? (float) $this->compare_at_price
                : null,
            'stock'            => $this->quantity,
            'is_active'        => (bool) $this->is_active,
            'weight'           => $this->weight,
            'weight_unit'      => $this->weight_unit,
            'manufacture_date' => $this->manufacture_date,
            'expiry_date'      => $this->expiry_date,

            // New system: semantic option values
            'options' => $this->whenLoaded('optionValues', function () {
                return $this->optionValues->map(fn($ov) => [
                    'name'  => $ov->relationLoaded('option')
                        ? ($ov->option?->name ?? '')
                        : '',
                    'value' => $ov->value ?? '',
                ]);
            }),

            // Forward-compatible media key.
            // Maps DB sort_order → API position.
            'media' => $this->whenLoaded('images', function () {
                return $this->images
                    ->sortBy('sort_order')
                    ->map(fn($img) => [
                        'id'         => $img->id,
                        'url'        => $img->full_url,  // Use accessor instead of asset()
                        'alt'        => $img->alt_text ?? null,
                        'position'   => $img->sort_order ?? 0,
                        'is_primary' => (bool) $img->is_primary,
                    ])
                    ->values();
            }),

            // Legacy images key: kept for storefront backward compatibility.
            // Remove after storefront is migrated to 'media' key.
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(fn($img) => [
                    'id'         => $img->id,
                    'url'        => $img->full_url,  // Use accessor instead of asset()
                    'alt_text'   => $img->alt_text,
                    'is_primary' => (bool) $img->is_primary,
                ]);
            }),
        ];
    }
}