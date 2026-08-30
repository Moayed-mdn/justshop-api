# Push Notifications — Setup Guide

## 1. Environment variables

Add to `.env` (see `.env.example` for the full block):

| Variable | Required | Description |
|---|---|---|
| `FIREBASE_PROJECT_ID` | Yes | Your Firebase project ID. |
| `FIREBASE_CREDENTIALS_JSON` | One of these two | Base64-encoded contents of the service account JSON key file. Preferred for most hosts (no extra file to manage/deploy). |
| `FIREBASE_CREDENTIALS_PATH` | One of these two | Absolute path to the service account JSON key file on disk, if you'd rather ship the file itself. |
| `FIREBASE_HTTP_TIMEOUT` | No (default `10`) | Seconds before an FCM HTTP call times out. |
| `NOTIFICATIONS_QUEUE_CONNECTION` | No | Queue connection for notification jobs/listeners. Defaults to your app's default queue connection. |
| `NOTIFICATIONS_QUEUE` | No (default `notifications`) | Queue name — kept separate so a burst of pushes can't starve other background work. |
| `NOTIFICATIONS_FCM_MAX_TRIES` | No (default `3`) | Retry attempts for a failing FCM send. |
| `NOTIFICATIONS_FCM_BACKOFF_SECONDS` | No (default `30`) | Delay between retry attempts. |
| `NOTIFICATIONS_HIGH_VALUE_ORDER_THRESHOLD` | No (default `2000`) | Orders at/above this total additionally notify platform admins. Set empty/null to disable. |

## 2. Firebase project setup

1. In the [Firebase console](https://console.firebase.google.com), create or open your project.
2. Enable **Cloud Messaging** (Build → Cloud Messaging).
3. Go to **Project settings → Service accounts → Generate new private key**. This downloads a JSON file — treat it like a password, never commit it.
4. Either:
   - `base64 -i service-account.json | tr -d '\n'` and put the result in `FIREBASE_CREDENTIALS_JSON`, or
   - Upload the file somewhere outside the web root and point `FIREBASE_CREDENTIALS_PATH` at it.
5. Set `FIREBASE_PROJECT_ID` to the `project_id` field inside that same JSON file.

No Firebase/Google Composer package is required — delivery is a hand-signed OAuth2 JWT (via PHP's built-in `openssl_sign`) and a plain HTTP POST to the FCM HTTP v1 API (`App\Services\Fcm\GoogleServiceAccountTokenProvider` / `FcmClient`).

## 3. Database changes

Run the two new migrations:

```bash
php artisan migrate
```

- `notifications` — Laravel's standard notifications table. The `User` model already used the `Notifiable` trait, but this table didn't exist yet; it now backs the in-app notification center for every actor type.
- `device_tokens` — one row per registered FCM token (`user_id`, `token` unique, `platform`, `device_id`, `device_name`, `last_used_at`).

Neither migration touches or renames any existing table/column.

## 4. Queue worker

Notification delivery and the fan-out listeners are queued. Make sure a worker is running against the queue you configured (default connection, `notifications` queue):

```bash
php artisan queue:work --queue=notifications,default
```

(If you run a single worker across all queues already, no change is needed — jobs will simply also land on the `notifications` queue name.)

## 5. Verifying it works

1. Register a test device token (see `API.md`) — either a real FCM token from a test app install, or any string if you just want to confirm the DB round-trip.
2. Trigger a real scenario (e.g. place a test order) or call `Notification::send($user, new \App\Notifications\Platform\StoreCreatedNotification($store->id, $store->name))` from `php artisan tinker`.
3. Check `storage/logs/notifications.log` for delivery attempts/errors, and the `notifications` table for the in-app record.
