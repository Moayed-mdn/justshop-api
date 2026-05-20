<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Documentation\Admin;

use App\Http\Requests\Cms\Documentation\Admin\PublishDocumentRequest;

class PublishDocumentDTO
{
    public function __construct(
        public int $storeId,
        public bool $isPublished,
        public ?string $publishedAt = null,
    ) {}

    public static function fromRequest(PublishDocumentRequest $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            isPublished: $request->boolean('is_published'),
            publishedAt: $request->input('published_at'),
        );
    }
}
