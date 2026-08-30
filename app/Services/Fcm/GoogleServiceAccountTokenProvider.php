<?php

declare(strict_types=1);

namespace App\Services\Fcm;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Exchanges a Firebase/Google service-account key for a short-lived OAuth2
 * access token, using the standard JWT-bearer grant
 * (RFC 7523 / Google's "self-signed JWT" flow).
 *
 * This is implemented by hand with PHP's built-in openssl_sign() (RS256)
 * rather than pulling in google/auth or kreait/firebase-php: the whole
 * flow is one signed JWT and one HTTP POST, which doesn't warrant a new
 * Composer dependency for this codebase.
 *
 * The resulting access token is cached for slightly less than its 1-hour
 * lifetime so a burst of notification sends doesn't re-sign a fresh JWT
 * and hit Google's token endpoint on every single message.
 */
class GoogleServiceAccountTokenProvider
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const CACHE_KEY = 'fcm:service_account_access_token';

    /**
     * Cache the token for 55 minutes even though Google issues it for 60,
     * so we never hand out a token that's about to expire mid-flight.
     */
    private const CACHE_TTL_SECONDS = 55 * 60;

    /**
     * @throws FcmConfigurationException
     */
    public function getAccessToken(): string
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return $this->requestNewAccessToken();
        });
    }

    /**
     * @return array{client_email: string, private_key: string, project_id: string}
     *
     * @throws FcmConfigurationException
     */
    public function credentials(): array
    {
        $raw = $this->loadRawCredentials();

        foreach (['client_email', 'private_key', 'project_id'] as $key) {
            if (empty($raw[$key])) {
                throw new FcmConfigurationException(
                    "Firebase service account credentials are missing required field [{$key}]."
                );
            }
        }

        return [
            'client_email' => $raw['client_email'],
            'private_key' => $raw['private_key'],
            'project_id' => $raw['project_id'],
        ];
    }

    /**
     * @throws FcmConfigurationException
     */
    private function requestNewAccessToken(): string
    {
        $credentials = $this->credentials();
        $jwt = $this->buildSignedJwt($credentials);

        $response = Http::asForm()
            ->timeout((int) config('services.firebase.http_timeout', 10))
            ->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

        if (!$response->successful()) {
            Log::channel('notifications')->error('FCM: failed to obtain OAuth2 access token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new FcmConfigurationException(
                'Failed to obtain a Google OAuth2 access token for FCM: HTTP '.$response->status()
            );
        }

        $accessToken = $response->json('access_token');

        if (!is_string($accessToken) || $accessToken === '') {
            throw new FcmConfigurationException('Google OAuth2 token response did not include an access_token.');
        }

        return $accessToken;
    }

    /**
     * @param array{client_email: string, private_key: string, project_id: string} $credentials
     */
    private function buildSignedJwt(array $credentials): string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_THROW_ON_ERROR));

        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => self::SCOPE,
            'aud' => self::TOKEN_URI,
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_THROW_ON_ERROR));

        $signingInput = "{$header}.{$claims}";

        $privateKey = openssl_pkey_get_private($credentials['private_key']);

        if ($privateKey === false) {
            throw new FcmConfigurationException(
                'Firebase service account private_key could not be parsed by OpenSSL.'
            );
        }

        $signature = '';
        $signed = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$signed) {
            throw new FcmConfigurationException('Failed to sign the FCM service-account JWT.');
        }

        return $signingInput.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @return array<string, mixed>
     *
     * @throws FcmConfigurationException
     */
    private function loadRawCredentials(): array
    {
        $json = config('services.firebase.credentials_json');

        if (!empty($json)) {
            $decoded = base64_decode((string) $json, true);

            if ($decoded === false) {
                throw new FcmConfigurationException('FIREBASE_CREDENTIALS_JSON is not valid base64.');
            }

            try {
                return json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new FcmConfigurationException(
                    'FIREBASE_CREDENTIALS_JSON did not decode to valid JSON: '.$e->getMessage()
                );
            }
        }

        $path = config('services.firebase.credentials_path');

        if (empty($path)) {
            throw new FcmConfigurationException(
                'No Firebase credentials configured. Set FIREBASE_CREDENTIALS_JSON or FIREBASE_CREDENTIALS_PATH.'
            );
        }

        if (!is_file($path) || !is_readable($path)) {
            throw new FcmConfigurationException("Firebase credentials file not found or unreadable at [{$path}].");
        }

        try {
            return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new FcmConfigurationException(
                "Firebase credentials file at [{$path}] is not valid JSON: ".$e->getMessage()
            );
        }
    }
}
