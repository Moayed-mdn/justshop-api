<?php

declare(strict_types=1);

namespace Tests\Unit\PaymentMethod;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\PaymentMethod;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Unit tests for PaymentMethodPolicy.
 *
 * PaymentMethodPolicy is registered against the PaymentMethod model in
 * AuthServiceProvider::$policies, so `Gate::forUser()->authorize()` /
 * `$user->can()` dispatch to it exactly as Laravel would in a real request,
 * even though no controller currently calls it (see PaymentMethodServiceTest
 * for the "orphaned domain" note).
 */
class PaymentMethodPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_owner_with_permission_can_update_own_payment_method(): void
    {
        $user = User::factory()->customer()->verified()->create();
        $user->givePermissionTo(PermissionEnum::PAYMENT_METHOD_UPDATE);
        $paymentMethod = PaymentMethod::factory()->for($user)->create();

        $this->assertTrue(Gate::forUser($user)->allows('update', $paymentMethod));
    }

    public function test_owner_without_permission_cannot_update_own_payment_method(): void
    {
        // Documents real (somewhat surprising) behavior: PaymentMethodPolicy
        // requires the Spatie PAYMENT_METHOD_UPDATE permission even to manage
        // one's own payment method — ownership alone is not sufficient.
        $user = User::factory()->customer()->verified()->create();
        $paymentMethod = PaymentMethod::factory()->for($user)->create();

        $this->assertFalse(Gate::forUser($user)->allows('update', $paymentMethod));
    }

    public function test_user_with_permission_cannot_update_another_users_payment_method(): void
    {
        $owner = User::factory()->customer()->verified()->create();
        $paymentMethod = PaymentMethod::factory()->for($owner)->create();

        $otherUser = User::factory()->customer()->verified()->create();
        $otherUser->givePermissionTo(PermissionEnum::PAYMENT_METHOD_UPDATE);

        $this->assertFalse(Gate::forUser($otherUser)->allows('update', $paymentMethod));
    }

    public function test_super_admin_with_permission_can_update_any_payment_method(): void
    {
        $owner = User::factory()->customer()->verified()->create();
        $paymentMethod = PaymentMethod::factory()->for($owner)->create();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleEnum::SUPER_ADMIN->value);
        $superAdmin->givePermissionTo(PermissionEnum::PAYMENT_METHOD_UPDATE);

        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $paymentMethod));
    }

    public function test_owner_with_permission_can_delete_own_payment_method(): void
    {
        $user = User::factory()->customer()->verified()->create();
        $user->givePermissionTo(PermissionEnum::PAYMENT_METHOD_DELETE);
        $paymentMethod = PaymentMethod::factory()->for($user)->create();

        $this->assertTrue(Gate::forUser($user)->allows('delete', $paymentMethod));
    }

    public function test_user_cannot_delete_another_users_payment_method_even_with_permission(): void
    {
        $owner = User::factory()->customer()->verified()->create();
        $paymentMethod = PaymentMethod::factory()->for($owner)->create();

        $otherUser = User::factory()->customer()->verified()->create();
        $otherUser->givePermissionTo(PermissionEnum::PAYMENT_METHOD_DELETE);

        $this->assertFalse(Gate::forUser($otherUser)->allows('delete', $paymentMethod));
    }
}
