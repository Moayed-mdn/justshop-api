<?php
namespace App\Notifications;

use App\Support\System\FrontendUrlBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        return (new MailMessage)
        ->subject('Verify Your Email Address - ' . config('app.name'))
        ->markdown('emails.verify-email', [ // Use your custom template
            'user' => $notifiable,
            'verificationUrl' => $verificationUrl,
            'logo' => asset('images/logo.png'), // Optional: custom logo
        ]);
    }



    
    protected function verificationUrl($notifiable)
    {
        $actorContext = $notifiable->getActorContext();
        
        $routeName = match($actorContext) {
             \App\Enums\Auth\ActorContextEnum::CUSTOMER => 'customer.auth.verification.verify',
             default => 'merchant.auth.verification.verify',
         };

        $backendUrl = URL::temporarySignedRoute( 
            $routeName, 
            now()->addMinutes(60),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );

        return FrontendUrlBuilder::buildSigned(
            '/verify-email/' . $notifiable->getKey() . '/' . sha1($notifiable->getEmailForVerification()),
            $backendUrl
        );
    }

    public function failed(\Exception $exception)
    {
        Log::error('VerifyEmail notification failed: ' . $exception->getMessage());
        Log::error('Stack trace: ' . $exception->getTraceAsString());
    }
}