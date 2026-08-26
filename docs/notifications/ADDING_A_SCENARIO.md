# Adding a New Notification Scenario

The transport, channel, device-token system, and API surface never need to change for a new
scenario — only these five steps:

## 1. Add a type (and category, if store-scoped)

`App\Enums\Notification\NotificationTypeEnum` — append a new case. Never rename or remove an
existing one; client builds in the wild may already switch on it.

If it's a store-scoped notification that should follow the Store Admin/Store Staff permission
rule, and none of the existing categories (`ORDER`, `INVENTORY`, `FINANCE`, `ADMIN_ONLY`) fit,
add a case to `App\Enums\Notification\NotificationCategoryEnum` and give it a
`staffGatePermission()`.

## 2. Write the Notification class

Put it under `App\Notifications\<Domain>\...`, extending Laravel's `Notification`:

```php
class SomethingHappenedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'fcm'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => NotificationTypeEnum::SOMETHING_HAPPENED->value,
            'title' => __('notification.something_happened_title'),
            'body' => __('notification.something_happened_body'),
            'entity_type' => 'order',
            'entity_id' => $this->order->id,
            'route' => 'orders.show',
            'data' => [],
        ];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        return new FcmMessage(
            title: __('notification.something_happened_title'),
            body: __('notification.something_happened_body'),
            data: [],
        );
    }
}
```

Add the title/body translation keys to both `lang/en/notification.php` and
`lang/ar/notification.php`.

## 3. Dispatch (or reuse) a domain event at the point the business event occurs

Small event, primitive IDs only (so it serializes cleanly onto the queue), matching
`App\Events\Order\OrderPlaced` etc. If it's dispatched from inside a DB transaction, implement
`ShouldDispatchAfterCommit` so listeners never see a half-committed state.

## 4. Write a small queued listener

```php
class SendSomethingHappenedNotificationListener implements ShouldQueue
{
    public function handle(SomethingHappened $event): void
    {
        $order = Order::find($event->orderId);
        if (!$order) return;

        // Store-scoped, permission-gated recipients:
        $recipients = app(StoreNotificationRecipientResolver::class)
            ->resolve($order->store, NotificationCategoryEnum::ORDER);

        // Or platform admins:
        // $recipients = app(PlatformRecipientRepository::class)->listAdminRecipients();

        Notification::send($recipients, new SomethingHappenedNotification($order));
    }
}
```

## 5. Register it

One line in `AppServiceProvider::boot()`, next to the others:

```php
Event::listen(SomethingHappened::class, SendSomethingHappenedNotificationListener::class);
```

That's it — no changes to `FcmClient`, `FcmChannel`, `SendFcmNotificationJob`, the device-token
system, or any controller/route.
