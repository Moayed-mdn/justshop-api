<?php

namespace App\Http\Controllers\Api\Billing;

use App\Actions\Billing\CreateBillingPortalSessionAction;
use App\DTOs\Billing\CreatePortalSessionDTO;
use App\Enums\ErrorCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\CreatePortalSessionRequest;
use App\Repositories\Billing\BillingAccountRepository;
use Illuminate\Http\JsonResponse;

class BillingPortalController extends Controller
{
    public function __construct(
        private CreateBillingPortalSessionAction $createPortalSession,
        private BillingAccountRepository $billingAccountRepo,
    ) {}

    /**
     * Create a Stripe Billing Portal session.
     * 
     * POST /api/v1/merchant/billing/portal
     */
    public function createSession(CreatePortalSessionRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $billingAccount = $this->billingAccountRepo->findByOwner($user->id);

        if (!$billingAccount) {
            return $this->error(
                message: 'Billing account not found',
                errorCode: ErrorCode::BIL_001->value,
                statusCode: 404
            );
        }

        try {
            $session = $this->createPortalSession->execute(
                new CreatePortalSessionDTO(
                    billingAccountId: $billingAccount->id,
                    returnUrl: $request->validated('return_url'),
                )
            );

            return $this->success([
                'session_id' => $session['session_id'],
                'url' => $session['url'],
            ], 'Billing portal session created successfully');
        } catch (\Exception $e) {
            return $this->error(
                message: 'Failed to create billing portal session',
                errorCode: ErrorCode::BIL_005->value,
                statusCode: 500
            );
        }
    }
}
