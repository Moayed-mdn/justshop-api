<?php

namespace App\Http\Requests\Admin\Tag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            /*
            |------------------------------------------------------------------
            | Tag Metadata
            |------------------------------------------------------------------
            */

            'type' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'color' => [
                'nullable',
                'string',
                'max:50',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],

            /*
            |------------------------------------------------------------------
            | Translations
            |------------------------------------------------------------------
            |
            | At least one translation is required.
            | slug is auto-generated from name if not provided.
            | slug uniqueness per locale is enforced at DB level.
            |------------------------------------------------------------------
            */

            'translations' => [
                'required',
                'array',
                'min:1',
            ],

            'translations.*.locale' => [
                'required',
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
                'required',
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
