<?php

declare(strict_types=1);

namespace App\DTOs\Store;

use App\Enums\Store\ProvisioningStatusEnum;

class ProvisioningStatusDTO
{
    public function __construct(
        public ProvisioningStatusEnum $status,
        public int $progress,
        public ?string $currentStep,
        public ?string $message,
        public bool $retryable,
    ) {}
}
