<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use Illuminate\Http\Request;

class GetBootstrapDTO
{
    public function __construct(
        public int $userId,
        public Request $request,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            (int) $request->user()->id,
            $request,
        );
    }
}
