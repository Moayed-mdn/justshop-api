<?php

declare(strict_types=1);

namespace App\Http\Requests\Merchant\Theme;

use Illuminate\Foundation\Http\FormRequest;

class UpdateThemeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'version' => ['sometimes', 'string', 'max:50'],
            'author' => ['nullable', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
