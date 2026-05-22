<?php

declare(strict_types=1);

namespace App\Actions\Cms\Blog\Admin;

use App\DTOs\Cms\Blog\Admin\CreateBlogPostDTO;
use App\Models\BlogPost;
use App\Repositories\Cms\Blog\BlogPostRepository;
use App\Services\Cms\Seo\SitemapService;
use App\Support\BlogReadingTimeCalculator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreateBlogPostAction
{
    public function __construct(
        private BlogPostRepository $repository,
        private BlogReadingTimeCalculator $readingTimeCalculator,
        private SitemapService $sitemapService,
    ) {}

    public function execute(CreateBlogPostDTO $dto): BlogPost
    {
        return DB::transaction(function () use ($dto): BlogPost {
            $translatedColumns = $this->transformTranslationsToColumns($dto->translations);

            $post = $this->repository->create(
                attributes: array_merge([
                    'author_id' => $dto->authorId,
                    'blog_category_id' => $dto->blogCategoryId,
                    'featured' => $dto->featured,
                    'is_published' => $dto->isPublished,
                    'published_at' => $dto->publishedAt ?? ($dto->isPublished ? now() : null),
                    'cover_image' => $dto->coverImage,
                    'reading_time' => $this->readingTimeCalculator->calculate($dto->translations),
                    'created_by' => $dto->createdBy,
                ], $translatedColumns),
                tagIds: $dto->tagIds,
            );

            // Invalidate caches
            Cache::tags(['cms:blog'])->flush();
            $this->sitemapService->invalidateBlog();

            return $post;
        });
    }

    private function transformTranslationsToColumns(array $translations): array
    {
        $columns = [
            'title' => [],
            'slug' => [],
            'excerpt' => [],
            'content' => [],
            'seo' => [],
        ];

        foreach ($translations as $locale => $trans) {
            $columns['title'][$locale] = $trans['title'];
            $columns['slug'][$locale] = $trans['slug'];
            $columns['excerpt'][$locale] = $trans['excerpt'] ?? null;
            $columns['content'][$locale] = $trans['content'];

            $columns['seo']['title'][$locale] = $trans['meta_title'] ?? $trans['title'];
            $columns['seo']['description'][$locale] = $trans['meta_description'] ?? $trans['excerpt'] ?? null;
            $columns['seo']['canonical_url'][$locale] = $trans['canonical_url'] ?? null;
            $columns['seo']['og_image'][$locale] = $trans['og_image'] ?? null;
            $columns['seo']['robots'][$locale] = $trans['robots'] ?? 'index,follow';
        }

        return $columns;
    }
}
