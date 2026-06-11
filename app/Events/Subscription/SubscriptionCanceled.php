<?php

namespace App\Events\Subscription;

use App\Models\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCanceled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public bool $immediately = false,
    ) {}
}
