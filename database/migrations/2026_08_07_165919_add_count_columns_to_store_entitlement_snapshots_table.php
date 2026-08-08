<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Add atomic counter columns to replace JSON-based counts.
     * This fixes race conditions and enables atomic increment/decrement.
     */
    public function up(): void
    {
        Schema::table('store_entitlement_snapshots', function (Blueprint $table) {
            $table->unsignedInteger('products_count')->default(0)->after('limits');
            $table->unsignedInteger('stores_count')->default(0)->after('products_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_entitlement_snapshots', function (Blueprint $table) {
            $table->dropColumn(['products_count', 'stores_count']);
        });
    }
};
