<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Store\Admin;

use App\DTOs\Cms\Marketing\Store\Admin\DeleteStoreMarketingPageDTO;
use App\Repositories\Cms\Marketing\Store\StoreMarketingPageRepository;

class DeleteStoreMarketingPageAction
{
    public function __construct(
        private readonly StoreMarketingPageRepository $repository,
    ) {}

    public function execute(DeleteStoreMarketingPageDTO $dto): void
    {
        $page = $this->repository->findByIdOrFail($dto->storeId, $dto->id);

        $this->repository->delete($page);
    }
}
