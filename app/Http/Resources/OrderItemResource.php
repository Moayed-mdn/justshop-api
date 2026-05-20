<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        // Safely resolve the variant — null if not loaded
        $variant = $this->resource->relationLoaded('productVariant')
            ? $this->resource->productVariant
            : null;

        // Get variant image if loaded
        $imageUrl = null;
        if ($variant && $variant->relationLoaded('images')) {
            $primaryImage = $variant->images->where('is_primary', true)->first()
                ?? $variant->images->first();
            $imageUrl = $primaryImage ? $primaryImage->full_url : null;
        }

        // Get product slug if loaded
        $slug = null;
        if ($variant && $variant->relationLoaded('product')) {
            $product = $variant->product;
            if ($product && $product->relationLoaded('translations')) {
                $locale = app()->getLocale();
                $translation = $product->translations->where('locale', $locale)->first()
                    ?? $product->translations->first();
                $slug = $translation?->slug;
            }
        }

        return [
            'id'                       => $this->id,
            'product_id'               => $this->product_id,
            'product_variant_id'       => $this->product_variant_id,
            'product_name'             => $this->product_name,
            'product_slug'             => $slug,
            'sku'                      => $this->sku,
            'image'                    => $imageUrl,
            'unit_price'               => (float) $this->unit_price,
            'unit_discount_percentage' => (float) $this->unit_discount_percentage,
            'quantity'                 => $this->quantity,
            'subtotal'                 => round((float) $this->unit_price * $this->quantity, 2),
            'attributes'               => $this->attributes,

            // Safe availability check
            'is_available' => $variant !== null
                && (bool) $variant->is_active
                && $variant->quantity > 0,
        ];
    }
}