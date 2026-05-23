<?php

declare(strict_types=1);

namespace App\Models\Auth;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImpersonationRecord extends Model
{
    protected $fillable = [
        'initiator_id',
        'target_id',
        'reason',
        'approval_metadata',
        'expires_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected $casts = [
        'approval_metadata' => 'array',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
