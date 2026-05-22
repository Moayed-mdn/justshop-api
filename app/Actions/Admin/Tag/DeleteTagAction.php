<?php

namespace App\Actions\Admin\Tag;

use App\Enums\RoleEnum;
use App\Exceptions\Store\UnauthorizedStoreAccessException;
use App\Repositories\Admin\Tag\AdminTagRepository;
use Illuminate\Support\Facades\Auth;

class DeleteTagAction
{
    public function __construct(
        private AdminTagRepository $repository,
    ) {}

    /**
     * Soft-delete a store-owned tag.
     *
     * Only store-owned tags (store_id = $storeId) can be deleted.
     * Global tags (store_id = null) are protected from store-level deletion.
     */
    public function execute(int $storeId, int $tagId): void
    {
        // findStoreOwnedTag throws TagNotFoundException if not found
        // or if the tag is global (not owned by this store).
        $tag = $this->repository->findStoreOwnedTag($tagId, $storeId);

        $this->repository->softDelete($tag);
    }
}
