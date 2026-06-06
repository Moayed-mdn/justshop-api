<?php

namespace App\DTOs\Theme;

use App\Enums\Theme\SectionTypeEnum;

readonly class UpdateSectionDTO
{
    public function __construct(
        public ?string $name = null,
        public ?SectionTypeEnum $type = null,
        public ?string $handle = null,
        public ?string $description = null,
        public ?array $settings = null,
        public ?int $position = null,
        public ?bool $isEnabled = null,
        public ?bool $isRemovable = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            type: isset($data['type']) ? ($data['type'] instanceof SectionTypeEnum ? $data['type'] : SectionTypeEnum::from($data['type'])) : null,
            handle: $data['handle'] ?? null,
            description: $data['description'] ?? null,
            settings: $data['settings'] ?? null,
            position: $data['position'] ?? null,
            isEnabled: $data['is_enabled'] ?? null,
            isRemovable: $data['is_removable'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'handle' => $this->handle,
            'description' => $this->description,
            'settings' => $this->settings,
            'position' => $this->position,
            'is_enabled' => $this->isEnabled,
            'is_removable' => $this->isRemovable,
        ], fn($value) => $value !== null);
    }
}
