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
        Schema::table('stores', function (Blueprint $table) {
            // Drop the existing plain index
            $table->dropIndex('stores_stripe_account_id_index');
            
            // Add unique constraint
            $table->unique('stripe_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('stores_stripe_account_id_unique');
            
            // Restore the plain index
            $table->index('stripe_account_id');
        });
    }
};
