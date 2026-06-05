<?php
// app/Http/Resources/ProductResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
   // In ProductResource.php
public function toArray($request)
{
    $variants = $this->whenLoaded('activeVariants', function() {
        return $this->formatVariants($this->activeVariants);
    });
    return [
        'id' => $this->id,
        'name' => $this->name,
        'description' => $this->description,
        'category' => $this->category->name ?? null,
        'variants' => $variants, // This should be a single array, not nested arrays
        'primary_image' => $this->primaryImageUrl,
        // 'created_at' => $this->created_at->format('Y-i-d'),
        // 'updated_at' => $this->updated_at->format('Y-i-d'),
    ];
}

private function formatVariants($variants)
{
    if (!$variants) return [];
    
    // Group variants by their attributes
    $grouped = $variants->groupBy(function($variant) {
        return $variant->attributes->sortBy('attribute_name')
                    ->pluck('attribute_value')
                    ->join('|');
    });

    return $grouped->map(function($variantGroup, $key) {
        $firstVariant = $variantGroup->first();
        
        return [
            'attribute_display' => $this->getAttributeDisplay($firstVariant),
            'total_quantity' => $variantGroup->sum('quantity'),
            'price_range' => [
                'min' => $variantGroup->min('price'),
                'max' => $variantGroup->max('price')
            ],
            'variants' => $variantGroup->map(function($v) {
                return [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'price' => $v->price,
                    'quantity' => $v->quantity,
                    'batch_number' => $v->batch_number,
                    'manufacture_date' => $v->manufacture_date,
                    'expiry_date' => $v->expiry_date,
                    'is_active' => $v->is_active,
                    'attributes' => $v->attributes->map(function($attr) {
                        return [
                            'attribute_name' => $attr->attribute_name,
                            'attribute_value' => $attr->attribute_value
                        ];
                    }),
                    // ADDED: Variant-specific images
                    'images' => $v->images ? $v->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'image_url' => $image->full_url,
                            'alt_text' => $image->alt_text,
                            'is_primary' => $image->is_primary
                        ];
                    }) : [],
                    'primary_image' => $v->images ? ($v->images->where('is_primary', true)->first()?->full_url ?? null) : null
                ];
            })->values()
        ];
    })->values(); // Remove keys to get a simple array
}

private function getAttributeDisplay($variant)
{
    if ($variant->attributes->isEmpty()) {
        return 'Standard';
    }
    
    return $variant->attributes->sortBy('attribute_name')
                ->pluck('attribute_value')
                ->join(', ');
}
}