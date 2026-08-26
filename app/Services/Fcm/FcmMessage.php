<?php

declare(strict_types=1);

namespace App\Services\Fcm;

/**
 * A single outbound push message, addressed to one device token.
 *
 * `data` must be string-only key/value pairs (FCM's `data` payload
 * requirement) — notification classes are responsible for casting
 * everything (ids, booleans, etc.) to strings before building this.
 */
final class FcmMessage
{
    /**
     * @param array<string, string> $data
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {
    }

    /**
     * Build the `message` object body for a `messages:send` call targeting
     * a specific device token.
     *
     * @return array<string, mixed>
     */
    public function toFcmPayload(string $token): array
    {
        return [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $this->title,
                    'body' => $this->body,
                ],
                'data' => $this->data,
                'android' => [
                    'priority' => 'high',
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];
    }
}
