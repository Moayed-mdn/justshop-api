<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform\Billing;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Repositories\Platform\PlatformSubscriptionRepository;
use App\Traits\ApiResponserTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Platform-admin read views over merchant subscriptions.
 *
 * This is the counterpart to PlatformUserController / PlatformOrderController
 * for the billing domain — until now there was no platform-side way to list
 * or inspect subscriptions at all, only manage Plan definitions
 * (see PlatformPlanController). Registered under the same `billing` route
 * prefix and platform_admin authority as plans — no per-action authorize()
 * calls, consistent with PlatformPlanController's convention.
 */
class PlatformSubscriptionController extends Controller
{
    use ApiResponserTrait;

    public function __construct(
        private readonly PlatformSubscriptionRepository $repository,
    ) {}

    /**
     * List subscriptions across all merchants.
     *
     * GET /v1/platform/billing/subscriptions
     * Query params: search, status, plan_id, sort, order, per_page
     */
    public function index(Request $request): JsonResponse
    {
        $subscriptions = $this->repository->list(
            search: $request->string('search')->toString() ?: null,
            status: $request->string('status')->toString() ?: null,
            planId: $request->filled('plan_id') ? (int) $request->input('plan_id') : null,
            sortBy: $request->string('sort', 'created_at')->toString(),
            sortOrder: $request->string('order', 'desc')->toString(),
            perPage: (int) $request->integer('per_page', 25),
        );

        $data = $subscriptions->getCollection()->map(function (Subscription $subscription) {
            $owner = $subscription->billingAccount?->owner;

            return [
                'id' => $subscription->id,
                'status' => $subscription->status->value,
                'billing_cycle' => $subscription->billing_cycle?->value,
                'plan' => [
                    'id' => $subscription->plan?->id,
                    'code' => $subscription->plan?->code,
                    'name' => $this->translatedField($subscription->plan?->name),
                ],
                'plan_price' => $subscription->planPrice ? [
                    'amount_cents' => $subscription->planPrice->amount_cents,
                    'currency' => $subscription->planPrice->currency,
                ] : null,
                'merchant' => [
                    'billing_account_id' => $subscription->billing_account_id,
                    'owner_name' => $owner?->name,
                    'owner_email' => $owner?->email,
                ],
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'current_period_ends_at' => $subscription->current_period_ends_at?->toIso8601String(),
                'cancel_at_period_end' => $subscription->cancel_at_period_end,
                'canceled_at' => $subscription->canceled_at?->toIso8601String(),
                'created_at' => $subscription->created_at->toIso8601String(),
            ];
        })->all();

        return $this->paginated($subscriptions, $data);
    }

    /**
     * Show a single subscription with full billing context: plan, invoices,
     * the merchant's stores, and the status-change event timeline.
     *
     * GET /v1/platform/billing/subscriptions/{subscription}
     */
    public function show(int $subscription): JsonResponse
    {
        $subscriptionModel = $this->repository->findWithRelations($subscription);

        if (!$subscriptionModel) {
            return $this->error(
                'Subscription not found',
                404,
                errorCode: \App\Enums\ErrorCode::SUB_001->value
            );
        }

        $owner = $subscriptionModel->billingAccount?->owner;

        return $this->success([
            'id' => $subscriptionModel->id,
            'status' => $subscriptionModel->status->value,
            'billing_cycle' => $subscriptionModel->billing_cycle?->value,
            'provider' => $subscriptionModel->provider,
            'provider_subscription_id' => $subscriptionModel->provider_subscription_id,
            'provider_status' => $subscriptionModel->provider_status,
            'provider_synced_at' => $subscriptionModel->provider_synced_at?->toIso8601String(),
            'plan' => $subscriptionModel->plan ? [
                'id' => $subscriptionModel->plan->id,
                'code' => $subscriptionModel->plan->code,
                'name' => $this->translatedField($subscriptionModel->plan->name),
                'tier' => $subscriptionModel->plan->tier?->value,
            ] : null,
            'pending_plan' => $subscriptionModel->pendingPlan ? [
                'id' => $subscriptionModel->pendingPlan->id,
                'code' => $subscriptionModel->pendingPlan->code,
                'name' => $this->translatedField($subscriptionModel->pendingPlan->name),
            ] : null,
            'pending_plan_effective_at' => $subscriptionModel->pending_plan_effective_at?->toIso8601String(),
            'plan_price' => $subscriptionModel->planPrice ? [
                'amount_cents' => $subscriptionModel->planPrice->amount_cents,
                'currency' => $subscriptionModel->planPrice->currency,
                'billing_cycle' => $subscriptionModel->planPrice->billing_cycle?->value,
            ] : null,
            'trial_starts_at' => $subscriptionModel->trial_starts_at?->toIso8601String(),
            'trial_ends_at' => $subscriptionModel->trial_ends_at?->toIso8601String(),
            'current_period_starts_at' => $subscriptionModel->current_period_starts_at?->toIso8601String(),
            'current_period_ends_at' => $subscriptionModel->current_period_ends_at?->toIso8601String(),
            'grace_period_ends_at' => $subscriptionModel->grace_period_ends_at?->toIso8601String(),
            'cancel_at_period_end' => $subscriptionModel->cancel_at_period_end,
            'canceled_at' => $subscriptionModel->canceled_at?->toIso8601String(),
            'ended_at' => $subscriptionModel->ended_at?->toIso8601String(),
            'created_at' => $subscriptionModel->created_at->toIso8601String(),
            'merchant' => [
                'billing_account_id' => $subscriptionModel->billing_account_id,
                'owner_id' => $owner?->id,
                'owner_name' => $owner?->name,
                'owner_email' => $owner?->email,
                'legal_name' => $subscriptionModel->billingAccount?->legal_name,
                'billing_email' => $subscriptionModel->billingAccount?->billing_email,
                'stores' => $owner?->stores->map(fn ($store) => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'slug' => $store->slug,
                    'status' => $store->status->value,
                ])->all() ?? [],
            ],
            'invoices' => $subscriptionModel->invoices->map(fn ($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status->value,
                'currency' => $invoice->currency,
                'total_cents' => $invoice->total_cents,
                'amount_paid_cents' => $invoice->amount_paid_cents,
                'amount_due_cents' => $invoice->amount_due_cents,
                'issued_at' => $invoice->issued_at?->toIso8601String(),
                'paid_at' => $invoice->paid_at?->toIso8601String(),
                'hosted_invoice_url' => $invoice->hosted_invoice_url,
            ])->all(),
            // Status-change audit trail — useful for spotting sync issues
            // between Stripe and local state (e.g. an out-of-order webhook
            // that got skipped) without digging through log files.
            'events' => $subscriptionModel->events->map(fn ($event) => [
                'id' => $event->id,
                'event_type' => $event->event_type?->value,
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'source' => $event->source,
                'reason' => $event->reason,
                'actor' => $event->actor?->name,
                'created_at' => $event->created_at->toIso8601String(),
            ])->all(),
        ]);
    }

    /**
     * Plan.name (and similar) are stored as a translatable JSON map, e.g.
     * {"en": "Growth", "ar": "..."}. Resolve the current locale with a
     * fallback to the first available translation, same pattern used in
     * EnhancedCheckoutService for product names.
     */
    private function translatedField(?array $field): ?string
    {
        if ($field === null) {
            return null;
        }

        return $field[app()->getLocale()] ?? array_values($field)[0] ?? null;
    }
}
