<?php

declare(strict_types=1);

namespace App\DTOs\Admin\HeroBanner;

use App\Enums\HeroBanner\HeroLinkTargetEnum;
use App\Enums\HeroBanner\HeroVisualTypeEnum;
use App\Http\Requests\Admin\HeroBanner\CreateHeroBannerRequest;

class CreateHeroBannerDTO
{
    public function __construct(
        public int $storeId,
        public string $catUrl,
        public int $position,
        public HeroVisualTypeEnum $visualType,
        public ?string $imagePath,
        public ?string $gradientFrom,
        public ?string $gradientTo,
        public ?string $videoUrl,
        public ?string $linkUrl,
        public ?string $linkText,
        public ?HeroLinkTargetEnum $linkTarget,
        public bool $isActive,
        public ?string $startsAt,
        public ?string $endsAt,
        public array $translations,
    ) {}

    public static function fromRequest(CreateHeroBannerRequest $request, int $storeId): self
    {
        return new self(
            storeId: $storeId,
            catUrl: $request->string('cat_url')->toString(),
            position: $request->integer('position'),
            visualType: HeroVisualTypeEnum::from($request->string('visual_type')->toString()),
            imagePath: $request->string('image_path')->toString() ?: null,
            gradientFrom: $request->string('gradient_from')->toString() ?: null,
            gradientTo: $request->string('gradient_to')->toString() ?: null,
            videoUrl: $request->string('video_url')->toString() ?: null,
            linkUrl: $request->string('link_url')->toString() ?: null,
            linkText: $request->string('link_text')->toString() ?: null,
            linkTarget: $request->has('link_target') 
                ? HeroLinkTargetEnum::from($request->string('link_target')->toString())
                : null,
            isActive: $request->boolean('is_active'),
            startsAt: $request->string('starts_at')->toString() ?: null,
            endsAt: $request->string('ends_at')->toString() ?: null,
            translations: $request->input('translations', []),
        );
    }
}
