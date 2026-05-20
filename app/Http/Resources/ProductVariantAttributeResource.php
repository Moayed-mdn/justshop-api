<?php
// app/Http/Resources/AttributeResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantAttributeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'attribute_name' => $this->attribute_name,
            'attribute_value' => $this->attribute_value,
        ];
    }
}