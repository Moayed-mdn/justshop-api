<?php

namespace App\Notifications;

use App\Support\System\FrontendUrlBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class CustomResetPassword extends Notification
{
    use Queueable;

    public $token;

    private ?string $frontendUrl = null;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, ?string $frontendUrl = null)
    {
        $this->token = $token;
        $this->frontendUrl = $frontendUrl;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $token = $this->token;
        $email = $notifiable->getEmailForPasswordReset();

        $frontendUrl = FrontendUrlBuilder::build('/reset-password', [
            'token' => $token,
            'email' => $email
        ], baseUrl: $this->frontendUrl);

        return (new MailMessage)
        ->subject('Reset Your Password - ' . config('app.name'))
        ->line('You are receiving this email because we received a password reset request for your account.')
        ->action('Reset Password', $frontendUrl)
        ->line('This password reset link will expire in 60 minutes.')
        ->line('If you did not request a password reset, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
