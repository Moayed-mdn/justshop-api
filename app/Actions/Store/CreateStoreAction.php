<?php

namespace App\Actions\Store;

use App\Enums\Auth\OnboardingStepEnum;
use App\DTOs\Store\CreateStoreDTO;
use App\Models\Store;
use App\Models\User;
use App\Repositories\Store\StoreRepository;
use App\Services\Store\StoreSlugService;
use App\Domain\Shared\Events\StoreCreated;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStoreAction
{
    public function __construct(
        private StoreRepository $storeRepository,
        private StoreSlugService $slugService,
    ) {}

    public function execute(CreateStoreDTO $dto): Store
    {
        // Normalize slug before processing
        $normalizedSlug = $this->slugService->normalize($dto->slug);

        return DB::transaction(function () use ($dto, $normalizedSlug) {
            // Final availability check within transaction to prevent race conditions
            if (!$this->slugService->isAvailable($normalizedSlug)) {
                throw ValidationException::withMessages([
                    'slug' => __('store.slug_taken'),
                ]);
            }

            // Create store with normalized slug
            $dto->slug = $normalizedSlug;
            $store = $this->storeRepository->create($dto);

            /** @var User $user */
            $user = User::findOrFail($dto->ownerId);

            // Update onboarding status if it's the first store
            if ($user->onboarding_step === OnboardingStepEnum::CREATE_STORE) {
                $user->update([
                    'onboarding_step' => OnboardingStepEnum::COMPLETED,
                    'onboarding_completed_at' => now(),
                ]);
            }

            // Set as last active store
            $user->update(['last_active_store_id' => $store->id]);

            // Dispatch Domain Event after commit
            DB::afterCommit(function () use ($store, $user) {
                StoreCreated::dispatch(
                    $store->id,
                    $user->id,
                    $store->slug,
                    $store->name
                );
            });

            return $store;
        });
    }
}
