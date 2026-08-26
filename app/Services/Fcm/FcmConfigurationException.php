<?php

declare(strict_types=1);

namespace App\Services\Fcm;

use RuntimeException;

/**
 * Configuration/credential problems (missing service account, malformed
 * JSON, etc). Distinct from delivery failures (FcmSendResult) because
 * these mean "the system isn't set up to send push at all" rather than
 * "this one send attempt failed" — callers generally want to log this
 * loudly rather than silently swallow it like a per-token delivery error.
 */
class FcmConfigurationException extends RuntimeException
{
}
