<?php

namespace App\DTOs\Theme;

readonly class CreateThemeDTO
{
    public function __construct(
        public int $storeId,
        public string $name,
        public ?string $slug = null,
        public ?string $description = null,
        public string $version = '1.0.0',
        public ?string $author = null,
        public ?array $settings = null,
        public ?array $metadata = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            storeId: $data['store_id'],
            name: $data['name'],
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            version: $data['version'] ?? '1.0.0',
            author: $data['author'] ?? null,
            settings: $data['settings'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'store_id' => $this->storeId,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'version' => $this->version,
            'author' => $this->author,
            'settings' => $this->settings,
            'metadata' => $this->metadata,
        ];
    }
}
