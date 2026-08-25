<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\Billing\WebhookStatusEnum;
use App\Models\BillingAccount;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * HTTP-level coverage for POST /api/webhooks/stripe (App\Http\Controllers\Api\Billing\StripeWebhookController).
 *
 * GAP THIS FILE CLOSES: every pre-existing Billing webhook test (HandleCheckoutSessionCompletedTest,
 * AbandonedCheckoutFlowTest, AbandonedCheckoutCanceledVsExpiredTest) calls the webhook *handler*
 * classes directly and never exercises the real route — so signature verification, the
 * "missing signature" / "invalid signature" rejections, idempotency on duplicate event ids, and
 * the "no handler for event type" path had zero coverage before this file. This suite hits the
 * real HTTP route with real Stripe-style signatures (HMAC-SHA256 over "{timestamp}.{payload}",
 * matching Stripe\Webhook::constructEvent, which StripeProvider::verifyWebhookSignature calls
 * directly) — no real Stripe API calls are made anywhere in this file.
 *
 * QUEUE_CONNECTION=sync (see phpunit.xml), so ProcessStripeWebhookJob runs synchronously inside
 * the request — DB state is final by the time postJson() returns, no manual queue draining needed.
 */
class StripeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/webhooks/stripe';

    private string $webhookSecret;
    private BillingAccount $billingAccount;
    private Plan $plan;
    private PlanPrice $planPrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookSecret = 'whsec_test_billing_secret';
        Config::set('services.stripe.webhook_secret', $this->webhookSecret);

        $user = User::factory()->create();

        $this->billingAccount = BillingAccount::create([
            'owner_user_id' => $user->id,
            'billing_email' => 'billing@example.com',
            'legal_name' => 'Test Company',
            'country_code' => 'US',
            'default_currency' => 'usd',
            'status' => 'active',
            'trial_used' => false,
            'stores_count' => 0,
            'stores_max' => 5,
        ]);

        $this->plan = Plan::create([
            'code' => 'pro-webhook-http-test',
            'name' => json_encode(['en' => 'Pro Plan']),
            'description' => json_encode(['en' => 'Professional plan']),
            'tier' => 'growth',
            'tier_rank' => 2,
            'is_public' => true,
            'is_active' => true,
            'trial_days' => 14,
            'sort_order' => 1,
        ]);

        $this->planPrice = PlanPrice::create([
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'amount_cents' => 2900,
            'currency' => 'usd',
            'provider' => 'stripe',
            'provider_price_id' => 'price_test_webhook_http_123',
            'is_active' => true,
        ]);
    }

    /**
     * Build a real Stripe-style "Stripe-Signature" header for the given raw payload,
     * matching Stripe\Webhook::constructEvent's own verification: HMAC-SHA256 of
     * "{timestamp}.{payload}" keyed with the webhook secret.
     */
    private function signedHeaders(string $rawPayload, ?string $secret = null): array
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$rawPayload}", $secret ?? $this->webhookSecret);

        return ['Stripe-Signature' => "t={$timestamp},v1={$signature}"];
    }

    /** @test */
    public function test_webhook_without_signature_header_is_rejected(): void
    {
        $payload = [
            'id' => 'evt_no_sig',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_no_sig', 'mode' => 'subscription']],
        ];

        $response = $this->postJson(self::ENDPOINT, $payload);

        $response->assertStatus(400);
        $this->assertDatabaseCount('stripe_webhook_events', 0);
    }

    /** @test */
    public function test_webhook_with_malformed_signature_is_rejected(): void
    {
        $payload = [
            'id' => 'evt_bad_sig',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_bad_sig', 'mode' => 'subscription']],
        ];

        $response = $this->postJson(self::ENDPOINT, $payload, [
            'Stripe-Signature' => 't=' . time() . ',v1=not_a_real_hmac_signature',
        ]);

        $response->assertStatus(400);
        $this->assertDatabaseCount('stripe_webhook_events', 0);
    }

    /** @test */
    public function test_webhook_signature_computed_with_wrong_secret_is_rejected(): void
    {
        // Edge case: a well-formed t=/v1= signature that is a real HMAC, just computed
        // with the WRONG secret. This guards against a verifier that only checks header
        // shape instead of actually recomputing and comparing the HMAC.
        $payload = [
            'id' => 'evt_wrong_secret',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_wrong_secret', 'mode' => 'subscription']],
        ];
        $rawPayload = json_encode($payload);

        $response = $this->postJson(self::ENDPOINT, $payload, $this->signedHeaders(
            $rawPayload,
            secret: 'whsec_totally_different_secret'
        ));

        $response->assertStatus(400);
        $this->assertDatabaseCount('stripe_webhook_events', 0);
    }

    /** @test */
    public function test_webhook_with_unknown_event_type_is_accepted_and_marked_skipped(): void
    {
        $payload = [
            'id' => 'evt_unknown_type',
            'type' => 'customer.updated', // not in ProcessStripeWebhookJob's handler map
            'data' => ['object' => ['id' => 'cus_test_123']],
        ];
        $rawPayload = json_encode($payload);

        $response = $this->postJson(self::ENDPOINT, $payload, $this->signedHeaders($rawPayload));

        $response->assertStatus(200);

        $this->assertDatabaseHas('stripe_webhook_events', [
            'provider_event_id' => 'evt_unknown_type',
            'event_type' => 'customer.updated',
            'status' => WebhookStatusEnum::SKIPPED->value,
        ]);
    }

    /** @test */
    public function test_duplicate_webhook_event_id_is_not_reprocessed(): void
    {
        $payload = [
            'id' => 'evt_duplicate_test',
            'type' => 'customer.updated',
            'data' => ['object' => ['id' => 'cus_test_dup']],
        ];
        $rawPayload = json_encode($payload);

        $first = $this->postJson(self::ENDPOINT, $payload, $this->signedHeaders($rawPayload));
        $first->assertStatus(200);
        $this->assertDatabaseCount('stripe_webhook_events', 1);

        // Stripe retries webhooks that don't get a fast 2xx; the SAME event id arriving
        // again must not create a second row or be reprocessed.
        $second = $this->postJson(self::ENDPOINT, $payload, $this->signedHeaders($rawPayload));
        $second->assertStatus(200);
        $second->assertSeeText('Webhook already processed');

        $this->assertDatabaseCount('stripe_webhook_events', 1);
    }

    /** @test */
    public function test_checkout_session_completed_webhook_activates_subscription_end_to_end(): void
    {
        $subscription = Subscription::create([
            'billing_account_id' => $this->billingAccount->id,
            'plan_id' => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'billing_cycle' => 'monthly',
            'status' => 'trialing',
            'provider' => 'stripe',
        ]);

        $payload = [
            'id' => 'evt_checkout_completed_http',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_http_flow',
                    'mode' => 'subscription',
                    'subscription' => 'sub_stripe_http_flow',
                    'metadata' => [
                        'billing_account_id' => (string) $this->billingAccount->id,
                        'local_subscription_id' => (string) $subscription->id,
                    ],
                ],
            ],
        ];
        $rawPayload = json_encode($payload);

        $response = $this->postJson(self::ENDPOINT, $payload, $this->signedHeaders($rawPayload));

        $response->assertStatus(200);

        $this->assertDatabaseHas('stripe_webhook_events', [
            'provider_event_id' => 'evt_checkout_completed_http',
            'event_type' => 'checkout.session.completed',
            'status' => WebhookStatusEnum::PROCESSED->value,
        ]);

        $subscription->refresh();
        $this->assertSame('active', $subscription->status->value);
        $this->assertSame('sub_stripe_http_flow', $subscription->provider_subscription_id);
    }
}
