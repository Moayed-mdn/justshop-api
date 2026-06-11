<?php

namespace App\Models;

use App\Enums\Billing\WebhookStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StripeWebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'provider_event_id',
        'event_type',
        'status',
        'attempts',
        'received_at',
        'processed_at',
        'payload',
        'error_message',
    ];

    protected $casts = [
        'status'       => WebhookStatusEnum::class,
        'attempts'     => 'integer',
        'received_at'  => 'datetime',
        'processed_at' => 'datetime',
        'payload'      => 'array',
    ];

    /**
     * Check if event has been processed.
     */
    public function isProcessed(): bool
    {
        return $this->status === WebhookStatusEnum::PROCESSED;
    }

    /**
     * Check if event failed.
     */
    public function isFailed(): bool
    {
        return $this->status === WebhookStatusEnum::FAILED;
    }

    /**
     * Check if event is being processed.
     */
    public function isProcessing(): bool
    {
        return $this->status === WebhookStatusEnum::PROCESSING;
    }

    /**
     * Check if event should be retried.
     */
    public function shouldRetry(int $maxAttempts = 5): bool
    {
        return $this->isFailed() && $this->attempts < $maxAttempts;
    }

    /**
     * Increment attempts counter.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Mark as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => WebhookStatusEnum::PROCESSING->value,
        ]);
    }

    /**
     * Mark as processed.
     */
    public function markAsProcessed(): void
    {
        $this->update([
            'status'       => WebhookStatusEnum::PROCESSED->value,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status'        => WebhookStatusEnum::FAILED->value,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Scope to get unprocessed events.
     */
    public function scopeUnprocessed($query)
    {
        return $query->where('status', WebhookStatusEnum::RECEIVED->value);
    }

    /**
     * Scope to get failed events that can be retried.
     */
    public function scopeRetryable($query, int $maxAttempts = 5)
    {
        return $query->where('status', WebhookStatusEnum::FAILED->value)
            ->where('attempts', '<', $maxAttempts);
    }
}
