<?php

namespace App\DTOs\Theme;

readonly class UpdateThemeDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $description = null,
        public ?string $version = null,
        public ?string $author = null,
        public ?array $settings = null,
        public ?array $metadata = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            version: $data['version'] ?? null,
            author: $data['author'] ?? null,
            settings: $data['settings'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'version' => $this->version,
            'author' => $this->author,
            'settings' => $this->settings,
            'metadata' => $this->metadata,
        ], fn($value) => $value !== null);
    }
}
