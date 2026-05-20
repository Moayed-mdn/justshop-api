<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Documentation\Admin;

use App\Http\Requests\Cms\Documentation\Admin\UpdateDocumentRequest;

class UpdateDocumentDTO
{
    public function __construct(
        public int $storeId,
        public array $title,
        public array $slug,
        public array $content,
        public ?int $sectionId = null,
        public ?int $parentId = null,
        public ?string $version = null,
        public ?array $excerpt = null,
        public int $sortOrder = 0,
        public bool $isPublished = false,
        public ?string $publishedAt = null,
        public ?array $metaTitle = null,
        public ?array $metaDescription = null,
        public ?array $canonicalUrl = null,
        public ?array $ogImage = null,
        public ?array $robots = null,
        public ?array $indexControls = null,
    ) {}

    public static function fromRequest(UpdateDocumentRequest $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            title: $request->input('title'),
            slug: $request->input('slug'),
            content: $request->input('content'),
            sectionId: $request->integer('section_id') ?: null,
            parentId: $request->integer('parent_id') ?: null,
            version: $request->string('version')->nullable(),
            excerpt: $request->input('excerpt'),
            sortOrder: $request->integer('sort_order', 0),
            isPublished: $request->boolean('is_published'),
            publishedAt: $request->input('published_at'),
            metaTitle: $request->input('meta_title'),
            metaDescription: $request->input('meta_description'),
            canonicalUrl: $request->input('canonical_url'),
            ogImage: $request->input('og_image'),
            robots: $request->input('robots'),
            indexControls: $request->input('index_controls'),
        );
    }
}
