<?php

namespace App\DTOs\Theme;

use App\Enums\Theme\BlockTypeEnum;

readonly class UpdateBlockDTO
{
    public function __construct(
        public ?string $name = null,
        public ?BlockTypeEnum $type = null,
        public ?string $handle = null,
        public ?string $description = null,
        public ?array $settings = null,
        public ?array $content = null,
        public ?int $position = null,
        public ?bool $isEnabled = null,
        public ?bool $isRemovable = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            type: isset($data['type']) ? ($data['type'] instanceof BlockTypeEnum ? $data['type'] : BlockTypeEnum::from($data['type'])) : null,
            handle: $data['handle'] ?? null,
            description: $data['description'] ?? null,
            settings: $data['settings'] ?? null,
            content: $data['content'] ?? null,
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
            'content' => $this->content,
            'position' => $this->position,
            'is_enabled' => $this->isEnabled,
            'is_removable' => $this->isRemovable,
        ], fn($value) => $value !== null);
    }
}
