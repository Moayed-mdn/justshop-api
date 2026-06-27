<?php

declare(strict_types=1);

namespace App\Services\Theme;

class ResolvedTemplate
{
    public function __construct(
        public readonly int $id,
        public readonly string $handle,
        public readonly string $name,
        public readonly array $sections,
        public readonly array $sectionOrder,
    ) {}

    /**
     * Get section configuration by ID
     */
    public function getSection(string $sectionId): ?array
    {
        return $this->sections[$sectionId] ?? null;
    }

    /**
     * Get section type by ID
     */
    public function getSectionType(string $sectionId): ?string
    {
        return $this->sections[$sectionId]['type'] ?? null;
    }

    /**
     * Get section settings by ID
     */
    public function getSectionSettings(string $sectionId): array
    {
        return $this->sections[$sectionId]['settings'] ?? [];
    }

    /**
     * Check if template has a specific section
     */
    public function hasSection(string $sectionId): bool
    {
        return isset($this->sections[$sectionId]);
    }

    /**
     * Convert to array for API responses
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'handle' => $this->handle,
            'name' => $this->name,
            'sections' => $this->sections,
            'section_order' => $this->sectionOrder,
        ];
    }
}
