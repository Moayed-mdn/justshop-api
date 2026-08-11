<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "════════════════════════════════════════════════════════════\n";
echo "💰 تدقيق Proration - هل تم تحصيل فرق السعر؟\n";
echo "════════════════════════════════════════════════════════════\n\n";

$sub6 = App\Models\Subscription::with(['plan', 'planPrice'])->find(6);

// Get upgrade event
$upgradeEvent = App\Models\SubscriptionEvent::where('subscription_id', 6)
    ->where('event_type', 'upgraded')
    ->first();

if (!$upgradeEvent) {
    echo "❌ No upgrade event found!\n";
    exit(1);
}

echo "=== معلومات الترقية ===\n";
echo "Upgrade Time: {$upgradeEvent->created_at->format('Y-m-d H:i:s')}\n";
echo "From Plan: {$upgradeEvent->payload['from_plan']}\n";
echo "To Plan: {$upgradeEvent->payload['to_plan']}\n";
echo "Billing Cycle: {$upgradeEvent->payload['cycle']}\n\n";

// Calculate expected proration
$periodStart = $sub6->current_period_starts_at;
$periodEnd = $sub6->current_period_ends_at;
$upgradeTime = $upgradeEvent->created_at;

$totalDays = $periodStart->diffInDays($periodEnd);
$daysRemaining = $upgradeTime->diffInDays($periodEnd);
$daysPassed = $periodStart->diffInDays($upgradeTime);

echo "=== تحليل الفترة ===\n";
echo "Period Start: {$periodStart->format('Y-m-d H:i:s')}\n";
echo "Period End: {$periodEnd->format('Y-m-d H:i:s')}\n";
echo "Upgrade Time: {$upgradeTime->format('Y-m-d H:i:s')}\n\n";

echo "Total Days in Period: {$totalDays}\n";
echo "Days Passed before Upgrade: {$daysPassed}\n";
echo "Days Remaining: {$daysRemaining}\n\n";

// Old plan: growth = 99 USD/month
// New plan: enterprise = 299 USD/month
$oldPlanPrice = 99.00; // growth
$newPlanPrice = 299.00; // enterprise
$priceDifference = $newPlanPrice - $oldPlanPrice;

echo "=== حساب Proration المتوقع ===\n";
echo "Old Plan Price: \${$oldPlanPrice}/month\n";
echo "New Plan Price: \${$newPlanPrice}/month\n";
echo "Price Difference: \${$priceDifference}/month\n\n";

// Proration calculation
$prorationAmount = ($priceDifference / $totalDays) * $daysRemaining;
echo "Expected Proration Charge: \$" . number_format($prorationAmount, 2) . "\n";
echo "  Formula: (\${$priceDifference} / {$totalDays} days) × {$daysRemaining} days remaining\n\n";

// Check invoices
echo "=== فحص الفواتير الفعلية ===\n";
$allInvoices = App\Models\Invoice::where('subscription_id', 6)
    ->orderBy('created_at')
    ->get();

$totalCharged = 0;
foreach ($allInvoices as $inv) {
    $amount = $inv->amount_cents / 100;
    echo "Invoice #{$inv->id}:\n";
    echo "  Amount: \${$amount}\n";
    echo "  Status: {$inv->status->value}\n";
    echo "  Created: {$inv->created_at->format('Y-m-d H:i:s')}\n";
    echo "  Provider ID: " . ($inv->provider_invoice_id ?? 'NULL') . "\n";
    
    if ($inv->status->value === 'paid') {
        $totalCharged += $amount;
    }
    echo "\n";
}

echo "Total Actually Charged: \${$totalCharged}\n\n";

// Analysis
echo "═══════════════════════════════════════════════════════════\n";
echo "🔍 التحليل:\n";
echo "═══════════════════════════════════════════════════════════\n\n";

if ($totalCharged == 0) {
    echo "🔴 ISSUE: No proration charge found!\n\n";
    echo "  Expected: \$" . number_format($prorationAmount, 2) . "\n";
    echo "  Actual: \$0.00\n\n";
    
    echo "💡 السبب المحتمل:\n";
    echo "  1. Stripe لم يرسل webhook للفاتورة الجديدة بعد\n";
    echo "  2. الترقية تمت للتو والفاتورة قيد الإنشاء\n";
    echo "  3. مشكلة في webhook handler\n";
    echo "  4. الترقية كانت \"immediate\" ولكن Stripe لم يحصّل بعد\n\n";
    
    echo "📌 ما يجب فعله:\n";
    echo "  - انتظر بضع دقائق وتحقق من Stripe Dashboard\n";
    echo "  - تحقق من logs: storage/logs/laravel.log\n";
    echo "  - تحقق من Stripe webhooks\n";
    echo "  - ابحث عن 'invoice.paid' webhook event\n\n";
    
    echo "⚠️ إذا لم تظهر الفاتورة:\n";
    echo "  - المستخدم يحصل على Enterprise بسعر Growth!\n";
    echo "  - خسارة محتملة: \$" . number_format($prorationAmount, 2) . "\n";
    
} else {
    $difference = abs($totalCharged - $prorationAmount);
    $tolerance = 0.50; // 50 cents tolerance
    
    if ($difference <= $tolerance) {
        echo "✅ Proration correct!\n";
        echo "  Expected: \$" . number_format($prorationAmount, 2) . "\n";
        echo "  Actual: \${$totalCharged}\n";
        echo "  Difference: \$" . number_format($difference, 2) . " (within tolerance)\n";
    } else {
        echo "⚠️ Proration mismatch!\n";
        echo "  Expected: \$" . number_format($prorationAmount, 2) . "\n";
        echo "  Actual: \${$totalCharged}\n";
        echo "  Difference: \$" . number_format($difference, 2) . "\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════\n";
