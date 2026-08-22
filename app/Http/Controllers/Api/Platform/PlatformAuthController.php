<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\DTOs\Auth\LoginUserDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\FrontendSessionMetadataResolver;
use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\SessionOwnershipManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

/**
 * Platform Authentication Controller
 * 
 * Wave 6: Platform-specific authentication endpoints.
 * Platform login MUST tag session with 'platform' auth domain.
 */
class PlatformAuthController extends Controller
{
    public function __construct(
        private readonly LoginUserAction $loginUserAction,
        private readonly LogoutUserAction $logoutUserAction,
        private readonly FrontendSessionMetadataResolver $frontendSessionMetadataResolver,
        private readonly IdentityContextResolver $identityContextResolver,
        private readonly SessionOwnershipManager $sessionOwnershipManager,
    ) {}

    public function login(LoginUserRequest $request): JsonResponse
    {
        $user = $this->loginUserAction->execute(
            LoginUserDTO::fromRequest($request)
        );

        Auth::login($user);
        $request->session()->regenerate();
        
        // CRITICAL: Tag session with 'platform' domain, not 'merchant'
        $this->sessionOwnershipManager->tag($request, $user, 'platform');

        $identityContext = $this->identityContextResolver->resolve($user);
        $sessionMetadata = $this->frontendSessionMetadataResolver->resolve($request, $identityContext);

        return $this->success([
            'user' => new UserResource($user),
            'email_verified' => !is_null($user->email_verified_at),
            'stores' => [],
            'active_store' => null,
            'active_store_id' => null,
            'onboarding' => [
                'step' => 'completed',
                'completed_steps' => [],
                'can_resume' => false,
                'store_id' => null,
                'is_completed' => true,
            ],
            'permissions' => [],
            'capabilities' => [],
            'features' => (object)[],  // Empty object, not array
            'config' => [
                'locale' => 'en',
                'timezone' => 'UTC',
                'currency' => 'USD',
                'default_currency' => 'USD',
                'supported_locales' => ['en', 'ar'],
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
            ],
            'localization' => [
                'locale' => 'en',
                'timezone' => 'UTC',
                'currency' => 'USD',
                'default_currency' => 'USD',
                'supported_locales' => ['en', 'ar'],
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
            ],
            'actor_context' => $identityContext->actorType->value,
            'auth_domain' => $identityContext->authDomain->value,
            'session' => $sessionMetadata,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();
        $meta = ['session' => $this->frontendSessionMetadataResolver->resolve(
            $request,
            $user ? $this->identityContextResolver->resolve($user) : null,
        )];

        $this->logoutUserAction->execute($request);

        return $this->successWithMeta(null, $meta, __('auth.logout_successful'));
    }

    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $identityContext = $this->identityContextResolver->resolve($user);
        $sessionMetadata = $this->frontendSessionMetadataResolver->resolve($request, $identityContext);

        return $this->success([
            'user' => new UserResource($user),
            'email_verified' => !is_null($user->email_verified_at),
            'stores' => [], // Platform users don't have stores
            'active_store' => null,
            'active_store_id' => null,
            'onboarding' => [
                'step' => 'completed',
                'completed_steps' => [],
                'can_resume' => false,
                'store_id' => null,
                'is_completed' => true,
            ],
            'permissions' => [], // Platform permissions - can be expanded later
            'capabilities' => [],
            'features' => (object)[],  // Empty object, not array
            'config' => [
                'locale' => 'en',
                'timezone' => 'UTC',
                'currency' => 'USD',
                'default_currency' => 'USD',
                'supported_locales' => ['en', 'ar'],
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
            ],
            'localization' => [
                'locale' => 'en',
                'timezone' => 'UTC',
                'currency' => 'USD',
                'default_currency' => 'USD',
                'supported_locales' => ['en', 'ar'],
                'supported_currencies' => ['USD', 'EUR', 'GBP'],
            ],
            'actor_context' => $identityContext->actorType->value,
            'auth_domain' => $identityContext->authDomain->value,
            'session' => $sessionMetadata, // Session in data, not meta
        ]);
    }
}
