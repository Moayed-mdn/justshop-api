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
        request()->attributes->set('storefront_runtime_artifact', 'route');

        return response()->json($this->runtimeService->resolveRoute(
            path: (string) $request->validated('path'),
            locale: (string) ($request->validated('locale') ?: app()->getLocale()),
        ));
    }

    public function page(RuntimePageRequest $request, string $id)
    {
        request()->attributes->set('storefront_runtime_artifact', 'page');

        return response()->json($this->runtimeService->pagePayload(
            pageId: $id,
            preview: (bool) $request->boolean('preview'),
        ));
    }

    public function navigation()
    {
        request()->attributes->set('storefront_runtime_artifact', 'navigation');

        return response()->json($this->runtimeService->navigationPayload());
    }

    public function theme()
    {
        request()->attributes->set('storefront_runtime_artifact', 'theme');

        return response()->json($this->runtimeService->themePayload());
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
        request()->attributes->set('storefront_runtime_artifact', 'template');

        $enumType = TemplateTypeEnum::tryFrom($type);
        if (!$enumType || $enumType->isSectionGroup()) {
            abort(404, "Unknown system template type: {$type}");
        }

        $store = app('currentStore');
        $theme = Theme::where('store_id', $store->id)->where('is_active', true)->first();

        if (!$theme) {
            return response()->json(['data' => null]);
        }

        $template = ThemeTemplate::where('theme_id', $theme->id)
            ->where('type', $enumType->value)
            ->with('sections.blocks')
            ->orderBy('is_default', 'desc')
            ->first();

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

        return response()->json([
            'data' => [
                'id' => $template->id,
                'type' => $template->type?->value,
                'handle' => $template->handle,
                'name' => $template->name,
                'sections' => $sections,
                'section_order' => $sectionOrder,
            ],
        ]);
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
