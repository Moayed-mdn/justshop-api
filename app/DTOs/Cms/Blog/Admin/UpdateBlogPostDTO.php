<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Blog\Admin;

use App\Enums\Cms\Blog\BlogPostPublishStateEnum;
use App\Http\Requests\Cms\Blog\UpdateBlogPostRequest;
use Carbon\CarbonImmutable;

class UpdateBlogPostDTO
{
    public function __construct(
        public readonly int $blogPostId,
        public readonly ?int $authorId,
        public readonly ?int $blogCategoryId,
        public readonly bool $featured,
        public readonly bool $isPublished,
        public readonly ?CarbonImmutable $publishedAt,
        public readonly ?string $coverImage,
        public readonly array $translations,
        public readonly array $tagIds,
        public readonly int $updatedBy,
    ) {}

    public static function fromRequest(UpdateBlogPostRequest $request, int $blogPostId): self
    {
        $status = $request->string('status')->toString();
        $isPublished = $status !== 'draft';

        return new self(
            blogPostId: $blogPostId,
            authorId: $request->filled('author_id') ? $request->integer('author_id') : null,
            blogCategoryId: $request->filled('blog_category_id') ? $request->integer('blog_category_id') : null,
            featured: $request->boolean('featured', false),
            isPublished: $isPublished,
            publishedAt: $request->filled('published_at') ? CarbonImmutable::parse($request->string('published_at')->toString()) : null,
            coverImage: $request->string('cover_image')->toString() ?: null,
            translations: self::normalizeTranslations($request->input('translations', [])),
            tagIds: $request->input('tag_ids', []),
            updatedBy: (int) $request->user()?->id,
        );
    }

    private static function normalizeTranslations(array $translations): array
    {
        $normalized = [];

        foreach ($translations as $locale => $translation) {
            $normalized[$locale] = [
                'title' => $translation['title'],
                'slug' => $translation['slug'],
                'excerpt' => $translation['excerpt'] ?? null,
                'content' => $translation['content'],
                'meta_title' => $translation['meta_title'] ?? null,
                'meta_description' => $translation['meta_description'] ?? null,
                'canonical_url' => $translation['canonical_url'] ?? null,
                'og_image' => $translation['og_image'] ?? null,
                'robots' => $translation['robots'] ?? null,
            ];
        }

        return $normalized;
    }
}
