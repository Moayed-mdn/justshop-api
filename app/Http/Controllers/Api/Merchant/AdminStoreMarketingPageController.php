<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Cms\Marketing\Store\Admin\CreateStoreMarketingPageAction;
use App\Actions\Cms\Marketing\Store\Admin\DeleteStoreMarketingPageAction;
use App\Actions\Cms\Marketing\Store\Admin\PublishStoreMarketingPageAction;
use App\Actions\Cms\Marketing\Store\Admin\UnpublishStoreMarketingPageAction;
use App\Actions\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageAction;
use App\DTOs\Cms\Marketing\Store\Admin\CreateStoreMarketingPageDTO;
use App\DTOs\Cms\Marketing\Store\Admin\DeleteStoreMarketingPageDTO;
use App\DTOs\Cms\Marketing\Store\Admin\PublishStoreMarketingPageDTO;
use App\DTOs\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Marketing\Store\Admin\CreateStoreMarketingPageRequest;
use App\Http\Requests\Cms\Marketing\Store\Admin\PublishStoreMarketingPageRequest;
use App\Http\Requests\Cms\Marketing\Store\Admin\UpdateStoreMarketingPageRequest;
use App\Http\Resources\Admin\Cms\Marketing\Store\AdminStoreMarketingPageResource;
use App\Models\Cms\Marketing\Store\StoreMarketingPage;
use App\Models\Store;
use App\Repositories\Cms\Marketing\Store\StoreMarketingPageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminStoreMarketingPageController extends Controller
{
    public function __construct(
        private readonly StoreMarketingPageRepository $repository,
    ) {}

    public function index(Request $request, Store $store): JsonResponse
    {
        $this->authorize('viewAny', [StoreMarketingPage::class, $store]);

        $pages = $this->repository->paginateAdmin(
            (int) $store->id,
            $request->integer('per_page', 15),
            $request->string('search')->toString() ?: null,
            $request->string('status')->toString() ?: null,
        );

        return $this->paginated(
            $pages,
            AdminStoreMarketingPageResource::collection($pages->items()),
        );
    }

    public function show(Store $store, int $id): JsonResponse
    {
        $this->authorize('view', [StoreMarketingPage::class, $store]);

        $page = $this->repository->findByIdOrFail((int) $store->id, $id);

        return $this->success(new AdminStoreMarketingPageResource($page));
    }

    public function store(
        CreateStoreMarketingPageRequest $request,
        Store $store,
        CreateStoreMarketingPageAction $action,
    ): JsonResponse {
        $this->authorize('create', [StoreMarketingPage::class, $store]);

        $page = $action->execute(
            CreateStoreMarketingPageDTO::fromRequest($request, (int) $store->id)
        );

        return $this->success(
            new AdminStoreMarketingPageResource($page),
            'cms.page_created',
            201,
        );
    }

    public function update(
        UpdateStoreMarketingPageRequest $request,
        Store $store,
        int $id,
        UpdateStoreMarketingPageAction $action,
    ): JsonResponse {
        $this->authorize('update', [StoreMarketingPage::class, $store]);

        $page = $action->execute(
            UpdateStoreMarketingPageDTO::fromRequest($request, (int) $store->id, $id)
        );

        return $this->success(
            new AdminStoreMarketingPageResource($page),
            'cms.page_updated',
        );
    }

    public function destroy(
        Store $store,
        int $id,
        DeleteStoreMarketingPageAction $action,
    ): JsonResponse {
        $this->authorize('delete', [StoreMarketingPage::class, $store]);

        $action->execute(new DeleteStoreMarketingPageDTO($id, (int) $store->id));

        return $this->success(null, 'cms.page_deleted');
    }

    public function publish(
        PublishStoreMarketingPageRequest $request,
        Store $store,
        int $id,
        PublishStoreMarketingPageAction $action,
    ): JsonResponse {
        $this->authorize('publish', [StoreMarketingPage::class, $store]);

        $page = $action->execute(
            PublishStoreMarketingPageDTO::fromRequest($request, (int) $store->id, $id)
        );

        return $this->success(
            new AdminStoreMarketingPageResource($page),
            'cms.page_published',
        );
    }

    public function unpublish(
        PublishStoreMarketingPageRequest $request,
        Store $store,
        int $id,
        UnpublishStoreMarketingPageAction $action,
    ): JsonResponse {
        $this->authorize('publish', [StoreMarketingPage::class, $store]);

        $page = $action->execute(
            PublishStoreMarketingPageDTO::fromRequest($request, (int) $store->id, $id)
        );

        return $this->success(
            new AdminStoreMarketingPageResource($page),
            'cms.page_unpublished',
        );
    }
}
