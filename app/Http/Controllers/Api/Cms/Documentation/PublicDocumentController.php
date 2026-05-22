<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Cms\Documentation;

use App\Actions\Cms\Documentation\ResolveDocumentBySlugAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Cms\Documentation\PublicDocumentResource;
use App\Http\Resources\Cms\Documentation\PublicSidebarResource;
use App\Repositories\Cms\Documentation\CmsDocumentRepository;
use Illuminate\Http\JsonResponse;

class PublicDocumentController extends Controller
{
    public function __construct(
        private CmsDocumentRepository $repository,
        private ResolveDocumentBySlugAction $resolveAction,
    ) {}

    public function sidebar(): JsonResponse
    {
        $tree = $this->repository->getSidebarTree();
        return $this->success(['items' => PublicSidebarResource::collection($tree)]);
    }

    public function show(string $slugPath): JsonResponse
    {
        $document = $this->resolveAction->execute($slugPath);

        if (!$document) {
            return $this->error('Document not found', 404);
        }

        return $this->success(new PublicDocumentResource($document));
    }

    public function navigation(string $slugPath): JsonResponse
    {
        $document = $this->resolveAction->execute($slugPath);

        if (!$document) {
            return $this->error('Document not found', 404);
        }

        return $this->success([
            'breadcrumbs' => $this->repository->getBreadcrumbs($document),
            'navigation' => $this->repository->getPreviousNext($document),
        ]);
    }
}
