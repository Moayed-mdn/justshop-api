<?php

declare(strict_types=1);

namespace App\Actions\Admin\HeroBanner;

use App\DTOs\Admin\HeroBanner\UpdateHeroBannerDTO;
use App\Models\HeroBanner;
use App\Repositories\HeroBanner\HeroBannerRepository;
use Illuminate\Support\Facades\DB;

class UpdateHeroBannerAction
{
    public function __construct(
        private HeroBannerRepository $repository,
    ) {}

    public function execute(UpdateHeroBannerDTO $dto): HeroBanner
    {
        return DB::transaction(function () use ($dto) {
            $banner = $this->repository->findByIdOrFail(
                storeId: $dto->storeId,
                id: $dto->bannerId,
            );

            $bannerData = [
                'cat_url' => $dto->catUrl,
                'position' => $dto->position,
                'visual_type' => $dto->visualType,
                'image_path' => $dto->imagePath,
                'gradient_from' => $dto->gradientFrom,
                'gradient_to' => $dto->gradientTo,
                'video_url' => $dto->videoUrl,
                'link_url' => $dto->linkUrl,
                'link_text' => $dto->linkText,
                'link_target' => $dto->linkTarget,
                'is_active' => $dto->isActive,
                'starts_at' => $dto->startsAt,
                'ends_at' => $dto->endsAt,
            ];

            return $this->repository->update(
                banner: $banner,
                data: $bannerData,
                translations: $dto->translations,
            );
        });
    }
}
