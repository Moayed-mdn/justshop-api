<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('onboarding_step_changed_at')
                ->nullable()
                ->after('onboarding_completed_at');
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->timestamp('provisioning_started_at')
                ->nullable()
                ->after('provisioning_retryable');
            $table->timestamp('provisioning_last_heartbeat_at')
                ->nullable()
                ->after('provisioning_started_at');
            $table->timestamp('provisioning_completed_at')
                ->nullable()
                ->after('provisioning_last_heartbeat_at');
            $table->timestamp('provisioning_failed_at')
                ->nullable()
                ->after('provisioning_completed_at');
            $table->unsignedInteger('provisioning_attempts')
                ->default(0)
                ->after('provisioning_failed_at');
            $table->text('provisioning_last_error')
                ->nullable()
                ->after('provisioning_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('onboarding_step_changed_at');
        });

        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn([
                'provisioning_started_at',
                'provisioning_last_heartbeat_at',
                'provisioning_completed_at',
                'provisioning_failed_at',
                'provisioning_attempts',
                'provisioning_last_error',
            ]);
        });
    }
};
