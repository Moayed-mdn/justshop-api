<?php

/**
 * Backfill Payment Transactions for Existing Paid Invoices
 * 
 * This script creates PaymentTransaction records for invoices that were paid
 * before the fix was applied to HandleInvoicePaymentSucceeded.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Actions\Billing\RecordPaymentTransactionAction;
use App\DTOs\Billing\RecordPaymentTransactionDTO;

echo "=== Backfilling Payment Transactions ===\n\n";

// Initialize Stripe
\Stripe\Stripe::setApiKey(config('services.stripe.secret'));

// Get all paid invoices that don't have payment transactions
$invoices = Invoice::where('status', 'paid')
    ->whereNotNull('provider_invoice_id')
    ->whereDoesntHave('paymentTransactions')
    ->with('subscription')
    ->get();

echo "📊 Found {$invoices->count()} paid invoices without payment transactions\n\n";

if ($invoices->isEmpty()) {
    echo "✅ All paid invoices already have payment transactions!\n";
    exit(0);
}

$recordAction = app(RecordPaymentTransactionAction::class);
$successCount = 0;
$failCount = 0;
$zeroAmountCount = 0;

foreach ($invoices as $invoice) {
    echo "Processing Invoice #{$invoice->invoice_number} ({$invoice->provider_invoice_id})...\n";
    
    // Skip zero-amount invoices (trials, discounts, etc.)
    if ($invoice->total_cents === 0) {
        echo "  ⏭️  Skipped (zero amount - trial/discount)\n\n";
        $zeroAmountCount++;
        continue;
    }
    
    try {
        // Fetch invoice from Stripe
        $stripeInvoice = \Stripe\Invoice::retrieve($invoice->provider_invoice_id);
        
        // Try to get payment reference
        $paymentId = $stripeInvoice->payment_intent ?? $stripeInvoice->charge ?? null;
        
        if (!$paymentId) {
            echo "  ⚠️  No payment_intent or charge found\n\n";
            $failCount++;
            continue;
        }
        
        $isPaymentIntent = isset($stripeInvoice->payment_intent);
        
        if ($isPaymentIntent) {
            // Fetch payment intent to get the charge
            $paymentIntent = \Stripe\PaymentIntent::retrieve($paymentId);
            
            if (!isset($paymentIntent->charges->data[0])) {
                echo "  ⚠️  Payment intent has no charges\n\n";
                $failCount++;
                continue;
            }
            
            $charge = $paymentIntent->charges->data[0];
        } else {
            // Fetch charge directly
            $charge = \Stripe\Charge::retrieve($paymentId);
        }
        
        // Create payment transaction
        $recordAction->execute(
            RecordPaymentTransactionDTO::fromStripeCharge(
                [
                    'id' => $charge->id,
                    'amount' => $charge->amount,
                    'currency' => $charge->currency,
                    'status' => $charge->status,
                    'created' => $charge->created,
                    'refunded' => $charge->refunded,
                    'payment_method' => $charge->payment_method ?? null,
                    'failure_code' => $charge->failure_code ?? null,
                    'failure_message' => $charge->failure_message ?? null,
                ],
                $invoice->billing_account_id,
                $invoice->id,
                $invoice->subscription_id
            )
        );
        
        echo "  ✅ Created PaymentTransaction (Charge: {$charge->id})\n";
        echo "     Amount: $" . ($charge->amount / 100) . "\n";
        echo "     Status: {$charge->status}\n\n";
        
        $successCount++;
        
    } catch (\Exception $e) {
        echo "  ❌ Error: {$e->getMessage()}\n\n";
        $failCount++;
    }
}

echo "=== Summary ===\n\n";
echo "✅ Successfully created: {$successCount}\n";
echo "⏭️  Skipped (zero amount): {$zeroAmountCount}\n";
echo "❌ Failed: {$failCount}\n";
echo "📊 Total processed: " . ($successCount + $failCount + $zeroAmountCount) . "\n";
