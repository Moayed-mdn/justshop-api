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
            'type' => ['sometimes', 'string', 'in:page,category,product,collection,external,custom,link,group'],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:navigation_menu_items,id',
                // Groups cannot be nested under other items (must be root-level)
                function ($attribute, $value, $fail) {
                    if ($this->input('type') === 'group' && $value !== null) {
                        $fail('Group items must be root-level and cannot have a parent.');
                    }
                },
            ],
            'url' => [
                'nullable',
                'string',
                'max:500',
                // URL required for link and external types
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');
                    if (($type === 'link' || $type === 'external') && empty($value)) {
                        $fail('URL is required for link and external items.');
                    }
                },
            ],
            'resource_id' => ['nullable', 'integer'],
            'resource_type' => ['nullable', 'string', 'max:255'],
            'target' => ['sometimes', 'string', 'in:_self,_blank'],
            'settings' => ['sometimes', 'array'],
            'position' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'The item type must be one of: page, category, product, collection, external, custom, link, or group.',
            'parent_id.exists' => 'The selected parent item does not exist.',
        ];
    }
}
