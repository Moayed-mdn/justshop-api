<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FCM device token registry.
     *
     * One row per registered device/token. A single table serves every
     * actor type (Customer, Merchant, Admin) because they are all the same
     * underlying `users` row in this codebase — there is no separate actor
     * model to key against.
     *
     * `token` is unique platform-wide (not just per-user): FCM tokens are
     * unique per app-install, so if the same token is registered again
     * (e.g. a shared/kiosk device logs out and a different user logs in),
     * the existing row is reassigned to the new user rather than creating
     * a duplicate — see DeviceTokenRepository::registerForUser().
     */
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // FCM registration tokens are typically ~150-250 chars in practice.
            // 512 keeps real-world headroom while staying well under MySQL/
            // InnoDB's utf8mb4 index key length limit (768 chars ≈ 3072 bytes).
            $table->string('token', 512)->unique();
            $table->string('platform', 20); // ios | android | web
            $table->string('device_id')->nullable();
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
