<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Shared\Payment;

use App\Actions\Payment\HandleStripeWebhookAction;
use App\DTOs\Payment\StripeWebhookDTO;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __construct(
        private HandleStripeWebhookAction $handleStripeWebhookAction
    ) {}

    /**
     * Handle incoming Stripe webhooks.
     */
    public function handle(Request $request): JsonResponse
    {
        $this->handleStripeWebhookAction->execute(
            StripeWebhookDTO::fromRequest($request)
        );

        return $this->success(null, 'Webhook handled.');
    }
}
