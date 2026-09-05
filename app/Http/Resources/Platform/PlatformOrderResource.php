<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Platform Order Resource
 *
 * Same shape as App\Http\Resources\Admin\Order\AdminOrderDetailResource,
 * plus the owning store's identity -- platform actors operate across
 * stores, so which store an order belongs to is always relevant here in a
 * way it isn't for a merchant viewing only their own store's orders.
 */
class PlatformOrderResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                 => $this->id,
            'store_id'           => $this->store_id,
            'store'              => $this->whenLoaded('store', fn () => [
                'id'   => $this->store->id,
                'name' => $this->store->name,
                'slug' => $this->store->slug,
            ]),
            'order_number'       => $this->order_number,
            'status'             => $this->status,
            'payment_status'     => $this->payment_status,
            'fulfillment_status' => $this->fulfillment_status ?? 'unfulfilled',
            'subtotal'           => (float) $this->subtotal,
            'tax'                => (float) $this->tax_amount,
            'shipping'           => (float) $this->shipping_amount,
            'discount_amount'    => (float) $this->discount_amount,
            'total'              => (float) $this->total,
            'currency'           => $this->currency ?? 'usd',
            'notes'              => $this->notes ?? null,
            'customer'           => $this->whenLoaded('user', fn () => [
                'id'    => $this->user->id,
                'name'  => $this->user->name,
                'email' => $this->user->email,
                'phone' => $this->user->phone ?? null,
            ]),
            'line_items'         => $this->whenLoaded('items', fn () =>
                $this->items->map(fn ($item) => [
                    'id'           => $item->id,
                    'product_id'   => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku'          => $item->sku ?? null,
                    'quantity'     => $item->quantity,
                    'price'        => (float) $item->unit_price,
                    'total'        => round((float) $item->unit_price * $item->quantity, 2),
                ])
            ),
            'items_count'        => $this->whenLoaded('items', fn () => $this->items->sum('quantity')),
            'created_at'         => $this->created_at,
            'updated_at'         => $this->updated_at,
        ];
    }
}
