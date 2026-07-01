<?php

declare(strict_types=1);

namespace App\DTOs\Theme;

use App\Enums\Theme\TemplateTypeEnum;
use App\Http\Requests\Theme\CreateSystemTemplateRequest;

class CreateSystemTemplateDTO
{
    public function __construct(
        public readonly int $themeId,
        public readonly string $name,
        public readonly string $handle,
        public readonly TemplateTypeEnum $type,
        public readonly ?string $description,
        public readonly array $sectionIds,
        public readonly array $settings,
        public readonly bool $isDefault,
    ) {}

    public static function fromRequest(CreateSystemTemplateRequest $request, int $themeId): self
    {
        return new self(
            themeId: $themeId,
            name: $request->string('name')->toString(),
            handle: $request->string('handle')->toString(),
            type: TemplateTypeEnum::from($request->string('type')->toString()),
            description: $request->string('description')->toString() ?: null,
            sectionIds: $request->input('section_ids', []),
            settings: $request->input('settings', []),
            isDefault: $request->boolean('is_default', false),
        );
    }
}
