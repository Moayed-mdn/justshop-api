<?php

declare(strict_types=1);

namespace App\Notifications\Auth;

use App\Support\System\FrontendUrlBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * WelcomeMerchantNotification
 *
 * Sent to a newly registered merchant after successful registration.
 * Dispatched asynchronously via SendWelcomeEmailJob.
 */
class WelcomeMerchantNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $dashboardUrl = FrontendUrlBuilder::build('/dashboard');

        return (new MailMessage())
            ->subject('Welcome to ' . config('app.name') . ' — Let\'s build your store')
            ->greeting('Hi ' . $notifiable->name . ',')
            ->line('Your account has been created successfully.')
            ->line('The next step is to verify your email address and create your first store.')
            ->action('Go to Dashboard', $dashboardUrl)
            ->line('If you did not create this account, no action is required.');
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('WelcomeMerchantNotification failed.', [
            'exception' => $exception->getMessage(),
        ]);
    }
}
