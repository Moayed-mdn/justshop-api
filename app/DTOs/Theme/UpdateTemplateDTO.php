<?php

declare(strict_types=1);

namespace App\DTOs\Theme;

use App\Http\Requests\Theme\UpdateTemplateRequest;

class UpdateTemplateDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?array $sections,
        public readonly ?array $sectionOrder,
        public readonly ?array $sectionSettings,
        public readonly ?bool $isDefault,
        public readonly ?bool $isActive,
        public readonly int $userId,
    ) {}

    public static function fromRequest(UpdateTemplateRequest $request): self
    {
        return new self(
            name: $request->has('name') ? $request->string('name')->toString() : null,
            description: $request->has('description') ? $request->string('description')->toString() : null,
            sections: $request->input('sections'),
            sectionOrder: $request->input('section_order'),
            sectionSettings: $request->input('section_settings'),
            isDefault: $request->has('is_default') ? $request->boolean('is_default') : null,
            isActive: $request->has('is_active') ? $request->boolean('is_active') : null,
            userId: $request->user()->id,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->sections !== null) {
            $data['sections'] = $this->sections;
        }

        if ($this->sectionOrder !== null) {
            $data['section_order'] = $this->sectionOrder;
        }

        if ($this->sectionSettings !== null) {
            $data['section_settings'] = $this->sectionSettings;
        }

        if ($this->isDefault !== null) {
            $data['is_default'] = $this->isDefault;
        }

        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        $data['updated_by'] = $this->userId;

        return $data;
    }
}
