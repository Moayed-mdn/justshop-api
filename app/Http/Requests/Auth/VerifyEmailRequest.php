<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Use a *relative* signature check (path + query only, no scheme/host).
        // This request can be proxied through the storefront frontend, which sets
        // its own Host header per-tenant (see ResolveStoreFromHeader — tenant
        // resolution uses X-Tenant-Id, not Host). An absolute check compares the
        // signed URL's original host against whatever Host the request arrives
        // with, which will never match through that proxy and always returns 403.
        return $this->hasValidSignature(absolute: false);
    }

    public function rules(): array
    {
        return [];
    }
}