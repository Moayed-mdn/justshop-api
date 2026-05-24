<?php

declare(strict_types=1);

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        // Google OAuth users have no password — skip confirmation.
        // Password-based users must confirm before deletion.
        if (is_null($user?->password)) {
            return [];
        }

        return [
            'password' => ['required', 'current_password'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => __('auth.account_deletion_password_required'),
            'password.current_password' => __('auth.account_deletion_password_incorrect'),
        ];
    }
}
