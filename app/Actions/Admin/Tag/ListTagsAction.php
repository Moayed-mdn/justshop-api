<?php

namespace App\Actions\Admin\Tag;

use App\DTOs\Admin\Tag\ListTagsDTO;
use App\Repositories\Admin\Tag\AdminTagRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListTagsAction
{
    public function __construct(
        private AdminTagRepository $repository,
    ) {}

    public function execute(ListTagsDTO $dto): LengthAwarePaginator
    {
        // Super admin sees all store tags + global tags.
        // Store-level users see store tags + global tags.
        // Both use includeGlobal = true (default behaviour).
        return $this->repository->listForStore(
            storeId:       $dto->storeId,
            search:        $dto->search,
            type:          $dto->type,
            active:        $dto->active,
            includeGlobal: true,
            perPage:       $dto->perPage,
        );
    }
}
