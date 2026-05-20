<?php

namespace App\Http\Requests\Admin\Product;

use App\Enums\Product\ProductStatusEnum;
use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ListProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        Log::info('Locale from header', ['locale' => $this->header('locale')]);
        return $this->user()->hasRole(RoleEnum::SUPER_ADMIN->value) 
            || $this->user()->hasPermissionTo('product.view', $this->route('store'));
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(ProductStatusEnum::values())],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
