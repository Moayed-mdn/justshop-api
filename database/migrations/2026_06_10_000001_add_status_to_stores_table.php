<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platform Stabilization: Store Lifecycle Status
 *
 * Adds a production-grade lifecycle status column to the stores table.
 *
 * Strategy:
 * - Additive only. The existing is_active boolean is preserved.
 * - All existing stores default to 'active' (no data loss).
 * - is_active remains the runtime gate for StoreContext middleware.
 * - status drives the lifecycle state machine going forward.
 * - A future migration can derive is_active from status once all
 *   consumers are updated.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->string('status')
                ->default('active')
                ->after('is_active')
                ->index()
                ->comment('StoreStatusEnum: pending_setup|active|suspended|archived|deleted_pending');

            $table->timestamp('status_changed_at')
                ->nullable()
                ->after('status')
                ->comment('When status last changed');

            $table->string('status_changed_by_actor_type')
                ->nullable()
                ->after('status_changed_at')
                ->comment('ActorContextEnum value of actor who changed status');

            $table->unsignedBigInteger('status_changed_by_actor_id')
                ->nullable()
                ->after('status_changed_by_actor_type')
                ->comment('ID of actor who changed status');

            $table->timestamp('setup_completed_at')
                ->nullable()
                ->after('status_changed_by_actor_id')
                ->comment('When store completed initial setup/onboarding');
        });

        // Backfill: active stores get status='active', inactive get status='suspended'
        DB::statement("UPDATE stores SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'suspended' END");
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table): void {
            $table->dropColumn([
                'status',
                'status_changed_at',
                'status_changed_by_actor_type',
                'status_changed_by_actor_id',
                'setup_completed_at',
            ]);
        });
    }
};
