<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use App\Enums\ErrorCode;

class InvalidIdentityDomainAccessException extends DomainException
{
    private ?string $logoutUrl = null;

    public function __construct(string $message = 'Identity context is not allowed to access this route.', ?string $logoutUrl = null)
    {
        parent::__construct($message, ErrorCode::IDENTITY_DOMAIN_MISMATCH, 403);
        $this->logoutUrl = $logoutUrl;
    }

    public function getLogoutUrl(): ?string
    {
        return $this->logoutUrl;
    }
}
