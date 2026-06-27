<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Update testimonials section to use 'brand' color scheme
$section = DB::table('store_marketing_sections')
    ->where('identifier', 'testimonials_home')
    ->first();

if ($section) {
    $settings = json_decode($section->settings, true);
    $settings['color_scheme'] = 'brand';  // Change from 'default' to 'brand'
    
    DB::table('store_marketing_sections')
        ->where('id', $section->id)
        ->update(['settings' => json_encode($settings)]);
    
    echo "✓ Updated testimonials_home to use 'brand' color scheme\n";
    echo "  Background will now be blue (#3B82F6) instead of white\n";
} else {
    echo "✗ testimonials_home section not found\n";
}
