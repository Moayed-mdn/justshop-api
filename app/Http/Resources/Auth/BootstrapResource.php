<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use App\Services\Auth\Bootstrap\BootstrapPayloadSerializer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BootstrapResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\DTOs\Auth\Bootstrap\GetBootstrapResponseDTO $dto */
        $dto = $this->resource;

        return BootstrapPayloadSerializer::toArray($dto);
    }
}
