<?php

declare(strict_types=1);

namespace App\Actions\Cms\Documentation\Admin;

use App\DTOs\Cms\Documentation\Admin\PublishDocumentDTO;
use App\Models\Cms\CmsDocument;
use App\Repositories\Cms\Documentation\CmsDocumentRepository;
use Illuminate\Support\Facades\DB;

class PublishDocumentAction
{
    public function __construct(
        private CmsDocumentRepository $repository
    ) {}

    public function execute(CmsDocument $document, PublishDocumentDTO $dto): CmsDocument
    {
        DB::transaction(function () use ($document, $dto) {
            $this->repository->update($document, [
                'is_published' => $dto->isPublished,
                'published_at' => $dto->publishedAt,
            ]);
        });

        return $document->fresh();
    }
}
