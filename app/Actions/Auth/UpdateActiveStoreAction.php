<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\GetBootstrapDTO;
use App\DTOs\Auth\UpdateActiveStoreDTO;
use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;
use App\Models\User;
use App\Models\Store;
use App\Exceptions\Domain\InvalidActorContextException;
use App\Exceptions\Domain\StoreMembershipException;
use App\Exceptions\Domain\InvalidStoreContextException;
use App\Enums\Auth\ActorContextEnum;

class UpdateActiveStoreAction
{
    public function __construct(
        private GetBootstrapAction $getBootstrapAction,
    ) {}

    public function execute(UpdateActiveStoreDTO $dto): GetBootstrapResponseDTO
    {
        $user = User::findOrFail($dto->userId);

        // Security: Ensure merchant only (Super Admin bypasses)
        if ($user->getActorContext() === ActorContextEnum::CUSTOMER) {
             throw new InvalidActorContextException(__('auth.merchant_only_action'));
        }

        // Security: Validate store membership
        if (!$user->isSuperAdmin()) {
            $isMember = $user->stores()
                ->where('store_id', $dto->storeId)
                ->exists();

            if (!$isMember) {
                throw new StoreMembershipException();
            }
        }

        // Security: Ensure store is active
        $store = Store::findOrFail($dto->storeId);
        if (!$store->is_active) {
            throw new InvalidStoreContextException(__('store.store_inactive'));
        }

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
