<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BaseApiException extends Exception
{
    protected int $statusCode;
    protected string $errorCode;
    protected ?array $errors;

    public function __construct(?string $message = null, int $statusCode = 500, string $errorCode = 'SYS_001', ?array $errors = null)
    {
        parent::__construct($message ?? __('error.internal_server_error'));
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->errors = $errors;
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'errors' => $this->errors ?? new \stdClass(),
        ], $this->statusCode);
    }
}