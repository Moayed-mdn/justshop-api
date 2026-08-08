<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add stores_count to billing_accounts table (account-level, not store-level).
     * This is the correct place for it since store count is per owner/account,
     * not per individual store.
     */
    public function up(): void
    {
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->unsignedInteger('stores_count')->default(0)->after('trial_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->dropColumn('stores_count');
        });
    }
};
