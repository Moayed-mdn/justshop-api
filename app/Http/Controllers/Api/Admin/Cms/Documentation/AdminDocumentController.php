<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Cms\Documentation;

use App\Actions\Cms\Documentation\Admin\CreateDocumentAction;
use App\Actions\Cms\Documentation\Admin\PublishDocumentAction;
use App\Actions\Cms\Documentation\Admin\ReorderDocumentsAction;
use App\Actions\Cms\Documentation\Admin\UpdateDocumentAction;
use App\DTOs\Cms\Documentation\Admin\CreateDocumentDTO;
use App\DTOs\Cms\Documentation\Admin\PublishDocumentDTO;
use App\DTOs\Cms\Documentation\Admin\UpdateDocumentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Documentation\Admin\CreateDocumentRequest;
use App\Http\Requests\Cms\Documentation\Admin\PublishDocumentRequest;
use App\Http\Requests\Cms\Documentation\Admin\UpdateDocumentRequest;
use App\Http\Resources\Admin\Cms\Documentation\AdminDocumentResource;
use App\Repositories\Cms\Documentation\CmsDocumentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDocumentController extends Controller
{
    public function __construct(
        private CmsDocumentRepository $repository,
        private CreateDocumentAction $createAction,
        private UpdateDocumentAction $updateAction,
        private PublishDocumentAction $publishAction,
        private ReorderDocumentsAction $reorderAction,
    ) {}

    public function index(int $store): JsonResponse
    {
        $documents = $this->repository->getPublishedDocuments($store);
        return $this->success(AdminDocumentResource::collection($documents));
    }

    public function store(CreateDocumentRequest $request, int $store): JsonResponse
    {
        $document = $this->createAction->execute(
            CreateDocumentDTO::fromRequest($request, $store)
        );

        return $this->success(new AdminDocumentResource($document));
    }

    public function show(int $store, int $id): JsonResponse
    {
        $document = $this->repository->findById($id, $store);

        if (!$document) {
            return $this->error('Document not found', 404);
        }

        return $this->success(new AdminDocumentResource($document));
    }

    public function update(UpdateDocumentRequest $request, int $store, int $id): JsonResponse
    {
        $document = $this->repository->findById($id, $store);

        if (!$document) {
            return $this->error('Document not found', 404);
        }

        $document = $this->updateAction->execute(
            $document,
            UpdateDocumentDTO::fromRequest($request, $store)
        );

        return $this->success(new AdminDocumentResource($document));
    }

    public function destroy(int $store, int $id): JsonResponse
    {
        $document = $this->repository->findById($id, $store);

        if (!$document) {
            return $this->error('Document not found', 404);
        }

        $this->repository->delete($document);

        return $this->success(null, 'Document deleted successfully');
    }

    public function publish(PublishDocumentRequest $request, int $store, int $id): JsonResponse
    {
        $document = $this->repository->findById($id, $store);

        if (!$document) {
            return $this->error('Document not found', 404);
        }

        $document = $this->publishAction->execute(
            $document,
            PublishDocumentDTO::fromRequest($request, $store)
        );

        return $this->success(new AdminDocumentResource($document));
    }

    public function reorder(Request $request, int $store): JsonResponse
    {
        $this->reorderAction->execute($store, $request->input('orders'));
        return $this->success(null, 'Documents reordered successfully');
    }
}
