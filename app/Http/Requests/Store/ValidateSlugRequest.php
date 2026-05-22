<?php

declare(strict_types=1);

namespace App\Http\Requests\Store;

use Illuminate\Foundation\Http\FormRequest;

class ValidateSlugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => ['required', 'string', 'min:3', 'max:50'],
        ];
    }
}
