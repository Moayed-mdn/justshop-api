<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        // Create users (one per role)
        $superAdminUser = User::firstOrCreate(
            ['email' => 'super@test.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );

        $storeAdminUser = User::firstOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Store Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );

        $staffUser = User::firstOrCreate(
            ['email' => 'staff@test.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );

        $customerUser = User::firstOrCreate(
            ['email' => 'customer@test.com'],
            [
                'name' => 'Customer User',
                'password' => Hash::make('password'),
                'email_verified_at' => now()
            ]
        );

        // Create the test store
        $store = Store::firstOrCreate(
            ['name' => 'Test Store'],
            [
                'user_id' => $storeAdminUser->id,
            ]
        );

        $permissionRegistrar = app(\Spatie\Permission\PermissionRegistrar::class);

        // Global team ID for roles that aren't store-specific
        $globalTeamId = 0;

        // super_admin -> global role (team_id = 0)
        $permissionRegistrar->setPermissionsTeamId($globalTeamId);
        $superAdminUser->assignRole(RoleEnum::SUPER_ADMIN->value);

        // store_admin -> store-scoped role
        $permissionRegistrar->setPermissionsTeamId($store->id);
        $storeAdminUser->assignRole(RoleEnum::STORE_ADMIN->value);

        // staff -> store-scoped role
        $staffUser->assignRole(RoleEnum::STAFF->value);

        // customer -> global role (team_id = 0)
        $permissionRegistrar->setPermissionsTeamId($globalTeamId);
        $customerUser->assignRole(RoleEnum::CUSTOMER->value);

        // Attach users to store via pivot table (except customer)
        // super_admin user -> attach with pivot role store_admin
        if (!$store->users()->where('user_id', $superAdminUser->id)->exists()) {
            $store->users()->attach($superAdminUser->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        }

        // store_admin user -> attach with pivot role store_admin
        if (!$store->users()->where('user_id', $storeAdminUser->id)->exists()) {
            $store->users()->attach($storeAdminUser->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        }

        // staff user -> attach with pivot role staff
        if (!$store->users()->where('user_id', $staffUser->id)->exists()) {
            $store->users()->attach($staffUser->id, ['role' => StoreRoleEnum::STAFF->value]);
        }

        // customer user -> NOT attached to store (customers are not store members)
    }
}