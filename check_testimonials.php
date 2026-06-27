<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$section = DB::table('store_marketing_sections')
    ->where('identifier', 'testimonials_home')
    ->first();

if ($section) {
    echo "Found testimonials_home section:\n";
    echo "ID: {$section->id}\n";
    echo "Type: {$section->section_type}\n";
    
    $settings = json_decode($section->settings, true);
    echo "\nSettings:\n" . json_encode($settings, JSON_PRETTY_PRINT) . "\n";
    echo "\nHas color_scheme: " . (isset($settings['color_scheme']) ? 'YES (' . $settings['color_scheme'] . ')' : 'NO') . "\n";
} else {
    echo "testimonials_home section NOT FOUND\n";
}

// Also check all sections to see which ones are missing color_scheme
echo "\n" . str_repeat('=', 80) . "\n";
echo "Checking ALL sections for missing color_scheme:\n\n";

$allSections = DB::table('store_marketing_sections')->get();
$missingCount = 0;

foreach ($allSections as $s) {
    $settings = json_decode($s->settings, true);
    if (!isset($settings['color_scheme'])) {
        echo "MISSING: ID {$s->id} - {$s->identifier} ({$s->section_type})\n";
        $missingCount++;
    }
}

echo "\nTotal sections missing color_scheme: {$missingCount} / " . $allSections->count() . "\n";
