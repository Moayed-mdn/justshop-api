<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$updated = 0;
$sections = DB::table('store_marketing_sections')->get();

foreach ($sections as $section) {
    $settings = json_decode($section->settings, true) ?? [];
    
    if (!isset($settings['color_scheme'])) {
        $settings['color_scheme'] = 'default';
        
        DB::table('store_marketing_sections')
            ->where('id', $section->id)
            ->update(['settings' => json_encode($settings)]);
        
        $updated++;
        echo "Updated section {$section->id} ({$section->identifier})\n";
    }
}

echo "\nTotal: Updated {$updated} sections with default color_scheme\n";
