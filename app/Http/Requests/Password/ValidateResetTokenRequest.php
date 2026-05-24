<?php

declare(strict_types=1);

namespace App\Http\Requests\Password;

use Illuminate\Foundation\Http\FormRequest;

class ValidateResetTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
        ];
    }
}
