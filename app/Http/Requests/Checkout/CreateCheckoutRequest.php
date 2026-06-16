<?php
// app/Http/Requests/Checkout/CreateCheckoutRequest.php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class CreateCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Both guests and logged-in users can checkout
    }

    public function rules(): array
    {
        // If the user is logged in, cart is read from DB — no items needed
        // Explicitly use 'sanctum' guard to handle both SPA session and Bearer token auth
        if ($this->user('sanctum')) {
            return [];
        }

        // Guest must send cart items
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => __('error.cart_empty'),
            'items.min'      => __('error.cart_empty'),
        ];
    }
}