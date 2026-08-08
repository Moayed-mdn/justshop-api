<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Auth\OnboardingStepEnum;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * PlatformUsersSeeder
 * 
 * Seeds diverse test users for platform dashboard development.
 * Creates users with various roles and statuses for comprehensive testing.
 */
class PlatformUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding platform test users...');

        // Get existing stores for attaching users
        $stores = Store::all();
        
        if ($stores->isEmpty()) {
            $this->command->warn('No stores found. Run StoreSeeder first.');
            return;
        }

        $permissionRegistrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $globalTeamId = 0;

        // Define test users with various roles and statuses
        $testUsers = [
            // Super Admins
            [
                'name' => 'Alice Johnson',
                'email' => 'alice@platform.test',
                'role' => RoleEnum::SUPER_ADMIN,
                'is_active' => true,
                'global_role' => true,
            ],
            [
                'name' => 'Bob Williams',
                'email' => 'bob@platform.test',
                'role' => RoleEnum::SUPER_ADMIN,
                'is_active' => false, // Suspended super admin
                'global_role' => true,
            ],
            
            // Store Admins (will be attached to stores)
            [
                'name' => 'Charlie Brown',
                'email' => 'charlie@store1.test',
                'role' => RoleEnum::STORE_ADMIN,
                'is_active' => true,
                'global_role' => false,
                'attach_to_stores' => 1,
            ],
            [
                'name' => 'Diana Prince',
                'email' => 'diana@store2.test',
                'role' => RoleEnum::STORE_ADMIN,
                'is_active' => true,
                'global_role' => false,
                'attach_to_stores' => 1,
            ],
            [
                'name' => 'Edward Smith',
                'email' => 'edward@store3.test',
                'role' => RoleEnum::STORE_ADMIN,
                'is_active' => true,
                'global_role' => false,
                'attach_to_stores' => 1,
            ],
            [
                'name' => 'Fiona Davis',
                'email' => 'fiona@store4.test',
                'role' => RoleEnum::STORE_ADMIN,
                'is_active' => false, // Suspended store admin
                'global_role' => false,
                'attach_to_stores' => 1,
            ],
            
            // Staff Members
            [
                'name' => 'George Wilson',
                'email' => 'george@staff.test',
                'role' => RoleEnum::STAFF,
                'is_active' => true,
                'global_role' => false,
                'attach_to_stores' => 1,
            ],
            [
                'name' => 'Hannah Martinez',
                'email' => 'hannah@staff.test',
                'role' => RoleEnum::STAFF,
                'is_active' => true,
                'global_role' => false,
                'attach_to_stores' => 1,
            ],
            [
                'name' => 'Ian Thompson',
                'email' => 'ian@staff.test',
                'role' => RoleEnum::STAFF,
                'is_active' => true,
                'global_role' => false,
                'attach_to_stores' => 1,
            ],
            
            // Customers
            [
                'name' => 'Julia Anderson',
                'email' => 'julia@customer.test',
                'role' => RoleEnum::CUSTOMER,
                'is_active' => true,
                'global_role' => true,
            ],
            [
                'name' => 'Kevin Lee',
                'email' => 'kevin@customer.test',
                'role' => RoleEnum::CUSTOMER,
                'is_active' => true,
                'global_role' => true,
            ],
            [
                'name' => 'Laura Martinez',
                'email' => 'laura@customer.test',
                'role' => RoleEnum::CUSTOMER,
                'is_active' => false, // Suspended customer
                'global_role' => true,
            ],
        ];

        // Create users using factory for more diverse data
        // Reduced from 40 to 10 for faster seeding (set env SEED_LARGE_DATASET=true for full data)
        $factoryUserCount = env('SEED_LARGE_DATASET', false) ? 40 : 10;
        $this->command->info("Creating {$factoryUserCount} additional users via factory...");
        
        // Pre-hash password once instead of per-user
        $hashedPassword = Hash::make('password');
        
        $factoryUsers = User::factory($factoryUserCount)->create([
            'password' => $hashedPassword,
            'email_verified_at' => now(),
        ]);

        // Assign random roles to factory users
        $roles = [
            RoleEnum::STORE_ADMIN,
            RoleEnum::STAFF,
            RoleEnum::CUSTOMER,
        ];

        // Batch operations for better performance
        $userUpdates = [];
        $storeAttachments = [];

        foreach ($factoryUsers as $index => $user) {
            $role = $roles[array_rand($roles)];
            $isGlobalRole = $role === RoleEnum::CUSTOMER;
            $isActive = $index % 7 !== 0; // Every 7th user is suspended
            
            $userUpdates[$user->id] = ['is_active' => $isActive];

            if ($isGlobalRole) {
                // Global role (customer)
                $permissionRegistrar->setPermissionsTeamId($globalTeamId);
                $user->assignRole($role->value);
            } else {
                // Store-scoped role
                $store = $stores->random();
                $permissionRegistrar->setPermissionsTeamId($store->id);
                $user->assignRole($role->value);
                
                // Prepare batch attachment
                $storeRole = $role === RoleEnum::STORE_ADMIN 
                    ? StoreRoleEnum::STORE_ADMIN 
                    : StoreRoleEnum::STAFF;
                
                if (!isset($storeAttachments[$store->id])) {
                    $storeAttachments[$store->id] = [];
                }
                $storeAttachments[$store->id][$user->id] = ['role' => $storeRole->value];
                
                $userUpdates[$user->id]['last_active_store_id'] = $store->id;
            }
        }

        // Batch update users
        foreach ($userUpdates as $userId => $updates) {
            User::where('id', $userId)->update($updates);
        }

        // Batch attach to stores
        foreach ($storeAttachments as $storeId => $users) {
            Store::find($storeId)->users()->syncWithoutDetaching($users);
        }

        // Create named test users
        $this->command->info('Creating named test users...');
        
        // Re-use the same hashed password for all test users
        $hashedPassword = Hash::make('password');
        
        foreach ($testUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $hashedPassword,
                    'onboarding_step' => $userData['role'] === RoleEnum::CUSTOMER 
                        ? null 
                        : OnboardingStepEnum::COMPLETED->value,
                    'is_active' => $userData['is_active'],
                    'email_verified_at' => now(),
                ]
            );

            if ($userData['global_role']) {
                // Assign global role
                $permissionRegistrar->setPermissionsTeamId($globalTeamId);
                $user->assignRole($userData['role']->value);
            } else {
                // Assign store-scoped role and attach to stores
                $storesToAttach = $stores->take($userData['attach_to_stores'] ?? 1);
                
                foreach ($storesToAttach as $store) {
                    $permissionRegistrar->setPermissionsTeamId($store->id);
                    $user->assignRole($userData['role']->value);
                    
                    if (!$store->users()->where('user_id', $user->id)->exists()) {
                        $storeRole = $userData['role'] === RoleEnum::STORE_ADMIN 
                            ? StoreRoleEnum::STORE_ADMIN 
                            : StoreRoleEnum::STAFF;
                        $store->users()->attach($user->id, ['role' => $storeRole->value]);
                    }
                }
                
                $user->update(['last_active_store_id' => $storesToAttach->first()->id]);
            }

            $this->command->info("Created: {$user->name} ({$user->email}) - {$userData['role']->value}");
        }

        $totalUsers = User::count();
        $this->command->info("✅ Platform users seeding complete! Total users: {$totalUsers}");
    }
}

