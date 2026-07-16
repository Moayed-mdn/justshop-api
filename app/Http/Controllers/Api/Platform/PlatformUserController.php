<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
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

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => null, // TODO: Get user role from roles table
                'status' => 'active', // TODO: Add status field to users table
                'email_verified' => $user->email_verified_at !== null,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
                'last_login_at' => null, // TODO: Track last login
                'stores_count' => $user->stores()->count(),
                'orders_count' => 0, // TODO: Count user orders
                'stats' => [
                    'last_login' => null, // TODO: Track last login
                    'total_orders' => 0, // TODO: Count orders
                    'total_spent' => 0, // TODO: Sum order totals
                ],
            ],
        ]);
    }

    public function suspend(User $user): JsonResponse
    {
        // TODO: Implement actual suspend logic
        // For now, just return success with user data
        
        return response()->json([
            'success' => true,
            'message' => 'User suspended successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => null,
                'status' => 'suspended',
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }

    public function activate(User $user): JsonResponse
    {
        // TODO: Implement actual activate logic
        // For now, just return success with user data
        
        return response()->json([
            'success' => true,
            'message' => 'User activated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => null,
                'status' => 'active',
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => now()->toISOString(),
            ],
        ]);
    }
}
