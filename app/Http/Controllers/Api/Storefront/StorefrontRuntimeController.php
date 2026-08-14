<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Storefront;

use App\Enums\Theme\TemplateTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\RuntimePageRequest;
use App\Http\Requests\Storefront\RuntimePreviewValidationRequest;
use App\Http\Requests\Storefront\RuntimeResolveRequest;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeSectionGroup;
use App\Models\Theme\ThemeTemplate;
use App\Services\Storefront\Runtime\StorefrontRuntimeService;

class StorefrontRuntimeController extends Controller
{
    public function __construct(
        private readonly StorefrontRuntimeService $runtimeService,
    ) {}

    public function resolve(RuntimeResolveRequest $request)
    {
        $start = microtime(true);
        $middlewareStart = request()->attributes->get('storefront_runtime_started_at', $start);
        
        \Log::info('[PERF] Controller resolve(): Start', [
            'ms_since_middleware' => round(($start - $middlewareStart) * 1000, 2),
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
        ]);

        request()->attributes->set('storefront_runtime_artifact', 'route');

        \Log::info('[PERF] Controller resolve(): Before resolveRoute()', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
        ]);

        $payload = $this->runtimeService->resolveRoute(
            path: (string) $request->validated('path'),
            locale: (string) ($request->validated('locale') ?: app()->getLocale()),
        );

        \Log::info('[PERF] Controller resolve(): After resolveRoute()', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
        ]);

        $response = response()->json($payload);

        \Log::info('[PERF] Controller resolve(): Total', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
            'ms_total_since_middleware' => round((microtime(true) - $middlewareStart) * 1000, 2),
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
        ]);

        return $response;
    }

    public function page(RuntimePageRequest $request, string $id)
    {
        $start = microtime(true);
        $middlewareStart = request()->attributes->get('storefront_runtime_started_at', $start);
        
        \Log::info('[PERF] Controller page(): Start', [
            'ms_since_middleware' => round(($start - $middlewareStart) * 1000, 2),
            'page_id' => $id,
        ]);

        request()->attributes->set('storefront_runtime_artifact', 'page');

        $payload = $this->runtimeService->pagePayload(
            pageId: $id,
            preview: (bool) $request->boolean('preview'),
        );

        \Log::info('[PERF] Controller page(): Total', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
            'ms_total_since_middleware' => round((microtime(true) - $middlewareStart) * 1000, 2),
        ]);

        return response()->json($payload);
    }

    public function navigation()
    {
        $start = microtime(true);
        $middlewareStart = request()->attributes->get('storefront_runtime_started_at', $start);
        
        \Log::info('[PERF] Controller navigation(): Start', [
            'ms_since_middleware' => round(($start - $middlewareStart) * 1000, 2),
        ]);

        request()->attributes->set('storefront_runtime_artifact', 'navigation');

        $payload = $this->runtimeService->navigationPayload();

        \Log::info('[PERF] Controller navigation(): Total', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
            'ms_total_since_middleware' => round((microtime(true) - $middlewareStart) * 1000, 2),
        ]);

        return response()->json($payload);
    }

    public function theme()
    {
        $start = microtime(true);
        $middlewareStart = request()->attributes->get('storefront_runtime_started_at', $start);
        
        \Log::info('[PERF] Controller theme(): Start', [
            'ms_since_middleware' => round(($start - $middlewareStart) * 1000, 2),
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
        ]);

        request()->attributes->set('storefront_runtime_artifact', 'theme');

        \Log::info('[PERF] Controller theme(): Before themePayload()', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
        ]);

        $payload = $this->runtimeService->themePayload();

        \Log::info('[PERF] Controller theme(): After themePayload()', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
            'payload_size_kb' => round(strlen(json_encode($payload)) / 1024, 2),
        ]);

        $response = response()->json($payload);

        \Log::info('[PERF] Controller theme(): After response()->json() - Total', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
            'ms_total_since_middleware' => round((microtime(true) - $middlewareStart) * 1000, 2),
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
        ]);

        return $response;
    }

    public function validatePreview(RuntimePreviewValidationRequest $request)
    {
        request()->attributes->set('storefront_runtime_artifact', 'preview');

        return response()->json($this->runtimeService->validatePreview(
            token: (string) $request->validated('token'),
            pageId: (string) $request->validated('pageId'),
            path: (string) $request->validated('path'),
            locale: (string) $request->validated('locale'),
        ));
    }

    public function systemTemplate(string $type)
    {
        $start = microtime(true);
        $middlewareStart = request()->attributes->get('storefront_runtime_started_at', $start);
        
        \Log::info('[PERF] Controller systemTemplate(): Start', [
            'ms_since_middleware' => round(($start - $middlewareStart) * 1000, 2),
            'template_type' => $type,
        ]);

        request()->attributes->set('storefront_runtime_artifact', 'template');

        $enumType = TemplateTypeEnum::tryFrom($type);
        if (!$enumType || $enumType->isSectionGroup()) {
            abort(404, "Unknown system template type: {$type}");
        }

        \Log::info('[PERF] Controller systemTemplate(): After enum validation', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
        ]);

        $store = app('currentStore');
        $theme = Theme::where('store_id', $store->id)->where('is_active', true)->first();

        \Log::info('[PERF] Controller systemTemplate(): After fetching theme', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
            'theme_id' => $theme?->id,
        ]);

        if (!$theme) {
            return response()->json(['data' => null]);
        }

        $template = ThemeTemplate::where('theme_id', $theme->id)
            ->where('type', $enumType->value)
            ->with('sections.blocks')
            ->orderBy('is_default', 'desc')
            ->first();

        \Log::info('[PERF] Controller systemTemplate(): After fetching template', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
            'template_id' => $template?->id,
            'sections_count' => $template?->sections->count() ?? 0,
        ]);

        if (!$template) {
            return response()->json(['data' => null]);
        }

        $sections = [];
        $sectionOrder = [];

        foreach ($template->sections as $section) {
            $key = $section->pivot->position . '_' . $section->id;
            $sectionOrder[] = $key;

            $blocks = [];
            foreach ($section->blocks->sortBy('position') as $block) {
                $blocks[] = [
                    'id' => (string) $block->id,
                    'type' => $block->type?->value ?? $block->type,
                    'name' => $block->name,
                    'is_enabled' => (bool) ($block->is_enabled ?? true),
                    'settings' => $this->runtimeService->resolveBlockImageUrls($block->settings ?? []),
                    'content' => $this->runtimeService->resolveBlockImageUrls($block->content ?? []),
                    'position' => (int) $block->position,
                ];
            }

            $sections[$key] = [
                'id' => (string) $section->id,
                'type' => $section->type?->value ?? $section->type,
                'settings' => $this->runtimeService->resolveBlockImageUrls($section->settings ?? []),
                'data' => json_decode($section->pivot->overrides ?? '{}', true) ?? [],
                'blocks' => $blocks,
                'enabled' => (bool) ($section->pivot->is_enabled ?? true),
            ];
        }

        \Log::info('[PERF] Controller systemTemplate(): After building sections', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
            'sections_count' => count($sections),
        ]);

        $response = response()->json([
            'data' => [
                'id' => $template->id,
                'type' => $template->type?->value,
                'handle' => $template->handle,
                'name' => $template->name,
                'sections' => $sections,
                'section_order' => $sectionOrder,
            ],
        ]);

        \Log::info('[PERF] Controller systemTemplate(): Total', [
            'ms_since_start' => round((microtime(true) - $start) * 1000, 2),
            'ms_total_since_middleware' => round((microtime(true) - $middlewareStart) * 1000, 2),
            'memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
        ]);

        return $response;
    }

    public function sectionGroups()
    {
        request()->attributes->set('storefront_runtime_artifact', 'theme');

        $store = app('currentStore');
        $theme = Theme::where('store_id', $store->id)->where('is_active', true)->first();

        if (!$theme) {
            return response()->json(['data' => ['header' => null, 'footer' => null]]);
        }

        $headerGroup = ThemeSectionGroup::where('theme_id', $theme->id)
            ->where('handle', 'header')
            ->first();

        $footerGroup = ThemeSectionGroup::where('theme_id', $theme->id)
            ->where('handle', 'footer')
            ->first();

        $formatGroup = fn(?ThemeSectionGroup $group) => $group ? [
            'handle' => $group->handle,
            'sections' => $group->sections,
        ] : null;

        return response()->json([
            'data' => [
                'header' => $formatGroup($headerGroup),
                'footer' => $formatGroup($footerGroup),
            ],
        ]);
    }
}
