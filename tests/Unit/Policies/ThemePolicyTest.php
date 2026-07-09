<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Policies\ThemePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ThemePolicyTest extends TestCase
{
    use RefreshDatabase;

    private ThemePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(ThemePolicy::class);
        Permission::findOrCreate(PermissionEnum::THEME_VIEW);
        Permission::findOrCreate(PermissionEnum::THEME_CREATE);
        Permission::findOrCreate(PermissionEnum::THEME_UPDATE);
        Permission::findOrCreate(PermissionEnum::THEME_DELETE);
        Permission::findOrCreate(PermissionEnum::THEME_PUBLISH);
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value);
    }

    public function test_super_admin_can_view(): void
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMIN->value);
        $store = Store::factory()->create();

        $this->assertTrue($this->policy->view($user, $store));
    }

    public function test_store_admin_with_permission_can_view(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->givePermissionTo(PermissionEnum::THEME_VIEW);

        $this->assertTrue($this->policy->view($user, $store));
    }

    public function test_store_admin_without_permission_cannot_view(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);

        $this->assertFalse($this->policy->view($user, $store));
    }

    public function test_member_with_permission_can_view(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STAFF->value]);
        $user->givePermissionTo(PermissionEnum::THEME_VIEW);

        $this->assertTrue($this->policy->view($user, $store));
    }

    public function test_non_member_cannot_view(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->create();
        $user->givePermissionTo(PermissionEnum::THEME_VIEW);

        $this->assertFalse($this->policy->view($user, $store));
    }

    public function test_store_admin_can_create(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->givePermissionTo(PermissionEnum::THEME_CREATE);

        $this->assertTrue($this->policy->create($user, $store));
    }

    public function test_store_admin_can_update(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->givePermissionTo(PermissionEnum::THEME_UPDATE);

        $this->assertTrue($this->policy->update($user, $store));
    }

    public function test_store_admin_can_delete(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->givePermissionTo(PermissionEnum::THEME_DELETE);

        $this->assertTrue($this->policy->delete($user, $store));
    }

    public function test_store_admin_can_publish(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->givePermissionTo(PermissionEnum::THEME_PUBLISH);

        $this->assertTrue($this->policy->publish($user, $store));
    }
}
