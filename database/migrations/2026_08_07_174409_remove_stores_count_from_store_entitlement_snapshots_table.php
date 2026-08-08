<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Remove stores_count from store_entitlement_snapshots since it's now in billing_accounts.
     * This eliminates duplication and drift risk.
     */
    public function up(): void
    {
        Schema::table('store_entitlement_snapshots', function (Blueprint $table) {
            $table->dropColumn('stores_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_entitlement_snapshots', function (Blueprint $table) {
            $table->unsignedInteger('stores_count')->default(0)->after('products_count');
        });
    }
};
