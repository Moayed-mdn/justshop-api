#!/usr/bin/env php
<?php

use App\Models\User;
use App\Models\Store;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use Spatie\Permission\PermissionRegistrar;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔄 Upgrading moayad@test.com to store_admin...\n\n";

$user = User::where('email', 'moayad@test.com')->first();
$store = Store::find(1);

if (!$user || !$store) {
    echo "❌ User or store not found\n";
    exit(1);
}

// Step 1: Update pivot table
$user->stores()->updateExistingPivot($store->id, [
    'role' => StoreRoleEnum::STORE_ADMIN->value
]);
echo "✅ Step 1: Store role updated to store_admin\n";

// Step 2: Assign global role with correct team context
$permissionRegistrar = app(PermissionRegistrar::class);
$permissionRegistrar->setPermissionsTeamId($store->id);

if (!$user->hasRole(RoleEnum::STORE_ADMIN->value)) {
    $user->assignRole(RoleEnum::STORE_ADMIN->value);
    echo "✅ Step 2: Global role assigned\n";
} else {
    echo "ℹ️  Step 2: User already has global store_admin role\n";
}

// Step 3: Clear cache
$permissionRegistrar->forgetCachedPermissions();
echo "✅ Step 3: Permission cache cleared\n\n";

// Step 4: Verify
$user = $user->fresh();
echo "📋 Verifying permissions:\n";

$permissions = [
    'category.update',
    'product.update',
    'brand.update',
    'tag.update',
    'user.create',
];

foreach ($permissions as $permission) {
    $has = $user->can($permission);
    $icon = $has ? '✅' : '❌';
    echo "  {$icon} {$permission}: " . ($has ? 'YES' : 'NO') . "\n";
}

echo "\n🎉 Done! User has been upgraded to store_admin.\n";
echo "💡 Please logout and login again in the frontend.\n";
