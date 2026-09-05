<?php

declare(strict_types=1);

namespace Tests\Unit\PaymentMethod;

use App\DTOs\PaymentMethod\StorePaymentMethodDTO;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\PaymentMethodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for PaymentMethodService.
 *
 * NOTE ON SCOPE: the PaymentMethod domain (Action/DTO/Service/Repository/
 * Policy/FormRequest/Resource) is fully implemented but is not wired to any
 * HTTP route or controller anywhere in the app (verified: no controller,
 * no api route, no GraphQL field references it). PaymentMethodPolicy IS
 * registered in AuthServiceProvider, so it is exercised through Laravel's
 * real Gate — see PaymentMethodPolicyTest. Since no real HTTP endpoint
 * exists, these are unit tests calling the real, existing service class
 * directly rather than fabricated feature/HTTP tests against a route that
 * does not exist in the application.
 */
class PaymentMethodServiceTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethodService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(PaymentMethodService::class);
        $this->user = User::factory()->customer()->verified()->create();
    }

    private function dto(bool $isDefault = false): StorePaymentMethodDTO
    {
        return new StorePaymentMethodDTO(
            provider: 'stripe',
            paymentMethodId: 'pm_' . uniqid(),
            brand: 'visa',
            lastFour: '4242',
            expMonth: 12,
            expYear: (int) now()->addYears(2)->format('Y'),
            isDefault: $isDefault,
            userId: $this->user->id,
        );
    }

    // ── Happy path ───────────────────────────────────────────────

    public function test_creating_a_payment_method_persists_it_for_the_user(): void
    {
        $paymentMethod = $this->service->createPaymentMethod($this->dto());

        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod->id,
            'user_id' => $this->user->id,
            'last_four' => '4242',
        ]);
    }

    public function test_creating_a_default_payment_method_unsets_previous_default(): void
    {
        $first = $this->service->createPaymentMethod($this->dto(isDefault: true));
        $this->assertTrue($first->fresh()->is_default);

        $second = $this->service->createPaymentMethod($this->dto(isDefault: true));

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_set_as_default_makes_target_default_and_unsets_others(): void
    {
        $first = PaymentMethod::factory()->for($this->user)->default()->create();
        $second = PaymentMethod::factory()->for($this->user)->create(['is_default' => false]);

        $this->service->setAsDefault($second);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_deleting_a_non_default_payment_method_leaves_existing_default_untouched(): void
    {
        $default = PaymentMethod::factory()->for($this->user)->default()->create();
        $other = PaymentMethod::factory()->for($this->user)->create(['is_default' => false]);

        $this->service->deletePaymentMethod($other);

        $this->assertSoftDeleted('payment_methods', ['id' => $other->id]);
        $this->assertTrue($default->fresh()->is_default);
    }

    // ── Edge case / isolation ────────────────────────────────────

    public function test_deleting_a_users_only_payment_method_leaves_no_default(): void
    {
        $only = PaymentMethod::factory()->for($this->user)->default()->create();

        $this->service->deletePaymentMethod($only);

        $this->assertSoftDeleted('payment_methods', ['id' => $only->id]);
        $this->assertNull(
            PaymentMethod::where('user_id', $this->user->id)->where('is_default', true)->first()
        );
    }

    /**
     * KNOWN BUG (confirmed by reading PaymentMethodService::deletePaymentMethod
     * and PaymentMethodRepository::getDefault):
     *
     * When deleting the default payment method while other payment methods
     * exist, the service is clearly *intended* to promote one of the
     * remaining methods to default (this is exactly what AddressService::
     * deleteAddress() does correctly for shipping/billing defaults).
     *
     * But deletePaymentMethod() calls getDefault($paymentMethod->user_id)
     * BEFORE deleting $paymentMethod. Since $paymentMethod itself still has
     * is_default = true at that point, getDefault() finds and returns
     * $paymentMethod itself — so $newDefault is never null, the
     * "promote next in line" branch never runs, and the method is deleted
     * with no replacement default assigned.
     *
     * This test documents the CORRECT/intended behavior (a remaining method
     * becomes default) and is expected to FAIL against the current code —
     * that failure is the reproduction of the bug, not a mistake in the
     * test. See final report.
     */
    public function test_deleting_the_default_payment_method_promotes_another_to_default(): void
    {
        $default = PaymentMethod::factory()->for($this->user)->default()->create();
        $other = PaymentMethod::factory()->for($this->user)->create(['is_default' => false]);

        $this->service->deletePaymentMethod($default);

        $this->assertSoftDeleted('payment_methods', ['id' => $default->id]);
        $this->assertTrue(
            $other->fresh()->is_default,
            'Expected the remaining payment method to be promoted to default after deleting the default one.'
        );
    }

    public function test_payment_methods_are_isolated_between_users(): void
    {
        $otherUser = User::factory()->customer()->verified()->create();
        PaymentMethod::factory()->for($otherUser)->count(2)->create();
        PaymentMethod::factory()->for($this->user)->count(1)->create();

        $methods = $this->service->getUserPaymentMethods($this->user->id);

        $this->assertCount(1, $methods);
        $this->assertTrue($methods->every(fn (PaymentMethod $pm) => $pm->user_id === $this->user->id));
    }
}
