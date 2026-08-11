<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('replaces_subscription_id')
                ->nullable()
                ->after('billing_account_id')
                ->constrained('subscriptions')
                ->nullOnDelete();
            
            $table->index('replaces_subscription_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['replaces_subscription_id']);
            $table->dropIndex(['replaces_subscription_id']);
            $table->dropColumn('replaces_subscription_id');
        });
    }
};
