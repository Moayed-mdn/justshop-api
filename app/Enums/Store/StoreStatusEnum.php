<?php

declare(strict_types=1);

namespace App\Enums\Store;

/**
 * StoreStatusEnum
 *
 * Production-grade store lifecycle states.
 *
 * Transition rules:
 *   pending_setup  → active           (owner completes onboarding)
 *   active         → suspended        (system/admin: billing failure, policy violation)
 *   active         → archived         (owner: voluntary close)
 *   suspended      → active           (admin: billing resolved / policy cleared)
 *   suspended      → archived         (admin: permanent suspension)
 *   archived       → deleted_pending  (automatic: after grace period)
 *   any            → deleted_pending  (admin: force delete with grace period)
 *
 * is_active mapping (backwards compatibility):
 *   active         → is_active = true
 *   all others     → is_active = false
 */
enum StoreStatusEnum: string
{
    /**
     * Store record created but onboarding not yet complete.
     * Store is not yet operational.
     */
    case PENDING_SETUP = 'pending_setup';

    /**
     * Store is currently being provisioned (e.g., creating resources, setting up services).
     * Not yet operational.
     */
    case PROVISIONING = 'provisioning';

    /**
     * Store is temporarily disabled. Could be due to maintenance, system issues, or manual intervention.
     * Different from suspended (which is usually billing/policy related) or archived (owner initiated close).
     */
    case DISABLED = 'disabled';

    /**
     * Fully operational. Accepting traffic.
     */
    case ACTIVE = 'active';

    /**
     * Temporarily disabled. Billing failure or policy violation.
     * Owner cannot access dashboard. Storefront returns 503.
     */
    case SUSPENDED = 'suspended';

    /**
     * Voluntarily closed by owner. Data retained.
     * Not publicly accessible.
     */
    case ARCHIVED = 'archived';

    /**
     * Scheduled for hard deletion after grace period.
     * Read-only access for data export only.
     */
    case DELETED_PENDING = 'deleted_pending';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Returns true if the store should be considered operationally active.
     * Used to derive the is_active boolean for backwards compatibility.
     */
    public function isOperational(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Returns the allowed next transitions from this status.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING_SETUP    => [self::PROVISIONING],
            self::PROVISIONING     => [self::ACTIVE],
            self::ACTIVE           => [self::SUSPENDED, self::ARCHIVED, self::DISABLED],
            self::SUSPENDED        => [self::ACTIVE, self::ARCHIVED],
            self::DISABLED         => [self::ACTIVE],
            self::ARCHIVED         => [self::DELETED_PENDING],
            self::DELETED_PENDING  => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
