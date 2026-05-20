<?php

namespace App\Http\Requests\Admin\Tag;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $this->user()->hasPermissionTo(
                'tag.update',
                $this->route('store'),
            );
    }

    public function rules(): array
    {
        return [

            /*
            |------------------------------------------------------------------
            | Tag Metadata
            | All fields optional — only provided fields are updated.
            |------------------------------------------------------------------
            */

            'type' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'color' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'is_active' => [
                'sometimes',
                'nullable',
                'boolean',
            ],

            /*
            |------------------------------------------------------------------
            | Translations
            | Each entry upserts the given locale.
            | Existing locales not included are not affected.
            |------------------------------------------------------------------
            */

            'translations' => [
                'sometimes',
                'nullable',
                'array',
                'min:1',
            ],

            'translations.*.locale' => [
                'required_with:translations',
                'string',
                'size:2',
                Rule::in(
                    config(
                        'content.editable_locales',
                        config('app.supported_locales', [])
                    )
                ),
            ],

            'translations.*.name' => [
                'required_with:translations',
                'string',
                'max:100',
            ],

            'translations.*.slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }
}
