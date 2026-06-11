<?php

namespace App\DTOs\Subscription;

use Illuminate\Http\Request;

class StartTrialDTO
{
    public function __construct(
        public int $ownerUserId,
        public int $storeId,
        public string $planCode = 'starter',
    ) {}

    public static function fromRequest(Request $request, int $ownerUserId, int $storeId): self
    {
        return new self(
            ownerUserId: $ownerUserId,
            storeId: $storeId,
            planCode: $request->string('plan_code', 'starter')->toString(),
        );
    }
}
