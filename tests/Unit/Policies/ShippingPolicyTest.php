<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\Store\StoreRoleEnum;
use App\Models\Store;
use App\Models\User;
use App\Exceptions\Authorization\PermissionDeniedException;
use App\Policies\ShippingPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShippingPolicyTest extends TestCase
{
    use RefreshDatabase;

    private ShippingPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = app(ShippingPolicy::class);
        Permission::findOrCreate(PermissionEnum::SHIPPING_VIEW);
        Permission::findOrCreate(PermissionEnum::SHIPPING_CREATE);
        Permission::findOrCreate(PermissionEnum::SHIPPING_UPDATE);
        Permission::findOrCreate(PermissionEnum::SHIPPING_DELETE);
        Role::findOrCreate(RoleEnum::SUPER_ADMIN->value);
    }

    public function test_super_admin_without_impersonation_cannot_view(): void
    {
        // SUPER_ADMIN grants no implicit bypass of tenant isolation. Without
        // an active, governed impersonation session (and without being a
        // genuine store member), access must be denied exactly like any
        // other non-member.
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SUPER_ADMIN->value);
        $store = Store::factory()->create();

        $this->assertFalse($this->policy->view($user, $store));
    }

    public function test_store_admin_with_permission_can_view(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->givePermissionTo(PermissionEnum::SHIPPING_VIEW);

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
        $user->givePermissionTo(PermissionEnum::SHIPPING_VIEW);

        $this->assertTrue($this->policy->view($user, $store));
    }

    public function test_non_member_cannot_view(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->create();
        $user->givePermissionTo(PermissionEnum::SHIPPING_VIEW);

        $this->assertFalse($this->policy->view($user, $store));
    }

    public function test_store_admin_can_create(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->givePermissionTo(PermissionEnum::SHIPPING_CREATE);

        $this->assertTrue($this->policy->create($user, $store));
    }

    public function test_member_cannot_create_without_permission(): void
    {
        $this->expectException(PermissionDeniedException::class);

        $user = User::factory()->merchant()->create();
        $store = Store::factory()->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STAFF->value]);

        $this->policy->create($user, $store);
    }

    public function test_store_admin_can_update(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->givePermissionTo(PermissionEnum::SHIPPING_UPDATE);

        $this->assertTrue($this->policy->update($user, $store));
    }

    public function test_store_admin_can_delete(): void
    {
        $user = User::factory()->merchant()->create();
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => StoreRoleEnum::STORE_ADMIN->value]);
        $user->givePermissionTo(PermissionEnum::SHIPPING_DELETE);

        $this->assertTrue($this->policy->delete($user, $store));
    }
}
