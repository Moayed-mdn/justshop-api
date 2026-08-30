<?php

declare(strict_types=1);

namespace App\Console\Commands\Order;

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Order\PaymentStatusEnum;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class ExpireAbandonedOrdersCommand extends Command
{
    /**
     * Statuses on a Stripe PaymentIntent that are still safe to cancel —
     * i.e. no charge has actually gone through yet.
     */
    private const CANCELABLE_PAYMENT_INTENT_STATUSES = [
        'requires_payment_method',
        'requires_confirmation',
        'requires_action',
        'requires_capture',
    ];

    protected $signature = 'orders:expire-abandoned
                            {--dry-run : Show which orders would be expired without actually expiring them}
                            {--minutes=60 : Age (in minutes) after which an unpaid draft order is considered abandoned}
                            {--batch-size=100 : Number of orders to process per run}';

    protected $description = 'Cancel abandoned checkout orders (created but never paid) and release their Stripe PaymentIntents';

    /**
     * Since EnhancedCheckoutService now reuses one draft order per
     * user/store checkout attempt instead of creating a new one on every
     * "continue to payment" retry, a customer only ever has a single
     * pending/pending order for a given store at a time. This sweep cleans
     * up the case where that single draft order is simply abandoned —
     * the customer never finished paying and never came back.
     *
     * Should run on a schedule (see routes/console.php).
     */
    public function handle(StripeClient $stripe): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $minutes = (int) $this->option('minutes');
        $batchSize = (int) $this->option('batch-size');

        $this->info("🔍 Searching for draft orders older than {$minutes} minutes...");

        $abandoned = Order::where('status', OrderStatusEnum::PENDING)
            ->where('payment_status', PaymentStatusEnum::PENDING)
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->limit($batchSize)
            ->get();

        if ($abandoned->isEmpty()) {
            $this->info('✅ No abandoned draft orders found.');
            return self::SUCCESS;
        }

        $this->warn("Found {$abandoned->count()} abandoned draft order(s)");

        if ($isDryRun) {
            $this->table(
                ['ID', 'Order #', 'Store', 'User', 'Created At', 'Minutes Old'],
                $abandoned->map(fn (Order $order) => [
                    $order->id,
                    $order->order_number,
                    $order->store_id,
                    $order->user_id,
                    $order->created_at->toDateTimeString(),
                    now()->diffInMinutes($order->created_at),
                ])
            );

            $this->info('🔸 Dry run mode - no changes made');
            return self::SUCCESS;
        }

        $expired = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($abandoned as $order) {
            try {
                if (!$this->safeToExpire($stripe, $order)) {
                    // A payment came in right around the time this sweep
                    // ran. Leave it to completeCheckout()/webhooks — it'll
                    // no longer match this query on the next run either way.
                    $this->comment("⏭️  Skipping order #{$order->order_number} — payment appears to be in flight");
                    $skipped++;
                    continue;
                }

                $order->update([
                    'status' => OrderStatusEnum::CANCELLED,
                    'payment_status' => PaymentStatusEnum::FAILED,
                    'cancelled_at' => now(),
                    'internal_notes' => trim(
                        ($order->internal_notes ? $order->internal_notes . "\n" : '')
                        . 'Auto-cancelled: checkout abandoned, payment never completed.'
                    ),
                ]);

                cache()->forget("checkout_session:{$order->id}");

                $this->info("✅ Expired order #{$order->order_number}");
                $expired++;
            } catch (\Throwable $e) {
                $this->error("❌ Failed to expire order #{$order->order_number}: {$e->getMessage()}");

                Log::error('orders.expire_abandoned.failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $failed++;
            }
        }

        $this->newLine();
        $this->info("✅ Processed: {$abandoned->count()}");
        $this->info("✅ Expired: {$expired}");

        if ($skipped > 0) {
            $this->info("⏭️  Skipped (payment in flight): {$skipped}");
        }

        if ($failed > 0) {
            $this->error("❌ Failed: {$failed}");
        }

        Log::info('orders.expire_abandoned.completed', [
            'processed' => $abandoned->count(),
            'expired' => $expired,
            'skipped' => $skipped,
            'failed' => $failed,
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Checks Stripe directly (the source of truth for payment state) right
     * before expiring the order, and cancels the PaymentIntent when it's
     * still safe to do so. Returns false — meaning "don't touch this order"
     * — if a payment actually went through around the same time this sweep
     * ran, so we never cancel an order the customer just successfully paid
     * for.
     */
    private function safeToExpire(StripeClient $stripe, Order $order): bool
    {
        if (!$order->payment_intent_id) {
            return true;
        }

        try {
            $paymentIntent = $stripe->paymentIntents->retrieve($order->payment_intent_id);

            if (in_array($paymentIntent->status, ['processing', 'succeeded'], true)) {
                return false;
            }

            if (in_array($paymentIntent->status, self::CANCELABLE_PAYMENT_INTENT_STATUSES, true)) {
                $stripe->paymentIntents->cancel($paymentIntent->id, [
                    'cancellation_reason' => 'abandoned',
                ]);
            }

            return true;
        } catch (ApiErrorException $e) {
            // PaymentIntent missing/unreachable on Stripe's side — don't
            // let that block expiring the local draft order.
            Log::warning('orders.expire_abandoned.stripe_lookup_failed', [
                'order_id' => $order->id,
                'payment_intent_id' => $order->payment_intent_id,
                'error' => $e->getMessage(),
            ]);

            return true;
        }
    }
}
