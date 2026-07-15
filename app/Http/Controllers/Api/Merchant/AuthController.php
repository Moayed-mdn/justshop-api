<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Auth\GetBootstrapAction;
use App\Actions\Auth\UpdateActiveStoreAction;
use App\Actions\Auth\GetMeAction;
use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\ResendVerificationEmailAction;
use App\Actions\Auth\VerifyEmailAction;
use App\Models\Store;
use App\Models\User;
use App\Services\Auth\FrontendSessionMetadataResolver;
use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\SessionOwnershipManager;
use App\DTOs\Auth\GetBootstrapDTO;
use App\DTOs\Auth\UpdateActiveStoreDTO;
use App\DTOs\Auth\GetMeDTO;
use App\DTOs\Auth\LoginUserDTO;
use App\DTOs\Auth\RegisterUserDTO;
use App\DTOs\Auth\ResendVerificationEmailDTO;
use App\DTOs\Auth\VerifyEmailDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\MeRequest;
use App\Http\Requests\Auth\RegistgerUserRequest;
use App\Http\Requests\Auth\ResendVerificationEmailRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Requests\Auth\UpdateActiveStoreRequest;
use App\Http\Resources\Auth\BootstrapResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends \App\Http\Controllers\Controller
{
    public function __construct(
        private RegisterUserAction $registerUserAction,
        private LoginUserAction $loginUserAction,
        private LogoutUserAction $logoutUserAction,
        private VerifyEmailAction $verifyEmailAction,
        private ResendVerificationEmailAction $resendVerificationEmailAction,
        private GetMeAction $getMeAction,
        private GetBootstrapAction $getBootstrapAction,
        private UpdateActiveStoreAction $updateActiveStoreAction,
        private FrontendSessionMetadataResolver $frontendSessionMetadataResolver,
        private IdentityContextResolver $identityContextResolver,
        private SessionOwnershipManager $sessionOwnershipManager,
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        // #region debug-point B:bootstrap-controller-authenticated
        (static function () use ($request): void {
            $debugUrl = 'http://127.0.0.1:7777/event';
            $debugSessionId = 'auth-session-reload';
            $envPath = base_path('.dbg/auth-session-reload.env');
            if (is_file($envPath)) {
                $envLines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($envLines as $envLine) {
                    if (str_starts_with($envLine, 'DEBUG_SERVER_URL=')) {
                        $debugUrl = substr($envLine, strlen('DEBUG_SERVER_URL='));
                    }
                    if (str_starts_with($envLine, 'DEBUG_SESSION_ID=')) {
                        $debugSessionId = substr($envLine, strlen('DEBUG_SESSION_ID='));
                    }
                }
            }

            $payload = [
                'sessionId' => $debugSessionId,
                'runId' => 'pre-fix',
                'hypothesisId' => 'B',
                'location' => 'app/Http/Controllers/Api/Merchant/AuthController.php',
                'msg' => '[DEBUG] Merchant bootstrap controller resolved authenticated user',
                'data' => [
                    'route_name' => $request->route()?->getName(),
                    'session_id' => $request->session()->getId(),
                    'request_user_id' => $request->user()?->id,
                    'default_guard' => Auth::getDefaultDriver(),
                    'web_guard_check' => Auth::guard('web')->check(),
                    'merchant_guard_check' => Auth::guard('merchant')->check(),
                    'session_auth_domain' => $request->session()->get('auth_domain'),
                ],
                'ts' => (int) round(microtime(true) * 1000),
            ];

            @file_get_contents($debugUrl, false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => json_encode($payload, JSON_THROW_ON_ERROR),
                    'timeout' => 1,
                ],
            ]));
        })();
        // #endregion

        $data = $this->getBootstrapAction->execute(
            GetBootstrapDTO::fromRequest($request)
        );

        return $this->successWithMeta(
            new BootstrapResource($data),
            ['session' => $data->session],
            __('auth.bootstrap_successful')
        );
    }

    public function updateActiveStore(UpdateActiveStoreRequest $request): JsonResponse
    {
        // Wave 2 Remediation: Explicit policy authorization moved from Action to Controller
        // Authorization now owned by StorePolicy::switchStore()
        $storeIdentifier = $request->input('store_id');
        $store = is_numeric($storeIdentifier)
            ? Store::findOrFail((int) $storeIdentifier)
            : Store::where('slug', $storeIdentifier)->firstOrFail();
        $this->authorize('switchStore', $store);

        // Ensure the request uses the actual store ID for the DTO
        $request->merge(['store_id' => $store->id]);

        $data = $this->updateActiveStoreAction->execute(
            UpdateActiveStoreDTO::fromRequest($request),
            $request
        );

        /** @var User $user */
        $user = $request->user();

        return $this->successWithMeta(
            new BootstrapResource($data),
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $this->identityContextResolver->resolve($user))],
            __('auth.active_store_updated')
        );
    }

    public function register(RegistgerUserRequest $request): JsonResponse
    {
        $user = $this->registerUserAction->execute(
            RegisterUserDTO::fromRequest($request)
        );

        Auth::login($user);
        $request->session()->regenerate();
        $this->sessionOwnershipManager->tag($request, $user, 'merchant');

        return $this->successWithMeta(
            new UserResource($user),
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $this->identityContextResolver->resolve($user))],
            __('auth.register_success'),
            201
        );
    }

    public function login(LoginUserRequest $request): JsonResponse
    {
        $user = $this->loginUserAction->execute(
            LoginUserDTO::fromRequest($request)
        );

        Auth::login($user);
        $request->session()->regenerate();
        $this->sessionOwnershipManager->tag($request, $user, 'merchant');

        // #region debug-point C:login-session-created
        (static function () use ($request, $user): void {
            $debugUrl = 'http://127.0.0.1:7777/event';
            $debugSessionId = 'auth-session-reload';
            $envPath = base_path('.dbg/auth-session-reload.env');
            if (is_file($envPath)) {
                $envLines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($envLines as $envLine) {
                    if (str_starts_with($envLine, 'DEBUG_SERVER_URL=')) {
                        $debugUrl = substr($envLine, strlen('DEBUG_SERVER_URL='));
                    }
                    if (str_starts_with($envLine, 'DEBUG_SESSION_ID=')) {
                        $debugSessionId = substr($envLine, strlen('DEBUG_SESSION_ID='));
                    }
                }
            }

            $payload = [
                'sessionId' => $debugSessionId,
                'runId' => 'pre-fix',
                'hypothesisId' => 'C',
                'location' => 'app/Http/Controllers/Api/Merchant/AuthController.php',
                'msg' => '[DEBUG] Merchant login created session',
                'data' => [
                    'user_id' => $user->id,
                    'route_name' => $request->route()?->getName(),
                    'session_id' => $request->session()->getId(),
                    'session_auth_domain' => $request->session()->get('auth_domain'),
                    'default_guard' => Auth::getDefaultDriver(),
                    'web_guard_check' => Auth::guard('web')->check(),
                    'merchant_guard_check' => Auth::guard('merchant')->check(),
                    'cookie_names' => array_keys($request->cookies->all()),
                ],
                'ts' => (int) round(microtime(true) * 1000),
            ];

            @file_get_contents($debugUrl, false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => json_encode($payload, JSON_THROW_ON_ERROR),
                    'timeout' => 1,
                ],
            ]));
        })();
        // #endregion

        return $this->successWithMeta(
            [
                'user' => new UserResource($user),
            ],
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $this->identityContextResolver->resolve($user))],
            __('auth.login_successful')
        );
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        $meta = ['session' => $this->frontendSessionMetadataResolver->resolve(
            $request,
            $user ? $this->identityContextResolver->resolve($user) : null,
        )];

        $this->logoutUserAction->execute($request);

        return $this->successWithMeta(null, $meta, __('auth.logout_successful'));
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $result = $this->verifyEmailAction->execute(
            VerifyEmailDTO::fromRequest($request)
        );

        if ($result['already_verified']) {
            return $this->success(null, __('auth.already_verified'));
        }

        return $this->success(null, __('auth.email_verified'));
    }

    public function resendVerificationEmail(ResendVerificationEmailRequest $request): JsonResponse
    {
        $result = $this->resendVerificationEmailAction->execute(
            ResendVerificationEmailDTO::fromRequest($request)
        );

        if ($result['already_verified']) {
            return $this->success(null, __('auth.email_already_verified'));
        }

        return $this->success(null, __('auth.verification_email_sent'));
    }

    public function me(MeRequest $request): JsonResponse
    {
        $user = $this->getMeAction->execute(
            GetMeDTO::fromRequest($request)
        );

        return $this->successWithMeta(
            new UserResource($user),
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $this->identityContextResolver->resolve($user))]
        );
    }
}
