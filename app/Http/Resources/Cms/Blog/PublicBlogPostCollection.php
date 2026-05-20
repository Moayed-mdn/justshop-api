<?php

declare(strict_types=1);

namespace App\Http\Resources\Cms\Blog;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PublicBlogPostCollection extends ResourceCollection
{
    public function toArray($request): array
    {
        return [
            'data' => PublicBlogPostResource::collection($this->collection),
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ],
        ];
    }
}
