<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Repositories\Cms\Marketing\Store\StoreMarketingPageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStoreMarketingPageController extends Controller
{
    public function __construct(
        private readonly StoreMarketingPageRepository $repository
    ) {}

    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorize('viewAny', [\App\Models\Cms\Marketing\Store\StoreMarketingPage::class, $store]);

        $pages = $this->repository->paginateAdmin(
            (int) $store->id,
            $request->integer('per_page', 15),
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null
        );

        return $this->paginated($pages, $pages->items());
    }

    public function show(Store $store, int $id): JsonResponse
    {
        $this->authorize('view', [\App\Models\Cms\Marketing\Store\StoreMarketingPage::class, $store]);

        $page = $this->repository->findByIdOrFail((int) $store->id, $id);

        return $this->success($page);
    }

    public function store(Request $request, Store $store): JsonResponse
    {
        $this->authorize('create', [\App\Models\Cms\Marketing\Store\StoreMarketingPage::class, $store]);

        // Basic implementation for now
        $page = $this->repository->create(array_merge($request->all(), [
            'store_id' => $store->id,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]));

        return $this->success($page, 'cms.page_created', 201);
    }

    public function update(Request $request, Store $store, int $id): JsonResponse
    {
        $this->authorize('update', [\App\Models\Cms\Marketing\Store\StoreMarketingPage::class, $store]);

        $page = $this->repository->findByIdOrFail((int) $store->id, $id);
        $page = $this->repository->update($page, array_merge($request->all(), [
            'updated_by' => $request->user()?->id,
        ]));

        return $this->success($page, 'cms.page_updated');
    }

    public function destroy(Store $store, int $id): JsonResponse
    {
        $this->authorize('delete', [\App\Models\Cms\Marketing\Store\StoreMarketingPage::class, $store]);

        $page = $this->repository->findByIdOrFail((int) $store->id, $id);
        $this->repository->delete($page);

        return $this->success(null, 'cms.page_deleted');
    }
}
