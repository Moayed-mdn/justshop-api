<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Actions\Admin\Tag\CreateTagAction;
use App\Actions\Admin\Tag\DeleteTagAction;
use App\Actions\Admin\Tag\ListTagsAction;
use App\Actions\Admin\Tag\UpdateTagAction;
use App\DTOs\Admin\Tag\CreateTagDTO;
use App\DTOs\Admin\Tag\ListTagsDTO;
use App\DTOs\Admin\Tag\UpdateTagDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tag\CreateTagRequest;
use App\Http\Requests\Admin\Tag\ListTagsRequest;
use App\Http\Requests\Admin\Tag\UpdateTagRequest;
use App\Http\Resources\Admin\Tag\AdminTagResource;
use App\Models\Store;
use App\Models\Tag;
use App\Repositories\Admin\Tag\AdminTagRepository;
use Illuminate\Http\JsonResponse;

class AdminTagController extends Controller
{
    /**
     * GET /api/v1/merchant/stores/{store}/tags
     */
    public function index(
        ListTagsRequest $request,
        Store $store,
        ListTagsAction $action,
    ): JsonResponse {
        $this->authorize('viewAny', [Tag::class, $this->currentStore()]);

        $tags = $action->execute(
            ListTagsDTO::fromRequest($request, $store->id)
        );

        return $this->paginated(
            $tags,
            AdminTagResource::collection($tags)
        );
    }

    /**
     * POST /api/v1/merchant/stores/{store}/tags
     */
    public function store(
        CreateTagRequest $request,
        Store $store,
        CreateTagAction $action,
    ): JsonResponse {
        $this->authorize('create', [Tag::class, $this->currentStore()]);

        $tag = $action->execute(
            CreateTagDTO::fromRequest($request, $store->id)
        );

        return $this->success(
            new AdminTagResource($tag),
            __('tag.created'),
            201,
        );
    }

    /**
     * GET /api/v1/merchant/stores/{store}/tags/{tag}
     */
    public function show(
        Store $store,
        int $tag,
        AdminTagRepository $repository,
    ): JsonResponse {
        $this->authorize('view', [Tag::class, $this->currentStore()]);

        $tagModel = $repository->findInStore($tag, $store->id);

        return $this->success(new AdminTagResource($tagModel));
    }

    /**
     * PUT /api/v1/merchant/stores/{store}/tags/{tag}
     */
    public function update(
        UpdateTagRequest $request,
        Store $store,
        int $tag,
        UpdateTagAction $action,
    ): JsonResponse {
        $this->authorize('update', [Tag::class, $this->currentStore()]);

        $tagModel = $action->execute(
            UpdateTagDTO::fromRequest($request, $store->id, $tag)
        );

        return $this->success(
            new AdminTagResource($tagModel),
            __('tag.updated'),
        );
    }

    /**
     * DELETE /api/v1/merchant/stores/{store}/tags/{tag}
     */
    public function destroy(
        Store $store,
        int $tag,
        DeleteTagAction $action,
    ): JsonResponse {
        $this->authorize('delete', [Tag::class, $this->currentStore()]);

        $action->execute($store->id, $tag);

        return $this->success(null, __('tag.deleted'));
    }
}
