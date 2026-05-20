<?php

declare(strict_types=1);

namespace App\Actions\Cms\MarketingPage\Admin;

use App\DTOs\Cms\MarketingPage\Admin\ListMarketingPagesDTO;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ListMarketingPagesAction
{
    public function __construct(
        private MarketingPageRepository $repository,
    ) {}

    public function execute(ListMarketingPagesDTO $dto): LengthAwarePaginator
    {
        return $this->repository->paginateAdmin($dto);
    }
}
