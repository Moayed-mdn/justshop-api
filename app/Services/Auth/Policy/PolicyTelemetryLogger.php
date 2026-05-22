<?php

declare(strict_types=1);

namespace App\Services\Auth\Policy;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PolicyTelemetryLogger
{
    public function __construct(
        private readonly Request $request,
        private readonly PolicyCapabilityResolver $capabilityResolver,
        private readonly PolicyCapabilityCatalog $capabilityCatalog,
    ) {}

    public function record(
        string $policyClass,
        string $ability,
        User $user,
        bool $allowed,
        mixed $subject = null,
        array $extraContext = [],
    ): bool {
        $route = $this->request->route();
        $middleware = $route?->gatherMiddleware() ?? [];
        $store = $this->resolveStore($subject, $extraContext);
        $capability = $this->capabilityResolver->resolve($policyClass, $ability);
        $policyCapability = $this->capabilityCatalog->resolve($policyClass, $ability, []);
        $middlewareCapability = $this->capabilityCatalog->resolveFromMiddleware($middleware);
        $middlewarePermissionAllowed = $middlewareCapability !== null ? $user->can($middlewareCapability) : null;

        Log::info('authorization.policy.decision', [
            'policy' => $policyClass,
            'ability' => $ability,
            'capability' => $capability,
            'policy_capability' => $policyCapability,
            'middleware_capability' => $middlewareCapability,
            'middleware_permission_allowed' => $middlewarePermissionAllowed,
            'middleware_policy_parity' => $middlewarePermissionAllowed === null ? null : $middlewarePermissionAllowed === $allowed,
            'allow' => $allowed,
            'deny' => !$allowed,
            'result' => $allowed ? 'allow' : 'deny',
            'actor_id' => (int) $user->id,
            'actor_context' => $user->getActorContext()->value,
            'store_context' => $store ? [
                'id' => (int) $store->id,
                'owner_id' => (int) $store->owner_id,
            ] : null,
            'subject_type' => is_object($subject) ? $subject::class : gettype($subject),
            'expected_policy_owner' => $extraContext['expected_policy_owner'] ?? $policyClass,
            'fallback_path_used' => $extraContext['fallback_path_used'] ?? false,
            'middleware_only_authorization' => $extraContext['middleware_only_authorization'] ?? false,
            'dual_authorization_path' => $extraContext['dual_authorization_path'] ?? ($middlewareCapability !== null),
            'route_name' => $route?->getName(),
            'route_uri' => $route ? '/' . ltrim($route->uri(), '/') : null,
            'controller_action' => $route?->getActionName(),
            'middleware' => $middleware,
            ...$extraContext,
        ]);

        return $allowed;
    }

    private function resolveStore(mixed $subject, array $extraContext): ?Store
    {
        if ($subject instanceof Store) {
            return $subject;
        }

        if ($subject instanceof Model && method_exists($subject, 'store')) {
            $resolvedStore = $subject->store;

            return $resolvedStore instanceof Store ? $resolvedStore : null;
        }

        $extraStore = $extraContext['store'] ?? null;

        return $extraStore instanceof Store ? $extraStore : null;
    }
}
