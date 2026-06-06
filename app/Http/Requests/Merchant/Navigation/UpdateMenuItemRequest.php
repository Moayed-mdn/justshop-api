<?php

declare(strict_types=1);

namespace App\Http\Requests\Merchant\Navigation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:page,category,product,collection,external,custom'],
            'parent_id' => ['nullable', 'integer', 'exists:navigation_menu_items,id'],
            'url' => ['nullable', 'string', 'max:500'],
            'resource_id' => ['nullable', 'integer'],
            'resource_type' => ['nullable', 'string', 'max:255'],
            'target' => ['sometimes', 'string', 'in:_self,_blank'],
            'settings' => ['sometimes', 'array'],
            'position' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
