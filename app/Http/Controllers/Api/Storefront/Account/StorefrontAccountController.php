<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront\Account;

use App\Actions\Auth\LogoutUserAction;
use App\Actions\Storefront\Account\GetStorefrontAccountBootstrapAction;
use App\Actions\Storefront\Account\LoginCustomerAction;
use App\Actions\Storefront\Account\RegisterCustomerAction;
use App\DTOs\Storefront\Account\LoginCustomerDTO;
use App\DTOs\Storefront\Account\RegisterCustomerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\Account\LoginCustomerRequest;
use App\Http\Requests\Storefront\Account\RegisterCustomerRequest;
use App\Http\Resources\Storefront\Account\StorefrontAccountBootstrapResource;
use App\Http\Resources\Storefront\Account\StorefrontAccountUserResource;
use App\Models\User;
use App\Services\Auth\FrontendSessionMetadataResolver;
use App\Services\Auth\IdentityContextResolver;
use App\Services\Auth\SessionOwnershipManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StorefrontAccountController extends Controller
{
    public function __construct(
        private readonly RegisterCustomerAction $registerCustomerAction,
        private readonly LoginCustomerAction $loginCustomerAction,
        private readonly LogoutUserAction $logoutUserAction,
        private readonly GetStorefrontAccountBootstrapAction $getStorefrontAccountBootstrapAction,
        private readonly FrontendSessionMetadataResolver $frontendSessionMetadataResolver,
        private readonly IdentityContextResolver $identityContextResolver,
        private readonly SessionOwnershipManager $sessionOwnershipManager,
    ) {}

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $user = $this->registerCustomerAction->execute(
            RegisterCustomerDTO::fromRequest($request)
        );

        Auth::login($user);
        $request->session()->regenerate();
        $this->sessionOwnershipManager->tag($request, $user, 'customer');

        return $this->successWithMeta(
            new StorefrontAccountUserResource($user),
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $this->identityContextResolver->resolve($user))],
            __('auth.customer_register_success'),
            201,
        );
    }

    public function login(LoginCustomerRequest $request): JsonResponse
    {
        $user = $this->loginCustomerAction->execute(
            LoginCustomerDTO::fromRequest($request)
        );

        Auth::login($user);
        $request->session()->regenerate();
        $this->sessionOwnershipManager->tag($request, $user, 'customer');

        return $this->successWithMeta(
            ['user' => new StorefrontAccountUserResource($user)],
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $this->identityContextResolver->resolve($user))],
            __('auth.customer_login_successful'),
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

        return $this->successWithMeta(null, $meta, __('auth.customer_logout_successful'));
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->successWithMeta(
            new StorefrontAccountUserResource($user),
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $this->identityContextResolver->resolve($user))],
            __('auth.customer_me_successful'),
        );
    }

    public function bootstrap(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $this->getStorefrontAccountBootstrapAction->execute($request, $user);

        return $this->successWithMeta(
            new StorefrontAccountBootstrapResource($data),
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $this->identityContextResolver->resolve($user))],
            __('auth.customer_bootstrap_successful'),
        );
    }
}
