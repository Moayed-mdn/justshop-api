<?php

namespace App\Http\Resources\Shipping;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreAddressSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'allowed_countries' => $this->allowed_countries ?? [],
            'required_fields' => $this->required_fields ?? [],
            'validation_rules' => $this->validation_rules ?? [],
            'require_phone' => $this->require_phone,
            'require_company' => $this->require_company,
            'allow_po_boxes' => $this->allow_po_boxes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
