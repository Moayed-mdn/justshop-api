<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Theme;

use App\Actions\Theme\CreateSectionAction;
use App\Actions\Theme\ReorderSectionsAction;
use App\Actions\Theme\UpdateSectionAction;
use App\DTOs\Theme\CreateSectionDTO;
use App\DTOs\Theme\UpdateSectionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\Theme\CreateSectionRequest;
use App\Http\Requests\Merchant\Theme\UpdateSectionRequest;
use App\Http\Resources\Theme\ThemeSectionResource;
use App\Models\Store;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeSection;
use App\Repositories\Theme\ThemeSectionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeSectionController extends Controller
{
    public function __construct(
        private ThemeSectionRepository $sectionRepository,
        private CreateSectionAction $createSectionAction,
        private UpdateSectionAction $updateSectionAction,
        private ReorderSectionsAction $reorderSectionsAction,
    ) {
    }

    /**
     * Get all sections for a theme
     */
    public function index(Store $store, Theme $theme): JsonResponse
    {
        $sections = $this->sectionRepository->getAllWithBlocksForTheme($theme->id);

        return $this->success(ThemeSectionResource::collection($sections));
    }

    /**
     * Get a single section
     */
    public function show(Store $store, Theme $theme, ThemeSection $section): JsonResponse
    {
        $section->load('blocks');

        return $this->success(new ThemeSectionResource($section));
    }

    /**
     * Create a new section
     */
    public function store(
        CreateSectionRequest $request,
        Store $store,
        Theme $theme,
    ): JsonResponse {
        $dto = CreateSectionDTO::fromArray(
            array_merge($request->validated(), ['theme_id' => $theme->id])
        );
        
        $section = $this->createSectionAction->execute($dto);
        $section->load('blocks');

        return $this->success(
            new ThemeSectionResource($section),
            __('theme.section_created'),
            201
        );
    }

    /**
     * Update a section
     */
    public function update(
        UpdateSectionRequest $request,
        Store $store,
        Theme $theme,
        ThemeSection $section,
    ): JsonResponse {
        $dto = UpdateSectionDTO::fromArray($request->validated());
        $section = $this->updateSectionAction->execute($section, $dto);
        $section->load('blocks');

        return $this->success(
            new ThemeSectionResource($section),
            __('theme.section_updated')
        );
    }

    /**
     * Delete a section
     */
    public function destroy(Store $store, Theme $theme, ThemeSection $section): JsonResponse
    {
        if (!$section->is_removable) {
            return response()->json([
                'status' => false,
                'message' => __('theme.section_not_removable'),
            ], 422);
        }

        $this->sectionRepository->delete($section);

        return $this->success(null, __('theme.section_deleted'));
    }

    /**
     * Reorder sections
     */
    public function reorder(Request $request, Store $store, Theme $theme): JsonResponse
    {
        $validated = $request->validate([
            'section_ids' => ['required', 'array'],
            'section_ids.*' => ['required', 'integer', 'exists:theme_sections,id'],
        ]);

        $this->reorderSectionsAction->execute($validated['section_ids']);

        return $this->success(null, __('theme.sections_reordered'));
    }
}
