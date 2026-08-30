# Push Notifications — API Reference

Identical endpoints exist under three prefixes — every actor type is the same underlying `User`
row, so there's nothing actor-specific about registering a device or reading your own
notifications:

- `/v1/merchant/notifications/*` (`auth:sanctum` + `identity.route:merchant_users,merchant,enforce`)
- `/v1/customer/notifications/*` (`auth:sanctum` + `identity.route:customer_account,customer,enforce`)
- `/v1/platform/notifications/*` (group-level `auth:sanctum` + `identity.route` + `platform.context` +
  `platform.authority:platform_admin`, already applied to the whole `/v1/platform` group)

Every endpoint scopes strictly to the authenticated user (`$request->user()`) — there is no way to
address another user's device tokens or notifications.

## Register a device token

```
POST /v1/{context}/notifications/device-tokens
```

```json
{
  "token": "the-fcm-registration-token",
  "platform": "ios",
  "device_id": "optional-stable-device-identifier",
  "device_name": "optional-display-name"
}
```

`platform` is one of `ios` | `android` | `web`. Registering a token that's already registered (to
this user or a different one — e.g. a shared device where someone else previously logged in)
reassigns/refreshes it rather than erroring.

**200 OK**
```json
{
  "success": true,
  "message": "Device registered for notifications.",
  "data": {
    "id": 42,
    "platform": "ios",
    "device_id": "optional-stable-device-identifier",
    "device_name": "optional-display-name",
    "last_used_at": "2026-08-23T12:00:00.000000Z",
    "created_at": "2026-08-23T12:00:00.000000Z"
  }
}
```

## Remove a device token

```
DELETE /v1/{context}/notifications/device-tokens/{token}
```

`{token}` is the raw FCM token string (URL-encode it). Returns 404 if it doesn't exist or doesn't
belong to the caller.

## List notifications

```
GET /v1/{context}/notifications?per_page=20
```

Standard paginated response (`per_page` capped at 100). Each item:

```json
{
  "id": "9f8b6f2e-uuid",
  "type": "order.placed",
  "title": "Order confirmed",
  "body": "Your order ORD-AB12CD34 has been confirmed. Total: 100.00.",
  "entity_type": "order",
  "entity_id": 123,
  "route": "orders.show",
  "data": { "order_id": 123, "order_number": "ORD-AB12CD34", "store_id": 7 },
  "read_at": null,
  "created_at": "2026-08-23T12:00:00.000000Z"
}
```

## Unread count

```
GET /v1/{context}/notifications/unread-count
```

```json
{ "success": true, "data": { "unread_count": 3 } }
```

## Mark one notification as read

```
PATCH /v1/{context}/notifications/{notification}/read
```

`{notification}` is the notification's UUID (the `id` field above). 404 if it doesn't exist or
doesn't belong to the caller.

## Mark all as read

```
PATCH /v1/{context}/notifications/read-all
```

See CLIENT_PAYLOADS.md for how to interpret `type`/`entity_type`/`route`/`data` on the client.
