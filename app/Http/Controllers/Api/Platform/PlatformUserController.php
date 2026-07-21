<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Repositories\Platform\User\PlatformUserRepository;
use Illuminate\Http\JsonResponse;

class PlatformUserController extends Controller
{
    public function __construct(
        private readonly PlatformUserRepository $userRepository,
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->userRepository->list(
            search: request()->string('search')->toString() ?: null,
            role: request()->string('role')->toString() ?: null,
            status: request()->string('status')->toString() ?: null,
            sortBy: request()->string('sort', 'created_at')->toString(),
            sortOrder: request()->string('order', 'desc')->toString(),
            perPage: (int) request()->integer('per_page', 25),
        );

        // Map users to response format
        $data = $users->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'status' => $user->is_active ? 'active' : 'suspended',
                'email_verified' => $user->email_verified_at !== null,
                'stores_count' => $user->stores_count,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        // Load relationships
        $user->load(['roles', 'stores']);
        $user->loadCount('stores');
        
        // Get user's first role name (Spatie Permission)
        $roleName = $user->roles->first()?->name;
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $roleName,
                'status' => $user->is_active ? 'active' : 'suspended',
                'email_verified' => $user->email_verified_at !== null,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
                'last_login_at' => null, // TODO: Track last login
                'stores_count' => $user->stores_count,
                'orders_count' => 0, // TODO: Count user orders
                'stats' => [
                    'last_login' => null, // TODO: Track last login
                    'total_orders' => 0, // TODO: Count orders
                    'total_revenue' => 0, // TODO: Sum order totals
                    'active_stores' => $user->stores()->count(),
                ],
                'stores' => $user->stores->map(fn($store) => [
                    'id' => $store->id,
                    'name' => $store->name,
                    'domain' => $store->domain,
                    'status' => $store->status->value,
                    'created_at' => $store->created_at->toISOString(),
                ]),
            ],
        ]);
    }

    public function suspend(User $user): JsonResponse
    {
        $user = $this->userRepository->suspend($user);
        
        $roleName = $user->roles->first()?->name;
        
        return response()->json([
            'success' => true,
            'message' => 'User suspended successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $roleName,
                'status' => 'suspended',
                'email_verified' => $user->email_verified_at !== null,
                'stores_count' => $user->stores()->count(),
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ],
        ]);
    }

    public function activate(User $user): JsonResponse
    {
        $user = $this->userRepository->activate($user);
        
        $roleName = $user->roles->first()?->name;
        
        return response()->json([
            'success' => true,
            'message' => 'User activated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $roleName,
                'status' => 'active',
                'email_verified' => $user->email_verified_at !== null,
                'stores_count' => $user->stores()->count(),
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ],
        ]);
    }

    public function update(User $user): JsonResponse
    {
        $validated = request()->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:users,email,' . $user->id,
            'role' => 'sometimes|nullable|string|in:super_admin,store_admin,staff,customer',
            'status' => 'sometimes|required|string|in:active,suspended,inactive',
        ]);

        // Update basic fields (name, email)
        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
        }
        if (isset($validated['email'])) {
            $updateData['email'] = $validated['email'];
        }
        
        if (!empty($updateData)) {
            $user = $this->userRepository->update($user, $updateData);
        }

        // Update role using Spatie
        if (isset($validated['role'])) {
            $user = $this->userRepository->syncRoles($user, [$validated['role']]);
        }

        // Update status (active/suspended)
        if (isset($validated['status'])) {
            if ($validated['status'] === 'active') {
                $user = $this->userRepository->activate($user);
            } elseif ($validated['status'] === 'suspended') {
                $user = $this->userRepository->suspend($user);
            }
        }

        // Reload relationships
        $user->load(['roles', 'stores']);
        $user->loadCount('stores');
        
        $roleName = $user->roles->first()?->name;

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $roleName,
                'status' => $user->is_active ? 'active' : 'suspended',
                'email_verified' => $user->email_verified_at !== null,
                'stores_count' => $user->stores_count,
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
            ],
        ]);
    }
}
