<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'slug' => $this->slug,
            'category_id' => $this->category_id,
            'primary_image' => $this->formatImageUrl($this->primary_image),
            'alt_text' => $this->alt_text,
            'product_name' => $this->product_name,
            'price' => $this->price,
            'description' => $this->description,
            'total_sold' => $this->total_sold,
        ];
    }

    /**
     * Format image URL to include /storage/ prefix for local paths.
     * Mirrors the Image model's getFullUrlAttribute() logic.
     */
    private function formatImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // Already absolute (external URL) → return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Strip leading "/storage/" if stored that way
        $path = preg_replace('#^/?storage/#', '', $path);

        return Storage::disk('public')->url($path);
    }
}