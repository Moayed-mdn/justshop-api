<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

class RuntimePreviewValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'pageId' => ['required', 'string'],
            'path' => ['required', 'string'],
            'locale' => ['required', 'string'],
        ];
    }
}
