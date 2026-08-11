<?php

/**
 * Fix Invoice Period Dates
 * 
 * This script corrects period_ends_at for existing invoices by fetching
 * the correct period from Stripe line items.
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Invoice;
use Carbon\Carbon;

echo "=== Fixing Invoice Period Dates ===\n\n";

// Initialize Stripe
\Stripe\Stripe::setApiKey(config('services.stripe.secret'));

// Get all invoices with period mismatch (where start = end)
$invoices = Invoice::whereNotNull('provider_invoice_id')
    ->whereColumn('period_starts_at', '=', 'period_ends_at')
    ->get();

echo "📊 Found {$invoices->count()} invoices with period_starts_at = period_ends_at\n\n";

if ($invoices->isEmpty()) {
    echo "✅ All invoices have correct period dates!\n";
    exit(0);
}

$fixedCount = 0;
$failedCount = 0;
$skippedCount = 0;

foreach ($invoices as $invoice) {
    echo "Processing Invoice #{$invoice->invoice_number} ({$invoice->provider_invoice_id})...\n";
    echo "  Current: {$invoice->period_starts_at->toDateTimeString()} → {$invoice->period_ends_at->toDateTimeString()}\n";
    
    try {
        // Fetch invoice from Stripe
        $stripeInvoice = \Stripe\Invoice::retrieve($invoice->provider_invoice_id);
        
        // Get line item period
        if (!isset($stripeInvoice->lines->data[0]->period)) {
            echo "  ⏭️  No line item period found - skipping\n\n";
            $skippedCount++;
            continue;
        }
        
        $lineItemPeriod = $stripeInvoice->lines->data[0]->period;
        $periodStart = Carbon::createFromTimestamp($lineItemPeriod->start);
        $periodEnd = Carbon::createFromTimestamp($lineItemPeriod->end);
        
        // Check if it's actually different
        if ($invoice->period_ends_at->timestamp === $periodEnd->timestamp) {
            echo "  ✅ Already correct - skipping\n\n";
            $skippedCount++;
            continue;
        }
        
        // Update the invoice
        $invoice->update([
            'period_starts_at' => $periodStart,
            'period_ends_at' => $periodEnd,
        ]);
        
        $duration = $periodEnd->diffInDays($periodStart);
        
        echo "  ✅ FIXED: {$periodStart->toDateTimeString()} → {$periodEnd->toDateTimeString()}\n";
        echo "     Duration: {$duration} days\n\n";
        
        $fixedCount++;
        
    } catch (\Exception $e) {
        echo "  ❌ Error: {$e->getMessage()}\n\n";
        $failedCount++;
    }
}

echo "=== Summary ===\n\n";
echo "✅ Fixed: {$fixedCount}\n";
echo "⏭️  Skipped: {$skippedCount}\n";
echo "❌ Failed: {$failedCount}\n";
echo "📊 Total: {$invoices->count()}\n\n";

if ($fixedCount > 0) {
    echo "🎉 Invoice periods have been corrected!\n";
    echo "💡 Future invoices will automatically use correct periods (fix applied to RecordInvoiceDTO).\n";
}
