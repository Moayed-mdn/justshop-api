<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use App\Actions\Store\GetProvisioningStatusAction;
use App\Http\Resources\Store\ProvisioningStatusResource;

class ProvisioningStatusController extends Controller
{
    use \App\Traits\ApiResponserTrait;

    public function __construct(private readonly GetProvisioningStatusAction $getProvisioningStatusAction) {}

    public function __invoke(Store $store): JsonResponse
    {
        $this->authorize('view', $store);

        $dto = $this->getProvisioningStatusAction->execute($store);

        return $this->success(new ProvisioningStatusResource($dto));
    }
}
