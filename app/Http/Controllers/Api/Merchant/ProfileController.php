<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Auth\DeleteAccountAction;
use App\Actions\Auth\GetProfileAction;
use App\Actions\Auth\UpdateProfileAvatarAction;
use App\Actions\Auth\UpdateProfileInfoAction;
use App\Actions\Auth\UpdateProfilePasswordAction;
use App\DTOs\Auth\DeleteAccountDTO;
use App\DTOs\Auth\GetProfileDTO;
use App\DTOs\Auth\UpdateProfileAvatarDTO;
use App\DTOs\Auth\UpdateProfileInfoDTO;
use App\DTOs\Auth\UpdateProfilePasswordDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\DeleteAccountRequest;
use App\Http\Requests\Profile\GetProfileRequest;
use App\Http\Requests\Profile\UpdateAvatarRequest;
use App\Http\Requests\Profile\UpdateInfoRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Resources\ProfileResource;
use App\Policies\ProfilePolicy;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        private GetProfileAction $getProfileAction,
        private UpdateProfileInfoAction $updateProfileInfoAction,
        private UpdateProfilePasswordAction $updateProfilePasswordAction,
        private UpdateProfileAvatarAction $updateProfileAvatarAction,
        private DeleteAccountAction $deleteAccountAction,
    ) {}

    public function show(GetProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('view', [ProfilePolicy::class, $user]);

        $user = $this->getProfileAction->execute(
            GetProfileDTO::fromRequest($request)
        );

        return $this->success(new ProfileResource($user));
    }

    public function updateInfo(UpdateInfoRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('updateInfo', [ProfilePolicy::class, $user]);

        $user = $this->updateProfileInfoAction->execute(
            UpdateProfileInfoDTO::fromRequest($request)
        );

        return $this->success(new ProfileResource($user), __('general.profile_updated'));
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('updatePassword', [ProfilePolicy::class, $user]);

        $this->updateProfilePasswordAction->execute(
            UpdateProfilePasswordDTO::fromRequest($request)
        );

        return $this->success(null, __('general.password_updated'));
    }

    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('updateAvatar', [ProfilePolicy::class, $user]);

        $avatarUrl = $this->updateProfileAvatarAction->execute(
            UpdateProfileAvatarDTO::fromRequest($request)
        );

        return $this->success(['avatar' => $avatarUrl], __('general.avatar_updated'));
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorize('delete', [ProfilePolicy::class, $user]);

        $this->deleteAccountAction->execute(
            DeleteAccountDTO::fromRequest($request)
        );

        return $this->success(null, __('general.account_deleted'));
    }
}
