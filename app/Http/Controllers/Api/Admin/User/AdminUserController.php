<?php

namespace App\Http\Controllers\Api\Admin\User;

use App\DTOs\Admin\User\BlockUserDTO;
use App\DTOs\Admin\User\DeleteUserDTO;
use App\DTOs\Admin\User\GetUserDTO;
use App\DTOs\Admin\User\ListUsersDTO;
use App\DTOs\Admin\User\RestoreUserDTO;
use App\DTOs\Admin\User\UnblockUserDTO;
use App\Actions\Admin\User\BlockUserAction;
use App\Actions\Admin\User\DeleteUserAction;
use App\Actions\Admin\User\GetUserAction;
use App\Actions\Admin\User\ListUsersAction;
use App\Actions\Admin\User\RestoreUserAction;
use App\Actions\Admin\User\UnblockUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\ListUsersRequest;
use App\Http\Resources\Admin\User\AdminUserDetailResource;
use App\Http\Resources\Admin\User\AdminUserResource;
use App\Policies\MembershipPolicy;
use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function index(ListUsersRequest $request, ListUsersAction $action, int $store): JsonResponse
    {
        $this->authorize('viewAny', [MembershipPolicy::class, app('currentStore')]);
        $dto = ListUsersDTO::fromRequest($request, $store);
        $users = $action->execute($dto);

        return $this->paginated(
            paginator: $users,
            data: AdminUserResource::collection($users),
        );
    }

    public function show(Request $request, GetUserAction $action, int $store, int $user): JsonResponse
    {
        $this->authorize('view', [MembershipPolicy::class, app('currentStore')]);
        $dto = GetUserDTO::fromRequest($request, $store, $user);
        $userModel = $action->execute($dto);

        return $this->success(new AdminUserDetailResource($userModel));
    }

    public function block(Request $request, BlockUserAction $action, int $store, int $user): JsonResponse
    {
        $this->authorize('update', [MembershipPolicy::class, app('currentStore')]);
        $dto = BlockUserDTO::fromRequest($request, $store, $user);
        $blockedUser = $action->execute($dto);

        return $this->success(
            new AdminUserResource($blockedUser),
            __('admin.user_blocked')
        );
    }

    public function unblock(Request $request, UnblockUserAction $action, int $store, int $user): JsonResponse
    {
        $this->authorize('update', [MembershipPolicy::class, app('currentStore')]);
        $dto = UnblockUserDTO::fromRequest($request, $store, $user);
        $unblockedUser = $action->execute($dto);

        return $this->success(
            new AdminUserResource($unblockedUser),
            __('admin.user_unblocked')
        );
    }

    public function destroy(Request $request, DeleteUserAction $action, int $store, int $user): JsonResponse
    {
        $this->authorize('delete', [MembershipPolicy::class, app('currentStore')]);
        $dto = DeleteUserDTO::fromRequest($request, $store, $user);
        $action->execute($dto);

        return $this->success(null, __('admin.user_deleted'));
    }

    public function restore(Request $request, RestoreUserAction $action, int $store, int $user): JsonResponse
    {
        $this->authorize('update', [MembershipPolicy::class, app('currentStore')]);
        $dto = RestoreUserDTO::fromRequest($request, $store, $user);
        $restoredUser = $action->execute($dto);

        return $this->success(
            new AdminUserResource($restoredUser),
            __('admin.user_restored')
        );
    }
}
