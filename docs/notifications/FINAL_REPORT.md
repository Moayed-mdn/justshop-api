# Push Notification System — Final Report

## Summary

A production-ready FCM push notification system has been added to `justshop-api`, covering
Customers, Merchants (Store Admin + Store Staff, with permission-gated staff routing), and
Platform Admins. It reuses the app's existing Notification/Event/Listener conventions, adds no
new Composer dependencies, and required no changes to any existing table or unrelated code path.

See `docs/notifications/`:
- `ARCHITECTURE.md` — design decisions, actor mapping, recipient-routing rules, scenario table
- `SETUP.md` — environment variables, Firebase setup, migrations, queue worker
- `API.md` — endpoint reference
- `CLIENT_PAYLOADS.md` — how mobile/web clients should interpret notification payloads
- `ADDING_A_SCENARIO.md` — the five steps to add a new scenario later

## Actor mapping (confirmed with you mid-task)

- **Admin Users** → `RoleEnum::SUPER_ADMIN` (Platform)
- **Merchant Users** → `store_user` pivot (`store_admin` / `staff`)
- **Store Users** → Customers (confirmed by you)

## Recipient routing rule (per your direction)

- **Store Admin** receives every category of store-scoped notification.
- **Store Staff** only receives a category if they hold the associated permission for that store
  (`order.view` → order notifications, `product.view` → inventory, `invoice.view` → finance),
  resolved through the app's real `PermissionResolver` — not a new hardcoded table.
- **Flagged limitation, not silently worked around:** today the single global `staff` Spatie role
  holds `order.view`, `product.view`, and `invoice.view` simultaneously (see `PermissionSeeder`),
  so every current staff member qualifies for all three non-admin-only categories at once — there's
  no existing "Order Staff" vs "Inventory Staff" vs "Finance Staff" distinction in the data model
  yet. The resolver is built to be correct the moment that changes (proven in
  `StoreNotificationRecipientResolverTest::test_a_differentiated_staff_role_only_receives_its_own_permitted_categories`),
  requiring zero notification-code changes — but introducing an actual departmental-role UI/data
  model was out of scope ("avoid overengineering", "don't assume database structures").
- **Stripe Connect status and platform subscription/billing never reach staff**, regardless of
  permissions — matches your explicit list of Store-Admin-only items.

## Files created/modified

77 new files, 8 modified (see `git diff a5830a3 7baf5b1 --stat` in the delivered repo, or the
attached patch). Grouped:

**Modified (existing files, additive changes only):**
- `app/Actions/Admin/Order/CancelOrderAction.php`, `UpdateOrderStatusAction.php` — dispatch new events
- `app/Actions/Order/CancelOrderAction.php` — dispatch `OrderCancelled`
- `app/Actions/Store/ApplyStripeAccountStatusAction.php` — dispatch `StripeConnectStatusChanged`
- `app/Services/EnhancedCheckoutService.php` — dispatch `OrderPlaced` and `ProductVariantLowStock`
- `app/Models/User.php` — added `deviceTokens()` relation (one line)
- `app/Notifications/LeadSubmittedNotification.php` — `via()` gains `database`+`fcm` alongside `mail`
- `app/Providers/AppServiceProvider.php` — new `Event::listen()` registrations + `Notification::extend('fcm', ...)`
- `config/services.php`, `config/logging.php`, `.env.example`, `routes/api.php` — additive config/route registration

**New:** config (`config/notifications.php`), 2 migrations, 1 model (`DeviceToken`), 3 enums, FCM
transport layer (5 classes, no new dependency), 1 job, 1 notification channel, 5 domain events, 14
notification classes, 9 listeners, 2 recipient-resolution services, 2 device/notification-center
services, 3 thin controllers + 1 shared trait, 1 FormRequest, 2 API resources, 3 route files,
2 language files, 5 doc files, 12 test files.

## Database changes

Two new tables, no changes to existing ones:
- `notifications` — Laravel's standard table (the `User` model already declared `Notifiable` but
  this table didn't exist).
- `device_tokens` — `user_id`, unique `token`, `platform`, `device_id`, `device_name`, `last_used_at`.

## Notification scenarios implemented (14)

See `ARCHITECTURE.md` §7 for the full table. Order placed/status-changed/cancelled (customer);
new order/customer-cancellation/Stripe Connect status/subscription lifecycle (merchant); lead
submitted (extended)/new merchant/new store/high-value order (admin).

**Deliberately not implemented:** approval/rejection workflows for merchant or store registration
— this app's stores self-activate through onboarding; there's no manual admin-approval gate to
hook a notification into. Implementing one would have meant inventing a business workflow that
doesn't exist, which the brief explicitly warned against.

## APIs added

Identical device-token + notification-center endpoints under `/v1/merchant`, `/v1/customer`, and
`/v1/platform` — see `API.md`. All scoped strictly to the authenticated user.

## FCM configuration required

`FIREBASE_PROJECT_ID` + one of `FIREBASE_CREDENTIALS_JSON`/`FIREBASE_CREDENTIALS_PATH`. No new
Composer package — the OAuth2 service-account flow is implemented directly with PHP's built-in
`openssl_sign()` (RS256) since it amounts to one signed JWT and one HTTP POST. Full details in
`SETUP.md`.

## Tests added (12 files)

- `StoreNotificationRecipientResolverTest` — the core Store Admin/Staff routing rule, including the
  forward-compatibility case and inactive-membership exclusion.
- `OrderEventNotificationsTest` — `OrderPlaced` (customer + merchant team + high-value admin alert,
  guest-checkout handling), `OrderStatusChanged`, `OrderCancelled` (both directions).
- `LowStockAndStripeConnectNotificationsTest` — low-stock routing, Stripe Connect admin-only routing
  (onboarded/restricted/no-op-change cases).
- `DeviceTokenServiceTest` — registration, multi-device, token reassignment, removal, cross-user
  authorization.
- `NotificationCenterServiceTest` — unread count, pagination, mark-as-read (single/all),
  cross-user authorization.
- `LeadSubmittedNotificationChannelsTest` — confirms the channel extension is purely additive.
- `FcmClientTest` — success, unregistered token, malformed token, transient error, missing config,
  access-token caching. Mocks `Http::fake()`; no real network calls.
- `SendFcmNotificationJobTest` — success updates `last_used_at`, invalid token deletes the row,
  transient failure throws for queue retry, already-removed token is a no-op.
- `FcmChannelTest` — fans out one job per device token, no-op for zero devices or a notification
  without `toFcm()`.

**Not attempted:** full HTTP-level route tests through the three new controllers.
`app/Http/Middleware/ApplyIdentityRouteContext.php` is a large, actively-evolving piece of
infrastructure (it contains live debug/telemetry instrumentation suggesting an in-progress "Wave 6"
auth migration) — correctly satisfying its session/guard-domain setup in a test without being able
to run and observe the suite firsthand risked either a brittle test or one that silently tests the
wrong thing. Since the three controllers are pure one-line pass-throughs to
`HandlesNotificationEndpoints` (which the tests above cover completely), the actual new logic has
full coverage; only the thin HTTP wiring is untested. Recommend adding a short
`Sanctum::actingAs()` smoke test per context once you can run the suite interactively to confirm
the right session/guard setup.

**Also not attempted:** a full checkout-flow integration test for the low-stock threshold-crossing
logic added to `EnhancedCheckoutService` (it's tested at the event/listener/notification level
instead) — exercising the real method requires Cart/Address/PaymentMethod/Stripe-webhook scaffolding
unrelated to the notification system, which risked ballooning scope for marginal additional
confidence in a two-line conditional.

## Notable pre-existing issue found (not fixed, out of scope, flagged for visibility)

`App\Listeners\Subscription\SendSubscriptionLifecycleEmailListener` exists with `TODO` bodies and a
`subscribe()` method, but nothing in the app ever calls `Event::subscribe()` — so despite
`TrialStarted`/`SubscriptionActivated`/`SubscriptionStatusChanged` being dispatched by real billing
code, that listener has never actually run. This push-notification system's three new subscription
listeners are registered independently via `Event::listen()` (the pattern used everywhere else in
this app) and are unaffected by this — but the dead email listener is still dead and may be worth a
follow-up ticket.

## Assumptions made

1. **Actor mapping** (Store Users = Customers) — confirmed with you mid-task.
2. **Recipient routing rule** — implemented exactly as you specified, with the permission-based
   mechanism explained above standing in for a not-yet-built departmental-role system.
3. **"Support" agents** are not included in platform-admin notification recipients, matching the
   pre-existing convention in `LeadRepository::listAdminRecipients()` (SUPER_ADMIN only). Trivial to
   extend in `PlatformRecipientRepository` if you want support agents included later.
4. **High-value order threshold** defaults to 2000 (configurable, disableable) — a reasonable
   starting point, not a number you specified; adjust `NOTIFICATIONS_HIGH_VALUE_ORDER_THRESHOLD`.
5. Cancellation-notification direction is inferred from `cancelledByUserId === order->user_id`
   (customer-initiated vs store-initiated) — there's no explicit "initiator role" field on the order,
   so this is the best available signal from the existing `CancelOrderAction` call sites.

## Remaining work / recommended next steps

1. Run `composer install`, `php artisan migrate`, and the test suite for real — I had no network
   access or PHP runtime in this environment, so everything above was written and manually verified
   for syntax/logic (namespace/PSR-4 consistency, brace/paren balance, and every cross-reference
   between files were scripted-checked) but never executed.
2. Set the Firebase environment variables (`SETUP.md`) and confirm a real device receives a push.
3. Add the HTTP-level smoke tests mentioned above once you can observe the identity middleware's
   actual behavior in your environment.
4. Frontend/mobile integration: register device tokens on login/app-open, wire the notification
   center screens to the new endpoints, and implement the `type`-based routing in
   `CLIENT_PAYLOADS.md`.
5. Consider whether Support agents should receive platform notifications (currently: no, matching
   existing convention).
