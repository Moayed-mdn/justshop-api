<?php

declare(strict_types=1);

namespace App\Services\Auth\Policy;

use Illuminate\Http\Request;

class PolicyCapabilityResolver
{
    public function __construct(
        private readonly Request $request,
        private readonly PolicyCapabilityCatalog $catalog,
    ) {}

    public function resolve(string $policyClass, string $ability): ?string
    {
        return $this->catalog->resolve($policyClass, $ability, $this->request->route()?->gatherMiddleware() ?? []);
    }

}
