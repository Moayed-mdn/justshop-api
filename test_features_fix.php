<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$store = \App\Models\Store::where('slug', 'justshop-demo')->first();
if (!$store) {
    echo "Store not found\n";
    exit(1);
}

echo "Store: {$store->name} (ID: {$store->id})\n";

$page = \App\Models\Cms\Marketing\Store\StoreMarketingPage::where('is_homepage', true)
    ->where('store_id', $store->id)
    ->with('sections')
    ->first();

if (!$page) {
    echo "No homepage found\n";
    exit(1);
}

echo "Homepage: {$page->slug} (ID: {$page->id})\n\n";

$service = app(\App\Services\Storefront\Runtime\StorefrontRuntimeService::class);

// Use reflection to call the private method
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('mapMarketingSections');
$method->setAccessible(true);

// Set current store context
$storeProperty = $reflection->getProperty('store');
$storeProperty->setAccessible(true);
$storeProperty->setValue($service, $store);

$sections = $method->invoke($service, $page, 'en');

$featuresSection = collect($sections)->firstWhere('type', 'features');
if ($featuresSection) {
    echo "Features section found!\n";
    echo "Props structure:\n";
    echo json_encode($featuresSection['props'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "No features section found\n";
}
