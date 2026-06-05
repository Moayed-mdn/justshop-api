<?php

declare(strict_types=1);

namespace App\Actions\Admin\HeroBanner;

use App\DTOs\Admin\HeroBanner\ShowHeroBannerDTO;
use App\Models\HeroBanner;
use App\Repositories\HeroBanner\HeroBannerRepository;

class ShowHeroBannerAction
{
    public function __construct(
        private HeroBannerRepository $repository,
    ) {}

    public function execute(ShowHeroBannerDTO $dto): HeroBanner
    {
        return $this->repository->findByIdOrFail(
            storeId: $dto->storeId,
            id: $dto->bannerId,
        );
    }
}
