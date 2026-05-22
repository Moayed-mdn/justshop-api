<?php

declare(strict_types=1);

namespace App\Actions\Cms\Documentation\Admin;

use App\DTOs\Cms\Documentation\Admin\UpdateDocumentDTO;
use App\Models\Cms\CmsDocument;
use App\Repositories\Cms\Documentation\CmsDocumentRepository;
use App\Services\Cms\Seo\SitemapService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateDocumentAction
{
    public function __construct(
        private CmsDocumentRepository $repository,
        private SitemapService $sitemapService,
    ) {}

    public function execute(CmsDocument $document, UpdateDocumentDTO $dto): CmsDocument
    {
        DB::transaction(function () use ($document, $dto) {

            $this->repository->update($document, [
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
                'seo' => [
                    'title' => $dto->metaTitle,
                    'description' => $dto->metaDescription,
                    'canonical_url' => $dto->canonicalUrl,
                    'og_image' => $dto->ogImage,
                    'robots' => $dto->robots,
                ],
            ]);

            // Invalidate cache
            Cache::tags(['cms:docs'])->flush();
            $this->sitemapService->invalidateDocs();
        });

        return $document->fresh();
    }
}
