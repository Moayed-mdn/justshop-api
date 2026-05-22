<?php

declare(strict_types=1);

namespace Tests\Feature\Observability;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\Support\Audit\AuditLoggerInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_audit_logger_persists_safe_request_scoped_metadata(): void
    {
        /** @var User $user */
        $user = User::factory()->merchant()->create();
        /** @var Store $store */
        $store = Store::factory()->for($user, 'owner')->create();
        $user->stores()->attach($store->id, ['role' => 'store_admin']);

        Route::middleware(['api', 'auth:sanctum', 'store.context'])
            ->post('/api/v1/stores/{store}/test-observability/audit', function (
                AuditLoggerInterface $auditLogger
            ) {
                $auditLogger->record('observability.test', [
                    'safe' => 'value',
                    'password' => 'secret',
                    'nested' => [
                        'api_token' => 'abc123',
                    ],
                ]);

                return response()->json(['recorded' => true]);
            });

        $response = $this->actingAs($user)->postJson(
            "/api/v1/stores/{$store->id}/test-observability/audit"
        );

        $response->assertOk()->assertHeader('X-Correlation-ID');

        /** @var AuditLog $auditLog */
        $auditLog = AuditLog::query()->firstOrFail();

        $this->assertSame('observability.test', $auditLog->event);
        $this->assertSame($user->id, $auditLog->actor_id);
        $this->assertSame('merchant', $auditLog->actor_type);
        $this->assertSame($store->id, $auditLog->store_id);
        $this->assertSame($response->headers->get('X-Correlation-ID'), $auditLog->correlation_id);
        $this->assertNotNull($auditLog->membership_id);
        $this->assertSame('value', $auditLog->metadata['safe']);
        $this->assertSame('[REDACTED]', $auditLog->metadata['password']);
        $this->assertSame('[REDACTED]', $auditLog->metadata['nested']['api_token']);
    }
}
