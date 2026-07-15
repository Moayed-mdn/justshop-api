<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PlatformAuditController extends Controller
{
    public function index(): JsonResponse
    {
        // Wave 6: Mock implementation for frontend development
        // TODO: Replace with real audit log repository queries
        
        // Generate mock audit logs
        $logs = [];
        $actions = ['user_created', 'user_suspended', 'user_activated', 'store_created', 'store_suspended', 'store_activated', 'feature_toggled'];
        $actors = ['Super Admin', 'Support Agent 1', 'Support Agent 2', 'System'];
        $resources = ['User', 'Store', 'Feature'];
        
        for ($i = 1; $i <= 100; $i++) {
            $action = $actions[array_rand($actions)];
            $resource = $resources[array_rand($resources)];
            
            $logs[] = [
                'id' => $i,
                'actor_name' => $actors[array_rand($actors)],
                'actor_email' => 'admin' . ($i % 3 + 1) . '@example.com',
                'action' => $action,
                'resource_type' => $resource,
                'resource_id' => rand(1, 50),
                'resource_name' => $resource . ' ' . rand(1, 50),
                'description' => ucfirst(str_replace('_', ' ', $action)),
                'ip_address' => '192.168.1.' . rand(1, 255),
                'user_agent' => 'Mozilla/5.0',
                'metadata' => json_encode(['reason' => 'Administrative action']),
                'created_at' => now()->subDays(rand(0, 30))->toISOString(),
            ];
        }
        
        // Simple pagination mock
        $page = (int) request()->get('page', 1);
        $perPage = (int) request()->get('per_page', 25);
        $total = count($logs);
        $offset = ($page - 1) * $perPage;
        
        $paginatedLogs = array_slice($logs, $offset, $perPage);
        
        return response()->json([
            'success' => true,
            'data' => $paginatedLogs,
            'meta' => [
                'current_page' => (int) $page,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => (int) $perPage,
                'total' => $total,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    public function show(int $log): JsonResponse
    {
        // Wave 6: Mock audit log details
        // TODO: Replace with real audit log repository query
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $log,
                'actor_name' => 'Super Admin',
                'actor_email' => 'admin@example.com',
                'action' => 'user_suspended',
                'resource_type' => 'User',
                'resource_id' => 5,
                'resource_name' => 'User 5',
                'description' => 'User suspended',
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0',
                'metadata' => json_encode(['reason' => 'Policy violation', 'duration' => 'indefinite']),
                'created_at' => now()->subDays(rand(0, 7))->toISOString(),
            ],
        ]);
    }
}
