<?php

declare(strict_types=1);

namespace App\DTOs\Auth\Bootstrap;

class BootstrapConfigDTO
{
    public function __construct(
        public array $supportedLocales,
        public string $defaultCurrency,
        public string $timezone,
    ) {}

    public static function fromDefaults(): self
    {
        return new self(
            supportedLocales: config('app.supported_locales', ['en']),
            defaultCurrency: config('app.default_currency', 'USD'),
            timezone: config('app.timezone', 'UTC'),
        );
    }
}
