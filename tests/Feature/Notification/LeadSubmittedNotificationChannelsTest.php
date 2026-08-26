<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Enums\RoleEnum;
use App\Events\Lead\LeadSubmitted;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadSubmittedNotification;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * LeadSubmittedNotification already existed (mail-only, to platform
 * admins). This confirms extending its via() to add 'database' and 'fcm'
 * is purely additive — mail keeps working, and admins now also get an
 * in-app + push notification for the exact same event.
 */
class LeadSubmittedNotificationChannelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_submitted_notifies_admins_on_all_three_channels(): void
    {
        $this->seed(PermissionSeeder::class);
        Notification::fake();

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SUPER_ADMIN->value);

        $lead = Lead::factory()->create();

        LeadSubmitted::dispatch($lead->id);

        Notification::assertSentTo($admin, LeadSubmittedNotification::class, function ($notification) {
            return in_array('mail', $notification->via($notification))
                && in_array('database', $notification->via($notification))
                && in_array('fcm', $notification->via($notification));
        });
    }
}
