<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PlatformFeatureController extends Controller
{
    public function index(): JsonResponse
    {
        // Wave 6: Mock implementation for frontend development
        // TODO: Replace with real feature flag repository queries
        
        $features = [
            [
                'id' => 1,
                'key' => 'allow_new_store_registrations',
                'name' => 'Allow New Store Registrations',
                'description' => 'Allow new stores to register on the platform',
                'enabled' => true,
                'critical' => true,
                'updated_at' => now()->subDays(5)->toISOString(),
            ],
            [
                'id' => 2,
                'key' => 'enable_email_notifications',
                'name' => 'Enable Email Notifications',
                'description' => 'Send email notifications to users and stores',
                'enabled' => true,
                'critical' => false,
                'updated_at' => now()->subDays(10)->toISOString(),
            ],
            [
                'id' => 3,
                'key' => 'enable_payment_processing',
                'name' => 'Enable Payment Processing',
                'description' => 'Allow stores to process payments',
                'enabled' => true,
                'critical' => true,
                'updated_at' => now()->subDays(2)->toISOString(),
            ],
            [
                'id' => 4,
                'key' => 'enable_advanced_analytics',
                'name' => 'Enable Advanced Analytics',
                'description' => 'Provide advanced analytics features to stores',
                'enabled' => false,
                'critical' => false,
                'updated_at' => now()->subDays(15)->toISOString(),
            ],
            [
                'id' => 5,
                'key' => 'maintenance_mode',
                'name' => 'Maintenance Mode',
                'description' => 'Put the platform in maintenance mode',
                'enabled' => false,
                'critical' => true,
                'updated_at' => now()->subDays(30)->toISOString(),
            ],
        ];
        
        return response()->json([
            'success' => true,
            'data' => $features,
        ]);
    }

    public function update(string $feature): JsonResponse
    {
        // Wave 6: Mock feature flag update
        // TODO: Implement actual feature flag update logic
        
        $enabled = request()->get('enabled', false);
        
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
}
