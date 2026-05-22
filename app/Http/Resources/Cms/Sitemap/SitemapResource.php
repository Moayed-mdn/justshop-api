<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Sitemap;

use App\DTOs\Cms\Sitemap\SitemapEntryDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a single sitemap entry for JSON API response.
 * Next.js consumes this to generate its own sitemap.ts.
 *
 * @mixin SitemapEntryDTO
 */
class SitemapResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var SitemapEntryDTO $entry */
        $entry = $this->resource;

        return $entry->toArray();
    }
}
