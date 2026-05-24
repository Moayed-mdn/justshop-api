<?php

declare(strict_types=1);

namespace App\Enums\Auth;

enum OnboardingStepEnum: string
{
    /**
     * User registered but has not verified their email.
     * Formerly: PENDING_VERIFICATION (value preserved for backwards compatibility).
     */
    case PENDING_VERIFICATION = 'pending_verification';

    /**
     * Email verified. User must now create their first store.
     * Formerly: CREATE_STORE (value preserved for backwards compatibility).
     */
    case CREATE_STORE = 'create_store';

    /**
     * Store creation has been initiated (transaction opened).
     * Guards against duplicate store creation on concurrent requests.
     */
    case STORE_CREATION_IN_PROGRESS = 'store_creation_in_progress';

    /**
     * Store record created and owner attached to store_user pivot.
     * Store exists but has not been configured yet.
     */
    case STORE_CREATED = 'store_created';

    /**
     * Store has been configured (currency, timezone, domain set).
     * Ready for full dashboard access.
     */
    case STORE_CONFIGURED = 'store_configured';

    /**
     * Onboarding fully complete. Full dashboard access granted.
     * Formerly: COMPLETED (value preserved for backwards compatibility).
     */
    case COMPLETED = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Returns true if this step grants full dashboard access.
     */
    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    /**
     * Returns true if the user is blocked from dashboard access.
     */
    public function isBlocked(): bool
    {
        return !$this->isCompleted();
    }

    /**
     * Returns the allowed next transitions from this step.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING_VERIFICATION      => [self::CREATE_STORE],
            self::CREATE_STORE              => [self::STORE_CREATION_IN_PROGRESS],
            self::STORE_CREATION_IN_PROGRESS => [self::STORE_CREATED, self::CREATE_STORE],
            self::STORE_CREATED             => [self::STORE_CONFIGURED, self::COMPLETED],
            self::STORE_CONFIGURED          => [self::COMPLETED],
            self::COMPLETED                 => [],
        };
    }

    /**
     * Returns true if transitioning to $target is a valid next step.
     */
    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
