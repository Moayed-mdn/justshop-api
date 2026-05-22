<?php

namespace App\Http\Middleware;

use App\Exceptions\Store\StoreNotFoundException;
use App\Models\Store;
use App\Models\User;
use App\Support\Observability\RequestTraceContextManager;
use Closure;
use Illuminate\Http\Request;

class StoreContext
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $storeId = $request->route('store');
        $user = $request->user();

        $store = Store::where('id', $storeId)
            ->where('is_active', true)
            ->first();

        if (!$store) {
            // Auto-heal: If the user's last_active_store_id points to a non-existent or inactive store
            if ($user && $user->last_active_store_id == $storeId) {
                $user->update(['last_active_store_id' => null]);
            }
            throw new StoreNotFoundException();
        }

        // Store context is request-scoped
        app()->instance('storeId', $store->id);
        app()->instance('currentStore', $store);
        $this->traceContext->enrichStore(
            storeId: $store->id,
            membershipId: $this->resolveMembershipId($user, $store->id),
        );

        return $next($request);
    }

    private function resolveMembershipId(?User $user, int $storeId): ?int
    {
        if (!$user) {
            return null;
        }

        $storeMembership = $user->stores()
            ->where('store_id', $storeId)
            ->first();

        return $storeMembership?->pivot?->id;
    }
}
