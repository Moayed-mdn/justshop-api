<?php

namespace Database\Seeders;

use App\Enums\Auth\OnboardingStepEnum;
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
        // 1. Create Merchant User (The primary owner of the first store)
        $merchantUser = User::updateOrCreate(
            ['email' => 'merchant@test.com'],
            [
                'name' => 'Merchant User',
                'password' => Hash::make('password'),
                'onboarding_step' => OnboardingStepEnum::COMPLETED->value,
                
            ]
        );
        $merchantUser->markEmailAsVerified();

        // 2. Create Super Admin User (Platform administrator)
        $superAdminUser = User::updateOrCreate(
            ['email' => 'super@test.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'onboarding_step' => OnboardingStepEnum::COMPLETED->value,
                'email_verified_at' => now()
            ]
        );
        $superAdminUser->markEmailAsVerified();

        // 3. Create Staff User
        $staffUser = User::updateOrCreate(
            ['email' => 'staff@test.com'],
            [
                'name' => 'Staff User',
                'password' => Hash::make('password'),
                'onboarding_step' => OnboardingStepEnum::COMPLETED->value,
            ]
        );
        $staffUser->markEmailAsVerified();

        // 4. Create Customer User
        $customerUser = User::updateOrCreate(
            ['email' => 'customer@test.com'],
            [
                'name' => 'Customer User',
                'password' => Hash::make('password'),
                'onboarding_step' => null,
            ]
        );
        $customerUser->markEmailAsVerified();

        // 5. Create the first store (Owned by Merchant User)
        $store = Store::updateOrCreate(
            ['slug' => 'merchant-store'],
            [
                'name' => 'JustShop Demo',
                'domain' => 'demo.justshop.test',
                'owner_id' => $merchantUser->id,
                'currency' => 'USD',
                'timezone' => 'UTC',
                'is_active' => true,
                'status' => 'active',
                'setup_completed_at' => now(),
            ]
        );

        $permissionRegistrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $globalTeamId = 0;

        // ── Assign Global Roles (team_id = 0) ───────────────────
        $permissionRegistrar->setPermissionsTeamId($globalTeamId);
        
        $superAdminUser->assignRole(RoleEnum::SUPER_ADMIN->value);
        $customerUser->assignRole(RoleEnum::CUSTOMER->value);

        // ── Assign Store-Scoped Roles ───────────────────────────
        $permissionRegistrar->setPermissionsTeamId($store->id);

        // Merchant User -> STORE_ADMIN for their store
        $merchantUser->assignRole(RoleEnum::STORE_ADMIN->value);
        if (!$store->users()->where('user_id', $merchantUser->id)->exists()) {
            $store->users()->attach($merchantUser->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        }
        $merchantUser->update(['last_active_store_id' => $store->id]);

        // Staff User -> STAFF for the merchant store
        $staffUser->assignRole(RoleEnum::STAFF->value);
        if (!$store->users()->where('user_id', $staffUser->id)->exists()) {
            $store->users()->attach($staffUser->id, ['role' => StoreRoleEnum::STAFF->value]);
        }
        $staffUser->update(['last_active_store_id' => $store->id]);

        // Optional: Let Super Admin also have access to this store for testing
        // $superAdminUser->assignRole(RoleEnum::STORE_ADMIN->value);
        // if (!$store->users()->where('user_id', $superAdminUser->id)->exists()) {
        //     $store->users()->attach($superAdminUser->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        // }
    }
}
