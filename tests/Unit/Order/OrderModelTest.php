<?php

declare(strict_types=1);

namespace Tests\Unit\Order;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    // ── canBeCancelled ───────────────────────────────────────────

    /**
     * @dataProvider cancellableStatuses
     */
    public function test_can_be_cancelled_is_true_for_pending_and_processing(OrderStatusEnum $status): void
    {
        $order = Order::factory()->create(['status' => $status]);

        $this->assertTrue($order->canBeCancelled());
    }

    public static function cancellableStatuses(): array
    {
        return [
            'pending' => [OrderStatusEnum::PENDING],
            'processing' => [OrderStatusEnum::PROCESSING],
        ];
    }

    /**
     * @dataProvider nonCancellableStatuses
     */
    public function test_can_be_cancelled_is_false_for_shipped_delivered_cancelled_and_refunded(
        OrderStatusEnum $status
    ): void {
        $order = Order::factory()->create(['status' => $status]);

        $this->assertFalse($order->canBeCancelled());
    }

    public static function nonCancellableStatuses(): array
    {
        return [
            'shipped' => [OrderStatusEnum::SHIPPED],
            'delivered' => [OrderStatusEnum::DELIVERED],
            'cancelled' => [OrderStatusEnum::CANCELLED],
            'refunded' => [OrderStatusEnum::REFUNDED],
        ];
    }

    // ── markAsPaid / markAsFailed ────────────────────────────────

    public function test_mark_as_paid_sets_processing_status_and_payment_intent(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
            'payment_intent_id' => null,
        ]);

        $order->markAsPaid('pi_test_123');

        $order->refresh();
        $this->assertSame(OrderStatusEnum::PROCESSING, $order->status);
        $this->assertSame(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertSame('pi_test_123', $order->payment_intent_id);
    }

    public function test_mark_as_failed_sets_cancelled_status(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
        ]);

        $order->markAsFailed();

        $order->refresh();
        $this->assertSame(OrderStatusEnum::CANCELLED, $order->status);
        $this->assertSame(PaymentStatusEnum::FAILED, $order->payment_status);
    }

    // ── customer_email accessor ──────────────────────────────────

    public function test_customer_email_uses_registered_users_email_when_present(): void
    {
        $user = User::factory()->customer()->verified()->create(['email' => 'jane@example.com']);
        $order = Order::factory()->create(['user_id' => $user->id, 'guest_email' => null]);

        $this->assertSame('jane@example.com', $order->customer_email);
    }

    public function test_customer_email_falls_back_to_guest_email_when_no_user(): void
    {
        $order = Order::factory()->create(['user_id' => null, 'guest_email' => 'guest@example.com']);

        $this->assertSame('guest@example.com', $order->customer_email);
    }

    public function test_customer_email_prefers_guest_email_over_user_email_when_both_present(): void
    {
        // Documents real precedence: getCustomerEmailAttribute() returns
        // guest_email ?? user->email — guest_email wins when both are set.
        $user = User::factory()->customer()->verified()->create(['email' => 'jane@example.com']);
        $order = Order::factory()->create(['user_id' => $user->id, 'guest_email' => 'guest@example.com']);

        $this->assertSame('guest@example.com', $order->customer_email);
    }
}
