<?php

declare(strict_types=1);

namespace App\Services\Authorization;

use App\Enums\Auth\ActorContextEnum;
use App\Enums\Auth\AuthDomainEnum;

/**
 * Policy Ownership Registry
 * 
 * Wave 6: Policies MUST declare ownership.
 * 
 * Policies MUST declare:
 * - Owning actor domain
 * - Supported actor domains
 * - Escalation rules
 * - Support override rules
 */
class PolicyOwnershipRegistry
{
    private array $registry = [];

    public function register(
        string $policyClass,
        AuthDomainEnum $owningDomain,
        array $supportedActorDomains,
        array $escalationRules = [],
        array $supportOverrideRules = [],
    ): void {
        $this->registry[$policyClass] = [
            'owning_domain' => $owningDomain->value,
            'supported_actor_domains' => array_map(fn ($d) => $d->value, $supportedActorDomains),
            'escalation_rules' => $escalationRules,
            'support_override_rules' => $supportOverrideRules,
        ];
    }

    public function get(string $policyClass): ?array
    {
        return $this->registry[$policyClass] ?? null;
    }

    public function isActorSupported(string $policyClass, ActorContextEnum $actorContext): bool
    {
        $metadata = $this->get($policyClass);

        if ($metadata === null) {
            return false;
        }

        $actorDomain = $this->resolveActorDomain($actorContext);

        return in_array($actorDomain->value, $metadata['supported_actor_domains'], true);
    }

    public function canEscalate(string $policyClass, ActorContextEnum $fromActor, ActorContextEnum $toActor): bool
    {
        $metadata = $this->get($policyClass);

        if ($metadata === null) {
            return false;
        }

        $escalationKey = $fromActor->value . '_to_' . $toActor->value;

        return in_array($escalationKey, $metadata['escalation_rules'], true);
    }

    public function canSupportOverride(string $policyClass, string $ability): bool
    {
        $metadata = $this->get($policyClass);

        if ($metadata === null) {
            return false;
        }

        return in_array($ability, $metadata['support_override_rules'], true);
    }

    public function getAll(): array
    {
        return $this->registry;
    }

    public function generateArtifact(): array
    {
        return [
            'policy_count' => count($this->registry),
            'policies' => $this->registry,
        ];
    }

    private function resolveActorDomain(ActorContextEnum $actorContext): AuthDomainEnum
    {
        return match ($actorContext) {
            ActorContextEnum::MERCHANT => AuthDomainEnum::MERCHANT,
            ActorContextEnum::CUSTOMER => AuthDomainEnum::CUSTOMER,
            ActorContextEnum::SUPER_ADMIN,
            ActorContextEnum::SUPPORT_AGENT,
            ActorContextEnum::PLATFORM_SYSTEM => AuthDomainEnum::PLATFORM,
        };
    }
}
