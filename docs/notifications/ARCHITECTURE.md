# Push Notification System — Architecture

## 1. Actor mapping (confirmed with product owner)

The codebase has a single `users` table. Actor type is *resolved*, not stored:

| Task's term    | This app's term                          | Resolution                                                              |
|-----------------|-------------------------------------------|---------------------------------------------------------------------------|
| Admin Users     | Platform (`RoleEnum::SUPER_ADMIN`)        | Spatie role `super_admin`                                                  |
| Merchant Users  | Merchant — Store Admin & Store Staff      | `store_user` pivot row (`role` = `store_admin` \| `staff`)                 |
| Store Users     | Customers                                 | Any user with no store membership / onboarding (`ActorContextEnum::CUSTOMER`) |

`Support` (`RoleEnum::SUPPORT`) is intentionally **not** wired into any notification recipient list in this
first pass, matching the existing convention in `LeadRepository::listAdminRecipients()`, which only
targets `super_admin`. Support agents can be added later by extending
`PlatformRecipientRepository::listAdminRecipients()`.

## 2. Merchant recipient targeting (Store Admin vs Store Staff)

Per product direction:

- **Store Admin** receives almost every operational notification for their store (new order,
  cancellation, payment issues, Stripe Connect status, subscription/billing, alerts).
- **Store Staff** only receives notifications relevant to what they're permitted to do — determined
  by their **existing Spatie permissions** for that store, not a new "department" concept.

**Important existing-system caveat (flagged, not silently worked around):** today every store's
`staff` members share one global Spatie `staff` role (`PermissionSeeder`), which already grants
`order.view`, `product.view`, `invoice.view`, `subscription.view` simultaneously. So *today*, in
practice, every staff member currently qualifies for the Order, Inventory, and Finance categories at
once — there is no existing concept of "Order Staff" vs "Inventory Staff" vs "Finance Staff" as
distinct people. Building a bespoke new role-per-department system was explicitly out of scope
("avoid overengineering", "do not assume database structures", "smallest clean change"), so instead
the recipient resolver checks **permissions directly** via the same `PermissionResolver` the rest of
the app already uses for authorization:

| Notification category | Gate permission (staff must hold this for the store) |
|------------------------|--------------------------------------------------------|
| `order`                | `PermissionEnum::ORDER_VIEW`                            |
| `inventory`            | `PermissionEnum::PRODUCT_VIEW`                           |
| `finance`              | `PermissionEnum::INVOICE_VIEW`                           |
| `admin_only`           | *(staff excluded entirely — Store Admin only)*           |

This is forward-compatible for free: the moment the product introduces differentiated staff
permission sets (e.g. a staff member without `product.view`), that person automatically stops
receiving inventory alerts — zero notification-code changes required. `admin_only` is used for
Stripe Connect status and platform subscription/billing, matching the product direction that those
stay with the store owner.

Implemented in `App\Services\Notification\StoreNotificationRecipientResolver`.

## 3. Transport: Firebase Cloud Messaging (HTTP v1)

No new Composer dependency is introduced. FCM HTTP v1 requires an OAuth2 access token obtained via a
Google service-account JWT bearer assertion — this is implemented directly with PHP's built-in
`openssl_sign()` (RS256) and Laravel's `Http` client, in
`App\Services\Fcm\GoogleServiceAccountTokenProvider` (token cached ~55 min) and
`App\Services\Fcm\FcmClient` (the actual `messages:send` call). This avoids pulling in
`kreait/firebase-php` for what is, at its core, one signed JWT and one HTTP POST.

Delivery is queue-based (`App\Jobs\Notification\SendFcmNotificationJob`, implements
`ShouldQueue`, per-device-token, with Laravel's standard queue retry/backoff), and one failing
device token never blocks delivery to a user's other devices because each device token gets its own
queued job.

## 4. Laravel-native notification plumbing

`User` already `use`s `Notifiable`. Rather than a bespoke notifications table, this adds Laravel's
**standard `notifications` table** (was missing) and a **custom `fcm` notification channel**
alongside the built-in `database` channel:

```php
public function via(object $notifiable): array
{
    return ['database', 'fcm'];
}
```

- `toDatabase()` → persisted in-app notification (survives even if push fails — channels are
  independent, satisfying "push delivery and persistent storage stay conceptually separate").
- `toFcm()` → returns an `App\Services\Fcm\FcmMessage`; `App\Notifications\Channels\FcmChannel`
  fans it out to every one of the user's registered device tokens via the queued job above, and
  deletes any device token FCM reports as unregistered/invalid.

This reuses `$user->notifications`, `$user->unreadNotifications`, `$notification->markAsRead()` —
all native — instead of inventing a parallel notification-center data model.

## 5. Device tokens

One new table, `device_tokens` (`user_id`, `token` unique, `platform` enum, `device_id` nullable,
`device_name` nullable, `last_used_at`). A single table is enough because every actor type is the
same underlying `User` row — no polymorphism needed. Registering an already-known token
reassigns/touches it (`updateOrCreate` by `token`) rather than erroring, so a shared device that logs
out and back in as a different user just re-points the token. Invalid/unregistered tokens reported by
FCM are deleted outright (simplest correct behavior — no soft-invalidation state to manage).

## 6. Event → Listener → Notification flow

Follows the existing convention exactly (`LeadSubmitted` → `SendLeadSubmittedNotificationListener`):
small events carrying primitive IDs, queued listeners, manual `Event::listen()` registration in
`AppServiceProvider::boot()`.

New events (all under `App\Events\...`, `ShouldDispatchAfterCommit` where dispatched inside a DB
transaction):

- `Order\OrderPlaced` — dispatched from `EnhancedCheckoutService::completeCheckout()`
- `Order\OrderStatusChanged` — dispatched from `Admin\UpdateOrderStatusAction`
- `Order\OrderCancelled` — dispatched from both the storefront and merchant-admin cancel actions
  (carries `cancelledByUserId` so the listener can tell whether the customer or the store team
  triggered it, and notify the *other* party)
- `Product\ProductVariantLowStock` — dispatched from `EnhancedCheckoutService::completeCheckout()`
  stock-deduction step, only on the transition from *above* threshold to *at-or-below* threshold
  (idempotent — doesn't re-fire on every subsequent order once already low)
- `Store\StripeConnectStatusChanged` — dispatched from `ApplyStripeAccountStatusAction`, only when
  the tracked boolean flags actually change

Existing events newly wired up (additive `Event::listen()` entries; no existing listener touched):

- `Domain\Shared\Events\MerchantRegistered` → notify platform admins
- `Domain\Shared\Events\StoreCreated` → notify platform admins
- `Events\Subscription\TrialStarted` / `SubscriptionActivated` / `SubscriptionStatusChanged` →
  notify the billing account owner. **Note:** these three events were already being dispatched from
  real billing code, but had *zero* registered listeners — `SendSubscriptionLifecycleEmailListener`
  exists with `TODO` bodies and a `subscribe()` method, but nothing in the app ever calls
  `Event::subscribe()`, so it has silently never run. That pre-existing gap is out of this task's
  scope to fix (it's about email, not push), but is worth flagging separately.
- `Events\Lead\LeadSubmitted` → no new listener; `LeadSubmittedNotification::via()` gains
  `database` + `fcm` alongside its existing `mail`.

A configurable "high-value order" check (`config('notifications.high_value_order_threshold')`) lives
inside the `OrderPlaced` listener rather than as its own event, to avoid an extra event class for what
is a threshold check on data the listener already has.

## 7. Notification-worthy scenarios implemented

| # | Event | Recipient(s) | Category |
|---|-------|--------------|----------|
| 1 | Order placed | Customer | — |
| 2 | Order placed | Store Admin + Order staff | `order` |
| 3 | Order total ≥ threshold | Platform admins | — |
| 4 | Order status changed | Customer | — |
| 5 | Order cancelled by customer | Store Admin + Order staff | `order` |
| 6 | Order cancelled by store | Customer | — |
| 7 | Product variant crosses low-stock threshold | Store Admin + Inventory staff | `inventory` |
| 8 | Stripe Connect status changed (enabled/restricted) | Store Admin only | `admin_only` |
| 9 | Store's platform subscription: trial started | Billing account owner | — |
| 10 | Store's platform subscription: activated | Billing account owner | — |
| 11 | Store's platform subscription: status changed (past_due/canceled/grace/expired/...) | Billing account owner | — |
| 12 | Lead submitted (extends existing) | Platform admins | — |
| 13 | New merchant registered | Platform admins | — |
| 14 | New store created | Platform admins | — |

Deliberately **not** implemented (would be arbitrary rather than meaningful, or would require
inventing business logic that doesn't exist yet): payment-method-level events, review/rating
notifications (no review-moderation workflow exists), "new business opportunities/assignments"
(no marketplace-assignment concept exists in this single-tenant-per-store app), and approval/
rejection workflows for store/merchant registration (stores currently self-activate through
onboarding; there's no manual admin-approval gate to hook into).

## 8. API surface

Mirrors the existing per-actor-context controller convention (`Api\Merchant\*`,
`Api\Storefront\Account\*` for `/v1/customer`, `Api\Platform\*`):

- `POST   /{context}/notifications/device-tokens` — register/refresh a token
- `DELETE /{context}/notifications/device-tokens/{token}` — unregister
- `GET    /{context}/notifications` — paginated list
- `GET    /{context}/notifications/unread-count`
- `PATCH  /{context}/notifications/{id}/read`
- `PATCH  /{context}/notifications/read-all`

All scoped strictly to `$request->user()` — nobody can touch another user's device tokens or
notifications; there is no cross-user ID parameter anywhere in these routes.

## 9. What a future scenario needs (extensibility)

1. Add a case to `NotificationTypeEnum` (and category to `NotificationCategoryEnum` if it's a new
   store-scoped category).
2. Write a `Notification` class with `toDatabase()` + `toFcm()`.
3. Dispatch (or reuse) a domain event carrying primitive IDs at the point the business event occurs.
4. Add a small queued listener that resolves recipients (via `StoreNotificationRecipientResolver` or
   `PlatformRecipientRepository`) and calls `Notification::send($recipients, new ...)`.
5. Register the listener with `Event::listen()` in `AppServiceProvider::boot()`.

No changes to the FCM transport, channel, device-token system, or API surface are ever needed for a
new scenario.
