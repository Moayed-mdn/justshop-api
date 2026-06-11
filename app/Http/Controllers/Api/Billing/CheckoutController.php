<?php

namespace App\Http\Controllers\Api\Billing;

use App\Actions\Billing\CreateCheckoutSessionAction;
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

        // Get billing account
        $billingAccount = $this->billingAccountRepository->findByOwner($user->id);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found. Please create a store first.',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
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
