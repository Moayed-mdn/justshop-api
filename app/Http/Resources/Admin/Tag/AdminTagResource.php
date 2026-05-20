<?php

namespace App\Http\Resources\Admin\Tag;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * AdminTagResource — serializes a Tag with all locale translations.
 *
 * Used by the tag management API.
 * Tags are store-scoped entities. name and slug live in tag_translations.
 * This resource always expects translations to be eager-loaded.
 *
 * Response shape:
 * {
 *   "id": 1,
 *   "store_id": 3,
 *   "type": "general",
 *   "color": "#FF0000",
 *   "is_active": true,
 *   "translations": {
 *     "en": { "locale": "en", "name": "Summer", "slug": "summer" },
 *     "ar": { "locale": "ar", "name": "صيف",    "slug": "summer-ar" }
 *   },
 *   "created_at": "...",
 *   "updated_at": "..."
 * }
 */
class AdminTagResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->id,
            'store_id'     => $this->store_id,
            'type'         => $this->type,
            'color'        => $this->color,
            'is_active'    => (bool) $this->is_active,
            'translations' => $this->buildTranslations(),
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }

    /**
     * Build all translations keyed by locale.
     *
     * Reads from the pre-loaded translations collection.
     * No N+1 risk — expects translations to be eager-loaded by the repository.
     * Returns empty object if translations relation not loaded.
     */
    private function buildTranslations(): array
    {
        if (!$this->relationLoaded('translations')) {
            return [];
        }

        return $this->translations
            ->keyBy('locale')
            ->map(fn($t) => [
                'locale' => $t->locale,
                'name'   => $t->name,
                'slug'   => $t->slug,
            ])
            ->toArray();
    }
}
