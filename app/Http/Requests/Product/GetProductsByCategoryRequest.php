<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class GetProductsByCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_slug' => ['sometimes', 'string'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'earliest_manufacture' => ['sometimes', 'date'],
            'latest_expiry' => ['sometimes', 'date'],
            'min_rating' => ['sometimes', 'numeric', 'min:0', 'max:5'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
