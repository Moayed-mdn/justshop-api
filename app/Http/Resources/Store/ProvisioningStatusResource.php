<?php

declare(strict_types=1);

namespace App\Http\Resources\Store;

use App\DTOs\Store\ProvisioningStatusDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvisioningStatusResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var ProvisioningStatusDTO $dto */
        $dto = $this->resource;

        return [
            'status' => $dto->status->value,
            'progress' => $dto->progress,
            'current_step' => $dto->currentStep,
            'message' => $dto->message,
            'retryable' => $dto->retryable,
        ];
    }
}
