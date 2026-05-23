<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wave 6: Enterprise Membership Lifecycle
 *
 * Adds lifecycle_status to store_user pivot to support the
 * MembershipLifecycleEnum vocabulary (invited, active, suspended,
 * delegated, temporary, support_managed, inherited, organization_scoped).
 *
 * Existing rows default to 'active' — no data loss.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_user', function (Blueprint $table): void {
            $table->string('lifecycle_status')
                ->default('active')
                ->after('role')
                ->index()
                ->comment('Wave 6: MembershipLifecycleEnum value');

            $table->timestamp('lifecycle_changed_at')
                ->nullable()
                ->after('lifecycle_status')
                ->comment('When lifecycle_status last changed');

            $table->string('lifecycle_changed_by_actor_type')
                ->nullable()
                ->after('lifecycle_changed_at')
                ->comment('ActorContextEnum value of actor who changed lifecycle');

            $table->unsignedBigInteger('lifecycle_changed_by_actor_id')
                ->nullable()
                ->after('lifecycle_changed_by_actor_type')
                ->comment('ID of actor who changed lifecycle');
        });
    }

    public function down(): void
    {
        Schema::table('store_user', function (Blueprint $table): void {
            $table->dropColumn([
                'lifecycle_status',
                'lifecycle_changed_at',
                'lifecycle_changed_by_actor_type',
                'lifecycle_changed_by_actor_id',
            ]);
        });
    }
};
