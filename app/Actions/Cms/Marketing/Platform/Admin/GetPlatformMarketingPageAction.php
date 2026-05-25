<?php

declare(strict_types=1);

namespace App\Actions\Cms\Marketing\Platform\Admin;

use App\DTOs\Cms\Marketing\Platform\Admin\GetPlatformMarketingPageDTO;
use App\Models\Cms\Marketing\Platform\PlatformMarketingPage;
use App\Repositories\Cms\Marketing\Platform\PlatformMarketingPageRepository;

class GetPlatformMarketingPageAction
{
    public function __construct(
        private PlatformMarketingPageRepository $repository,
    ) {}

    public function execute(GetPlatformMarketingPageDTO $dto): PlatformMarketingPage
    {
        return $this->repository->findByIdOrFail($dto->id);
    }
}
