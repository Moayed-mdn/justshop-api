<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Actions\Platform\Dashboard\GetCmsStatsAction;
use App\Actions\Platform\Dashboard\GetPlatformDashboardStatsAction;
use App\DTOs\Platform\Dashboard\GetCmsStatsDTO;
use App\DTOs\Platform\Dashboard\GetPlatformDashboardStatsDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\Platform\CmsStatsResource;
use App\Http\Resources\Platform\PlatformDashboardStatsResource;
use Illuminate\Http\JsonResponse;

/**
 * Platform Dashboard Controller
 * 
 * Wave 6: Platform authority domain controller.
 * Platform authority is INDEPENDENT from merchant authority.
 */
class PlatformDashboardController extends Controller
{
    public function __construct(
        private readonly GetPlatformDashboardStatsAction $getDashboardStatsAction,
        private readonly GetCmsStatsAction $getCmsStatsAction,
    ) {}

    public function index(): JsonResponse
    {
        $stats = $this->getDashboardStatsAction->execute(
            new GetPlatformDashboardStatsDTO()
        );

        return $this->success(
            new PlatformDashboardStatsResource($stats)
        );
    }

    public function cmsStats(): JsonResponse
    {
        $stats = $this->getCmsStatsAction->execute(
            new GetCmsStatsDTO()
        );

        return $this->success(
            new CmsStatsResource($stats)
        );
    }
}

