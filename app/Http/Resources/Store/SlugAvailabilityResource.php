<?php

declare(strict_types=1);

namespace App\Http\Resources\Store;

use App\DTOs\Store\SlugAvailabilityDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SlugAvailabilityResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var SlugAvailabilityDTO $dto */
        $dto = $this->resource;

        return [
            'available' => $dto->available,
            'reason' => $dto->reason,
        ];
    }
}
