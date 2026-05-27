<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

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

    public function index(): JsonResponse
    {
        $sections = $this->repository->getAll();
        return $this->success(AdminDocumentSectionResource::collection($sections));
    }

    public function store(CreateDocumentSectionRequest $request): JsonResponse
    {
        $section = $this->createAction->execute(
            CreateDocumentSectionDTO::fromRequest($request)
        );

        return $this->success(new AdminDocumentSectionResource($section));
    }

    public function show(int $id): JsonResponse
    {
        $section = $this->repository->findById($id);

        if (!$section) {
            return $this->error('Section not found', 404);
        }

        return $this->success(new AdminDocumentSectionResource($section));
    }

    public function destroy(int $id): JsonResponse
    {
        $section = $this->repository->findById($id);

        if (!$section) {
            return $this->error('Section not found', 404);
        }

        $this->repository->delete($section);

        return $this->success(null, 'Section deleted successfully');
    }
}
