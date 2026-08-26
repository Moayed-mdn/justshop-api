# Push Notifications — Client Payload Guide

Every notification (in-app record and FCM push alike) carries the same core fields, so a client
can use one code path to decide what to show and where to navigate regardless of whether the user
tapped a push or opened the in-app notification list.

## Shape

| Field | Type | Meaning |
|---|---|---|
| `type` | string | Stable identifier, e.g. `order.placed`. Never reused for a different meaning — see `App\Enums\Notification\NotificationTypeEnum`. Switch on this to decide what screen to open. |
| `title` | string | Localized (per the recipient's locale at send time) notification title. |
| `body` | string | Localized notification body. |
| `entity_type` | string | What kind of thing this is about: `order`, `product_variant`, `store`, `subscription`, `lead`, `user`. |
| `entity_id` | int | The ID of that entity. |
| `route` | string | A named route/screen hint for the client's own router — not a URL. |
| `data` | object | Extra structured fields specific to `type` (order number, store id, status, quantity, etc.) — always string values in the FCM `data` payload (FCM requires string-only data), native types in the in-app API response. |

## FCM message shape

Every push is sent as **both** a `notification` payload (title/body, for the OS to show when the
app is backgrounded/killed) and a `data` payload (for the app to handle deep-linking when
foregrounded or when the user taps the notification):

```json
{
  "notification": { "title": "Order confirmed", "body": "Your order ORD-AB12 has been confirmed. Total: 100.00." },
  "data": {
    "type": "order.placed",
    "entity_type": "order",
    "entity_id": "123",
    "route": "orders.show",
    "order_number": "ORD-AB12",
    "store_id": "7"
  },
  "android": { "priority": "high" },
  "apns": { "payload": { "aps": { "sound": "default" } } }
}
```

## Recommended client handling

```
on notification tapped / received in foreground:
    switch data.type:
        case "order.placed", "order.status_changed", "order.cancelled":
            navigate to Order Detail screen using data.entity_id
        case "order.received_merchant", "order.cancelled_by_customer", "order.high_value":
            navigate to Merchant Order Detail using data.entity_id
        case "product.low_stock":
            navigate to Product Detail using data.entity_id (or data.product_id)
        case "store.stripe_connect_status_changed":
            navigate to Payments Settings
        case "subscription.trial_started", "subscription.activated", "subscription.status_changed":
            navigate to Billing / Subscription screen
        case "lead.submitted", "merchant.registered", "store.created":
            navigate to the relevant Platform admin screen using data.entity_id
        default:
            open the in-app notification list (forward-compatible fallback for
            any new type shipped after this client build)
```

Always keep a `default` fallback: new notification types can ship on the backend at any time (see
`ADDING_A_SCENARIO.md`), and older client builds should degrade gracefully rather than crash on an
unrecognized `type`.

## Full list of types shipped today

`order.placed`, `order.status_changed`, `order.cancelled`, `order.received_merchant`,
`order.cancelled_by_customer`, `order.high_value`, `product.low_stock`,
`store.stripe_connect_status_changed`, `subscription.trial_started`, `subscription.activated`,
`subscription.status_changed`, `lead.submitted`, `merchant.registered`, `store.created`.
