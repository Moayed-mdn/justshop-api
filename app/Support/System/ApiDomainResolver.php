<?php

declare(strict_types=1);

namespace App\Support\System;

use App\Enums\System\ApiDomainEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiDomainResolver
{
    /**
     * Resolve the active API domain based on the request path.
     */
    public function resolve(Request $request): ApiDomainEnum
    {
        $path = $request->path();

        if (Str::startsWith($path, 'api/v1/admin')) {
            return ApiDomainEnum::MERCHANT_ADMIN;
        }

        if (Str::startsWith($path, 'api/v1/users') || Str::startsWith($path, 'api/v1/auth')) {
            return ApiDomainEnum::MERCHANT_AUTH;
        }

        if (Str::startsWith($path, 'api/v1/storefront/account')) {
            return ApiDomainEnum::CUSTOMER_IDENTITY;
        }

        if (Str::startsWith($path, 'api/v1/storefront')) {
            return ApiDomainEnum::STOREFRONT;
        }

        if (Str::startsWith($path, 'api/v1/platform')) {
            return ApiDomainEnum::PLATFORM_ADMIN;
        }

        if (Str::contains($path, '/cms/')) {
            return ApiDomainEnum::CMS;
        }

        // Default fallback for legacy or uncategorized merchant routes
        return ApiDomainEnum::MERCHANT_ADMIN;
    }
}
