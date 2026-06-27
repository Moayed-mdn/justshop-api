<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Store;

// Get the store first
$store = Store::where('slug', 'merchant-store')->first();
if (!$store) {
    echo "❌ Store not found!\n";
    exit(1);
}

// Set the resolved store in the service container
app()->instance('storefront.resolved_store', $store);

echo "Store: {$store->name} (ID: {$store->id})\n";
echo "Store Domain: {$store->primary_domain}\n\n";

$page = StoreMarketingPage::where('slug->en', 'home')->first();

if (!$page) {
    echo "❌ Home page not found!\n";
    exit(1);
}

echo "Page ID: {$page->id}\n";
echo "Page Title: " . json_encode($page->title) . "\n";
echo "Page has " . $page->sections()->count() . " sections\n\n";

// Show all section identifiers
echo "Section identifiers:\n";
foreach ($page->sections as $section) {
    $settings = is_array($section->settings) ? $section->settings : json_decode($section->settings, true);
    $colorScheme = $settings['color_scheme'] ?? 'NOT SET';
    echo "  - {$section->identifier} ({$section->section_type}) - color_scheme: {$colorScheme}\n";
}

echo "\n" . str_repeat('=', 80) . "\n\n";

// Now call the service
use App\Services\Storefront\Runtime\StorefrontRuntimeService;
$service = app(StorefrontRuntimeService::class);

echo "Calling StorefrontRuntimeService::pagePayload({$page->id})\n\n";

try {
    $result = $service->pagePayload((string)$page->id, false);
    
    echo "API Response Structure:\n";
    echo "  - success: " . ($result['success'] ? 'true' : 'false') . "\n";
    echo "  - has data: " . (isset($result['data']) ? 'yes' : 'no') . "\n";
    
    if (isset($result['data']['page'])) {
        echo "  - has page: yes\n";
        echo "  - page sections count: " . count($result['data']['page']['sections'] ?? []) . "\n\n";
        
        echo "Sections in API response:\n";
        foreach ($result['data']['page']['sections'] ?? [] as $section) {
            $colorScheme = $section['settings']['color_scheme'] ?? 'NOT SET';
            echo "  - {$section['identifier']} ({$section['type']}) - color_scheme: {$colorScheme}\n";
        }
    } else {
        echo "  - has page: NO!\n";
        echo "\nFull response:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
