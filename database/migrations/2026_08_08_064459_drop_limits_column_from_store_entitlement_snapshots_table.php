<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Remove limits column from store_entitlement_snapshots since it's obsolete.
     * Usage counts are now stored in atomic counter columns (products_count)
     * and account-level table (billing_accounts.stores_count).
     */
    public function up(): void
    {
        Schema::table('store_entitlement_snapshots', function (Blueprint $table) {
            $table->dropColumn('limits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_entitlement_snapshots', function (Blueprint $table) {
            $table->json('limits')->nullable()->after('features');
        });
    }
};
