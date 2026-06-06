<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant\Theme;

use App\Actions\Theme\CreateBlockAction;
use App\Actions\Theme\ReorderBlocksAction;
use App\Actions\Theme\UpdateBlockAction;
use App\DTOs\Theme\CreateBlockDTO;
use App\DTOs\Theme\UpdateBlockDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\Theme\CreateBlockRequest;
use App\Http\Requests\Merchant\Theme\UpdateBlockRequest;
use App\Http\Resources\Theme\ThemeBlockResource;
use App\Models\Store;
use App\Models\Theme\Theme;
use App\Models\Theme\ThemeBlock;
use App\Models\Theme\ThemeSection;
use App\Repositories\Theme\ThemeBlockRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemeBlockController extends Controller
{
    public function __construct(
        private ThemeBlockRepository $blockRepository,
        private CreateBlockAction $createBlockAction,
        private UpdateBlockAction $updateBlockAction,
        private ReorderBlocksAction $reorderBlocksAction,
    ) {
    }

    /**
     * Get all blocks for a section
     */
    public function index(Store $store, Theme $theme, ThemeSection $section): JsonResponse
    {
        $blocks = $this->blockRepository->getAllForSection($section->id);

        return $this->success(ThemeBlockResource::collection($blocks));
    }

    /**
     * Get a single block
     */
    public function show(Store $store, Theme $theme, ThemeSection $section, ThemeBlock $block): JsonResponse
    {
        return $this->success(new ThemeBlockResource($block));
    }

    /**
     * Create a new block
     */
    public function store(
        CreateBlockRequest $request,
        Store $store,
        Theme $theme,
        ThemeSection $section,
    ): JsonResponse {
        $dto = CreateBlockDTO::fromArray(
            array_merge($request->validated(), ['section_id' => $section->id])
        );

        $block = $this->createBlockAction->execute($dto);

        return $this->success(
            new ThemeBlockResource($block),
            __('theme.block_created'),
            201
        );
    }

    /**
     * Update a block
     */
    public function update(
        UpdateBlockRequest $request,
        Store $store,
        Theme $theme,
        ThemeSection $section,
        ThemeBlock $block,
    ): JsonResponse {
        $dto = UpdateBlockDTO::fromArray($request->validated());
        $block = $this->updateBlockAction->execute($block, $dto);

        return $this->success(
            new ThemeBlockResource($block),
            __('theme.block_updated')
        );
    }

    /**
     * Delete a block
     */
    public function destroy(Store $store, Theme $theme, ThemeSection $section, ThemeBlock $block): JsonResponse
    {
        if (!$block->is_removable) {
            return response()->json([
                'status' => false,
                'message' => __('theme.block_not_removable'),
            ], 422);
        }

        $this->blockRepository->delete($block);

        return $this->success(null, __('theme.block_deleted'));
    }

    /**
     * Reorder blocks
     */
    public function reorder(Request $request, Store $store, Theme $theme, ThemeSection $section): JsonResponse
    {
        $validated = $request->validate([
            'block_ids' => ['required', 'array'],
            'block_ids.*' => ['required', 'integer', 'exists:theme_blocks,id'],
        ]);

        $this->reorderBlocksAction->execute($validated['block_ids']);

        return $this->success(null, __('theme.blocks_reordered'));
    }
}
