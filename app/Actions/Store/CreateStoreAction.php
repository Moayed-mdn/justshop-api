<?php

namespace App\Actions\Store;

use App\Actions\Subscription\StartTrialAction;
use App\DTOs\Subscription\StartTrialDTO;
use App\Enums\Auth\OnboardingStepEnum;
use App\DTOs\Store\CreateStoreDTO;
use App\Models\Store;
use App\Models\User;
use App\Repositories\Billing\BillingAccountRepository;
use App\Repositories\Store\StoreRepository;
use App\Services\Auth\OnboardingTransitionService;
use App\Services\Store\StoreSlugService;
use App\Domain\Shared\Events\StoreCreated;
use App\Enums\Auth\ActorContextEnum;
use App\Enums\Entitlement\FeatureKeyEnum;
use App\Exceptions\Domain\InvalidIdentityDomainAccessException;
use App\Exceptions\Entitlement\QuotaExceededException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CreateStoreAction
{
    public function __construct(
        private StoreRepository $storeRepository,
        private StoreSlugService $slugService,
        private OnboardingTransitionService $onboardingTransitionService,
        private BillingAccountRepository $billingAccountRepository,
        private StartTrialAction $startTrialAction,
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

            // ── Atomic quota guard, race-condition safe ─────────────────────
            // No billing account = first store = always allowed (trial starts below).
            // lockForUpdate() here serializes any concurrent requests for the same owner
            // on this check specifically — this was the missing piece in the earlier solution.
            $billingAccount = $this->billingAccountRepository->findByOwnerForUpdate($lockedUser->id);

            if (
                $billingAccount
                && $billingAccount->stores_max !== null
                && $billingAccount->stores_count >= $billingAccount->stores_max
            ) {
                throw new QuotaExceededException(
                    featureKey: FeatureKeyEnum::STORES_MAX->value,
                    limit: $billingAccount->stores_max,
                );
            }
            // ─────────────────────────────────────────────────────────────────

            if (!$this->slugService->isAvailable($normalizedSlug)) {
                throw ValidationException::withMessages([
                    'slug' => __('store.slug_taken'),
                ]);
            }

            if ($lockedUser->onboarding_step === OnboardingStepEnum::CREATE_STORE) {
                $this->onboardingTransitionService->transition(
                    $lockedUser,
                    OnboardingStepEnum::STORE_CREATION_IN_PROGRESS,
                );
            }

            $dto->slug = $normalizedSlug;
            $store = $this->storeRepository->create($dto);

            $lockedUser->refresh();

            if ($lockedUser->onboarding_step === OnboardingStepEnum::STORE_CREATION_IN_PROGRESS) {
                $this->onboardingTransitionService->transition(
                    $lockedUser,
                    OnboardingStepEnum::STORE_CREATED,
                );
            }

            $lockedUser->update(['last_active_store_id' => $store->id]);

            // Reuse same $billingAccount from check above — no second query
            if (!$billingAccount) {
                try {
                    $this->startTrialAction->execute(
                        new StartTrialDTO(
                            ownerUserId: $lockedUser->id,
                            storeId: $store->id,
                            planCode: 'starter',
                        )
                    );

                    Log::channel('billing')->info('trial.auto_started', [
                        'store_id' => $store->id,
                        'owner_user_id' => $lockedUser->id,
                        'trigger' => 'store_creation',
                    ]);
                } catch (\Exception $e) {
                    Log::channel('billing')->error('trial.auto_start_failed', [
                        'store_id' => $store->id,
                        'owner_user_id' => $lockedUser->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::afterCommit(function () use ($store, $lockedUser) {
                StoreCreated::dispatch($store->id, $lockedUser->id, $store->slug, $store->name);
            });

            return $store;
        });
    }
}
