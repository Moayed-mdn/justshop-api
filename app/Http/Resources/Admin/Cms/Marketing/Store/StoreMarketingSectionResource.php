<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Cms\Marketing\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Cms\Marketing\Store\StoreMarketingSection
 */
class StoreMarketingSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'section_type' => $this->section_type instanceof \BackedEnum
                ? $this->section_type->value
                : $this->section_type,
            'identifier'   => $this->identifier,
            'sort_order'   => $this->sort_order,
            'title'        => $this->title,
            'subtitle'     => $this->subtitle,
            'content'      => $this->content,
            'settings'     => $this->settings,
            'is_active'    => $this->is_active,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
