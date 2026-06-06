<?php

namespace App\DTOs\Theme;

use App\Enums\Theme\BlockTypeEnum;

readonly class CreateBlockDTO
{
    public function __construct(
        public int $sectionId,
        public string $name,
        public BlockTypeEnum $type,
        public ?string $handle = null,
        public ?string $description = null,
        public ?array $settings = null,
        public ?array $content = null,
        public ?int $position = null,
        public bool $isEnabled = true,
        public bool $isRemovable = true,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            sectionId: $data['section_id'],
            name: $data['name'],
            type: $data['type'] instanceof BlockTypeEnum ? $data['type'] : BlockTypeEnum::from($data['type']),
            handle: $data['handle'] ?? null,
            description: $data['description'] ?? null,
            settings: $data['settings'] ?? null,
            content: $data['content'] ?? null,
            position: $data['position'] ?? null,
            isEnabled: $data['is_enabled'] ?? true,
            isRemovable: $data['is_removable'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'section_id' => $this->sectionId,
            'name' => $this->name,
            'type' => $this->type,
            'handle' => $this->handle,
            'description' => $this->description,
            'settings' => $this->settings,
            'content' => $this->content,
            'position' => $this->position,
            'is_enabled' => $this->isEnabled,
            'is_removable' => $this->isRemovable,
        ];
    }
}
