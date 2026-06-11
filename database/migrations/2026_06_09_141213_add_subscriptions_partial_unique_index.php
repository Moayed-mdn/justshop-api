<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Note: Partial unique indexes are not supported in MySQL/MariaDB
        // The constraint "one active subscription per billing account" will be
        // enforced at the application level in the SubscriptionRepository
        // and validated in Actions before creating new subscriptions.
        
        // For PostgreSQL users, uncomment the following:
        /*
        DB::statement("
            CREATE UNIQUE INDEX subscriptions_one_active_per_account
            ON subscriptions (billing_account_id)
            WHERE status IN ('trialing','active','past_due','grace_period','paused')
            AND deleted_at IS NULL
        ");
        */
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // DB::statement("DROP INDEX IF EXISTS subscriptions_one_active_per_account");
    }
};
