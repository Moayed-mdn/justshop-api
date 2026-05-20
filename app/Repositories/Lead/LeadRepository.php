<?php

declare(strict_types=1);

namespace App\Repositories\Lead;

use App\DTOs\Lead\ListLeadsDTO;
use App\Enums\Lead\LeadStatusEnum;
use App\Exceptions\Lead\LeadNotFoundException;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class LeadRepository
{
    public function create(array $attributes): Lead
    {
        return Lead::query()->create($attributes);
    }

    public function findByIdOrFail(int $id): Lead
    {
        $lead = Lead::query()
            ->with('resolvedByUser:id,name,email')
            ->find($id);

        if ($lead === null) {
            throw new LeadNotFoundException();
        }

        return $lead;
    }

    public function paginate(ListLeadsDTO $dto): LengthAwarePaginator
    {
        $query = Lead::query()
            ->with('resolvedByUser:id,name,email')
            ->latest();

        if ($dto->type !== null) {
            $query->where('type', $dto->type->value);
        }

        if ($dto->status instanceof LeadStatusEnum) {
            $query->where('status', $dto->status->value);
        }

        if ($dto->email !== null) {
            $query->where('email', 'like', '%' . $dto->email . '%');
        }

        if ($dto->createdFrom !== null) {
            $query->where('created_at', '>=', Carbon::parse($dto->createdFrom)->startOfDay());
        }

        if ($dto->createdTo !== null) {
            $query->where('created_at', '<=', Carbon::parse($dto->createdTo)->endOfDay());
        }

        return $query->paginate($dto->perPage);
    }

    public function findRecentDuplicate(
        string $email,
        string $message,
        ?string $ipAddress,
        Carbon $since,
    ): ?Lead {
        return Lead::query()
            ->where('email', $email)
            ->where('message', $message)
            ->where('ip_address', $ipAddress)
            ->where('created_at', '>=', $since)
            ->first();
    }

    public function updateStatus(Lead $lead, array $attributes): Lead
    {
        $lead->update($attributes);

        return $lead->fresh(['resolvedByUser:id,name,email']) ?? $lead;
    }

    public function delete(Lead $lead): void
    {
        $lead->delete();
    }

    public function listAdminRecipients(): Collection
    {
        return User::query()
            ->role(\App\Enums\RoleEnum::SUPER_ADMIN->value)
            ->whereNotNull('email')
            ->get(['id', 'name', 'email']);
    }
}
