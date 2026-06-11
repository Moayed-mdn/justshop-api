<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Theme;

use App\Actions\Theme\CreateThemeAction;
use App\Actions\Theme\DuplicateThemeAction;
use App\Actions\Theme\PublishThemeAction;
use App\Actions\Theme\UpdateThemeAction;
use App\DTOs\Theme\CreateThemeDTO;
use App\DTOs\Theme\UpdateThemeDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\Theme\CreateThemeRequest;
use App\Http\Requests\Merchant\Theme\UpdateThemeRequest;
use App\Http\Resources\Theme\ThemeResource;
use App\Models\Store;
use App\Models\Theme\Theme;
use App\Repositories\Theme\ThemeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeController extends Controller
{
    public function __construct(
        private ThemeRepository $themeRepository,
        private CreateThemeAction $createThemeAction,
        private UpdateThemeAction $updateThemeAction,
        private PublishThemeAction $publishThemeAction,
        private DuplicateThemeAction $duplicateThemeAction,
    ) {
    }

    /**
     * Get all themes for a store
     */
    public function index(Store $store, Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        
        $themes = Theme::where('store_id', $store->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginated(
            $themes,
            ThemeResource::collection($themes->items())->resolve()
        );
    }

    /**
     * Get a single theme
     */
    public function show(Store $store, Theme $theme): JsonResponse
    {
        $theme->load(['sections.blocks', 'templates']);

        return $this->success(new ThemeResource($theme));
    }

    /**
     * Create a new theme
     */
    public function store(
        CreateThemeRequest $request,
        Store $store,
    ): JsonResponse {
        $dto = CreateThemeDTO::fromArray(
            array_merge($request->validated(), ['store_id' => $store->id])
        );
        
        $theme = $this->createThemeAction->execute($dto);

        return $this->success(
            new ThemeResource($theme),
            __('theme.created_successfully'),
            201
        );
    }

    /**
     * Update a theme
     */
    public function update(
        UpdateThemeRequest $request,
        Store $store,
        Theme $theme,
    ): JsonResponse {
        $dto = UpdateThemeDTO::fromArray($request->validated());
        $theme = $this->updateThemeAction->execute($theme, $dto);

        return $this->success(
            new ThemeResource($theme),
            __('theme.updated_successfully')
        );
    }

    /**
     * Delete a theme
     */
    public function destroy(Store $store, Theme $theme): JsonResponse
    {
        if ($theme->is_active) {
            return response()->json([
                'status' => false,
                'message' => __('theme.cannot_delete_active'),
            ], 422);
        }

        $this->themeRepository->delete($theme);

        return $this->success(null, __('theme.deleted_successfully'));
    }

    /**
     * Publish a theme
     */
    public function publish(Store $store, Theme $theme): JsonResponse
    {
        $theme = $this->publishThemeAction->execute($theme);

        return $this->success(
            new ThemeResource($theme),
            __('theme.published_successfully')
        );
    }

    /**
     * Duplicate a theme
     */
    public function duplicate(
        Request $request,
        Store $store,
        Theme $theme,
    ): JsonResponse {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $newTheme = $this->duplicateThemeAction->execute(
            $theme,
            $validated['name'] ?? null
        );

        return $this->success(
            new ThemeResource($newTheme),
            __('theme.duplicated_successfully'),
            201
        );
    }
}
