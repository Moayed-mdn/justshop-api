<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;
use App\Services\Auth\Policy\PolicyTelemetryLogger;

trait InteractsWithPolicyTelemetry
{
    protected function decision(
        User $user,
        string $ability,
        bool $allowed,
        mixed $subject = null,
        array $extraContext = [],
    ): bool {
        return app(PolicyTelemetryLogger::class)->record(
            policyClass: static::class,
            ability: $ability,
            user: $user,
            allowed: $allowed,
            subject: $subject,
            extraContext: $extraContext,
        );
    }
}
