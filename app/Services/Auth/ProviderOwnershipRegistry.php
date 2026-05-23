<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\Auth\AuthDomainEnum;
use Illuminate\Support\Facades\Log;

class ProviderOwnershipRegistry
{
    /**
     * @return array<string, mixed>
     */
    public function getProviderMetadata(AuthDomainEnum $domain): array
    {
        return match ($domain) {
            AuthDomainEnum::MERCHANT, AuthDomainEnum::PLATFORM => [
                'provider' => 'users',
                'broker' => 'users',
                'model' => \App\Models\User::class,
                'isolation_state' => 'shared_transitional',
            ],
            AuthDomainEnum::CUSTOMER => [
                'provider' => 'users',
                'broker' => 'customers',
                'model' => \App\Models\User::class,
                'isolation_state' => 'shared_transitional',
            ],
        };
    }

    public function logProviderAccess(AuthDomainEnum $domain, string $operation): void
    {
        $metadata = $this->getProviderMetadata($domain);
        
        Log::info('auth.provider.accessed', [
            'auth_domain' => $domain->value,
            'operation' => $operation,
            'provider' => $metadata['provider'],
            'isolation_state' => $metadata['isolation_state'],
        ]);
    }
}
