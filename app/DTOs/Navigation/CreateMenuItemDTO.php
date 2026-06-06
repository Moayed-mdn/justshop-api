<?php

namespace App\DTOs\Navigation;

readonly class CreateMenuItemDTO
{
    public function __construct(
        public int $menuId,
        public string $label,
        public string $type,
        public ?int $parentId = null,
        public ?string $url = null,
        public ?int $resourceId = null,
        public ?string $resourceType = null,
        public string $target = '_self',
        public ?array $settings = null,
        public ?int $position = null,
        public bool $isActive = true,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            menuId: $data['menu_id'],
            label: $data['label'],
            type: $data['type'],
            parentId: $data['parent_id'] ?? null,
            url: $data['url'] ?? null,
            resourceId: $data['resource_id'] ?? null,
            resourceType: $data['resource_type'] ?? null,
            target: $data['target'] ?? '_self',
            settings: $data['settings'] ?? null,
            position: $data['position'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'menu_id' => $this->menuId,
            'label' => $this->label,
            'type' => $this->type,
            'parent_id' => $this->parentId,
            'url' => $this->url,
            'resource_id' => $this->resourceId,
            'resource_type' => $this->resourceType,
            'target' => $this->target,
            'settings' => $this->settings,
            'position' => $this->position,
            'is_active' => $this->isActive,
        ];
    }
}
