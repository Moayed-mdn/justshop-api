<?php

namespace App\Actions\Store;

use App\Enums\Auth\OnboardingStepEnum;
use App\DTOs\Store\CreateStoreDTO;
use App\Models\Store;
use App\Models\User;
use App\Repositories\Store\StoreRepository;
use App\Services\Auth\OnboardingTransitionService;
use App\Services\Store\StoreSlugService;
use App\Domain\Shared\Events\StoreCreated;
use App\Enums\Auth\ActorContextEnum;
use App\Exceptions\Domain\InvalidIdentityDomainAccessException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateStoreAction
{
    public function __construct(
        private StoreRepository $storeRepository,
        private StoreSlugService $slugService,
        private OnboardingTransitionService $onboardingTransitionService,
    ) {}

    public function execute(CreateStoreDTO $dto): Store
    {
        /** @var User $user */
        $user = User::findOrFail($dto->ownerId);

        // Architectural safety: ensure customer actors cannot create stores.
        if ($user->getActorContext() === ActorContextEnum::CUSTOMER) {
            throw new InvalidIdentityDomainAccessException('Customer actors cannot create stores.');
        }

        // Normalize slug before processing.
        $normalizedSlug = $this->slugService->normalize($dto->slug);
        $initialOnboardingStep = $user->onboarding_step;

        return DB::transaction(function () use ($dto, $normalizedSlug, $user, $initialOnboardingStep) {
            /** @var User $lockedUser */
            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Reject stale duplicate first-store submissions that were started
            // before a concurrent request already advanced onboarding.
            if (
                $initialOnboardingStep === OnboardingStepEnum::CREATE_STORE
                && $lockedUser->onboarding_step === OnboardingStepEnum::STORE_CREATION_IN_PROGRESS
            ) {
                throw ValidationException::withMessages([
                    'store' => 'Store creation is already in progress.',
                ]);
            }

            if (
                $initialOnboardingStep === OnboardingStepEnum::CREATE_STORE
                && $lockedUser->onboarding_step !== OnboardingStepEnum::CREATE_STORE
                && $lockedUser->stores()->exists()
            ) {
                throw ValidationException::withMessages([
                    'store' => 'Your first store has already been created.',
                ]);
            }

            // Final availability check within transaction to prevent race conditions.
            if (!$this->slugService->isAvailable($normalizedSlug)) {
                throw ValidationException::withMessages([
                    'slug' => __('store.slug_taken'),
                ]);
            }

            // Mark onboarding as in-progress to prevent duplicate concurrent creation.
            // This is idempotent — if already past this step, the service is a no-op.
            if ($lockedUser->onboarding_step === OnboardingStepEnum::CREATE_STORE) {
                $this->onboardingTransitionService->transition(
                    $lockedUser,
                    OnboardingStepEnum::STORE_CREATION_IN_PROGRESS,
                );
            }

            // Create store with normalized slug.
            $dto->slug = $normalizedSlug;
            $store = $this->storeRepository->create($dto);

            // Reload user to get fresh onboarding_step after the transition above.
            $lockedUser->refresh();

            // Advance to STORE_CREATED.
            if ($lockedUser->onboarding_step === OnboardingStepEnum::STORE_CREATION_IN_PROGRESS) {
                $this->onboardingTransitionService->transition(
                    $lockedUser,
                    OnboardingStepEnum::STORE_CREATED,
                );
            }

            // Set as last active store.
            $lockedUser->update(['last_active_store_id' => $store->id]);

            // Dispatch Domain Event after commit.
            DB::afterCommit(function () use ($store, $lockedUser) {
                StoreCreated::dispatch(
                    $store->id,
                    $lockedUser->id,
                    $store->slug,
                    $store->name
                );
            });

            return $store;
        });
    }
}
