<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\Bootstrap\BootstrapResolution;
use App\DTOs\Auth\Bootstrap\BootstrapResolutionMetadata;
use App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO;
use App\DTOs\Auth\GetBootstrapDTO;
use App\Models\User;
use App\Services\Auth\Bootstrap\BootstrapCompatibilityAdapter;
use App\Services\Auth\Bootstrap\BootstrapConfigResolver;
use App\Services\Auth\Bootstrap\BootstrapIdentityResolver;
use App\Services\Auth\Bootstrap\BootstrapOnboardingResolver;
use App\Services\Auth\Bootstrap\BootstrapPermissionResolver;
use App\Services\Auth\Bootstrap\BootstrapShadowParityService;
use App\Services\Auth\Bootstrap\BootstrapStoreResolver;
use App\Services\Auth\Bootstrap\BootstrapTelemetry;
use App\Services\Auth\Bootstrap\LegacyBootstrapCompatibilityAdapter;
use App\Services\Auth\FrontendSessionMetadataResolver;
use App\Services\Auth\Permission\PermissionTransformer;
use App\Support\FeatureFlags\FeatureFlag;

class GetBootstrapAction
{
    public function __construct(
        private readonly BootstrapIdentityResolver $identityResolver,
        private readonly \App\Services\Auth\IdentityContextResolver $identityContextResolver,
        private readonly BootstrapStoreResolver $storeResolver,
        private readonly BootstrapPermissionResolver $permissionResolver,
        private readonly BootstrapOnboardingResolver $onboardingResolver,
        private readonly BootstrapConfigResolver $configResolver,
        private readonly BootstrapCompatibilityAdapter $compatibilityAdapter,
        private readonly LegacyBootstrapCompatibilityAdapter $legacyCompatibilityAdapter,
        private readonly BootstrapShadowParityService $shadowParityService,
        private readonly BootstrapTelemetry $telemetry,
        private readonly FrontendSessionMetadataResolver $frontendSessionMetadataResolver,
    ) {}

    public function execute(GetBootstrapDTO $dto): GetBootstrapResponseDTO
    {
        $user = User::findOrFail($dto->userId);
        $responseVersion = (string) FeatureFlag::value('bootstrap.response_version');
        $useDecomposedAuthority = FeatureFlag::enabled('bootstrap.v2.enabled');
        $shadowReadEnabled = FeatureFlag::enabled('bootstrap.shadow_read');
        $authorityPath = $useDecomposedAuthority ? 'decomposed' : 'legacy';

        $this->telemetry->logStarted($user, $authorityPath, $responseVersion);

        if ($useDecomposedAuthority) {
            [$authoritativeResponse, $metadata] = $this->resolveDecomposed($dto, $user, $responseVersion, 'decomposed');

            if ($shadowReadEnabled) {
                $session = $this->frontendSessionMetadataResolver->resolve($dto->request, $this->identityContextResolver->resolve($user));
                $shadowResponse = $this->legacyCompatibilityAdapter->adapt($user, $session);
                $this->shadowParityService->compare(
                    $user,
                    $authoritativeResponse,
                    $shadowResponse,
                    $metadata,
                    'legacy',
                );
            }

            $this->telemetry->logCompleted($user, $metadata, $authoritativeResponse);

            return $authoritativeResponse;
        }

        $session = $this->frontendSessionMetadataResolver->resolve($dto->request, $this->identityContextResolver->resolve($user));
        $authoritativeResponse = $this->legacyCompatibilityAdapter->adapt($user, $session);
        $metadata = new BootstrapResolutionMetadata(
            responseVersion: $responseVersion,
            resolverVersion: 'legacy_compat',
            authorityPath: 'legacy',
        );

        if ($shadowReadEnabled) {
            [$shadowResponse, $shadowMetadata] = $this->resolveDecomposed($dto, $user, $responseVersion, 'shadow');
            $this->shadowParityService->compare(
                $user,
                $authoritativeResponse,
                $shadowResponse,
                $shadowMetadata,
                'decomposed',
            );
        }

        $this->telemetry->logCompleted($user, $metadata, $authoritativeResponse);

        return $authoritativeResponse;
    }

    /**
     * @return array{0: GetBootstrapResponseDTO, 1: BootstrapResolutionMetadata}
     */
    private function resolveDecomposed(GetBootstrapDTO $dto, User $user, string $responseVersion, string $authorityPath): array
    {
        $metadata = new BootstrapResolutionMetadata(
            responseVersion: $responseVersion,
            resolverVersion: 'decomposed_internal',
            authorityPath: $authorityPath,
        );

        $identity = $this->telemetry->measure('BootstrapIdentityResolver', fn () => $this->identityResolver->resolve($user));
        $metadata = $metadata->withResolverTiming('BootstrapIdentityResolver', $identity['elapsed_ms']);

        $stores = $this->telemetry->measure('BootstrapStoreResolver', fn () => $this->storeResolver->resolve($user));
        $metadata = $metadata->withResolverTiming('BootstrapStoreResolver', $stores['elapsed_ms']);

        $permissions = $this->telemetry->measure(
            'BootstrapPermissionResolver',
            fn () => $this->permissionResolver->resolve($user, $stores['value']->activeStoreModel),
        );
        $metadata = $metadata->withResolverTiming('BootstrapPermissionResolver', $permissions['elapsed_ms']);

        $onboarding = $this->telemetry->measure('BootstrapOnboardingResolver', fn () => $this->onboardingResolver->resolve($user));
        $metadata = $metadata->withResolverTiming('BootstrapOnboardingResolver', $onboarding['elapsed_ms']);

        $config = $this->telemetry->measure('BootstrapConfigResolver', fn () => $this->configResolver->resolve());
        $metadata = $metadata->withResolverTiming('BootstrapConfigResolver', $config['elapsed_ms']);

        $actorContext = $user->getActorContext();

        // Wave 3: Explicitly document actor-context ownership for bootstrap
        $session = $this->frontendSessionMetadataResolver->resolve(
            $dto->request,
            $this->identityContextResolver->resolve($user),
        );
        $resolution = new BootstrapResolution(
            user: $identity['value'],
            stores: $stores['value']->stores,
            activeStore: $stores['value']->activeStore,
            onboarding: $onboarding['value'],
            permissions: $permissions['value']->permissions,
            capabilities: PermissionTransformer::toFrontendFlags($permissions['value']->permissions),
            config: $config['value'],
            actorContext: $actorContext,
            session: $session,
            metadata: $metadata,
        );

        return [
            $this->compatibilityAdapter->adapt($resolution),
            $metadata,
        ];
    }
}
