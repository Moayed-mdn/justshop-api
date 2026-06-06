<?php

namespace App\DTOs\Navigation;

readonly class CreateMenuDTO
{
    public function __construct(
        public int $storeId,
        public string $name,
        public ?string $handle = null,
        public ?string $description = null,
        public ?array $settings = null,
        public bool $isActive = true,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            storeId: $data['store_id'],
            name: $data['name'],
            handle: $data['handle'] ?? null,
            description: $data['description'] ?? null,
            settings: $data['settings'] ?? null,
            isActive: $data['is_active'] ?? true,
        );
    }

    public function toArray(): array
    {
        return [
            'store_id' => $this->storeId,
            'name' => $this->name,
            'handle' => $this->handle,
            'description' => $this->description,
            'settings' => $this->settings,
            'is_active' => $this->isActive,
        ];
    }
}
