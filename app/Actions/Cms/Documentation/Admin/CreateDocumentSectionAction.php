<?php

declare(strict_types=1);

namespace App\Actions\Cms\Documentation\Admin;

use App\DTOs\Cms\Documentation\Admin\CreateDocumentSectionDTO;
use App\Models\Cms\CmsDocumentSection;
use App\Repositories\Cms\Documentation\CmsDocumentSectionRepository;
use Illuminate\Support\Facades\DB;

class CreateDocumentSectionAction
{
    public function __construct(
        private CmsDocumentSectionRepository $repository
    ) {}

    public function execute(CreateDocumentSectionDTO $dto): CmsDocumentSection
    {
        return DB::transaction(function () use ($dto) {
            return $this->repository->create([
                'parent_id' => $dto->parentId,
                'version' => $dto->version,
                'title' => $dto->title,
                'slug' => $dto->slug,
                'description' => $dto->description,
                'sort_order' => $dto->sortOrder,
                'is_published' => $dto->isPublished,
                'published_at' => $dto->publishedAt,
            ]);
        });
    }
}
