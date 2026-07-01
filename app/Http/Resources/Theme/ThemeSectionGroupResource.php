<?php

declare(strict_types=1);

namespace App\Http\Resources\Theme;

use App\Models\Theme\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ThemeSectionGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $routeTheme = $request->route('theme');
        $themeSlug = $routeTheme instanceof Theme
            ? $routeTheme->slug
            : $this->theme?->slug;
        $sections = [];

        foreach (($this->sections ?? []) as $sectionId => $section) {
            $sections[(string) $sectionId] = [
                'type' => $section['type'] ?? null,
                'settings' => $section['settings'] ?? [],
            ];
        }

        return [
            'id' => $this->id,
            'theme_id' => $this->theme_id,
            'theme_slug' => $themeSlug,
            'theme_identifier' => $themeSlug,
            'name' => $this->name,
            'handle' => $this->handle,
            'sections' => (object) $sections,
            'order' => $this->order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
