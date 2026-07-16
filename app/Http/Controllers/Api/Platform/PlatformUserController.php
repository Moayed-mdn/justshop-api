<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class PlatformUserController extends Controller
{
    public function index(): JsonResponse
    {
        // Wave 6: Mock implementation for frontend development
        // TODO: Replace with real user repository queries
        
        // Generate mock users
        $users = [];
        $roles = ['store_admin', 'store_staff', 'customer'];
        $statuses = ['active', 'suspended'];
        
        for ($i = 1; $i <= 50; $i++) {
            $users[] = [
                'id' => $i,
                'name' => 'User ' . $i,
                'email' => 'user' . $i . '@example.com',
                'role' => $roles[array_rand($roles)],
                'status' => $i % 7 === 0 ? 'suspended' : 'active',
                'created_at' => now()->subDays(rand(1, 365))->toISOString(),
                'updated_at' => now()->subDays(rand(0, 30))->toISOString(),
            ];
        }
        
        // Simple pagination mock
        $page = (int) request()->get('page', 1);
        $perPage = (int) request()->get('per_page', 25);
        $total = count($users);
        $offset = ($page - 1) * $perPage;
        
        $paginatedUsers = array_slice($users, $offset, $perPage);
        
        return response()->json([
            'success' => true,
            'data' => $paginatedUsers,
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

    public function show(int $user): JsonResponse
    {
        // Wave 6: Mock user details
        // TODO: Replace with real user repository query
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user,
                'name' => 'User ' . $user,
                'email' => 'user' . $user . '@example.com',
                'role' => 'store_admin',
                'status' => 'active',
                'created_at' => now()->subDays(rand(1, 365))->toISOString(),
                'updated_at' => now()->subDays(rand(0, 30))->toISOString(),
                'last_login_at' => now()->subDays(rand(0, 7))->toISOString(),
                'stores_count' => rand(1, 5),
                'orders_count' => rand(0, 100),
                'stats' => [
                    'last_login' => now()->subDays(rand(0, 7))->toISOString(),
                    'total_orders' => rand(0, 100),
                    'total_spent' => rand(0, 10000),
                ],
            ],
        ]);
    }

    public function suspend(int $user): JsonResponse
    {
        // Wave 6: Mock suspend
        // TODO: Implement actual suspend logic
        
        return response()->json([
            'success' => true,
            'message' => 'User suspended successfully',
            'data' => [
                'id' => $user,
                'name' => 'User ' . $user,
                'email' => 'user' . $user . '@example.com',
                'role' => 'store_admin',
                'status' => 'suspended',
                'created_at' => now()->subDays(rand(1, 365))->toISOString(),
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }

    public function activate(int $user): JsonResponse
    {
        // Wave 6: Mock activate
        // TODO: Implement actual activate logic
        
        return response()->json([
            'success' => true,
            'message' => 'User activated successfully',
            'data' => [
                'id' => $user,
                'name' => 'User ' . $user,
                'email' => 'user' . $user . '@example.com',
                'role' => 'store_admin',
                'status' => 'active',
                'created_at' => now()->subDays(rand(1, 365))->toISOString(),
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }
}
