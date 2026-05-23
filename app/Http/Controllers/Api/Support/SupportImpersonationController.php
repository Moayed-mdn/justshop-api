<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use App\Services\Platform\Impersonation\ImpersonationLifecycleManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Support Impersonation Controller
 * 
 * Wave 6: GOVERNED impersonation only.
 * NOT unrestricted impersonation.
 */
class SupportImpersonationController extends Controller
{
    public function __construct(
        private readonly ImpersonationLifecycleManager $impersonationManager,
    ) {}

    public function request(Request $request): JsonResponse
    {
        // Wave 6: Stub implementation
        // Future: Implement impersonation request with approval workflow
        return response()->json([
            'message' => 'Impersonation request created',
            'status' => 'pending',
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $active = $this->impersonationManager->getActive($user);

        return response()->json([
            'active_impersonation' => $active,
        ]);
    }

    public function terminate(Request $request): JsonResponse
    {
        // Wave 6: Stub implementation
        // Future: Implement impersonation termination
        return response()->json([
            'message' => 'Impersonation terminated',
        ]);
    }
}
