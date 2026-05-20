<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Brand;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'store_id'       => $this->store_id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'logo_url'       => $this->logo_url,
            'sort_order'     => $this->sort_order,
            'is_active'      => $this->is_active,
            'products_count' => $this->whenCounted('products'),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
            'deleted_at'     => $this->deleted_at?->toISOString(),
        ];
    }
}
