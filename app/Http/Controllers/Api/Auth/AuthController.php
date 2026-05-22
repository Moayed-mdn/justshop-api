<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Actions\Auth\GetBootstrapAction;
use App\Actions\Auth\UpdateActiveStoreAction;
use App\Actions\Auth\GetMeAction;
use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\LogoutUserAction;
use App\Actions\Auth\RegisterUserAction;
use App\Actions\Auth\ResendVerificationEmailAction;
use App\Actions\Auth\VerifyEmailAction;
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

class AuthController extends Controller
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
    ) {}

    public function bootstrap(Request $request): JsonResponse
    {
        $data = $this->getBootstrapAction->execute(
            GetBootstrapDTO::fromRequest($request)
        );

        return $this->success(
            new BootstrapResource($data),
            __('auth.bootstrap_successful')
        );
    }

    public function updateActiveStore(UpdateActiveStoreRequest $request): JsonResponse
    {
        $data = $this->updateActiveStoreAction->execute(
            UpdateActiveStoreDTO::fromRequest($request)
        );

        return $this->success(
            new BootstrapResource($data),
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

        return $this->success(
            new UserResource($user),
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

        return $this->success(
            [
                'user' => new UserResource($user),
            ],
            __('auth.login_successful')
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->logoutUserAction->execute($request);

        return $this->success(null, __('auth.logout_successful'));
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

        return $this->success(
            new UserResource($user)
        );
    }
}
