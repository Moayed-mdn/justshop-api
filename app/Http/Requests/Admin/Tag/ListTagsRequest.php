<?php

namespace App\Http\Requests\Admin\Tag;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class ListTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        Log::info('Locale from header Tag', ['locale' => $this->header('locale')]);
        return $this->user()->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $this->user()->hasPermissionTo(
                'tag.view',
                $this->route('store'),
            );
    }

    public function rules(): array
    {
        return [
            'search'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'type'     => ['sometimes', 'nullable', 'string', 'max:50'],
            'active'   => ['sometimes', 'nullable', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
