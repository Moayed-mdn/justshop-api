<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class PlatformFeatureController extends Controller
{
    /**
     * Get hardcoded platform feature flags
     * 
     * Note: These are configuration-based feature flags for platform-level features.
     * For per-plan features, see PlanFeature model.
     */
    public function index(): JsonResponse
    {
        $features = [
            [
                'id' => 'allow_new_store_registrations',
                'key' => 'allow_new_store_registrations',
                'name' => 'Allow New Store Registrations',
                'description' => 'Allow new stores to register on the platform',
                'enabled' => $this->isFeatureEnabled('allow_new_store_registrations', true),
                'critical' => true,
                'usage_count' => Store::count(), // Number of stores as usage
                'updated_at' => Cache::get('feature.allow_new_store_registrations.updated_at', now()->toISOString()),
            ],
            [
                'id' => 'enable_email_notifications',
                'key' => 'enable_email_notifications',
                'name' => 'Enable Email Notifications',
                'description' => 'Send email notifications to users and stores',
                'enabled' => $this->isFeatureEnabled('enable_email_notifications', true),
                'critical' => false,
                'usage_count' => 0,
                'updated_at' => Cache::get('feature.enable_email_notifications.updated_at', now()->toISOString()),
            ],
            [
                'id' => 'enable_payment_processing',
                'key' => 'enable_payment_processing',
                'name' => 'Enable Payment Processing',
                'description' => 'Allow stores to process payments',
                'enabled' => $this->isFeatureEnabled('enable_payment_processing', true),
                'critical' => true,
                'usage_count' => 0,
                'updated_at' => Cache::get('feature.enable_payment_processing.updated_at', now()->toISOString()),
            ],
            [
                'id' => 'enable_advanced_analytics',
                'key' => 'enable_advanced_analytics',
                'name' => 'Enable Advanced Analytics',
                'description' => 'Provide advanced analytics features to stores',
                'enabled' => $this->isFeatureEnabled('enable_advanced_analytics', false),
                'critical' => false,
                'usage_count' => 0,
                'updated_at' => Cache::get('feature.enable_advanced_analytics.updated_at', now()->toISOString()),
            ],
            [
                'id' => 'maintenance_mode',
                'key' => 'maintenance_mode',
                'name' => 'Maintenance Mode',
                'description' => 'Put the platform in maintenance mode',
                'enabled' => $this->isFeatureEnabled('maintenance_mode', false),
                'critical' => true,
                'usage_count' => 0,
                'updated_at' => Cache::get('feature.maintenance_mode.updated_at', now()->toISOString()),
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }

    public function update(string $feature): JsonResponse
    {
        $enabled = (bool) request()->get('enabled', false);
        
        // Store feature flag state in cache
        Cache::forever("feature.{$feature}.enabled", $enabled);
        Cache::forever("feature.{$feature}.updated_at", now()->toISOString());
        
        return response()->json([
            'success' => true,
            'message' => 'Feature flag updated successfully',
            'data' => [
                'id' => $feature,
                'key' => $feature,
                'enabled' => $enabled,
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }
    
    /**
     * Check if a feature is enabled
     */
    private function isFeatureEnabled(string $feature, bool $default = false): bool
    {
        return Cache::get("feature.{$feature}.enabled", $default);
    }
}

