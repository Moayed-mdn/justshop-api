<?php

declare(strict_types=1);

namespace App\Support\Security;

enum SecurityEventType: string
{
    case AUTH_LOGIN_FAILED = 'auth.login.failed';
    case AUTH_GUARD_MISMATCH = 'auth.guard.mismatch';
    case AUTH_ONBOARDING_DENIED = 'auth.onboarding.denied';
    case TENANT_STORE_MISMATCH = 'tenant.store_mismatch';
    case AUTHORIZATION_DENIED = 'authorization.denied';

    // Wave 7: Membership Governance
    case MEMBERSHIP_LIFECYCLE_TRANSITION = 'membership.lifecycle.transition';
    case MEMBERSHIP_LIFECYCLE_INVALID_TRANSITION = 'membership.lifecycle.invalid_transition';
    case MEMBERSHIP_STALE_DETECTION = 'membership.stale_detection';
    case MEMBERSHIP_SUSPENDED_PRIVILEGE_LEAKAGE = 'membership.suspended_privilege_leakage';
}
