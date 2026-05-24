<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * LogoutAllDevicesRequest
 *
 * Requires password confirmation before revoking all other sessions.
 * Google OAuth users (no password) are exempt from the password check.
 */
class LogoutAllDevicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        // Google OAuth users have no password — skip confirmation.
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
            'password.required'         => __('auth.password_required_for_session_revocation'),
            'password.current_password' => __('auth.password_incorrect'),
        ];
    }
}
