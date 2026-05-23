<?php

declare(strict_types=1);

namespace App\Support\Queue;

use Illuminate\Support\Facades\Log;

/**
 * Queue Observability Foundations
 * 
 * Wave 1 additive-only queue telemetry infrastructure.
 * 
 * This class provides correlation continuity and basic telemetry
 * for queue operations WITHOUT changing queue behavior.
 * 
 * Governance Rules:
 * - Additive only
 * - No async architecture changes
 * - No queue redesign
 * - Preserve sync behavior
 * - Correlation continuity only
 */
class QueueTelemetry
{
    /**
     * Log queue job enqueued event
     */
    public static function logEnqueued(string $jobClass, array $context = []): void
    {
        Log::info('queue.job.enqueued', array_merge([
            'job_class' => $jobClass,
            'queue_domain' => static::extractDomain($jobClass),
        ], $context));
    }

    /**
     * Log queue job processing started
     */
    public static function logProcessing(string $jobClass, string $jobId, array $context = []): void
    {
        Log::info('queue.job.processing', array_merge([
            'job_class' => $jobClass,
            'job_id' => $jobId,
            'queue_domain' => static::extractDomain($jobClass),
        ], $context));
    }

    /**
     * Log queue job processed successfully
     */
    public static function logProcessed(string $jobClass, string $jobId, float $durationMs, array $context = []): void
    {
        Log::info('queue.job.processed', array_merge([
            'job_class' => $jobClass,
            'job_id' => $jobId,
            'duration_ms' => $durationMs,
            'queue_domain' => static::extractDomain($jobClass),
        ], $context));
    }

    /**
     * Log queue job failed
     */
    public static function logFailed(string $jobClass, string $jobId, string $error, array $context = []): void
    {
        Log::error('queue.job.failed', array_merge([
            'job_class' => $jobClass,
            'job_id' => $jobId,
            'error' => $error,
            'queue_domain' => static::extractDomain($jobClass),
        ], $context));
    }

    /**
     * Log queue job retry
     */
    public static function logRetry(string $jobClass, string $jobId, int $attempt, array $context = []): void
    {
        Log::warning('queue.job.retry', array_merge([
            'job_class' => $jobClass,
            'job_id' => $jobId,
            'attempt' => $attempt,
            'queue_domain' => static::extractDomain($jobClass),
        ], $context));
    }

    /**
     * Propagate correlation ID to queue job
     * 
     * This ensures correlation continuity across async boundaries.
     */
    public static function propagateCorrelation(): ?string
    {
        // Get correlation ID from current request context
        $correlationId = request()->header(config('observability.correlation_header', 'X-Correlation-ID'));

        if (!$correlationId) {
            // Generate new correlation ID if not in request context
            $correlationId = (string) \Illuminate\Support\Str::uuid();
        }

        return $correlationId;
    }

    /**
     * Extract domain from job class name
     */
    private static function extractDomain(string $jobClass): string
    {
        // Extract domain from namespace
        // Example: App\Jobs\Order\ProcessOrderJob -> order
        
        if (preg_match('/\\\\Jobs\\\\([^\\\\]+)\\\\/', $jobClass, $matches)) {
            return strtolower($matches[1]);
        }

        if (preg_match('/\\\\Listeners\\\\([^\\\\]+)\\\\/', $jobClass, $matches)) {
            return strtolower($matches[1]);
        }

        return 'unknown';
    }

    /**
     * Create queue context for telemetry
     */
    public static function createContext(array $additionalContext = []): array
    {
        return array_merge([
            'correlation_id' => static::propagateCorrelation(),
            'release_version' => config('observability.release_version', 'dev'),
        ], $additionalContext);
    }
}
