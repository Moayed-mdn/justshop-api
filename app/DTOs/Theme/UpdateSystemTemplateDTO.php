<?php

declare(strict_types=1);

namespace App\DTOs\Theme;

use App\Http\Requests\Theme\UpdateSystemTemplateRequest;

class UpdateSystemTemplateDTO
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $description,
        public readonly ?array $sectionIds,
        public readonly ?array $sectionOverrides,
        public readonly ?array $sectionVisibility,
        public readonly ?array $settings,
        public readonly ?bool $isDefault,
    ) {}

    public static function fromRequest(UpdateSystemTemplateRequest $request): self
    {
        return new self(
            name: $request->has('name') ? $request->string('name')->toString() : null,
            description: $request->has('description') ? $request->string('description')->toString() : null,
            sectionIds: $request->input('section_ids'),
            sectionOverrides: $request->input('section_overrides'),
            sectionVisibility: $request->input('section_visibility'),
            settings: $request->input('settings'),
            isDefault: $request->has('is_default') ? $request->boolean('is_default') : null,
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

        if ($this->settings !== null) {
            $data['settings'] = $this->settings;
        }

        if ($this->isDefault !== null) {
            $data['is_default'] = $this->isDefault;
        }

        return $data;
    }
}
