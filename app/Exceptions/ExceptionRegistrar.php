<?php
namespace App\Exceptions;

use App\Enums\ErrorCode;
use App\Exceptions\Auth\AccountDisabledException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\UnauthorizedPlatformAccessException;
use App\Exceptions\Authorization\PermissionDeniedException;
use App\Exceptions\BaseApiException;
use App\Exceptions\Domain\DomainException;
use App\Exceptions\Domain\InvalidStoreContextException;
use App\Exceptions\Domain\OnboardingIncompleteException;
use App\Services\Storefront\Runtime\RuntimeContractException;
use App\Services\Storefront\Runtime\RuntimeLogger;
use App\Services\Storefront\Runtime\RuntimeResponseFactory;
use App\Exceptions\Store\StoreDisabledException;
use App\Exceptions\Store\StoreNotFoundException;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Support\Observability\RequestTraceContextManager;
use App\Support\Security\SecurityEventLoggerInterface;
use App\Support\Security\SecurityEventType;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ExceptionRegistrar
{
    public function handle(Exceptions $exceptions): void
    {
        $exceptions->render(function (Throwable $e) {
            $this->recordSecurityEvent($e);

            if ($e instanceof UnauthorizedStoreAccessException) {
                return $this->attachTraceHeaders(response()->json([
                    'success' => false,
                    'code' => ErrorCode::IDENTITY_DOMAIN_MISMATCH->value,
                    'message' => $e->getMessage(),
                    'redirect' => '/dashboard',
                    'errors' => new \stdClass(),
                ], 403));
            }

            if ($e instanceof UnauthorizedPlatformAccessException) {
                return $this->attachTraceHeaders(response()->json([
                    'success' => false,
                    'code' => ErrorCode::ACCESS_DENIED->value,
                    'message' => $e->getMessage(),
                    'errors' => new \stdClass(),
                ], 403));
            }

            if ($e instanceof PermissionDeniedException) {
                return $this->attachTraceHeaders($e->render(request()));
            }

            if ($e instanceof BaseApiException) {
                return $this->attachTraceHeaders($e->render(request()));
            }

            if ($e instanceof RuntimeContractException) {
                $payload = app(RuntimeResponseFactory::class)->errorPayload($e);

                app(RuntimeLogger::class)->info('runtime.error.normalized', [
                    'artifact' => (string) request()->attributes->get('storefront_runtime_artifact', 'route'),
                    'status' => 'failure',
                    'path' => data_get($payload, 'requestContext.path', '/'),
                    'error_code' => $e->runtimeCode(),
                ]);

                return $this->attachTraceHeaders(response()->json($payload, $e->httpStatus()));
            }

            if ($e instanceof DomainException) {
                $response = [
                    'success' => false,
                    'code' => $e->getErrorCode(),
                    'message' => $e->getMessage(),
                    'errors' => new \stdClass(),
                ];

                // Include logout URL for identity domain mismatch errors
                if ($e instanceof \App\Exceptions\Domain\InvalidIdentityDomainAccessException && $e->getLogoutUrl()) {
                    $response['logoutUrl'] = $e->getLogoutUrl();
                    $response['action'] = 'logout_required';
                }

                return $this->attachTraceHeaders(response()->json($response, $e->getStatus()));
            }

            if ($e instanceof ValidationException) {
                if (request()->is('api/v1/storefront/runtime/*')) {
                    $payload = app(RuntimeResponseFactory::class)->validationErrorPayload([
                        'errors' => $e->errors(),
                    ]);

                    app(RuntimeLogger::class)->info('runtime.error.normalized', [
                        'artifact' => (string) request()->attributes->get('storefront_runtime_artifact', 'route'),
                        'status' => 'failure',
                        'path' => data_get($payload, 'requestContext.path', '/'),
                        'error_code' => 'runtime.validation_failed',
                    ]);

                    return $this->attachTraceHeaders(response()->json($payload, 400));
                }

                return $this->attachTraceHeaders(response()->json([
                    'success' => false,
                    'code' => ErrorCode::VAL_001->value,
                    'message' => __('error.validation_failed'),
                    'errors' => $e->errors(),
                ], 422));
            }

            if (request()->is('api/v1/storefront/runtime/*')) {
                $payload = app(RuntimeResponseFactory::class)->unexpectedErrorPayload([
                    'exception' => $e::class,
                ]);

                app(RuntimeLogger::class)->error('runtime.error.normalized', [
                    'artifact' => (string) request()->attributes->get('storefront_runtime_artifact', 'route'),
                    'status' => 'failure',
                    'path' => data_get($payload, 'requestContext.path', '/'),
                    'error_code' => 'runtime.internal_error',
                ]);

                return $this->attachTraceHeaders(response()->json($payload, 500));
            }

            if ($e instanceof AuthenticationException) {
                return $this->attachTraceHeaders(response()->json([
                    'success' => false,
                    'code' => ErrorCode::AUTH_002->value,
                    'message' => $e->getMessage(),
                    'errors' => new \stdClass(),
                ], 401));
            }

            if ($e instanceof AuthorizationException || $e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
                return $this->attachTraceHeaders(response()->json([
                    'success' => false,
                    'code' => ErrorCode::ACCESS_DENIED->value,
                    'message' => $e->getMessage(),
                    'redirect' => '/dashboard',
                    'errors' => new \stdClass(),
                ], 403));
            }

            if ($e instanceof StoreNotFoundException || $e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return $this->attachTraceHeaders(response()->json([
                    'success' => false,
                    'code' => ErrorCode::STR_001->value,
                    'message' => $e->getMessage(),
                    'errors' => new \stdClass(),
                ], 404));
            }

            if ($e instanceof StoreDisabledException) {
                return $this->attachTraceHeaders(response()->json([
                    'success' => false,
                    'code' => ErrorCode::STR_002->value,
                    'message' => $e->getMessage(),
                    'errors' => new \stdClass(),
                ], 403));
            }

            if ($e instanceof HttpExceptionInterface) {
                return $this->attachTraceHeaders(response()->json([
                    'success' => false,
                    'code' => "HTTP_{$e->getStatusCode()}",
                    'message' => $e->getMessage(),
                    'errors' => new \stdClass(),
                ], $e->getStatusCode()));
            }

            Log::error($e);

            return $this->attachTraceHeaders(response()->json([
                'success' => false,
                'code' => ErrorCode::SYS_001->value,
                'message' => config('app.debug') ? $e->getMessage() : __('error.internal_server_error'),
                'errors' => new \stdClass(),
            ], 500));
        });
    }

    private function attachTraceHeaders(JsonResponse $response): JsonResponse
    {
        $response->headers->set(
            (string) config('observability.correlation_header'),
            app(RequestTraceContextManager::class)->correlationId(),
        );

        return $response;
    }

    private function recordSecurityEvent(Throwable $exception): void
    {
        try {
            $securityEventLogger = app(SecurityEventLoggerInterface::class);

            match (true) {
                $exception instanceof InvalidCredentialsException => $securityEventLogger->record(
                    SecurityEventType::AUTH_LOGIN_FAILED,
                    ['path' => request()->path()],
                    'notice',
                ),
                $exception instanceof AccountDisabledException => $securityEventLogger->record(
                    SecurityEventType::AUTH_LOGIN_FAILED,
                    ['path' => request()->path(), 'reason' => 'account_disabled'],
                    'warning',
                ),
                $exception instanceof OnboardingIncompleteException => $securityEventLogger->record(
                    SecurityEventType::AUTH_ONBOARDING_DENIED,
                    ['path' => request()->path()],
                    'notice',
                ),
                $exception instanceof InvalidStoreContextException,
                $exception instanceof UnauthorizedStoreAccessException => $securityEventLogger->record(
                    SecurityEventType::TENANT_STORE_MISMATCH,
                    ['path' => request()->path()],
                    'warning',
                ),
                $exception instanceof PermissionDeniedException => $securityEventLogger->record(
                    SecurityEventType::AUTHORIZATION_DENIED,
                    [
                        'path' => request()->path(),
                        'resource' => $exception->getResource(),
                        'action' => $exception->getAction(),
                        'permission' => $exception->getPermission(),
                    ],
                    'warning',
                ),
                $exception instanceof AuthorizationException => $securityEventLogger->record(
                    SecurityEventType::AUTHORIZATION_DENIED,
                    ['path' => request()->path()],
                    'warning',
                ),
                default => null,
            };
        } catch (Throwable) {
            // Observability failures must never change exception handling behavior.
        }
    }
}
