<?php

declare(strict_types=1);

namespace App\Actions\Admin\HeroBanner;

use App\DTOs\Admin\HeroBanner\ListHeroBannersDTO;
use App\Repositories\HeroBanner\HeroBannerRepository;
use Illuminate\Database\Eloquent\Collection;

class ListHeroBannersAction
{
    public function __construct(
        private HeroBannerRepository $repository,
    ) {}

    public function execute(ListHeroBannersDTO $dto): Collection
    {
        return $this->repository->list(
            storeId: $dto->storeId,
            status: $dto->status,
            search: $dto->search,
        );
    }
}
