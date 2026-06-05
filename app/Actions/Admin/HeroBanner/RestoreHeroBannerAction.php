<?php

declare(strict_types=1);

namespace App\Actions\Admin\HeroBanner;

use App\DTOs\Admin\HeroBanner\RestoreHeroBannerDTO;
use App\Repositories\HeroBanner\HeroBannerRepository;

class RestoreHeroBannerAction
{
    public function __construct(
        private HeroBannerRepository $repository,
    ) {}

    public function execute(RestoreHeroBannerDTO $dto): bool
    {
        $banner = $this->repository->findByIdOrFail(
            storeId: $dto->storeId,
            id: $dto->bannerId,
        );

        return $this->repository->restore($banner);
    }
}
