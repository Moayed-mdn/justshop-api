<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Cleaning up features section content...\n\n";

$sections = \App\Models\Cms\Marketing\Store\StoreMarketingSection::where('section_type', 'features')->get();

echo "Found {$sections->count()} features sections\n\n";

foreach ($sections as $section) {
    echo "Section ID: {$section->id} (Page ID: {$section->page_id})\n";
    
    $content = $section->content;
    $modified = false;
    
    if (is_array($content) && isset($content['items'])) {
        echo "  - Has 'items' key, removing it...\n";
        unset($content['items']);
        $section->content = $content;
        $modified = true;
    }
    
    if ($modified) {
        $section->save();
        echo "  ✓ Cleaned and saved\n";
    } else {
        echo "  - Already clean\n";
    }
    
    echo "\n";
}

echo "Cleanup complete!\n";
