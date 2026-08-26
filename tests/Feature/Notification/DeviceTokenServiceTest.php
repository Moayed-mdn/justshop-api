<?php

declare(strict_types=1);

namespace Tests\Feature\Notification;

use App\Enums\Notification\DevicePlatformEnum;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Notification\DeviceTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * These exercise DeviceTokenService directly rather than going through
 * HTTP — the three actor-context controllers (Merchant/Customer/Platform)
 * are pure pass-throughs (see HandlesNotificationEndpoints), so this is
 * where the actual new logic lives and where correctness matters.
 */
class DeviceTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    private DeviceTokenService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->app->make(DeviceTokenService::class);
    }

    public function test_registering_a_new_token_creates_a_device_token_row(): void
    {
        $user = User::factory()->create();

        $deviceToken = $this->service->registerForUser(
            $user,
            'fcm-token-abc',
            DevicePlatformEnum::ANDROID,
            'device-123',
            'Pixel 9',
        );

        $this->assertDatabaseHas('device_tokens', [
            'id' => $deviceToken->id,
            'user_id' => $user->id,
            'token' => 'fcm-token-abc',
            'platform' => 'android',
            'device_id' => 'device-123',
            'device_name' => 'Pixel 9',
        ]);
    }

    public function test_a_user_can_register_multiple_devices(): void
    {
        $user = User::factory()->create();

        $this->service->registerForUser($user, 'token-1', DevicePlatformEnum::IOS, 'dev-1', 'iPhone');
        $this->service->registerForUser($user, 'token-2', DevicePlatformEnum::ANDROID, 'dev-2', 'Pixel');
        $this->service->registerForUser($user, 'token-3', DevicePlatformEnum::WEB, null, 'Chrome');

        $tokens = $this->service->listForUser($user);

        $this->assertCount(3, $tokens);
    }

    public function test_registering_an_already_known_token_reassigns_it_rather_than_erroring(): void
    {
        $originalUser = User::factory()->create();
        $newUser = User::factory()->create();

        $this->service->registerForUser($originalUser, 'shared-device-token', DevicePlatformEnum::ANDROID, null, null);

        // Same token, different user (e.g. a shared/kiosk device where a
        // different user has now logged in).
        $this->service->registerForUser($newUser, 'shared-device-token', DevicePlatformEnum::ANDROID, null, null);

        $this->assertSame(1, DeviceToken::where('token', 'shared-device-token')->count());
        $this->assertSame($newUser->id, DeviceToken::where('token', 'shared-device-token')->first()->user_id);
        $this->assertCount(0, $this->service->listForUser($originalUser));
        $this->assertCount(1, $this->service->listForUser($newUser));
    }

    public function test_removing_a_token_deletes_it(): void
    {
        $user = User::factory()->create();
        $this->service->registerForUser($user, 'token-to-remove', DevicePlatformEnum::IOS, null, null);

        $removed = $this->service->removeForUser($user, 'token-to-remove');

        $this->assertTrue($removed);
        $this->assertDatabaseMissing('device_tokens', ['token' => 'token-to-remove']);
    }

    public function test_a_user_cannot_remove_another_users_token(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $this->service->registerForUser($owner, 'owners-token', DevicePlatformEnum::IOS, null, null);

        $removed = $this->service->removeForUser($attacker, 'owners-token');

        $this->assertFalse($removed);
        $this->assertDatabaseHas('device_tokens', ['token' => 'owners-token', 'user_id' => $owner->id]);
    }

    public function test_removing_a_nonexistent_token_returns_false(): void
    {
        $user = User::factory()->create();

        $removed = $this->service->removeForUser($user, 'does-not-exist');

        $this->assertFalse($removed);
    }
}
