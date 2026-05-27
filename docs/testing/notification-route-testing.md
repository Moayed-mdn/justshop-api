# Notification & Link Testing

This guide explains how to test that notifications and links use the correct canonical route names and parameters.

## Objectives
- Ensure no legacy route names are used in notifications.
- Verify that signed URLs are correctly generated for the intended context.
- Confirm that tenant scoping is preserved in generated links.

## Testing Verification Links
When testing email verification, assert that the generated URL uses the canonical `merchant.auth` or `customer.auth` namespace and is correctly mapped to the frontend via `FrontendUrlBuilder`.

```php
public function test_verify_email_notification_generates_signed_frontend_url(): void
{
    $user = User::factory()->create();
    $notification = new VerifyEmail();
    
    $mailMessage = $notification->toMail($user);
    $url = $mailMessage->viewData['verificationUrl'];
    
    $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
    
    $this->assertStringStartsWith($frontendUrl . '/verify-email/' . $user->id, $url);
    $this->assertStringContainsString('signature=', $url);
}
```

## Testing Context Boundaries
Verify that a notification intended for a merchant does not accidentally link to a platform-level route.

## Best Practices
- Use `Notification::fake()` to intercept and inspect notification content.
- Use `str_contains()` or regex to verify URL patterns in `actionUrl`.
- Always verify the presence of required parameters (e.g., `{store}`, `{id}`, `{hash}`).
