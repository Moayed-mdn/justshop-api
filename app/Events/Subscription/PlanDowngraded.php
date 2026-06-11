<?php

namespace App\Events\Subscription;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlanDowngraded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public Plan $oldPlan,
        public Plan $newPlan,
    ) {}
}
