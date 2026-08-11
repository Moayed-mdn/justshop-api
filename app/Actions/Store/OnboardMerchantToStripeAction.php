<?php

declare(strict_types=1);

namespace App\Actions\Store;

use App\DTOs\Store\OnboardMerchantToStripeDTO;
use App\Exceptions\BaseApiException;
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

/**
 * Onboard a merchant to Stripe Connect Express for split payments.
 * Creates a Stripe Connect account if none exists, then generates an
 * onboarding link for the merchant to complete their account setup.
 */
class OnboardMerchantToStripeAction
{
    public function __construct(
        private StripeClient $stripe,
    ) {}

    /**
     * Execute merchant onboarding to Stripe Connect.
     * 
     * @param OnboardMerchantToStripeDTO $dto
     * @return string The onboarding URL for the merchant to complete setup
     * @throws BaseApiException
     */
    public function execute(OnboardMerchantToStripeDTO $dto): string
    {
        $store = Store::with('owner')->findOrFail($dto->storeId);

        // Create Stripe Connect account if not already linked
        if (empty($store->stripe_account_id)) {
            try {
                $account = $this->createStripeConnectAccount($store);
                
                $store->update([
                    'stripe_account_id' => $account->id,
                    'stripe_account_type' => 'express',
                ]);

                Log::info('Stripe Connect account created', [
                    'store_id' => $store->id,
                    'stripe_account_id' => $account->id,
                ]);
            } catch (ApiErrorException $e) {
                Log::error('Failed to create Stripe Connect account', [
                    'store_id' => $store->id,
                    'error' => $e->getMessage(),
                ]);

                throw new BaseApiException(
                    message: 'Failed to create Stripe Connect account: ' . $e->getMessage(),
                    statusCode: 500,
                    errorCode: \App\Enums\ErrorCode::SYS_001->value
                );
            }
        }

        // Generate account onboarding link
        try {
            $accountLink = $this->createAccountLink($store->stripe_account_id);

            Log::info('Stripe Connect onboarding link created', [
                'store_id' => $store->id,
                'stripe_account_id' => $store->stripe_account_id,
            ]);

            return $accountLink->url;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe Connect onboarding link', [
                'store_id' => $store->id,
                'stripe_account_id' => $store->stripe_account_id,
                'error' => $e->getMessage(),
            ]);

            throw new BaseApiException(
                message: 'Failed to create onboarding link: ' . $e->getMessage(),
                statusCode: 500,
                errorCode: \App\Enums\ErrorCode::SYS_001->value
            );
        }
    }

    /**
     * Create a new Stripe Connect Express account.
     */
    private function createStripeConnectAccount(Store $store): object
    {
        return $this->stripe->accounts->create([
            'type' => 'express',
            'email' => $store->owner->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'individual',
            'metadata' => [
                'store_id' => (string) $store->id,
                'store_slug' => $store->slug,
                'store_name' => $store->name,
            ],
        ]);
    }

    /**
     * Create an account onboarding link for the merchant.
     */
    private function createAccountLink(string $stripeAccountId): object
    {
        $returnBaseUrl = config('services.stripe.connect_return_base_url');

        return $this->stripe->accountLinks->create([
            'account' => $stripeAccountId,
            'refresh_url' => "{$returnBaseUrl}/merchant/settings/payments/stripe/onboard",
            'return_url' => "{$returnBaseUrl}/merchant/settings/payments/stripe/success",
            'type' => 'account_onboarding',
        ]);
    }
}
