<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Theme;

use App\Http\Controllers\Controller;
use App\Http\Resources\Theme\ThemeSectionGroupResource;
use App\Models\Store;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeSectionGroup;
use App\Policies\ThemePolicy;
use App\Traits\ApiResponserTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeSectionGroupController extends Controller
{
    use ApiResponserTrait;

    public function index(Store $store, Theme $theme): JsonResponse
    {
        $this->authorize('viewAny', [ThemePolicy::class, $store]);

        $groups = $theme->sectionGroups()->orderBy('handle')->get();

        return $this->success(ThemeSectionGroupResource::collection($groups));
    }

    public function store(Request $request, Store $store, Theme $theme): JsonResponse
    {
        $this->authorize('create', [ThemePolicy::class, $store]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'handle' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-_]+$/'],
            'sections' => ['sometimes', 'array'],
            'order' => ['sometimes', 'array'],
        ]);

        $group = $theme->sectionGroups()->create($validated);

        return $this->success(new ThemeSectionGroupResource($group), 'success', 201);
    }

    public function show(Store $store, Theme $theme, ThemeSectionGroup $sectionGroup): JsonResponse
    {
        $this->authorize('view', [ThemePolicy::class, $store]);

        return $this->success(new ThemeSectionGroupResource($sectionGroup));
    }

    public function update(Request $request, Store $store, Theme $theme, ThemeSectionGroup $sectionGroup): JsonResponse
    {
        $this->authorize('update', [ThemePolicy::class, $store]);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'sections' => ['sometimes', 'array'],
            'order' => ['sometimes', 'array'],
        ]);

        $sectionGroup->update($validated);

        return $this->success(new ThemeSectionGroupResource($sectionGroup->fresh()));
    }

    public function destroy(Store $store, Theme $theme, ThemeSectionGroup $sectionGroup): JsonResponse
    {
        $this->authorize('delete', [ThemePolicy::class, $store]);

        $sectionGroup->delete();

        return $this->success(null, 'success', 204);
    }
}
