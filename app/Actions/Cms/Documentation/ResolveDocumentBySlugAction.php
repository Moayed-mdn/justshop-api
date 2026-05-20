<?php

declare(strict_types=1);

namespace App\Actions\Cms\Documentation;

use App\Models\Cms\CmsDocument;
use App\Repositories\Cms\Documentation\CmsDocumentRepository;

class ResolveDocumentBySlugAction
{
    public function __construct(
        private CmsDocumentRepository $repository
    ) {}

    public function execute(string $slugPath, int $storeId): ?CmsDocument
    {
        $slugs = explode('/', $slugPath);
        return $this->repository->findBySlugPath($slugs, $storeId);
    }
}
