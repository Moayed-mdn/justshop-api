<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Theme;

use App\Http\Controllers\Controller;
use App\Http\Resources\Theme\ThemeBlockInstanceResource;
use App\Models\Store;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeBlockInstance;
use App\Models\Theme\ThemeSection;
use App\Policies\ThemePolicy;
use App\Traits\ApiResponserTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeBlockInstanceController extends Controller
{
    use ApiResponserTrait;

    public function index(Store $store, Theme $theme, ThemeSection $section): JsonResponse
    {
        $this->authorize('viewAny', [ThemePolicy::class, $store]);

        $blocks = $section->blockInstances()->enabled()->get();

        return $this->success(ThemeBlockInstanceResource::collection($blocks));
    }

    public function store(Request $request, Store $store, Theme $theme, ThemeSection $section): JsonResponse
    {
        $this->authorize('create', [ThemePolicy::class, $store]);

        $validated = $request->validate([
            'type' => ['required', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
            'content' => ['nullable', 'array'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ]);

        $maxPosition = $section->blockInstances()->max('position') ?? -1;
        $validated['position'] ??= $maxPosition + 1;

        $block = $section->blockInstances()->create($validated);

        return $this->success(new ThemeBlockInstanceResource($block), 'success', 201);
    }

    public function show(Store $store, Theme $theme, ThemeSection $section, ThemeBlockInstance $blockInstance): JsonResponse
    {
        $this->authorize('view', [ThemePolicy::class, $store]);

        return $this->success(new ThemeBlockInstanceResource($blockInstance));
    }

    public function update(Request $request, Store $store, Theme $theme, ThemeSection $section, ThemeBlockInstance $blockInstance): JsonResponse
    {
        $this->authorize('update', [ThemePolicy::class, $store]);

        $validated = $request->validate([
            'type' => ['sometimes', 'string'],
            'name' => ['nullable', 'string', 'max:255'],
            'settings' => ['sometimes', 'array'],
            'content' => ['nullable', 'array'],
            'position' => ['sometimes', 'integer', 'min:0'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        $blockInstance->update($validated);

        return $this->success(new ThemeBlockInstanceResource($blockInstance->fresh()));
    }

    public function destroy(Store $store, Theme $theme, ThemeSection $section, ThemeBlockInstance $blockInstance): JsonResponse
    {
        $this->authorize('delete', [ThemePolicy::class, $store]);

        $blockInstance->delete();

        return $this->success(null, 'success', 204);
    }

    public function reorder(Request $request, Store $store, Theme $theme, ThemeSection $section): JsonResponse
    {
        $this->authorize('update', [ThemePolicy::class, $store]);

        $validated = $request->validate([
            'block_ids' => ['required', 'array'],
            'block_ids.*' => ['required', 'integer', 'exists:theme_block_instances,id'],
        ]);

        foreach ($validated['block_ids'] as $position => $blockId) {
            ThemeBlockInstance::where('id', $blockId)
                ->where('container_type', $section->getMorphClass())
                ->where('container_id', $section->id)
                ->update(['position' => $position]);
        }

        return $this->success(null, 'success');
    }
}
