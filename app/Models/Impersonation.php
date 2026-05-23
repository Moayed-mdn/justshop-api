<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Impersonation Model
 * 
 * Wave 6: Governed impersonation persistence.
 * 
 * @property int $id
 * @property int $initiator_id
 * @property int $target_id
 * @property string $reason
 * @property string $status
 * @property \Carbon\Carbon $requested_at
 * @property \Carbon\Carbon|null $activated_at
 * @property \Carbon\Carbon|null $terminated_at
 * @property \Carbon\Carbon $expires_at
 * @property string|null $termination_reason
 * @property string|null $approval_token
 * @property string|null $session_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Impersonation extends Model
{
    use HasFactory;

    protected $fillable = [
        'initiator_id',
        'target_id',
        'reason',
        'status',
        'requested_at',
        'activated_at',
        'terminated_at',
        'expires_at',
        'termination_reason',
        'approval_token',
        'session_id',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'activated_at' => 'datetime',
        'terminated_at' => 'datetime',
        'expires_at' => 'datetime',
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
        return $this->status === 'active' && $this->expires_at > now();
    }

    public function isExpired(): bool
    {
        return $this->expires_at <= now();
    }
}
