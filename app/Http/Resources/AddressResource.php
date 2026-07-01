<?php
// app/Http/Resources/AddressResource.php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'is_default' => (bool) ($this->is_default_shipping || $this->is_default_billing),
            'is_default_shipping' => (bool) $this->is_default_shipping,
            'is_default_billing' => (bool) $this->is_default_billing,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'company' => $this->company,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'phone' => $this->phone,
            'full_address' => $this->full_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
