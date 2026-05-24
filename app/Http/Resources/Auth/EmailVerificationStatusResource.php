<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use App\DTOs\Auth\EmailVerificationStatusDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmailVerificationStatusResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var EmailVerificationStatusDTO $dto */
        $dto = $this->resource;

        return [
            'email_verified' => $dto->emailVerified,
            'email_verified_at' => $dto->emailVerifiedAt,
        ];
    }
}
