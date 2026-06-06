<?php

namespace App\DTOs\Theme;

use App\Enums\Theme\SectionTypeEnum;

readonly class CreateSectionDTO
{
    public function __construct(
        public int $themeId,
        public string $name,
        public SectionTypeEnum $type,
        public ?string $handle = null,
        public ?string $description = null,
        public ?array $settings = null,
        public ?int $position = null,
        public bool $isEnabled = true,
        public bool $isRemovable = true,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            themeId: $data['theme_id'],
            name: $data['name'],
            type: $data['type'] instanceof SectionTypeEnum ? $data['type'] : SectionTypeEnum::from($data['type']),
            handle: $data['handle'] ?? null,
            description: $data['description'] ?? null,
            settings: $data['settings'] ?? null,
            position: $data['position'] ?? null,
            isEnabled: $data['is_enabled'] ?? true,
            isRemovable: $data['is_removable'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'theme_id' => $this->themeId,
            'name' => $this->name,
            'type' => $this->type,
            'handle' => $this->handle,
            'description' => $this->description,
            'settings' => $this->settings,
            'position' => $this->position,
            'is_enabled' => $this->isEnabled,
            'is_removable' => $this->isRemovable,
        ];
    }
}
