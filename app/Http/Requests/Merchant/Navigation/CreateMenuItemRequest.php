<?php

declare(strict_types=1);

namespace App\Http\Requests\Merchant\Navigation;

use Illuminate\Foundation\Http\FormRequest;

class CreateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:page,category,product,collection,external,custom'],
            'parent_id' => ['nullable', 'integer', 'exists:navigation_menu_items,id'],
            'url' => ['nullable', 'string', 'max:500'],
            'resource_id' => ['nullable', 'integer'],
            'resource_type' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'string', 'in:_self,_blank'],
            'settings' => ['nullable', 'array'],
            'position' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
