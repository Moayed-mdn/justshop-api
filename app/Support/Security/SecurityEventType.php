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
}
