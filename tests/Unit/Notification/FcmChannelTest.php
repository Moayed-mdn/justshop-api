<?php

declare(strict_types=1);

namespace Tests\Unit\Notification;

use App\Enums\Notification\DevicePlatformEnum;
use App\Jobs\Notification\SendFcmNotificationJob;
use App\Models\DeviceToken;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\Platform\StoreCreatedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FcmChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_one_job_per_registered_device_token(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'token-1', 'platform' => DevicePlatformEnum::IOS]);
        DeviceToken::create(['user_id' => $user->id, 'token' => 'token-2', 'platform' => DevicePlatformEnum::ANDROID]);

        (new FcmChannel())->send($user, new StoreCreatedNotification(1, 'Test Store'));

        Queue::assertPushed(SendFcmNotificationJob::class, 2);
    }

    public function test_a_user_with_no_devices_results_in_no_jobs(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        (new FcmChannel())->send($user, new StoreCreatedNotification(1, 'Test Store'));

        Queue::assertNotPushed(SendFcmNotificationJob::class);
    }

    public function test_a_notification_without_tofcm_is_a_noop(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        DeviceToken::create(['user_id' => $user->id, 'token' => 'token-1', 'platform' => DevicePlatformEnum::IOS]);

        $notificationWithoutFcm = new class extends \Illuminate\Notifications\Notification {
            public function via(object $notifiable): array
            {
                return ['database'];
            }
        };

        (new FcmChannel())->send($user, $notificationWithoutFcm);

        Queue::assertNotPushed(SendFcmNotificationJob::class);
    }
}
