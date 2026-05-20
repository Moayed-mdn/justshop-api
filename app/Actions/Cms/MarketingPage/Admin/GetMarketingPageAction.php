<?php

declare(strict_types=1);

namespace App\Actions\Cms\MarketingPage\Admin;

use App\DTOs\Cms\MarketingPage\Admin\GetMarketingPageDTO;
use App\Models\Cms\MarketingPage;
use App\Repositories\Cms\MarketingPage\MarketingPageRepository;

class GetMarketingPageAction
{
    public function __construct(
        private MarketingPageRepository $repository,
    ) {}

    public function execute(GetMarketingPageDTO $dto): MarketingPage
    {
        return $this->repository->findByIdOrFail($dto->id);
    }
}
