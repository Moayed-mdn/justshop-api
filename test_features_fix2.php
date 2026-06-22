<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Finding stores...\n";
$stores = \App\Models\Store::all();
echo "Found " . $stores->count() . " stores\n";

foreach ($stores as $store) {
    echo "- {$store->name} (slug: {$store->slug}, id: {$store->id})\n";
}

echo "\nFinding homepage pages...\n";
$pages = \App\Models\Cms\Marketing\Store\StoreMarketingPage::where('is_homepage', true)->with('sections')->get();
echo "Found " . $pages->count() . " homepage pages\n";

foreach ($pages as $page) {
    echo "- Page ID: {$page->id}, Store ID: {$page->store_id}, Slug: {$page->slug}\n";
    echo "  Sections: " . $page->sections->count() . "\n";
    
    $featuresSection = $page->sections->where('section_type', 'features')->first();
    if ($featuresSection) {
        echo "  Has features section (ID: {$featuresSection->id})\n";
        
        // Test the fix
        $service = app(\App\Services\Storefront\Runtime\StorefrontRuntimeService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('mapMarketingSections');
        $method->setAccessible(true);
        
        $storeProperty = $reflection->getProperty('store');
        $storeProperty->setAccessible(true);
        $storeProperty->setValue($service, $page->store);
        
        $sections = $method->invoke($service, $page, 'en');
        $mappedFeatures = collect($sections)->firstWhere('type', 'features');
        
        if ($mappedFeatures) {
            echo "\n  Mapped props:\n";
            echo "  " . json_encode($mappedFeatures['props'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        }
    }
}
