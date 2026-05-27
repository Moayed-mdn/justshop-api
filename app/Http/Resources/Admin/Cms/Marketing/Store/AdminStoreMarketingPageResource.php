<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Cms\Marketing\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin response resource for StoreMarketingPage.
 *
 * Mirrors the shape of AdminPlatformMarketingPageResource with the
 * addition of store_id and sections as a typed collection.
 *
 * @mixin \App\Models\Cms\Marketing\Store\StoreMarketingPage
 */
class AdminStoreMarketingPageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'store_id'     => $this->store_id,

            // Localized fields — raw maps returned to admin; frontend resolves locale
            'title'        => $this->title,
            'slug'         => $this->slug,
            'excerpt'      => $this->excerpt,
            'content'      => $this->content,

            // Publishing
            'status'       => $this->status instanceof \BackedEnum
                ? $this->status->value
                : $this->status,
            'published_at' => $this->published_at?->toIso8601String(),

            // Metadata
            'template'     => $this->template instanceof \BackedEnum
                ? $this->template->value
                : $this->template,
            'sort_order'   => $this->sort_order,

            // SEO — raw map; admin editor needs full structure
            'seo'          => $this->seo,

            // Audit
            'created_at'   => $this->created_at?->toIso8601String(),
            'updated_at'   => $this->updated_at?->toIso8601String(),
            'creator'      => $this->whenLoaded('creator', fn () => $this->creator ? [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ] : null),
            'updater'      => $this->whenLoaded('updater', fn () => $this->updater ? [
                'id'   => $this->updater->id,
                'name' => $this->updater->name,
            ] : null),

            // Sections — typed collection, only when loaded
            'sections'     => $this->whenLoaded(
                'sections',
                fn () => StoreMarketingSectionResource::collection($this->sections)
            ),
        ];
    }
}
