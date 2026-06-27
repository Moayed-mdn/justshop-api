<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Update testimonials section to use dark color scheme
$updated = DB::table('store_marketing_sections')
    ->where('identifier', 'testimonials_home')
    ->update([
        'settings->color_scheme' => 'dark',
        'updated_at' => now()
    ]);

echo "Updated {$updated} testimonials section(s) to use 'dark' color scheme\n";

// Verify the change
$section = DB::table('store_marketing_sections')
    ->where('identifier', 'testimonials_home')
    ->first();

if ($section) {
    $settings = json_decode($section->settings, true);
    echo "\nCurrent color_scheme: " . ($settings['color_scheme'] ?? 'NOT SET') . "\n";
    echo "\nFull settings:\n";
    echo json_encode($settings, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "\n❌ Section not found!\n";
}
