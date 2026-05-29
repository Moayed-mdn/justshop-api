<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use App\Actions\Store\GetProvisioningStatusAction;
use App\Http\Resources\Store\ProvisioningStatusResource;
use Illuminate\Http\Request;

class ProvisioningStatusController extends Controller
{
    use \App\Traits\ApiResponserTrait;

    public function __construct(private readonly GetProvisioningStatusAction $getProvisioningStatusAction) {}

    public function __invoke(Request $request, Store $store): JsonResponse
    {
        $user = $request->user();
        $hasAccess = $user !== null
            && ($store->owner_id === $user->id || $user->stores()->whereKey($store->id)->exists());

        if (!$hasAccess) {
            throw new UnauthorizedStoreAccessException();
        }

        $this->authorize('view', $store);

        $dto = $this->getProvisioningStatusAction->execute($store);

        return $this->success(new ProvisioningStatusResource($dto));
    }
}
