<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\GetBootstrapDTO;
use App\DTOs\Auth\UpdateActiveStoreDTO;
use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;
use App\Models\User;
use App\Models\Store;

class UpdateActiveStoreAction
{
    public function __construct(
        private GetBootstrapAction $getBootstrapAction,
    ) {}

    public function execute(UpdateActiveStoreDTO $dto): GetBootstrapResponseDTO
    {
        // Wave 2 Remediation: Authorization removed from Action
        // Authorization now explicitly owned by StorePolicy::switchStore() in controller
        // This action is now orchestration-focused only
        
        $user = User::findOrFail($dto->userId);
        $store = Store::findOrFail($dto->storeId);

        // Update last active store
        $user->update([
            'last_active_store_id' => $dto->storeId,
        ]);

        // Return refreshed bootstrap payload
        return $this->getBootstrapAction->execute(
            new GetBootstrapDTO($user->id)
        );
    }
}
