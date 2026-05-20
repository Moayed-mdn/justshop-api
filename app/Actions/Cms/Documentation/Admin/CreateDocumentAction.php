<?php

declare(strict_types=1);

namespace App\Actions\Cms\Documentation\Admin;

use App\DTOs\Cms\Documentation\Admin\CreateDocumentDTO;
use App\Models\Cms\CmsDocument;
use App\Repositories\Cms\Documentation\CmsDocumentRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreateDocumentAction
{
    public function __construct(
        private CmsDocumentRepository $repository
    ) {}

    public function execute(CreateDocumentDTO $dto): CmsDocument
    {
        return DB::transaction(function () use ($dto) {
            $document = $this->repository->create([
                'store_id' => $dto->storeId,
                'section_id' => $dto->sectionId,
                'parent_id' => $dto->parentId,
                'version' => $dto->version,
                'title' => $dto->title,
                'slug' => $dto->slug,
                'excerpt' => $dto->excerpt,
                'content' => $dto->content,
                'sort_order' => $dto->sortOrder,
                'is_published' => $dto->isPublished,
                'published_at' => $dto->publishedAt,
                'meta_title' => $dto->metaTitle,
                'meta_description' => $dto->metaDescription,
                'canonical_url' => $dto->canonicalUrl,
                'og_image' => $dto->ogImage,
                'robots' => $dto->robots,
                'index_controls' => $dto->indexControls,
            ]);

            // Invalidate cache
            Cache::tags(['store:' . $dto->storeId, 'cms:docs'])->flush();

            return $document;
        });
    }
}
