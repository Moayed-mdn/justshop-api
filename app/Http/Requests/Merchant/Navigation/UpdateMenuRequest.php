<?php

declare(strict_types=1);

namespace App\Http\Requests\Merchant\Navigation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'handle' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'settings' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
