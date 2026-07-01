<?php

namespace App\Http\Controllers\Api\Merchant;

use App\DTOs\Admin\User\BlockUserDTO;
use App\DTOs\Admin\User\CreateUserDTO;
use App\DTOs\Admin\User\DeleteUserDTO;
use App\DTOs\Admin\User\GetUserDTO;
use App\DTOs\Admin\User\ListUsersDTO;
use App\DTOs\Admin\User\RestoreUserDTO;
use App\DTOs\Admin\User\UnblockUserDTO;
use App\Actions\Admin\User\BlockUserAction;
use App\Actions\Admin\User\CreateUserAction;
use App\Actions\Admin\User\DeleteUserAction;
use App\Actions\Admin\User\GetUserAction;
use App\Actions\Admin\User\ListUsersAction;
use App\Actions\Admin\User\RestoreUserAction;
use App\Actions\Admin\User\UnblockUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\CreateUserRequest;
use App\Http\Requests\Admin\User\ListUsersRequest;
use App\Http\Resources\Admin\User\AdminUserDetailResource;
use App\Http\Resources\Admin\User\AdminUserResource;
use App\Models\Store;
use App\Models\User as ManagedUser;
use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;

class AdminUserController extends Controller
{
    public function index(ListUsersRequest $request, ListUsersAction $action, Store $store): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('viewAny', [ManagedUser::class, $storeModel]);
        
        $dto = ListUsersDTO::fromRequest($request, $store->id);
        $users = $action->execute($dto);

        return $this->paginated(
            paginator: $users,
            data: AdminUserResource::collection($users),
        );
    }

    public function store(CreateUserRequest $request, CreateUserAction $action, Store $store): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('create', [ManagedUser::class, $storeModel]);
        
        $dto = CreateUserDTO::fromRequest($request, $store->id);
        $user = $action->execute($dto);

        return $this->success(
            new AdminUserResource($user),
            __('admin.user_created'),
            201
        );
    }

    public function show(Request $request, GetUserAction $action, Store $store, int $user): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('view', [ManagedUser::class, $storeModel]);
        
        $dto = GetUserDTO::fromRequest($request, $store->id, $user);
        $userModel = $action->execute($dto);

        return $this->success(new AdminUserDetailResource($userModel));
    }

    public function block(Request $request, BlockUserAction $action, Store $store, int $user): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('update', [ManagedUser::class, $storeModel]);
        
        $dto = BlockUserDTO::fromRequest($request, $store->id, $user);
        $blockedUser = $action->execute($dto);

        return $this->success(
            new AdminUserResource($blockedUser),
            __('admin.user_blocked')
        );
    }

    public function unblock(Request $request, UnblockUserAction $action, Store $store, int $user): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('update', [ManagedUser::class, $storeModel]);
        
        $dto = UnblockUserDTO::fromRequest($request, $store->id, $user);
        $unblockedUser = $action->execute($dto);

        return $this->success(
            new AdminUserResource($unblockedUser),
            __('admin.user_unblocked')
        );
    }

    public function destroy(Request $request, DeleteUserAction $action, Store $store, int $user): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('delete', [ManagedUser::class, $storeModel]);
        
        $dto = DeleteUserDTO::fromRequest($request, $store->id, $user);
        $action->execute($dto);

        return $this->success(null, __('admin.user_deleted'));
    }

    public function restore(Request $request, RestoreUserAction $action, Store $store, int $user): JsonResponse
    {
        // Wave 2 Remediation: Normalize policy ownership from generic currentStore to explicit Store model
        $storeModel = $store;
        $this->authorize('update', [ManagedUser::class, $storeModel]);
        
        $dto = RestoreUserDTO::fromRequest($request, $store->id, $user);
        $restoredUser = $action->execute($dto);

        return $this->success(
            new AdminUserResource($restoredUser),
            __('admin.user_restored')
        );
    }
}
