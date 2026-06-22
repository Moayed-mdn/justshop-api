<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Http\Requests\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

// Get page 2
$page = App\Models\Cms\Marketing\Store\StoreMarketingPage::with('sections')->find(2);

echo "Testing validation for Page {$page->id}: " . json_encode($page->title) . "\n\n";

// Build the payload exactly as the frontend would send it
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
        'sort_order' => $s->sort_order,
        'title' => $s->title,
        'subtitle' => $s->subtitle,
        'content' => $s->content,
        'settings' => $s->settings,
        'is_active' => $s->is_active,
    ])->toArray(),
];

echo "Payload sections:\n";
foreach ($payload['sections'] as $i => $section) {
    echo "  [$i] section_type = " . $section['section_type'] . "\n";
}
echo "\n";

// Validate using the same rules as the UpdateRequest
$rules = [
    'title' => ['required', 'array'],
    'title.*' => ['required', 'string', 'max:255'],
    'slug' => ['required', 'array'],
    'slug.*' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
    'excerpt' => ['sometimes', 'nullable', 'array'],
    'excerpt.*' => ['sometimes', 'nullable', 'string', 'max:500'],
    'content' => ['sometimes', 'nullable', 'array'],
    'status' => ['required', 'string', Rule::in(App\Enums\Cms\Marketing\MarketingPageStatusEnum::values())],
    'published_at' => ['sometimes', 'nullable', 'date'],
    'template' => ['sometimes', 'nullable', 'string', Rule::in(App\Enums\Cms\Marketing\MarketingPageTemplateEnum::storeTemplates())],
    'sort_order' => ['sometimes', 'integer', 'min:0'],
    'is_homepage' => ['sometimes', 'boolean'],
    'seo' => ['sometimes', 'nullable', 'array'],
    'seo.meta_title' => ['sometimes', 'nullable', 'array'],
    'seo.meta_title.*' => ['sometimes', 'nullable', 'string', 'max:255'],
    'seo.meta_description' => ['sometimes', 'nullable', 'array'],
    'seo.meta_description.*' => ['sometimes', 'nullable', 'string', 'max:500'],
    'seo.canonical_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
    'seo.robots' => ['sometimes', 'nullable', 'string', 'max:100'],
    'seo.og_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
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
    'sections.*.identifier' => ['sometimes', 'nullable', 'string', 'max:100'],
    'sections.*.sort_order' => ['sometimes', 'integer', 'min:0'],
    'sections.*.title' => ['sometimes', 'nullable', 'array'],
    'sections.*.title.*' => ['sometimes', 'nullable', 'string', 'max:255'],
    'sections.*.subtitle' => ['sometimes', 'nullable', 'array'],
    'sections.*.subtitle.*' => ['sometimes', 'nullable', 'string'],
    'sections.*.content' => ['sometimes', 'nullable', 'array'],
    'sections.*.settings' => ['sometimes', 'nullable', 'array'],
    'sections.*.is_active' => ['sometimes', 'boolean'],
];

$validator = Validator::make($payload, $rules);

if ($validator->fails()) {
    echo "❌ VALIDATION FAILED:\n\n";
    foreach ($validator->errors()->all() as $error) {
        echo "  - $error\n";
    }
    echo "\n";
    echo "Detailed errors:\n";
    print_r($validator->errors()->toArray());
} else {
    echo "✅ VALIDATION PASSED!\n";
}
