<?php

namespace App\Actions\Store;

use App\DTOs\Store\UpdateStoreDTO;
use App\Models\Store;
use App\Repositories\Store\StoreRepository;
use App\Services\Store\StoreSlugService;
use Illuminate\Validation\ValidationException;

class UpdateStoreAction
{
    public function __construct(
        private StoreRepository $storeRepository,
        private StoreSlugService $slugService,
    ) {}

    public function execute(UpdateStoreDTO $dto): Store
    {
        // If a slug update is requested, validate availability excluding the current store.
        if ($dto->slug !== null) {
            $normalizedSlug = $this->slugService->normalize($dto->slug);
            $dto->slug = $normalizedSlug;

            if (!$this->slugService->isAvailable($normalizedSlug, $dto->storeId)) {
                throw ValidationException::withMessages([
                    'slug' => __('store.slug_taken'),
                ]);
            }
        }

        return $this->storeRepository->update($dto);
    }
}
