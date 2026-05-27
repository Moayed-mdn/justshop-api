<?php

namespace App\Http\Middleware;

use App\Exceptions\Store\StoreNotFoundException;
use App\Exceptions\Store\StoreDisabledException;
use App\Enums\Store\StoreStatusEnum;
use App\Models\Store;
use App\Support\Observability\RequestTraceContextManager;
use App\Services\Auth\Membership\MembershipResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StoreContext
{
    public function __construct(
        private readonly RequestTraceContextManager $traceContext,
        private readonly MembershipResolver $membershipResolver,
    ) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $store = $request->route('store');
        $user = $request->user();

        if (!($store instanceof Store)) {
            $store = Store::find($store);
        }

        if (!$store instanceof Store) {
            if ($user && $user->last_active_store_id == $request->route('store')) {
                $user->update(['last_active_store_id' => null]);
            }

            throw new StoreNotFoundException();
        }

        if ($store->status === StoreStatusEnum::DISABLED) {
            throw new StoreDisabledException();
        }

        if (!$store->isOperational()) {
            if ($user && $user->last_active_store_id == $request->route('store')) {
                $user->update(['last_active_store_id' => null]);
            }

            throw new StoreNotFoundException();
        }

        app()->instance('storeId', $store->id);
        app()->instance('currentStore', $store);

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
        ]);

        return $next($request);
    }
}
