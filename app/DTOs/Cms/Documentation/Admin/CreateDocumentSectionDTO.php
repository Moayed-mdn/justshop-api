<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Documentation\Admin;

use App\Http\Requests\Cms\Documentation\Admin\CreateDocumentSectionRequest;

class CreateDocumentSectionDTO
{
    public function __construct(
        public array $title,
        public array $slug,
        public ?int $parentId = null,
        public ?string $version = null,
        public ?array $description = null,
        public int $sortOrder = 0,
        public bool $isPublished = false,
        public ?string $publishedAt = null,
    ) {}

    public static function fromRequest(CreateDocumentSectionRequest $request): self
    {
        return new self(
            title: $request->input('title'),
            slug: $request->input('slug'),
            parentId: $request->integer('parent_id') ?: null,
            version: $request->string('version')->nullable(),
            description: $request->input('description'),
            sortOrder: $request->integer('sort_order', 0),
            isPublished: $request->boolean('is_published'),
            publishedAt: $request->input('published_at'),
        );
    }
}
