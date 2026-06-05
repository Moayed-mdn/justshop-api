<?php

declare(strict_types=1);

namespace App\Actions\Admin\HeroBanner;

use App\DTOs\Admin\HeroBanner\CreateHeroBannerDTO;
use App\Models\HeroBanner;
use App\Repositories\HeroBanner\HeroBannerRepository;
use Illuminate\Support\Facades\DB;

class CreateHeroBannerAction
{
    public function __construct(
        private HeroBannerRepository $repository,
    ) {}

    public function execute(CreateHeroBannerDTO $dto): HeroBanner
    {
        return DB::transaction(function () use ($dto) {
            $bannerData = [
                'store_id' => $dto->storeId,
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

            return $this->repository->create(
                data: $bannerData,
                translations: $dto->translations,
            );
        });
    }
}
