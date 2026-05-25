<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Platform\Admin;

use App\DTOs\Cms\Marketing\Platform\Admin\ListPlatformMarketingPagesDTO;
use App\Repositories\Cms\Marketing\Platform\PlatformMarketingPageRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ListPlatformMarketingPagesAction
{
    public function __construct(
        private PlatformMarketingPageRepository $repository,
    ) {}

    public function execute(ListPlatformMarketingPagesDTO $dto): LengthAwarePaginator
    {
        return $this->repository->paginateAdmin($dto);
    }
}
