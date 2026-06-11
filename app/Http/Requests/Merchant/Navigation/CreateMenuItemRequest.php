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
            'type' => ['required', 'string', 'in:page,category,product,collection,external,custom,link,group'],
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
                    if (($type === 'link' || $type === 'external' || $type === 'custom') && empty($value)) {
                        $fail('URL is required for link, external, and custom items.');
                    }
                },
            ],
            // Resource ID required for page, category, product types
            'resource_id' => [
                function ($attribute, $value, $fail) {
                    $type = $this->input('type');
                    if (in_array($type, ['page', 'category', 'product']) && empty($value)) {
                        $fail('A resource must be selected for ' . $type . ' items.');
                    }
                },
                'nullable',
                'integer',
            ],
            // Resource type required when resource_id is provided
            'resource_type' => [
                function ($attribute, $value, $fail) {
                    if ($this->has('resource_id') && !empty($this->input('resource_id')) && empty($value)) {
                        $fail('Resource type is required when linking to a resource.');
                    }
                },
                'nullable',
                'string',
                'max:255',
            ],
            'target' => ['nullable', 'string', 'in:_self,_blank'],
            'settings' => ['nullable', 'array'],
            'position' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
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
