<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Actions\Platform\Analytics\GetPlatformAnalyticsAction;
use App\DTOs\Platform\Analytics\GetPlatformAnalyticsDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\PlatformAnalyticsResource;
use App\Policies\Platform\PlatformAnalyticsPolicy;
use Illuminate\Http\JsonResponse;

/**
 * Platform Analytics Controller
 * 
 * Thin controller following architecture rules.
 */
class PlatformAnalyticsController extends Controller
{
    public function __construct(
        private readonly GetPlatformAnalyticsAction $getPlatformAnalyticsAction,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', PlatformAnalyticsPolicy::class);

        $analytics = $this->getPlatformAnalyticsAction->execute(
            new GetPlatformAnalyticsDTO()
        );

        return $this->success(new PlatformAnalyticsResource($analytics));
    }
}
