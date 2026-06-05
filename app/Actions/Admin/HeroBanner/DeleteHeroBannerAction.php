<?php

declare(strict_types=1);

namespace App\Actions\Admin\HeroBanner;

use App\DTOs\Admin\HeroBanner\DeleteHeroBannerDTO;
use App\Repositories\HeroBanner\HeroBannerRepository;

class DeleteHeroBannerAction
{
    public function __construct(
        private HeroBannerRepository $repository,
    ) {}

    public function execute(DeleteHeroBannerDTO $dto): bool
    {
        $banner = $this->repository->findByIdOrFail(
            storeId: $dto->storeId,
            id: $dto->bannerId,
        );

        return $this->repository->delete($banner);
    }
}
