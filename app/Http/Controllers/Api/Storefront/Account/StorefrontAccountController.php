<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront\Account;

use App\Actions\Auth\DeleteAccountAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\SendResetLinkAction;
use App\Actions\Auth\ResendVerificationEmailAction;
use App\Actions\Auth\UpdateProfileAvatarAction;
use App\Actions\Auth\UpdateProfileInfoAction;
use App\Actions\Auth\UpdateProfilePasswordAction;
use App\Actions\Auth\VerifyEmailAction;
use App\Actions\Storefront\Account\GetStorefrontAccountBootstrapAction;
use App\Actions\Storefront\Account\LoginCustomerAction;
use App\Actions\Storefront\Account\RegisterCustomerAction;
use App\DTOs\Auth\DeleteAccountDTO;
use App\DTOs\Auth\ResetPasswordDTO;
use App\DTOs\Auth\SendResetLinkDTO;
use App\DTOs\Auth\ResendVerificationEmailDTO;
use App\DTOs\Auth\UpdateProfileAvatarDTO;
use App\DTOs\Auth\UpdateProfileInfoDTO;
use App\DTOs\Auth\UpdateProfilePasswordDTO;
use App\DTOs\Auth\VerifyEmailDTO;
use App\DTOs\Storefront\Account\LoginCustomerDTO;
use App\DTOs\Storefront\Account\RegisterCustomerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Password\ResetPasswordRequest;
use App\Http\Requests\Password\SendResetLinkRequest;
use App\Http\Requests\Auth\ResendVerificationEmailRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Http\Requests\Profile\UpdateInfoRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
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
        private readonly VerifyEmailAction $verifyEmailAction,
        private readonly ResendVerificationEmailAction $resendVerificationEmailAction,
        private readonly SendResetLinkAction $sendResetLinkAction,
        private readonly ResetPasswordAction $resetPasswordAction,
        private readonly UpdateProfileInfoAction $updateProfileInfoAction,
        private readonly UpdateProfilePasswordAction $updateProfilePasswordAction,
        private readonly UpdateProfileAvatarAction $updateProfileAvatarAction,
        private readonly DeleteAccountAction $deleteAccountAction,
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
        $identityContext = $this->identityContextResolver->resolve($user);
        $this->sessionOwnershipManager->tag($request, $user, $identityContext->authDomain->value);

        return $this->successWithMeta(
            new StorefrontAccountUserResource($user),
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $identityContext)],
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
        $identityContext = $this->identityContextResolver->resolve($user);
        $this->sessionOwnershipManager->tag($request, $user, $identityContext->authDomain->value);

        return $this->successWithMeta(
            ['user' => new StorefrontAccountUserResource($user)],
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $identityContext)],
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

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $result = $this->verifyEmailAction->execute(
            VerifyEmailDTO::fromRequest($request)
        );

        if ($result['already_verified']) {
            return $this->success(null, __('auth.email_already_verified'));
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

    public function forgotPassword(SendResetLinkRequest $request): JsonResponse
    {
        $this->sendResetLinkAction->execute(
            SendResetLinkDTO::fromRequest($request)
        );

        return $this->success(null, __('auth.password_reset_link_sent'));
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->resetPasswordAction->execute(
            ResetPasswordDTO::fromRequest($request)
        );

        return $this->success(null, __('auth.password_reset_success'));
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

    public function updateInfo(UpdateInfoRequest $request): JsonResponse
    {
        $user = $this->updateProfileInfoAction->execute(
            UpdateProfileInfoDTO::fromRequest($request)
        );

        return $this->successWithMeta(
            new StorefrontAccountUserResource($user),
            ['session' => $this->frontendSessionMetadataResolver->resolve($request, $this->identityContextResolver->resolve($user))],
            __('general.profile_updated'),
        );
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->updateProfilePasswordAction->execute(
            UpdateProfilePasswordDTO::fromRequest($request)
        );

        return $this->success(null, __('general.password_updated'));
    }

    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $avatarUrl = $this->updateProfileAvatarAction->execute(
            UpdateProfileAvatarDTO::fromRequest($request)
        );

        return $this->success(['avatar' => $avatarUrl], __('general.avatar_updated'));
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $this->deleteAccountAction->execute(
            DeleteAccountDTO::fromRequest($request)
        );

        return $this->success(null, __('general.account_deleted'));
    }
}
