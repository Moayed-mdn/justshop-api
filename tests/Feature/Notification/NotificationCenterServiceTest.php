<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Notifications\Platform\StoreCreatedNotification;
use App\Services\Notification\NotificationCenterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationCenterServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationCenterService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(NotificationCenterService::class);
    }

    public function test_unread_count_reflects_sent_notifications(): void
    {
        $user = User::factory()->create();

        $user->notify(new StoreCreatedNotification(1, 'Test Store'));
        $user->notify(new StoreCreatedNotification(2, 'Another Store'));

        $this->assertSame(2, $this->service->unreadCount($user));
    }

    public function test_paginate_lists_the_users_notifications(): void
    {
        $user = User::factory()->create();
        $user->notify(new StoreCreatedNotification(1, 'Test Store'));

        $page = $this->service->paginate($user);

        $this->assertSame(1, $page->total());
        $this->assertSame('store.created', $page->items()[0]->data['type']);
    }

    public function test_mark_as_read_marks_the_correct_notification(): void
    {
        $user = User::factory()->create();
        $user->notify(new StoreCreatedNotification(1, 'Test Store'));
        $notificationId = $user->notifications()->first()->id;

        $result = $this->service->markAsRead($user, $notificationId);

        $this->assertTrue($result);
        $this->assertSame(0, $this->service->unreadCount($user));
    }

    public function test_a_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $owner->notify(new StoreCreatedNotification(1, 'Test Store'));
        $notificationId = $owner->notifications()->first()->id;

        $result = $this->service->markAsRead($intruder, $notificationId);

        $this->assertFalse($result);
        $this->assertSame(1, $this->service->unreadCount($owner));
    }

    public function test_mark_as_read_for_unknown_id_returns_false(): void
    {
        $user = User::factory()->create();

        $result = $this->service->markAsRead($user, (string) \Illuminate\Support\Str::uuid());

        $this->assertFalse($result);
    }

    public function test_mark_all_as_read_clears_unread_count(): void
    {
        $user = User::factory()->create();
        $user->notify(new StoreCreatedNotification(1, 'Store A'));
        $user->notify(new StoreCreatedNotification(2, 'Store B'));

        $this->service->markAllAsRead($user);

        $this->assertSame(0, $this->service->unreadCount($user));
    }

    public function test_mark_all_as_read_does_not_affect_another_users_notifications(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userA->notify(new StoreCreatedNotification(1, 'Store A'));
        $userB->notify(new StoreCreatedNotification(2, 'Store B'));

        $this->service->markAllAsRead($userA);

        $this->assertSame(0, $this->service->unreadCount($userA));
        $this->assertSame(1, $this->service->unreadCount($userB));
    }
}
