<?php

namespace App\Http\Requests\Store;

use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $store = $this->route('store');
        $storeId = $store instanceof Store ? $store->id : $store;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('stores', 'slug')->ignore($storeId), 'regex:/^[a-z0-9-]+$/'],
            'domain' => ['sometimes', 'nullable', 'string', 'max:255'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'timezone' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
