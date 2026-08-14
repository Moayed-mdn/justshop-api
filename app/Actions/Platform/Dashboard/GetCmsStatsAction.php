<?php

declare(strict_types=1);

namespace App\Actions\Platform\Dashboard;

use App\DTOs\Platform\Dashboard\GetCmsStatsDTO;
use App\Repositories\Platform\PlatformDashboardRepository;

class GetCmsStatsAction
{
    public function __construct(
        private readonly PlatformDashboardRepository $repository,
    ) {}

    public function execute(GetCmsStatsDTO $dto): array
    {
        return [
            'blog' => $this->repository->getBlogStats(),
            'pages' => $this->repository->getMarketingPagesStats(),
            'docs' => $this->repository->getDocumentationStats(),
        ];
    }
}
