<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Cms\Marketing\Platform;

use App\Actions\Cms\Marketing\Platform\Admin\CreatePlatformMarketingPageAction;
use App\Actions\Cms\Marketing\Platform\Admin\DeletePlatformMarketingPageAction;
use App\Actions\Cms\Marketing\Platform\Admin\GetPlatformMarketingPageAction;
use App\Actions\Cms\Marketing\Platform\Admin\ListPlatformMarketingPagesAction;
use App\Actions\Cms\Marketing\Platform\Admin\PublishPlatformMarketingPageAction;
use App\Actions\Cms\Marketing\Platform\Admin\UpdatePlatformMarketingPageAction;
use App\DTOs\Cms\Marketing\Platform\Admin\CreatePlatformMarketingPageDTO;
use App\DTOs\Cms\Marketing\Platform\Admin\DeletePlatformMarketingPageDTO;
use App\DTOs\Cms\Marketing\Platform\Admin\GetPlatformMarketingPageDTO;
use App\DTOs\Cms\Marketing\Platform\Admin\ListPlatformMarketingPagesDTO;
use App\DTOs\Cms\Marketing\Platform\Admin\PublishPlatformMarketingPageDTO;
use App\DTOs\Cms\Marketing\Platform\Admin\UpdatePlatformMarketingPageDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Marketing\Platform\Admin\CreatePlatformMarketingPageRequest;
use App\Http\Requests\Cms\Marketing\Platform\Admin\ListPlatformMarketingPagesRequest;
use App\Http\Requests\Cms\Marketing\Platform\Admin\PublishPlatformMarketingPageRequest;
use App\Http\Requests\Cms\Marketing\Platform\Admin\UpdatePlatformMarketingPageRequest;
use App\Http\Resources\Admin\Cms\Marketing\Platform\AdminPlatformMarketingPageResource;
use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminPlatformMarketingPageController extends Controller
{
    public function index(
        ListPlatformMarketingPagesRequest $request,
        ListPlatformMarketingPagesAction $action,
    ): JsonResponse {
        $pages = $action->execute(ListPlatformMarketingPagesDTO::fromRequest($request));

        return $this->paginated(
            $pages,
            AdminPlatformMarketingPageResource::collection($pages->items())
        );
    }

    public function store(
        CreatePlatformMarketingPageRequest $request,
        CreatePlatformMarketingPageAction $action,
    ): JsonResponse {
        $page = $action->execute(CreatePlatformMarketingPageDTO::fromRequest($request));

        return $this->success(new AdminPlatformMarketingPageResource($page), 'cms.page_created', 201);
    }

    public function show(
        int $id,
        GetPlatformMarketingPageAction $action,
    ): JsonResponse {
        $page = $action->execute(new GetPlatformMarketingPageDTO($id));

        return $this->success(new AdminPlatformMarketingPageResource($page));
    }

    public function update(
        UpdatePlatformMarketingPageRequest $request,
        int $id,
        UpdatePlatformMarketingPageAction $action,
    ): JsonResponse {
        $page = $action->execute(UpdatePlatformMarketingPageDTO::fromRequest($request, $id));

        return $this->success(new AdminPlatformMarketingPageResource($page), 'cms.page_updated');
    }

    public function destroy(
        int $id,
        DeletePlatformMarketingPageAction $action,
    ): JsonResponse {
        $action->execute(new DeletePlatformMarketingPageDTO($id));

        return $this->success(null, 'cms.page_deleted');
    }

    public function publish(
        PublishPlatformMarketingPageRequest $request,
        int $id,
        PublishPlatformMarketingPageAction $action,
    ): JsonResponse {
        $page = $action->execute(PublishPlatformMarketingPageDTO::fromRequest($request, $id));

        return $this->success(new AdminPlatformMarketingPageResource($page), 'cms.page_published');
    }
}
