<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Platform;

use App\Actions\Platform\Features\GetFeatureFlagsAction;
use App\Actions\Platform\Features\UpdateFeatureFlagAction;
use App\DTOs\Platform\Features\UpdateFeatureFlagDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\Features\UpdateFeatureFlagRequest;
use App\Http\Resources\Platform\FeatureFlagResource;
use App\Policies\Platform\FeatureFlagPolicy;
use Illuminate\Http\JsonResponse;

/**
 * Platform Feature Flag Controller
 * 
 * Thin controller following architecture rules.
 */
class PlatformFeatureController extends Controller
{
    public function __construct(
        private readonly GetFeatureFlagsAction $getFeatureFlagsAction,
        private readonly UpdateFeatureFlagAction $updateFeatureFlagAction,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', FeatureFlagPolicy::class);

        $features = $this->getFeatureFlagsAction->execute();

        return $this->success(FeatureFlagResource::collection($features));
    }

    public function update(string $feature, UpdateFeatureFlagRequest $request): JsonResponse
    {
        $this->authorize('update', FeatureFlagPolicy::class);

        $updatedFeature = $this->updateFeatureFlagAction->execute(
            UpdateFeatureFlagDTO::fromRequest($request, $feature)
        );

        return $this->success(
            new FeatureFlagResource($updatedFeature),
            __('platform.feature_flag_updated')
        );
    }
}
