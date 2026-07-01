<?php

declare(strict_types=1);

namespace App\Http\Resources\Theme;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SystemTemplateCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => SystemTemplateResource::collection($this->collection),
        ];
    }
}
