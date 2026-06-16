<?php

namespace App\Http\Controllers\Api\Billing;

use App\Actions\Billing\CreateBillingAccountAction;
use App\Actions\Billing\CreateCheckoutSessionAction;
use App\DTOs\Billing\CreateBillingAccountDTO;
use App\DTOs\Billing\CreateCheckoutSessionDTO;
use App\Enums\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CreateCheckoutSessionRequest;
use App\Repositories\Billing\BillingAccountRepository;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private CreateCheckoutSessionAction $createCheckoutSessionAction,
        private CreateBillingAccountAction $createBillingAccountAction,
        private BillingAccountRepository $billingAccountRepository,
    ) {}

    /**
     * Create a Stripe Checkout session for subscription signup.
     * 
     * POST /api/v1/billing/checkout
     */
    public function createSession(CreateCheckoutSessionRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Get or create billing account
        $billingAccount = $this->billingAccountRepository->findByOwner($user->id);

        if (!$billingAccount) {
            $billingAccount = $this->createBillingAccountAction->execute(
                new CreateBillingAccountDTO(
                    ownerUserId: $user->id,
                    billingEmail: $user->email,
                )
            );
        }

        // Create checkout session
        $session = $this->createCheckoutSessionAction->execute(
            CreateCheckoutSessionDTO::fromRequest($request, $billingAccount->id)
        );

        return $this->success([
            'session_id' => $session['session_id'],
            'url' => $session['url'],
            'expires_at' => $session['expires_at'],
        ], 'Checkout session created successfully');
    }

    /**
     * Start a 14-day free trial (alias for createSession).
     * 
     * POST /api/v1/billing/trial/start
     */
    public function startTrial(CreateCheckoutSessionRequest $request): JsonResponse
    {
        return $this->createSession($request);
    }
}
