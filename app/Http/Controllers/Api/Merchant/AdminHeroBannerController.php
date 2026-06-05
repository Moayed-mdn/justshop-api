<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Admin\HeroBanner\CreateHeroBannerAction;
use App\Actions\Admin\HeroBanner\DeleteHeroBannerAction;
use App\Actions\Admin\HeroBanner\ListHeroBannersAction;
use App\Actions\Admin\HeroBanner\RestoreHeroBannerAction;
use App\Actions\Admin\HeroBanner\ShowHeroBannerAction;
use App\Actions\Admin\HeroBanner\UpdateHeroBannerAction;
use App\DTOs\Admin\HeroBanner\CreateHeroBannerDTO;
use App\DTOs\Admin\HeroBanner\DeleteHeroBannerDTO;
use App\DTOs\Admin\HeroBanner\ListHeroBannersDTO;
use App\DTOs\Admin\HeroBanner\RestoreHeroBannerDTO;
use App\DTOs\Admin\HeroBanner\ShowHeroBannerDTO;
use App\DTOs\Admin\HeroBanner\UpdateHeroBannerDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HeroBanner\CreateHeroBannerRequest;
use App\Http\Requests\Admin\HeroBanner\UpdateHeroBannerRequest;
use App\Http\Resources\Admin\AdminHeroBannerResource;
use App\Models\HeroBanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminHeroBannerController extends Controller
{
    public function __construct(
        private ListHeroBannersAction $listAction,
        private ShowHeroBannerAction $showAction,
        private CreateHeroBannerAction $createAction,
        private UpdateHeroBannerAction $updateAction,
        private DeleteHeroBannerAction $deleteAction,
        private RestoreHeroBannerAction $restoreAction,
    ) {}

    /**
     * List hero banners for a store.
     */
    public function index(Request $request, int $store): JsonResponse
    {
        $this->authorize('viewAny', [HeroBanner::class, $this->currentStore()]);

        $banners = $this->listAction->execute(
            dto: ListHeroBannersDTO::fromRequest($request, $store),
        );

        return $this->success(
            data: AdminHeroBannerResource::collection($banners),
        );
    }

    /**
     * Show a specific hero banner.
     */
    public function show(int $store, int $id): JsonResponse
    {
        $banner = $this->showAction->execute(
            dto: new ShowHeroBannerDTO(storeId: $store, bannerId: $id),
        );

        $this->authorize('view', [$banner, $this->currentStore()]);

        return $this->success(
            data: new AdminHeroBannerResource($banner),
        );
    }

    /**
     * Create a new hero banner.
     */
    public function store(CreateHeroBannerRequest $request, int $store): JsonResponse
    {
        $this->authorize('create', [HeroBanner::class, $this->currentStore()]);

        $banner = $this->createAction->execute(
            dto: CreateHeroBannerDTO::fromRequest($request, $store),
        );

        return $this->success(
            data: new AdminHeroBannerResource($banner),
            message: 'Hero banner created successfully',
            statusCode: 201,
        );
    }

    /**
     * Update a hero banner.
     */
    public function update(UpdateHeroBannerRequest $request, int $store, int $id): JsonResponse
    {
        $banner = $this->showAction->execute(
            dto: new ShowHeroBannerDTO(storeId: $store, bannerId: $id),
        );

        $this->authorize('update', [$banner, $this->currentStore()]);

        $updatedBanner = $this->updateAction->execute(
            dto: UpdateHeroBannerDTO::fromRequest($request, $store, $id),
        );

        return $this->success(
            data: new AdminHeroBannerResource($updatedBanner),
            message: 'Hero banner updated successfully',
        );
    }

    /**
     * Delete a hero banner (soft delete).
     */
    public function destroy(int $store, int $id): JsonResponse
    {
        $banner = $this->showAction->execute(
            dto: new ShowHeroBannerDTO(storeId: $store, bannerId: $id),
        );

        $this->authorize('delete', [$banner, $this->currentStore()]);

        $this->deleteAction->execute(
            dto: new DeleteHeroBannerDTO(storeId: $store, bannerId: $id),
        );

        return $this->success(
            message: 'Hero banner deleted successfully',
        );
    }

    /**
     * Restore a soft-deleted hero banner.
     */
    public function restore(int $store, int $id): JsonResponse
    {
        $banner = $this->showAction->execute(
            dto: new ShowHeroBannerDTO(storeId: $store, bannerId: $id),
        );

        $this->authorize('restore', [$banner, $this->currentStore()]);

        $this->restoreAction->execute(
            dto: new RestoreHeroBannerDTO(storeId: $store, bannerId: $id),
        );

        return $this->success(
            message: 'Hero banner restored successfully',
        );
    }
}
