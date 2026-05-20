<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Cms\Documentation;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'section_id' => $this->section_id,
            'parent_id' => $this->parent_id,
            'version' => $this->version,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'published_at' => $this->published_at,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'canonical_url' => $this->canonical_url,
            'og_image' => $this->og_image,
            'robots' => $this->robots,
            'index_controls' => $this->index_controls,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
