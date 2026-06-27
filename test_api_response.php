<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Simulate an API request to get the home page with sections
$page = DB::table('store_marketing_pages')
    ->where('slug->en', 'home')
    ->first();

if (!$page) {
    echo "❌ Home page not found!\n";
    exit(1);
}

$sections = DB::table('store_marketing_sections')
    ->where('store_marketing_page_id', $page->id)
    ->where('identifier', 'testimonials_home')
    ->get();

foreach ($sections as $section) {
    $settings = json_decode($section->settings, true);
    
    echo "Section: {$section->identifier}\n";
    echo "Type: {$section->section_type}\n";
    echo "Color Scheme: " . ($settings['color_scheme'] ?? 'NOT SET') . "\n";
    echo "\nFull Settings:\n";
    echo json_encode($settings, JSON_PRETTY_PRINT) . "\n";
    echo "\n" . str_repeat('=', 80) . "\n";
}

// Also check what the cache driver is
echo "\nCurrent Cache Driver: " . config('cache.default') . "\n";
echo "Cache Stores Available: " . implode(', ', array_keys(config('cache.stores'))) . "\n";
