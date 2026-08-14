<?php

namespace App\Actions\Billing;

use App\Models\Plan;
use App\Repositories\Subscription\PlanRepository;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Support\Facades\Log;

class ArchivePlanAction
{
    public function __construct(
        private readonly PlanRepository $planRepository,
        private readonly AuditLoggerInterface $auditLogger,
    ) {}

    /**
     * Archive a plan (mark as inactive and non-public).
     * This is always safe and does not affect existing subscribers.
     */
    public function execute(int $planId): Plan
    {
        $plan = $this->planRepository->findByIdOrFail($planId);

        $plan->update([
            'is_active' => false,
            'is_public' => false,
        ]);

        $this->auditLogger->record('platform.plan.archived', [
            'resource_type' => 'plan',
            'resource_id' => $plan->id,
            'resource_name' => $plan->code,
        ]);

        Log::channel('billing')->info('plan.archived', [
            'plan_id' => $plan->id,
            'code' => $plan->code,
        ]);

        return $plan->fresh();
    }
}
