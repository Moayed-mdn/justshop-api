<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class StripeConnectEcommerceWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookSecret = 'whsec_test_ecommerce_secret';
        Config::set('services.stripe.ecommerce_webhook_secret', $this->webhookSecret);

        $this->store = Store::factory()->create([
            'status' => StoreStatusEnum::ACTIVE,
            'is_active' => true,
            'stripe_account_id' => 'acct_test_merchant',
            'stripe_account_type' => 'express',
            'stripe_details_submitted' => false,
            'stripe_charges_enabled' => false,
            'stripe_payouts_enabled' => false,
        ]);
    }

    public function test_webhook_verifies_stripe_signature(): void
    {
        $payload = json_encode([
            'id' => 'evt_test',
            'type' => 'account.updated',
            'data' => ['object' => ['id' => 'acct_test']],
        ]);

        $response = $this->postJson('/api/webhooks/stripe/ecommerce', json_decode($payload, true), [
            'Stripe-Signature' => 'invalid_signature',
        ]);

        $response->assertStatus(400);
    }

    public function test_account_updated_syncs_stripe_connect_status(): void
    {
        $payload = [
            'id' => 'evt_test_account_updated',
            'type' => 'account.updated',
            'data' => [
                'object' => [
                    'id' => 'acct_test_merchant',
                    'details_submitted' => true,
                    'charges_enabled' => true,
                    'payouts_enabled' => true,
                ],
            ],
        ];

        $timestamp = time();
        $signedPayload = $this->generateStripeSignature(json_encode($payload), $timestamp);

        $response = $this->postJson('/api/webhooks/stripe/ecommerce', $payload, [
            'Stripe-Signature' => "t={$timestamp},v1={$signedPayload}",
        ]);

        $response->assertStatus(200);

        $this->store->refresh();
        $this->assertTrue($this->store->stripe_details_submitted);
        $this->assertTrue($this->store->stripe_charges_enabled);
        $this->assertTrue($this->store->stripe_payouts_enabled);
        $this->assertNotNull($this->store->stripe_onboarded_at);
        $this->assertTrue($this->store->canReceivePayments());
    }

    public function test_account_updated_sets_onboarded_timestamp_only_once(): void
    {
        // First update - fully onboarded
        $payload = [
            'id' => 'evt_test_1',
            'type' => 'account.updated',
            'data' => [
                'object' => [
                    'id' => 'acct_test_merchant',
                    'details_submitted' => true,
                    'charges_enabled' => true,
                    'payouts_enabled' => true,
                ],
            ],
        ];

        $timestamp = time();
        $signedPayload = $this->generateStripeSignature(json_encode($payload), $timestamp);

        $this->postJson('/api/webhooks/stripe/ecommerce', $payload, [
            'Stripe-Signature' => "t={$timestamp},v1={$signedPayload}",
        ]);

        $this->store->refresh();
        $firstOnboardedAt = $this->store->stripe_onboarded_at;
        $this->assertNotNull($firstOnboardedAt);

        sleep(1);

        // Second update - status unchanged
        $payload['id'] = 'evt_test_2';
        $timestamp = time();
        $signedPayload = $this->generateStripeSignature(json_encode($payload), $timestamp);

        $this->postJson('/api/webhooks/stripe/ecommerce', $payload, [
            'Stripe-Signature' => "t={$timestamp},v1={$signedPayload}",
        ]);

        $this->store->refresh();
        $this->assertEquals($firstOnboardedAt->timestamp, $this->store->stripe_onboarded_at->timestamp);
    }

    public function test_account_updated_handles_partial_onboarding(): void
    {
        $payload = [
            'id' => 'evt_test_partial',
            'type' => 'account.updated',
            'data' => [
                'object' => [
                    'id' => 'acct_test_merchant',
                    'details_submitted' => true,
                    'charges_enabled' => false, // Charges not yet enabled
                    'payouts_enabled' => false,
                ],
            ],
        ];

        $timestamp = time();
        $signedPayload = $this->generateStripeSignature(json_encode($payload), $timestamp);

        $this->postJson('/api/webhooks/stripe/ecommerce', $payload, [
            'Stripe-Signature' => "t={$timestamp},v1={$signedPayload}",
        ]);

        $this->store->refresh();
        $this->assertTrue($this->store->stripe_details_submitted);
        $this->assertFalse($this->store->stripe_charges_enabled);
        $this->assertFalse($this->store->stripe_payouts_enabled);
        $this->assertNull($this->store->stripe_onboarded_at);
        $this->assertFalse($this->store->canReceivePayments());
    }

    public function test_account_updated_ignores_unknown_account(): void
    {
        $payload = [
            'id' => 'evt_test_unknown',
            'type' => 'account.updated',
            'data' => [
                'object' => [
                    'id' => 'acct_unknown_merchant',
                    'details_submitted' => true,
                    'charges_enabled' => true,
                    'payouts_enabled' => true,
                ],
            ],
        ];

        $timestamp = time();
        $signedPayload = $this->generateStripeSignature(json_encode($payload), $timestamp);

        $response = $this->postJson('/api/webhooks/stripe/ecommerce', $payload, [
            'Stripe-Signature' => "t={$timestamp},v1={$signedPayload}",
        ]);

        $response->assertStatus(200); // Webhook still returns 200 to prevent retries
    }

    public function test_payment_intent_succeeded_completes_checkout(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TEST-' . uniqid(),
            'store_id' => $this->store->id,
            'subtotal' => 50.00,
            'tax_amount' => 0,
            'shipping_amount' => 5.00,
            'discount_amount' => 0,
            'total' => 55.00,
            'currency' => 'usd',
            'status' => OrderStatusEnum::PENDING,
            'payment_status' => PaymentStatusEnum::PENDING,
            'payment_intent_id' => 'pi_test_12345',
            'shipping_method' => 'standard',
        ]);

        // Mock the completeCheckout call by directly updating order
        $this->mock(\App\Services\EnhancedCheckoutService::class, function ($mock) use ($order) {
            $mock->shouldReceive('completeCheckout')
                ->once()
                ->with('pi_test_12345')
                ->andReturnUsing(function () use ($order) {
                    $order->update([
                        'payment_status' => PaymentStatusEnum::PAID,
                        'status' => OrderStatusEnum::PROCESSING,
                    ]);
                    return $order->fresh();
                });
        });

        $payload = [
            'id' => 'evt_test_payment',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_12345',
                    'metadata' => [
                        'order_id' => (string) $order->id,
                    ],
                ],
            ],
        ];

        $timestamp = time();
        $signedPayload = $this->generateStripeSignature(json_encode($payload), $timestamp);

        $response = $this->postJson('/api/webhooks/stripe/ecommerce', $payload, [
            'Stripe-Signature' => "t={$timestamp},v1={$signedPayload}",
        ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals(PaymentStatusEnum::PAID, $order->payment_status);
        $this->assertEquals(OrderStatusEnum::PROCESSING, $order->status);
    }

    public function test_payment_intent_succeeded_ignores_missing_order_metadata(): void
    {
        $payload = [
            'id' => 'evt_test_no_order',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_no_metadata',
                    'metadata' => [], // No order_id
                ],
            ],
        ];

        $timestamp = time();
        $signedPayload = $this->generateStripeSignature(json_encode($payload), $timestamp);

        $response = $this->postJson('/api/webhooks/stripe/ecommerce', $payload, [
            'Stripe-Signature' => "t={$timestamp},v1={$signedPayload}",
        ]);

        $response->assertStatus(200); // Still returns 200 to prevent retries
    }

    public function test_unhandled_event_types_return_success(): void
    {
        $payload = [
            'id' => 'evt_test_unhandled',
            'type' => 'charge.refunded', // Not handled
            'data' => [
                'object' => [
                    'id' => 'ch_test_12345',
                ],
            ],
        ];

        $timestamp = time();
        $signedPayload = $this->generateStripeSignature(json_encode($payload), $timestamp);

        $response = $this->postJson('/api/webhooks/stripe/ecommerce', $payload, [
            'Stripe-Signature' => "t={$timestamp},v1={$signedPayload}",
        ]);

        $response->assertStatus(200);
    }

    /**
     * Generate a valid Stripe signature for testing.
     */
    private function generateStripeSignature(string $payload, int $timestamp): string
    {
        $signedPayload = "{$timestamp}.{$payload}";
        return hash_hmac('sha256', $signedPayload, $this->webhookSecret);
    }
}
