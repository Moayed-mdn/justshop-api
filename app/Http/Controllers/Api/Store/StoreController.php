<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Requests\Store\CreateStoreRequest;
use App\Http\Requests\Store\UpdateStoreRequest;
use App\Http\Requests\Store\ValidateSlugRequest;
use App\Http\Resources\Store\StoreResource;
use App\Actions\Store\CreateStoreAction;
use App\Actions\Store\UpdateStoreAction;
use App\DTOs\Store\CreateStoreDTO;
use App\DTOs\Store\UpdateStoreDTO;
use App\Models\Store;
use App\Services\Store\StoreSlugService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StoreController extends Controller
{
    public function __construct(
        private CreateStoreAction $createStoreAction,
        private UpdateStoreAction $updateStoreAction,
        private StoreSlugService $slugService,
    ) {}

    public function validateSlug(ValidateSlugRequest $request): JsonResponse
    {
        $slug = $this->slugService->normalize($request->validated('slug'));
        $isAvailable = $this->slugService->isAvailable($slug);

        return $this->success([
            'slug' => $slug,
            'is_available' => $isAvailable,
            'is_reserved' => $this->slugService->isReserved($slug),
        ], 'Slug validation completed');
    }

    public function create(CreateStoreRequest $request): JsonResponse
    {
        $this->authorize('create', Store::class);

        $dto = CreateStoreDTO::fromRequest($request);
        $store = $this->createStoreAction->execute($dto);

        return $this->success(new StoreResource($store), 'Store created successfully', 201);
    }

    public function show(Request $request, int $store): JsonResponse
    {
        $storeModel = app('currentStore');
        $this->authorize('view', $storeModel);

        return $this->success(new StoreResource($storeModel), 'Store retrieved successfully');
    }

    public function update(UpdateStoreRequest $request, int $store): JsonResponse
    {
        $storeModel = app('currentStore');
        $this->authorize('update', $storeModel);

        $dto = UpdateStoreDTO::fromRequest($request, $store);
        $storeModel = $this->updateStoreAction->execute($dto);

        return $this->success(new StoreResource($storeModel), 'Store updated successfully');
    }
}
