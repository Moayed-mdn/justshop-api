<?php

declare(strict_types=1);

namespace Tests\Unit\Fcm;

use App\Services\Fcm\FcmClient;
use App\Services\Fcm\FcmMessage;
use App\Services\Fcm\GoogleServiceAccountTokenProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FcmClientTest extends TestCase
{
    private const PROJECT_ID = 'test-project-123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.firebase.credentials_json' => base64_encode(json_encode($this->fakeServiceAccount())),
            'services.firebase.project_id' => self::PROJECT_ID,
        ]);
    }

    /**
     * A structurally-real (freshly generated, throwaway) RSA keypair is
     * required: GoogleServiceAccountTokenProvider signs a real JWT with
     * openssl_sign(), so the private_key must actually parse.
     */
    private function fakeServiceAccount(): array
    {
        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($keyPair, $privateKeyPem);

        return [
            'client_email' => 'test@test-project-123.iam.gserviceaccount.com',
            'private_key' => $privateKeyPem,
            'project_id' => self::PROJECT_ID,
        ];
    }

    private function fakeOAuthTokenResponse(): array
    {
        return ['access_token' => 'fake-access-token', 'expires_in' => 3600];
    }

    public function test_successful_send_returns_a_success_result(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response($this->fakeOAuthTokenResponse()),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/test/messages/1']),
        ]);

        $client = $this->app->make(FcmClient::class);
        $result = $client->send(new FcmMessage('Title', 'Body', ['type' => 'test']), 'some-device-token');

        $this->assertTrue($result->successful);
        $this->assertFalse($result->tokenInvalid);
    }

    public function test_unregistered_token_returns_an_invalid_token_result(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response($this->fakeOAuthTokenResponse()),
            'fcm.googleapis.com/*' => Http::response([
                'error' => ['status' => 'UNREGISTERED', 'message' => 'Requested entity was not found.'],
            ], 404),
        ]);

        $client = $this->app->make(FcmClient::class);
        $result = $client->send(new FcmMessage('Title', 'Body'), 'dead-token');

        $this->assertFalse($result->successful);
        $this->assertTrue($result->tokenInvalid);
    }

    public function test_malformed_token_argument_error_returns_an_invalid_token_result(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response($this->fakeOAuthTokenResponse()),
            'fcm.googleapis.com/*' => Http::response([
                'error' => ['status' => 'INVALID_ARGUMENT', 'message' => 'The registration token is not a valid FCM registration token'],
            ], 400),
        ]);

        $client = $this->app->make(FcmClient::class);
        $result = $client->send(new FcmMessage('Title', 'Body'), 'not-a-real-token');

        $this->assertTrue($result->tokenInvalid);
    }

    public function test_transient_server_error_returns_a_failed_result_not_invalid_token(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response($this->fakeOAuthTokenResponse()),
            'fcm.googleapis.com/*' => Http::response([
                'error' => ['status' => 'UNAVAILABLE', 'message' => 'Server temporarily unavailable'],
            ], 503),
        ]);

        $client = $this->app->make(FcmClient::class);
        $result = $client->send(new FcmMessage('Title', 'Body'), 'some-token');

        $this->assertFalse($result->successful);
        $this->assertFalse($result->tokenInvalid);
        $this->assertNotNull($result->error);
    }

    public function test_access_token_is_cached_across_multiple_sends(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response($this->fakeOAuthTokenResponse()),
            'fcm.googleapis.com/*' => Http::response(['name' => 'ok']),
        ]);

        $client = $this->app->make(FcmClient::class);
        $client->send(new FcmMessage('T', 'B'), 'token-1');
        $client->send(new FcmMessage('T', 'B'), 'token-2');

        Http::assertSentCount(3); // 1 OAuth token exchange + 2 FCM sends (token reused).
    }

    public function test_missing_credentials_configuration_returns_a_failed_result(): void
    {
        config(['services.firebase.credentials_json' => null, 'services.firebase.credentials_path' => null]);

        $client = $this->app->make(FcmClient::class);
        $result = $client->send(new FcmMessage('T', 'B'), 'some-token');

        $this->assertFalse($result->successful);
        $this->assertFalse($result->tokenInvalid);
    }
}
