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
use App\Policies\ThemePolicy;
use App\Repositories\Theme\ThemeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

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
        $this->authorize('view', [ThemePolicy::class, $store]);

        $perPage = (int) $request->input('per_page', 15);
        $status = $request->input('status');
        
        $query = Theme::where('store_id', $store->id);

        // Filter by status (published = both is_published AND is_active)
        if ($status === 'published') {
            $query->where('is_published', true)
                  ->where('is_active', true);
        } elseif ($status === 'draft') {
            $query->where(function ($q) {
                $q->where('is_published', false)
                  ->orWhere('is_active', false);
            });
        }
        
        $themes = $query->orderBy('created_at', 'desc')
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
        $this->authorize('view', [ThemePolicy::class, $store]);

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
        $this->authorize('create', [ThemePolicy::class, $store]);

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
        $this->authorize('update', [ThemePolicy::class, $store]);

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
        $this->authorize('delete', [ThemePolicy::class, $store]);

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
        $this->authorize('publish', [ThemePolicy::class, $store]);

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
        $this->authorize('create', [ThemePolicy::class, $store]);

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

    /**
     * Update theme settings (including button configuration)
     */
    public function updateSettings(
        Request $request,
        Store $store,
        Theme $theme,
    ): JsonResponse {
        $this->authorize('update', [ThemePolicy::class, $store]);

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.colors' => ['sometimes', 'array'],
            'settings.colors.primary' => ['sometimes', 'string'],
            'settings.colors.secondary' => ['sometimes', 'string'],
            'settings.colors.accent' => ['sometimes', 'string'],
            'settings.colors.background' => ['sometimes', 'string'],
            'settings.colors.text' => ['sometimes', 'string'],
            'settings.colors.textMuted' => ['sometimes', 'string'],
            'settings.colors.border' => ['sometimes', 'string'],
            'settings.colors.success' => ['sometimes', 'string'],
            'settings.colors.error' => ['sometimes', 'string'],
            'settings.colors.warning' => ['sometimes', 'string'],
            'settings.fonts' => ['sometimes', 'array'],
            'settings.fonts.heading' => ['sometimes', 'string'],
            'settings.fonts.body' => ['sometimes', 'string'],
            'settings.typography' => ['sometimes', 'array'],
            'settings.typography.headingFont' => ['sometimes', 'string'],
            'settings.typography.bodyFont' => ['sometimes', 'string'],
            'settings.typography.headingWeight' => ['sometimes', 'string', 'in:normal,medium,semibold,bold'],
            'settings.typography.bodyWeight' => ['sometimes', 'string', 'in:normal,medium,semibold,bold'],
            'settings.typography.baseFontSize' => ['sometimes', 'string', 'in:sm,base,lg'],
            'settings.typography.lineHeight' => ['sometimes', 'string', 'in:tight,normal,relaxed'],
            'settings.typography.letterSpacing' => ['sometimes', 'string', 'in:tight,normal,wide'],
            'settings.radius' => ['sometimes', 'string', 'in:none,sm,md,lg,xl'],
            'settings.direction' => ['sometimes', 'string', 'in:ltr,rtl'],
            'settings.tagline' => ['sometimes', 'string'],
            'settings.buttons' => ['sometimes', 'array'],
            'settings.buttons.primary' => ['sometimes', 'array'],
            'settings.buttons.secondary' => ['sometimes', 'array'],
            'settings.buttons.outline' => ['sometimes', 'array'],
            'settings.color_schemes' => ['sometimes', 'array'],
            'settings.color_schemes.*' => ['sometimes', 'array'],
            'settings.color_schemes.*.name' => ['sometimes', 'string'],
            'settings.color_schemes.*.background' => ['sometimes', 'string'],
            'settings.color_schemes.*.text' => ['sometimes', 'string'],
            'settings.color_schemes.*.button_background' => ['sometimes', 'string'],
            'settings.color_schemes.*.button_text' => ['sometimes', 'string'],
            'settings.color_schemes.*.secondary_background' => ['sometimes', 'string'],
            'settings.color_schemes.*.border' => ['sometimes', 'string'],
        ]);

        // Merge existing settings with new settings
        // Special handling: color_schemes should be replaced, not merged
        $currentSettings = $theme->settings ?? [];
        
        // If color_schemes is in the request, replace it entirely (don't merge)
        if (isset($validated['settings']['color_schemes'])) {
            $currentSettings['color_schemes'] = $validated['settings']['color_schemes'];
            unset($validated['settings']['color_schemes']);
        }
        
        // Merge the rest of the settings
        $newSettings = $this->mergeSettings($currentSettings, $validated['settings']);

        // Update the theme
        $theme->update(['settings' => $newSettings]);

        // Clear cache for this store's theme
        Cache::forget("storefront:theme:store:{$store->id}");

        return $this->success(
            new ThemeResource($theme->fresh()),
            __('theme.settings_updated_successfully')
        );
    }

    /**
     * Merge settings arrays without converting scalar values to arrays
     */
    private function mergeSettings(array $current, array $new): array
    {
        foreach ($new as $key => $value) {
            if (is_array($value) && isset($current[$key]) && is_array($current[$key])) {
                // If both are arrays, recursively merge
                // But check if it's an associative array (settings) or indexed array (should be replaced)
                if (array_keys($value) !== range(0, count($value) - 1)) {
                    // Associative array - merge recursively
                    $current[$key] = $this->mergeSettings($current[$key], $value);
                } else {
                    // Indexed array - replace entirely
                    $current[$key] = $value;
                }
            } else {
                // Replace the value (don't merge scalars into arrays)
                $current[$key] = $value;
            }
        }
        
        return $current;
    }
}
