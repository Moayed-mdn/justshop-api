<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\Auth\WelcomeMerchantNotification;
use App\Notifications\CustomResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_notification_uses_frontend_dashboard_url(): void
    {
        $user = User::factory()->create();
        $notification = new WelcomeMerchantNotification();
        
        $mailMessage = $notification->toMail($user);
        
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $this->assertEquals($frontendUrl . '/dashboard', $mailMessage->actionUrl);
    }

    public function test_verify_email_notification_generates_signed_frontend_url(): void
    {
        $user = User::factory()->create();
        $notification = new VerifyEmail();
        
        $mailMessage = $notification->toMail($user);
        $url = $mailMessage->viewData['verificationUrl'];
        
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        
        $this->assertStringStartsWith($frontendUrl . '/verify-email/' . $user->id, $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);
    }

    public function test_password_reset_notification_uses_frontend_reset_url(): void
    {
        $user = User::factory()->create();
        $token = 'test-token';
        $notification = new CustomResetPassword($token);
        
        $mailMessage = $notification->toMail($user);
        
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $expectedUrl = $frontendUrl . '/reset-password?token=' . $token . '&email=' . urlencode($user->email);
        
        $this->assertEquals($expectedUrl, $mailMessage->actionUrl);
    }
}
