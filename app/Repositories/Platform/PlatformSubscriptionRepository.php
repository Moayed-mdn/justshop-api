<?php

declare(strict_types=1);

namespace App\Repositories\Platform;

use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read-side repository for platform-admin subscription views.
 *
 * Mirrors the shape of PlatformUserRepository::list() — search/status
 * filters, sortable, paginated — so the two list endpoints behave
 * consistently for whoever builds the admin frontend against them.
 */
class PlatformSubscriptionRepository
{
    private const ALLOWED_SORTS = ['created_at', 'current_period_ends_at', 'status'];

    public function list(
        ?string $search = null,
        ?string $status = null,
        ?int $planId = null,
        string $sortBy = 'created_at',
        string $sortOrder = 'desc',
        int $perPage = 25,
    ): LengthAwarePaginator {
        $query = Subscription::query()
            ->with(['plan', 'planPrice', 'billingAccount.owner']);

        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        if ($planId !== null) {
            $query->where('plan_id', $planId);
        }

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('provider_subscription_id', 'LIKE', "%{$search}%")
                    ->orWhereHas('billingAccount', function ($ba) use ($search) {
                        $ba->where('legal_name', 'LIKE', "%{$search}%")
                            ->orWhere('billing_email', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('billingAccount.owner', function ($u) use ($search) {
                        $u->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%");
                    });
            });
        }

        $sortBy = in_array($sortBy, self::ALLOWED_SORTS, true) ? $sortBy : 'created_at';
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    public function findWithRelations(int $id): ?Subscription
    {
        return Subscription::with([
            'plan',
            'planPrice',
            'pendingPlan',
            'billingAccount.owner',
            'billingAccount.owner.stores',
            'invoices' => fn ($q) => $q->orderByDesc('issued_at')->limit(20),
            'events' => fn ($q) => $q->with('actor')->orderByDesc('created_at')->limit(50),
        ])->find($id);
    }
}
