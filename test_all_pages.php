<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

$pages = App\Models\Cms\Marketing\Store\StoreMarketingPage::with('sections')->get();

$rules = [
    'sections' => ['sometimes', 'nullable', 'array'],
    'sections.*' => ['array'],
    'sections.*.section_type' => [
        'nullable',
        'string',
        Rule::in(App\Enums\Cms\Marketing\MarketingSectionTypeEnum::values()),
        'required_without:sections.*.type',
    ],
];

echo "Testing all pages for validation errors...\n\n";

$allPassed = true;

foreach ($pages as $page) {
    $payload = [
        'title' => $page->title,
        'slug' => $page->slug,
        'excerpt' => $page->excerpt,
        'template' => $page->template instanceof \BackedEnum ? $page->template->value : $page->template,
        'status' => $page->status instanceof \BackedEnum ? $page->status->value : $page->status,
        'published_at' => $page->published_at?->toIso8601String(),
        'sort_order' => $page->sort_order,
        'is_homepage' => $page->is_homepage,
        'seo' => $page->seo,
        'sections' => $page->sections->map(fn($s) => [
            'section_type' => $s->section_type,
            'identifier' => $s->identifier,
            'title' => $s->title,
            'subtitle' => $s->subtitle,
            'content' => $s->content,
            'settings' => $s->settings,
            'is_active' => $s->is_active,
        ])->toArray(),
    ];

    // Create the full request and validate
    $request = new \App\Http\Requests\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageRequest();
    $request->setContainer(app());
    $request->setMethod('PUT');
    $request->replace($payload);
    
    // Mock the route parameters
    $request->setRouteResolver(function() use ($page) {
        $route = new \Illuminate\Routing\Route('PUT', 'test', []);
        $route->parameters = ['store' => $page->store, 'id' => $page->id];
        return $route;
    });
    
    // Mock the user
    $request->setUserResolver(function() {
        return App\Models\User::first();
    });

    $validator = Validator::make($payload, $request->rules());
    $request->withValidator($validator);
    
    $titleEn = $page->title['en'] ?? 'Page ' . $page->id;
    
    if ($validator->fails()) {
        echo "❌ Page {$page->id} ({$titleEn}): FAILED\n";
        foreach ($validator->errors()->all() as $error) {
            echo "   - $error\n";
        }
        echo "\n";
        $allPassed = false;
    } else {
        echo "✅ Page {$page->id} ({$titleEn}): PASSED\n";
    }
}

echo "\n" . str_repeat("═", 60) . "\n";
if ($allPassed) {
    echo "✅ ALL PAGES PASSED VALIDATION!\n";
} else {
    echo "❌ SOME PAGES FAILED VALIDATION\n";
}
