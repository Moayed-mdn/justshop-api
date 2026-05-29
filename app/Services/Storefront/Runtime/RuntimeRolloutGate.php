<?php

declare(strict_types=1);

namespace App\Services\Storefront\Runtime;

use App\Models\Store;

class RuntimeRolloutGate
{
    public function isEnabled(Store $store): bool
    {
        if ((bool) config('storefront_runtime.rollout.kill_switch', false)) {
            return false;
        }

        $mode = (string) config('storefront_runtime.rollout.mode', 'full');

        return match ($mode) {
            'full' => true,
            'off' => false,
            'internal' => $this->matchesTenantList($store, 'internal_tenant_keys'),
            'pilot' => $this->matchesTenantList($store, 'internal_tenant_keys')
                || $this->matchesTenantList($store, 'pilot_tenant_keys'),
            default => false,
        };
    }

  /**
     * @return list<string>
     */
    public function enabledTenantKeys(): array
    {
        if ((bool) config('storefront_runtime.rollout.kill_switch', false)) {
            return [];
        }

        $mode = (string) config('storefront_runtime.rollout.mode', 'full');

        return match ($mode) {
            'full' => ['*'],
            'off' => [],
            'internal' => $this->tenantKeysFor('internal_tenant_keys'),
            'pilot' => array_values(array_unique(array_merge(
                $this->tenantKeysFor('internal_tenant_keys'),
                $this->tenantKeysFor('pilot_tenant_keys'),
            ))),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    private function tenantKeysFor(string $configKey): array
    {
        $keys = config('storefront_runtime.rollout.' . $configKey, []);

        return is_array($keys) ? array_values(array_filter(array_map('strval', $keys))) : [];
    }

    private function matchesTenantList(Store $store, string $configKey): bool
    {
        $keys = $this->tenantKeysFor($configKey);

        if ($keys === []) {
            return false;
        }

        return in_array((string) $store->slug, $keys, true)
            || in_array('store_' . $store->id, $keys, true)
            || in_array((string) $store->domain, $keys, true);
    }
}
