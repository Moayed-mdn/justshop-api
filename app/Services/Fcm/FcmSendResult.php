<?php

declare(strict_types=1);

namespace App\Services\Fcm;

/**
 * Outcome of one FcmClient::send() call.
 *
 * Deliberately not an exception for the INVALID_TOKEN / ERROR cases: an
 * unregistered token isn't a failure of the send operation, it's useful
 * information (the caller should delete that DeviceToken), and a
 * transient error should be retried by the queue rather than thrown as an
 * exception that aborts the whole job before other tokens are tried.
 */
final class FcmSendResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly bool $tokenInvalid,
        public readonly ?string $error = null,
    ) {
    }

    public static function success(): self
    {
        return new self(successful: true, tokenInvalid: false);
    }

    public static function invalidToken(string $reason): self
    {
        return new self(successful: false, tokenInvalid: true, error: $reason);
    }

    public static function failed(string $reason): self
    {
        return new self(successful: false, tokenInvalid: false, error: $reason);
    }
}
