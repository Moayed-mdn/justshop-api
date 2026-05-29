<?php

namespace App\Exceptions\Store;

use App\Enums\ErrorCode;
use App\Exceptions\BaseApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UnauthorizedStoreAccessException extends BaseApiException
{
    public function __construct()
    {
        parent::__construct(
            message: __('error.unauthorized_store'),
            statusCode: 403,
            errorCode: ErrorCode::IDENTITY_DOMAIN_MISMATCH->value,
        );
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => ErrorCode::IDENTITY_DOMAIN_MISMATCH->value,
            'message' => $this->getMessage(),
            'redirect' => '/dashboard',
            'errors' => new \stdClass(),
        ], 403);
    }
}
