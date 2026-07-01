<?php

declare(strict_types=1);

namespace App\Http\Resources\Theme;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemTemplateSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sectionType = $this->type?->value ?? $this->type;
        $overrides = $this->pivot->overrides
            ? (is_string($this->pivot->overrides) ? json_decode($this->pivot->overrides, true) : $this->pivot->overrides)
            : [];

        return [
            'id' => $this->id,
            'section_type' => $sectionType,
            'position' => $this->pivot->position ?? 0,
            'overrides' => $this->normalizeLocalizedSettings($sectionType, is_array($overrides) ? $overrides : []),
            'settings' => $this->normalizeLocalizedSettings($sectionType, is_array($this->settings) ? $this->settings : []),
            'is_visible' => (bool) ($this->pivot?->is_enabled ?? $this->is_enabled),
            'blocks' => ThemeBlockResource::collection($this->whenLoaded('blocks')),
        ];
    }

    private function normalizeLocalizedSettings(string $sectionType, array $settings): array
    {
        $localizedFields = match ($sectionType) {
            'announcement_bar' => ['text', 'offer_text', 'shop_now_text'],
            'copyright_bar' => ['text'],
            default => [],
        };

        foreach ($localizedFields as $field) {
            if (isset($settings[$field]) && is_string($settings[$field])) {
                $settings[$field] = ['en' => $settings[$field], 'ar' => ''];
            }
        }

        return $settings;
    }
}
