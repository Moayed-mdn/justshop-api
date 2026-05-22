<?php

declare(strict_types=1);

namespace App\DTOs\Cms\Documentation\Admin;

use App\Http\Requests\Cms\Documentation\Admin\PublishDocumentRequest;

class PublishDocumentDTO
{
    public function __construct(
        public bool $isPublished,
        public ?string $publishedAt = null,
    ) {}

    public static function fromRequest(PublishDocumentRequest $request): self
    {
        return new self(
            isPublished: $request->boolean('is_published'),
            publishedAt: $request->input('published_at'),
        );
    }
}
