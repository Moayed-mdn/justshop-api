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
        Schema::table('plan_prices', function (Blueprint $table) {
            // Drop the unique constraint that blocks price versioning
            $table->dropUnique('plan_prices_unique');
            
            // Keep plan_prices_provider_unique untouched - that one is correct
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_prices', function (Blueprint $table) {
            // Restore the unique constraint
            $table->unique(
                ['plan_id', 'billing_cycle', 'currency', 'provider'],
                'plan_prices_unique'
            );
        });
    }
};
