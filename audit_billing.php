<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "════════════════════════════════════════════════════════════\n";
echo "🔍 تدقيق الدفع والحسابات - كشف الغش المحتمل\n";
echo "════════════════════════════════════════════════════════════\n\n";

$issues = [];
$warnings = [];

// 1. Check Active Subscription
echo "=== 1️⃣ تدقيق الاشتراك النشط ===\n";
$sub6 = App\Models\Subscription::with(['plan', 'planPrice'])->find(6);

echo "Subscription #6:\n";
echo "  Status: {$sub6->status->value}\n";
echo "  Plan: {$sub6->plan->code} (ID: {$sub6->plan_id})\n";
echo "  Price: " . ($sub6->planPrice->amount_cents / 100) . " USD/{$sub6->planPrice->billing_cycle->value}\n";
echo "  Provider Subscription ID: " . ($sub6->provider_subscription_id ?? 'NULL') . "\n";
echo "  Current Period: {$sub6->current_period_starts_at->format('Y-m-d')} to {$sub6->current_period_ends_at->format('Y-m-d')}\n\n";

// Critical Check: Provider Subscription ID
if (!$sub6->provider_subscription_id) {
    $issues[] = "❌ CRITICAL: Subscription #6 has no provider_subscription_id - Customer getting FREE service!";
} else {
    echo "✅ Provider subscription exists: {$sub6->provider_subscription_id}\n";
}
echo "\n";

// 2. Check Invoices
echo "=== 2️⃣ تدقيق الفواتير ===\n";
$invoices = App\Models\Invoice::where('subscription_id', 6)->orderBy('id')->get();
echo "عدد الفواتير للاشتراك #6: {$invoices->count()}\n\n";

$totalBilled = 0;
foreach ($invoices as $inv) {
    echo "Invoice #{$inv->id}:\n";
    echo "  Amount: " . ($inv->amount_cents / 100) . " USD\n";
    echo "  Status: {$inv->status->value}\n";
    echo "  Provider Invoice ID: " . ($inv->provider_invoice_id ?? 'NULL') . "\n";
    echo "  Issued: {$inv->issued_at->format('Y-m-d H:i:s')}\n";
    echo "  Paid: " . ($inv->paid_at ? $inv->paid_at->format('Y-m-d H:i:s') : 'NOT PAID') . "\n\n";
    
    if (!$inv->provider_invoice_id) {
        $warnings[] = "⚠️ Invoice #{$inv->id} has no provider_invoice_id";
    }
    
    if ($inv->status->value === 'paid') {
        $totalBilled += $inv->amount_cents;
    }
}

echo "📊 Total Billed (Paid): " . ($totalBilled / 100) . " USD\n";

// Expected billing for upgrade
$now = now();
$periodStart = $sub6->current_period_starts_at;
$periodEnd = $sub6->current_period_ends_at;
$daysInPeriod = $periodStart->diffInDays($periodEnd);
$daysPassed = $periodStart->diffInDays($now);

echo "  Current Period: {$daysInPeriod} days\n";
echo "  Days Passed: {$daysPassed} days\n\n";

// 3. Check Abandoned Checkouts
echo "=== 3️⃣ تدقيق الاشتراكات المهجورة ===\n";
$expiredSubs = App\Models\Subscription::where('billing_account_id', 1)
    ->where('status', 'expired')
    ->whereNull('provider_subscription_id')
    ->get();

echo "Expired Subscriptions without provider_subscription_id: {$expiredSubs->count()}\n";

foreach ($expiredSubs as $exp) {
    echo "  Subscription #{$exp->id}:\n";
    echo "    plan_price_id: {$exp->plan_price_id}";
    $price = App\Models\PlanPrice::find($exp->plan_price_id);
    if ($price) {
        echo " (" . ($price->amount_cents / 100) . " USD)";
    }
    echo "\n";
}
echo "\n";

// Check for invoices on expired subs
$expiredInvoices = App\Models\Invoice::whereIn('subscription_id', $expiredSubs->pluck('id'))->get();
if ($expiredInvoices->count() > 0) {
    $issues[] = "❌ FRAUD ALERT: Found {$expiredInvoices->count()} invoices for expired (never activated) subscriptions!";
    foreach ($expiredInvoices as $inv) {
        $issues[] = "  - Invoice #{$inv->id}: " . ($inv->amount_cents / 100) . " USD ({$inv->status->value})";
    }
} else {
    echo "✅ لا توجد فواتير للاشتراكات المهجورة - صحيح\n";
}
echo "\n";

// 4. Check Trial Subscription
echo "=== 4️⃣ تدقيق الاشتراك التجريبي ===\n";
$sub1 = App\Models\Subscription::find(1);
echo "Subscription #1 (Trial):\n";
echo "  Status: {$sub1->status->value}\n";
echo "  plan_price_id: " . ($sub1->plan_price_id ?? 'NULL') . "\n";
echo "  canceled_at: " . ($sub1->canceled_at ? $sub1->canceled_at->format('Y-m-d H:i:s') : 'NULL') . "\n";

$trialInvoices = App\Models\Invoice::where('subscription_id', 1)->get();
echo "  Invoices: {$trialInvoices->count()}\n";

if ($trialInvoices->count() > 0) {
    foreach ($trialInvoices as $inv) {
        if ($inv->amount_cents > 0) {
            $issues[] = "❌ FRAUD: Invoice #{$inv->id} charging {$inv->amount_cents} cents for trial subscription!";
        }
    }
} else {
    echo "✅ لا توجد فواتير للتجريبي - صحيح\n";
}
echo "\n";

// 5. Check Sync Status
echo "=== 5️⃣ فحص التزامن مع Stripe ===\n";
echo "  Last synced: " . ($sub6->provider_synced_at ? $sub6->provider_synced_at->format('Y-m-d H:i:s') : 'NEVER') . "\n";
echo "  Last updated: {$sub6->updated_at->format('Y-m-d H:i:s')}\n";

if ($sub6->provider_synced_at) {
    $syncDiff = $sub6->provider_synced_at->diffInMinutes($sub6->updated_at);
    if ($syncDiff > 5) {
        $warnings[] = "⚠️ Subscription updated but not synced with Stripe for {$syncDiff} minutes";
    } else {
        echo "✅ Recently synced\n";
    }
} else {
    $warnings[] = "⚠️ Never synced with Stripe";
}
echo "\n";

// 6. Check for duplicate active subscriptions
echo "=== 6️⃣ فحص الاشتراكات المكررة ===\n";
$activeCount = App\Models\Subscription::where('billing_account_id', 1)
    ->where('status', 'active')
    ->count();
echo "Active subscriptions: {$activeCount}\n";
if ($activeCount > 1) {
    $issues[] = "❌ CRITICAL: Multiple active subscriptions found - customer might be charged twice!";
} else {
    echo "✅ Only one active subscription\n";
}
echo "\n";

// 7. Check getActiveForAccount consistency
echo "=== 7️⃣ فحص اتساق getActiveForAccount ===\n";
$repo = app(App\Repositories\Subscription\SubscriptionRepository::class);
$active = $repo->getActiveForAccount(1);

if (!$active) {
    $issues[] = "❌ CRITICAL: getActiveForAccount returns NULL but active subscription exists!";
} elseif ($active->id !== 6) {
    $issues[] = "❌ CRITICAL: getActiveForAccount returns wrong subscription (#{$active->id} instead of #6)";
} else {
    echo "✅ getActiveForAccount returns correct subscription\n";
}
echo "\n";

// Final Report
echo "════════════════════════════════════════════════════════════\n";
echo "📊 التقرير النهائي\n";
echo "════════════════════════════════════════════════════════════\n\n";

if (count($issues) > 0) {
    echo "🔴 CRITICAL ISSUES (" . count($issues) . "):\n";
    foreach ($issues as $issue) {
        echo "{$issue}\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️ WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $warning) {
        echo "{$warning}\n";
    }
    echo "\n";
}

if (count($issues) === 0 && count($warnings) === 0) {
    echo "✅ No fraud detected - All billing checks passed!\n";
    echo "✅ Customer is being charged correctly\n";
    echo "✅ No free services being given\n";
    echo "✅ No double charging\n";
}

echo "════════════════════════════════════════════════════════════\n";
