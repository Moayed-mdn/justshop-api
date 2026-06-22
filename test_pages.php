<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Get all pages
$pages = App\Models\Cms\Marketing\Store\StoreMarketingPage::with('sections')->get();

echo "Total pages: " . $pages->count() . "\n\n";

foreach ($pages as $page) {
    echo "═══════════════════════════════════════════════\n";
    echo "Page ID: " . $page->id . "\n";
    echo "Title: " . json_encode($page->title) . "\n";
    echo "is_homepage: " . ($page->is_homepage ? 'true' : 'false') . "\n";
    echo "Sections: " . $page->sections->count() . "\n";
    
    foreach ($page->sections as $i => $section) {
        $valid = in_array($section->section_type, App\Enums\Cms\Marketing\MarketingSectionTypeEnum::values());
        $status = $valid ? '✓ VALID' : '✗ INVALID';
        echo "  [$i] type={$section->section_type}, identifier={$section->identifier} - {$status}\n";
    }
    echo "\n";
}

echo "\nValid section types:\n";
foreach (App\Enums\Cms\Marketing\MarketingSectionTypeEnum::values() as $type) {
    echo "  - $type\n";
}
