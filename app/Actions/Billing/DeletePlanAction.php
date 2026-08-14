<?php

namespace App\Actions\Billing;

use App\Enums\ErrorCode;
use App\Models\Plan;
use App\Repositories\Subscription\PlanRepository;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Facades\Log;

class DeletePlanAction
{
    public function __construct(
        private readonly PlanRepository $planRepository,
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Soft-delete a plan.
     * Only allowed if the plan has NEVER been referenced by any subscription.
     */
    public function execute(int $planId): void
    {
        $plan = $this->planRepository->findByIdOrFail($planId);

        // Block deletion if plan is in use (any subscription, any status, ever)
        if ($this->planRepository->isInUse($planId)) {
            throw new \DomainException(
                ErrorCode::BIL_015->value . ': Plan is in use by one or more subscriptions. Use archive instead.'
            );
        }

        $code = $plan->code;

        // Soft delete
        $plan->delete();

        $this->auditLogger->record('platform.plan.deleted', [
            'resource_type' => 'plan',
            'resource_id' => $planId,
            'resource_name' => $code,
        ]);

        Log::channel('billing')->info('plan.deleted', [
            'plan_id' => $planId,
            'code' => $code,
        ]);
    }
}
