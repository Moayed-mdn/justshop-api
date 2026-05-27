<?php

namespace Tests\Feature\Admin;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_create_user_in_store(): void
    {
        // 1. Setup Merchant Admin
        $merchant = User::factory()->merchant()->create();
        $store = Store::factory()->for($merchant, 'owner')->create();
        $merchant->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        // 2. Assign Permission to the Store Admin role (which is used in store_user pivot)
        Permission::findOrCreate(PermissionEnum::USER_CREATE, 'web');
        $storeAdminRole = Role::findOrCreate(StoreRoleEnum::STORE_ADMIN->value, 'web');
        $storeAdminRole->syncPermissions([PermissionEnum::USER_CREATE]);

        Sanctum::actingAs($merchant, ['*'], 'merchant');

        // 3. Create User Request
        $userData = [
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => StoreRoleEnum::STORE_ADMIN->value,
        ];

        $response = $this->postJson("/api/v1/merchant/stores/{$store->id}/users", $userData);

        // 4. Assertions
        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', __('admin.user_created'));

        $this->assertDatabaseHas('users', [
            'email' => 'staff@example.com',
            'name' => 'Staff Member',
        ]);

        $newUser = User::where('email', 'staff@example.com')->first();
        $this->assertDatabaseHas('store_user', [
            'user_id' => $newUser->id,
            'store_id' => $store->id,
            'role' => StoreRoleEnum::STORE_ADMIN->value,
        ]);
    }

    public function test_merchant_cannot_create_user_without_permission(): void
    {
        $merchant = User::factory()->merchant()->create();
        $store = Store::factory()->for($merchant, 'owner')->create();
        $merchant->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        Sanctum::actingAs($merchant, ['*'], 'merchant');

        $response = $this->postJson("/api/v1/merchant/stores/{$store->id}/users", [
            'name' => 'Staff Member',
            'email' => 'staff@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => StoreRoleEnum::STORE_ADMIN->value,
        ]);

        $response->assertStatus(403);
    }
}
