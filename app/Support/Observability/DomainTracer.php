<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Enums\System\ApiDomainEnum;
use App\Enums\Auth\ActorContextEnum;
use Illuminate\Support\Facades\Log;

class DomainTracer
{
    /**
     * Log a domain-specific operational event.
     */
    public static function logEvent(
        string $message, 
        ApiDomainEnum $domain, 
        ?ActorContextEnum $actor = null, 
        array $context = []
    ): void {
        Log::info("[{$domain->value}]" . ($actor ? "[{$actor->value}]" : "") . ": {$message}", $context);
    }

    /**
     * Log a security-sensitive event.
     */
    public static function logSecurity(
        string $message, 
        array $context = []
    ): void {
        Log::warning("[SECURITY]: {$message}", $context);
    }
}
