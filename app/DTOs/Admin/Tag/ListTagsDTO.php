<?php

namespace App\DTOs\Admin\Tag;

use App\Http\Requests\Admin\Tag\ListTagsRequest;

class ListTagsDTO
{
    /**
     * @param  int          $storeId
     * @param  string|null  $search         Filter by translated name.
     * @param  string|null  $type           Filter by tag type.
     * @param  bool|null    $active         Filter by is_active status.
     * @param  int          $perPage
     */
    public function __construct(
        public int     $storeId,
        public ?string $search  = null,
        public ?string $type    = null,
        public ?bool   $active  = null,
        public int     $perPage = 15,
    ) {}

    public static function fromRequest(
        ListTagsRequest $request,
        int $storeId,
    ): self {
        return new self(
            storeId:  $storeId,
            search:   $request->input('search'),
            type:     $request->input('type'),
            active:   $request->exists('active')
                ? $request->boolean('active')
                : null,
            perPage:  (int) $request->input('per_page', 15),
        );
    }
}
