<?php

declare(strict_types=1);

namespace Tests\Unit\Notification;

use App\Enums\Notification\DevicePlatformEnum;
use App\Jobs\Notification\SendFcmNotificationJob;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Fcm\FcmMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SendFcmNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($keyPair, $privateKeyPem);

        config([
            'services.firebase.credentials_json' => base64_encode(json_encode([
                'client_email' => 'test@test.iam.gserviceaccount.com',
                'private_key' => $privateKeyPem,
                'project_id' => 'test-project',
            ])),
        ]);

        Http::fake(['oauth2.googleapis.com/*' => Http::response(['access_token' => 'fake', 'expires_in' => 3600])]);
    }

    private function makeDeviceToken(): DeviceToken
    {
        $user = User::factory()->create();

        return DeviceToken::create([
            'user_id' => $user->id,
            'token' => 'device-token-under-test',
            'platform' => DevicePlatformEnum::ANDROID,
        ]);
    }

    public function test_successful_send_updates_last_used_at(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'fake', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response(['name' => 'ok']),
        ]);

        $deviceToken = $this->makeDeviceToken();
        $this->assertNull($deviceToken->last_used_at);

        (new SendFcmNotificationJob($deviceToken->id, new FcmMessage('T', 'B')))
            ->handle($this->app->make(\App\Services\Fcm\FcmClient::class));

        $this->assertNotNull($deviceToken->fresh()->last_used_at);
        $this->assertDatabaseHas('device_tokens', ['id' => $deviceToken->id]);
    }

    public function test_invalid_token_response_deletes_the_device_token(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'fake', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response([
                'error' => ['status' => 'UNREGISTERED', 'message' => 'gone'],
            ], 404),
        ]);

        $deviceToken = $this->makeDeviceToken();

        (new SendFcmNotificationJob($deviceToken->id, new FcmMessage('T', 'B')))
            ->handle($this->app->make(\App\Services\Fcm\FcmClient::class));

        $this->assertDatabaseMissing('device_tokens', ['id' => $deviceToken->id]);
    }

    public function test_transient_failure_throws_so_the_queue_retries(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'fake', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::response([
                'error' => ['status' => 'INTERNAL', 'message' => 'oops'],
            ], 500),
        ]);

        $deviceToken = $this->makeDeviceToken();

        $this->expectException(\RuntimeException::class);

        (new SendFcmNotificationJob($deviceToken->id, new FcmMessage('T', 'B')))
            ->handle($this->app->make(\App\Services\Fcm\FcmClient::class));
    }

    public function test_job_is_a_noop_if_the_device_token_was_already_removed(): void
    {
        // No HTTP fake for fcm.googleapis.com — if the job tried to send,
        // this would throw/fail; asserting no exception confirms it
        // returned early instead.
        Http::fake(['oauth2.googleapis.com/*' => Http::response(['access_token' => 'fake', 'expires_in' => 3600])]);

        (new SendFcmNotificationJob(999999, new FcmMessage('T', 'B')))
            ->handle($this->app->make(\App\Services\Fcm\FcmClient::class));

        $this->assertTrue(true); // Reaching here without an exception is the assertion.
    }
}
