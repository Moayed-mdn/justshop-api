<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds UNIQUE constraint on provider_subscription_id.
     * MySQL allows multiple NULLs in a UNIQUE column (each NULL is treated as distinct),
     * so this is safe with the current pattern where NULL = "not yet linked to Stripe".
     * 
     * This constraint makes duplicate provider_subscription_id physically impossible,
     * preventing data corruption at the database level.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unique('provider_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique(['provider_subscription_id']);
        });
    }
};
