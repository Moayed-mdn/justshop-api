<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin\Cms\Documentation;

use App\Actions\Cms\Documentation\Admin\CreateDocumentSectionAction;
use App\DTOs\Cms\Documentation\Admin\CreateDocumentSectionDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\Documentation\Admin\CreateDocumentSectionRequest;
use App\Http\Resources\Admin\Cms\Documentation\AdminDocumentSectionResource;
use App\Repositories\Cms\Documentation\CmsDocumentSectionRepository;
use Illuminate\Http\JsonResponse;

class AdminDocumentSectionController extends Controller
{
    public function __construct(
        private CmsDocumentSectionRepository $repository,
        private CreateDocumentSectionAction $createAction,
    ) {}

    public function index(int $store): JsonResponse
    {
        $sections = $this->repository->getAll($store);
        return $this->success(AdminDocumentSectionResource::collection($sections));
    }

    public function store(CreateDocumentSectionRequest $request, int $store): JsonResponse
    {
        $section = $this->createAction->execute(
            CreateDocumentSectionDTO::fromRequest($request, $store)
        );

        return $this->success(new AdminDocumentSectionResource($section));
    }

    public function show(int $store, int $id): JsonResponse
    {
        $section = $this->repository->findById($id, $store);

        if (!$section) {
            return $this->error('Section not found', 404);
        }

        return $this->success(new AdminDocumentSectionResource($section));
    }

    public function destroy(int $store, int $id): JsonResponse
    {
        $section = $this->repository->findById($id, $store);

        if (!$section) {
            return $this->error('Section not found', 404);
        }

        $this->repository->delete($section);

        return $this->success(null, 'Section deleted successfully');
    }
}
