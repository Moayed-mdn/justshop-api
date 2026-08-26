<?php

declare(strict_types=1);

namespace App\Services\Fcm;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper over the FCM HTTP v1 `messages:send` endpoint.
 *
 * https://fcm.googleapis.com/v1/projects/{project_id}/messages:send
 */
class FcmClient
{
    public function __construct(
        private readonly GoogleServiceAccountTokenProvider $tokenProvider,
    ) {
    }

    public function send(FcmMessage $message, string $token): FcmSendResult
    {
        try {
            $accessToken = $this->tokenProvider->getAccessToken();
            $projectId = $this->tokenProvider->credentials()['project_id'];
        } catch (FcmConfigurationException $e) {
            Log::channel('notifications')->error('FCM: configuration error, cannot send', [
                'error' => $e->getMessage(),
            ]);

            return FcmSendResult::failed($e->getMessage());
        }

        $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        $response = Http::withToken($accessToken)
            ->timeout((int) config('services.firebase.http_timeout', 10))
            ->post($endpoint, $message->toFcmPayload($token));

        if ($response->successful()) {
            return FcmSendResult::success();
        }

        $errorStatus = $response->json('error.status');
        $errorMessage = $response->json('error.message', 'Unknown FCM error');

        // FCM reports a dead/expired/unregistered registration token this
        // way — the caller should stop using it, not retry.
        if (in_array($errorStatus, ['UNREGISTERED', 'NOT_FOUND'], true)
            || $response->status() === 404
        ) {
            Log::channel('notifications')->info('FCM: device token is no longer registered', [
                'status' => $errorStatus,
                'http_status' => $response->status(),
            ]);

            return FcmSendResult::invalidToken((string) $errorMessage);
        }

        if ($errorStatus === 'INVALID_ARGUMENT') {
            // Malformed token string — also not worth retrying.
            Log::channel('notifications')->warning('FCM: invalid argument (likely malformed token)', [
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);

            return FcmSendResult::invalidToken((string) $errorMessage);
        }

        Log::channel('notifications')->warning('FCM: send failed', [
            'http_status' => $response->status(),
            'error_status' => $errorStatus,
            'body' => $response->body(),
        ]);

        return FcmSendResult::failed((string) $errorMessage);
    }
}
