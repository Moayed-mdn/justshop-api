<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$sections = DB::table('store_marketing_sections')
    ->select('id', 'identifier', 'section_type', 'settings')
    ->limit(5)
    ->get();

echo "Found " . $sections->count() . " sections (showing first 5):\n\n";

foreach ($sections as $section) {
    echo "ID: {$section->id}\n";
    echo "Identifier: {$section->identifier}\n";
    echo "Type: {$section->section_type}\n";
    
    $settings = json_decode($section->settings, true);
    echo "Settings: " . json_encode($settings, JSON_PRETTY_PRINT) . "\n";
    echo "Has color_scheme: " . (isset($settings['color_scheme']) ? 'YES (' . $settings['color_scheme'] . ')' : 'NO') . "\n";
    echo str_repeat('-', 80) . "\n";
}
