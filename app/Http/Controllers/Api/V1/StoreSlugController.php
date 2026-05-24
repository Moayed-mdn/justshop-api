<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Actions\Store\CheckSlugAvailabilityAction;
use App\Http\Resources\Store\SlugAvailabilityResource;
use App\Traits\ApiResponserTrait;

class StoreSlugController extends Controller
{
    use ApiResponserTrait;

    public function __construct(private readonly CheckSlugAvailabilityAction $checkSlugAvailabilityAction) {}

    public function __invoke(Request $request): JsonResponse
    {
        $slug = $request->input('slug');

        $dto = $this->checkSlugAvailabilityAction->execute($slug);

        return $this->success(new SlugAvailabilityResource($dto));
    }
}
