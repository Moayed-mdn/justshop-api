<?php

declare(strict_types=1);

namespace App\DTOs\Theme;

use App\Http\Requests\Theme\CreateTemplateRequest;

class CreateTemplateDTO
{
    public function __construct(
        public readonly int $storeId,
        public readonly string $name,
        public readonly string $handle,
        public readonly string $type,
        public readonly ?string $description,
        public readonly array $sections,
        public readonly array $sectionOrder,
        public readonly ?array $sectionSettings,
        public readonly bool $isDefault,
        public readonly int $userId,
    ) {}

    public static function fromRequest(CreateTemplateRequest $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            name: $request->string('name')->toString(),
            handle: $request->string('handle')->toString(),
            type: $request->string('type')->toString(),
            description: $request->string('description')->toString() ?: null,
            sections: $request->input('sections', []),
            sectionOrder: $request->input('section_order', []),
            sectionSettings: $request->input('section_settings'),
            isDefault: $request->boolean('is_default', false),
            userId: $request->user()->id,
        );
    }
}
