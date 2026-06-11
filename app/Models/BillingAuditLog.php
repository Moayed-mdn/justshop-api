<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingAuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'billing_account_id',
        'actor_user_id',
        'actor_type',
        'action',
        'subject_type',
        'subject_id',
        'ip_address',
        'user_agent',
        'changes',
    ];

    protected $casts = [
        'billing_account_id' => 'integer',
        'actor_user_id'      => 'integer',
        'subject_id'         => 'integer',
        'changes'            => 'array',
    ];

    public function billingAccount(): BelongsTo
    {
        return $this->belongsTo(BillingAccount::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Get the subject model.
     */
    public function subject()
    {
        if (!$this->subject_type || !$this->subject_id) {
            return null;
        }

        return $this->subject_type::find($this->subject_id);
    }

    /**
     * Check if action was performed by user.
     */
    public function isByUser(): bool
    {
        return $this->actor_type === 'user';
    }

    /**
     * Check if action was performed by system.
     */
    public function isBySystem(): bool
    {
        return $this->actor_type === 'system';
    }

    /**
     * Check if action was performed by webhook.
     */
    public function isByWebhook(): bool
    {
        return $this->actor_type === 'webhook';
    }

    /**
     * Scope to get logs by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get logs by actor type.
     */
    public function scopeByActorType($query, string $actorType)
    {
        return $query->where('actor_type', $actorType);
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
