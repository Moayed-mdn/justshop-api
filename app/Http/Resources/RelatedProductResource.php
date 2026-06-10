<?php
// app/Http/Resources/RelatedProductResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RelatedProductResource extends JsonResource
{
    public function toArray($request)
    {
        $locale = app()->getLocale();

        $translation = $this->translations->where('locale', $locale)->first()
            ?? $this->translations->first();

        // Get display variant for price + image
        $displayVariant = $this->defaultVariant ?? $this->activeVariants->first() ?? $this->variants->first();

        // Primary image from display variant
        $primaryImage = null;
        if ($displayVariant && $displayVariant->relationLoaded('images')) {
            $img = $displayVariant->images->where('is_primary', true)->first()
                ?? $displayVariant->images->first();
            $primaryImage = $img ? asset($img->image_url) : null;
        }

        return [
            'id'                 => $this->id,
            'name'               => $translation?->name ?? '',
            'slug'               => $translation?->slug ?? '',
            'description'        => $translation?->description ?? '',
            'primary_image'      => $primaryImage,
            'price'              => $displayVariant ? (float) $displayVariant->price : null,
            'category_id'        => $this->category_id,
            'product_variant_id' => $displayVariant?->id,
        ];
    }
}