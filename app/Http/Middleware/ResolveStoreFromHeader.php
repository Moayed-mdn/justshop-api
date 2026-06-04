<?php

namespace App\Http\Middleware;

use App\Enums\Store\StoreStatusEnum;
use App\Exceptions\Store\StoreDisabledException;
use App\Exceptions\Store\StoreNotFoundException;
use App\Models\Store;
use App\Services\Auth\Membership\MembershipResolver;
use App\Support\Observability\RequestTraceContextManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResolveStoreFromHeader
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
        private readonly MembershipResolver $membershipResolver,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $storeId = $request->header('X-Tenant-Id');

        if ($storeId === null || $storeId === '') {
            return $next($request);
        }

        if (preg_match('/^store_(\d+)$/', $storeId, $matches)) {
            $storeId = (int) $matches[1];
        }

        $store = Store::find($storeId);

        if (!$store instanceof Store) {
            throw new StoreNotFoundException();
        }

        if ($store->status === StoreStatusEnum::DISABLED) {
            throw new StoreDisabledException();
        }

        if (!$store->isOperational()) {
            throw new StoreNotFoundException();
        }

        app()->instance('storeId', $store->id);
        app()->instance('currentStore', $store);

        $user = $request->user();
        $membership = $user ? $this->membershipResolver->resolveForStore($user, $store) : null;

        $this->traceContext->enrichStore(
            storeId: $store->id,
            membershipId: $membership?->membershipId,
        );

        Log::info('store.context.enriched', [
            'store_id' => (int) $store->id,
            'membership_id' => $membership?->membershipId,
            'membership_role' => $membership?->role,
            'membership_source' => $membership?->source,
            'source' => 'x-tenant-id-header',
        ]);

        return $next($request);
    }
}
