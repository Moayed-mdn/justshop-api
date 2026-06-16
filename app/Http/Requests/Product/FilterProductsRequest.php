<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Rules\CategorySlugExists;
use Illuminate\Foundation\Http\FormRequest;

class FilterProductsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_slug' => ['nullable', 'string', new CategorySlugExists()],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0', 'gte:min_price'],
            'earliest_manufacture' => ['nullable', 'date'],
            'latest_expiry' => ['nullable', 'date', 'after_or_equal:earliest_manufacture'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
