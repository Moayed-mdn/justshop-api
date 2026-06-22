<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Http\Resources\Admin\Cms\Marketing\Store\AdminStoreMarketingPageResource;
use Illuminate\Http\Request;

echo "═══════════════════════════════════════════════════════════\n";
echo "SIMULATING: GET /api/v1/merchant/stores/1/cms/pages/1\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Step 1: Load page like the frontend would
$page = App\Models\Cms\Marketing\Store\StoreMarketingPage::with(['sections'])->find(1);

if (!$page) {
    echo "❌ Page not found!\n";
    exit(1);
}

// Step 2: Format as Resource (what API returns)
$request = Request::create('/api/v1/merchant/stores/1/cms/pages/1', 'GET');
$resource = new AdminStoreMarketingPageResource($page);
$apiResponse = $resource->toArray($request);

echo "API Response sections:\n";
foreach ($apiResponse['sections'] as $i => $section) {
    $hasType = isset($section['section_type']);
    $value = $hasType ? $section['section_type'] : 'MISSING';
    echo "  [$i] section_type = '$value'\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "SIMULATING: Frontend mapper (section_type → type)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Step 3: Simulate frontend mapper
$frontendSections = array_map(function($s) {
    return [
        'type' => $s['section_type'] ?? '',
        'identifier' => $s['identifier'],
        'title' => $s['title'],
        'subtitle' => $s['subtitle'],
        'content' => $s['content'],
        'settings' => $s['settings'],
        'is_active' => $s['is_active'],
    ];
}, $apiResponse['sections']->toArray($request));

echo "Frontend sections (after mapper):\n";
foreach ($frontendSections as $i => $section) {
    echo "  [$i] type = '{$section['type']}'\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "SIMULATING: Frontend submits PUT request (type → section_type)\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Step 4: Simulate frontend transform back
$backendPayload = [
    'title' => $apiResponse['title'],
    'slug' => $apiResponse['slug'],
    'excerpt' => $apiResponse['excerpt'],
    'template' => $apiResponse['template'],
    'status' => $apiResponse['status'],
    'published_at' => $apiResponse['published_at'],
    'sort_order' => $apiResponse['sort_order'],
    'is_homepage' => $apiResponse['is_homepage'],
    'seo' => $apiResponse['seo'],
    'sections' => array_map(function($s) {
        return [
            'section_type' => $s['type'],  // Convert back
            'identifier' => $s['identifier'],
            'title' => $s['title'],
            'subtitle' => $s['subtitle'],
            'content' => $s['content'],
            'settings' => $s['settings'],
            'is_active' => $s['is_active'],
        ];
    }, $frontendSections),
];

echo "PUT payload sections:\n";
foreach ($backendPayload['sections'] as $i => $section) {
    echo "  [$i] section_type = '{$section['section_type']}'\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "SIMULATING: Backend validation\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Step 5: Validate
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

$rules = [
    'sections' => ['sometimes', 'nullable', 'array'],
    'sections.*' => ['array'],
    'sections.*.section_type' => [
        'nullable',
        'string',
        Rule::in(App\Enums\Cms\Marketing\MarketingSectionTypeEnum::values()),
        'required_without:sections.*.type',
    ],
    'sections.*.type' => [
        'nullable',
        'string',
        Rule::in(App\Enums\Cms\Marketing\MarketingSectionTypeEnum::values()),
        'required_without:sections.*.section_type',
    ],
];

$validator = Validator::make($backendPayload, $rules);

if ($validator->fails()) {
    echo "❌ VALIDATION FAILED!\n\n";
    foreach ($validator->errors()->all() as $error) {
        echo "  • $error\n";
    }
} else {
    echo "✅ VALIDATION PASSED!\n";
}

echo "\n═══════════════════════════════════════════════════════════\n";
echo "RESULT: Full cycle complete\n";
echo "═══════════════════════════════════════════════════════════\n";
