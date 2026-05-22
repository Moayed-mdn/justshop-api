<?php

declare(strict_types=1);

namespace App\Services\Auth\Bootstrap;

use App\DTOs\Auth\Bootstrap\BootstrapConfigDTO;

class BootstrapConfigResolver
{
    public function resolve(): BootstrapConfigDTO
    {
        return BootstrapConfigDTO::fromDefaults();
    }
}
