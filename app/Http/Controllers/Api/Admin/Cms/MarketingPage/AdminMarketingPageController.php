<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Cms\MarketingPage;

use App\Actions\Cms\MarketingPage\Admin\CreateMarketingPageAction;
use App\Actions\Cms\MarketingPage\Admin\DeleteMarketingPageAction;
use App\Actions\Cms\MarketingPage\Admin\GetMarketingPageAction;
use App\Actions\Cms\MarketingPage\Admin\ListMarketingPagesAction;
use App\Actions\Cms\MarketingPage\Admin\PublishMarketingPageAction;
use App\Actions\Cms\MarketingPage\Admin\UpdateMarketingPageAction;
use App\DTOs\Cms\MarketingPage\Admin\CreateMarketingPageDTO;
use App\DTOs\Cms\MarketingPage\Admin\DeleteMarketingPageDTO;
use App\DTOs\Cms\MarketingPage\Admin\GetMarketingPageDTO;
use App\DTOs\Cms\MarketingPage\Admin\ListMarketingPagesDTO;
use App\DTOs\Cms\MarketingPage\Admin\PublishMarketingPageDTO;
use App\DTOs\Cms\MarketingPage\Admin\UpdateMarketingPageDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\MarketingPage\Admin\CreateMarketingPageRequest;
use App\Http\Requests\Cms\MarketingPage\Admin\ListMarketingPagesRequest;
use App\Http\Requests\Cms\MarketingPage\Admin\PublishMarketingPageRequest;
use App\Http\Requests\Cms\MarketingPage\Admin\UpdateMarketingPageRequest;
use App\Http\Resources\Admin\Cms\MarketingPage\AdminMarketingPageResource;
use App\Models\Cms\MarketingPage;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminMarketingPageController extends Controller
{
    public function index(
        ListMarketingPagesRequest $request,
        ListMarketingPagesAction $action,
    ): JsonResponse {
        $this->authorize('viewAny', MarketingPage::class);

        $pages = $action->execute(ListMarketingPagesDTO::fromRequest($request));

        return $this->paginated(
            $pages,
            AdminMarketingPageResource::collection($pages->items())
        );
    }

    public function store(
        CreateMarketingPageRequest $request,
        CreateMarketingPageAction $action,
    ): JsonResponse {
        $this->authorize('create', MarketingPage::class);

        $page = $action->execute(CreateMarketingPageDTO::fromRequest($request));

        return $this->success(new AdminMarketingPageResource($page), 'cms.page_created', 201);
    }

    public function show(
        int $id,
        GetMarketingPageAction $action,
    ): JsonResponse {
        $page = $action->execute(new GetMarketingPageDTO($id));

        $this->authorize('view', $page);

        return $this->success(new AdminMarketingPageResource($page));
    }

    public function update(
        UpdateMarketingPageRequest $request,
        int $id,
        UpdateMarketingPageAction $action,
    ): JsonResponse {
        $page = $action->execute(UpdateMarketingPageDTO::fromRequest($request, $id));

        $this->authorize('update', $page);

        return $this->success(new AdminMarketingPageResource($page), 'cms.page_updated');
    }

    public function destroy(
        int $id,
        DeleteMarketingPageAction $action,
    ): JsonResponse {
        // Fetch page first for authorization
        $getAction = app(GetMarketingPageAction::class);
        $page = $getAction->execute(new GetMarketingPageDTO($id));

        $this->authorize('delete', $page);

        $action->execute(new DeleteMarketingPageDTO($id));

        return $this->success(null, 'cms.page_deleted');
    }

    public function publish(
        PublishMarketingPageRequest $request,
        int $id,
        PublishMarketingPageAction $action,
    ): JsonResponse {
        $page = $action->execute(PublishMarketingPageDTO::fromRequest($request, $id));

        $this->authorize('publish', $page);

        return $this->success(new AdminMarketingPageResource($page), 'cms.page_published');
    }
}
