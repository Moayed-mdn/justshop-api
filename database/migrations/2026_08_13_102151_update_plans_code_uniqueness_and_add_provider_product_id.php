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
        Schema::table('plans', function (Blueprint $table) {
            // Drop the unique constraint on code to allow versioning
            $table->dropUnique(['code']);
            
            // Add non-unique index for lookup performance
            $table->index('code');
            
            // Add provider_product_id for Stripe Product tracking
            $table->string('provider_product_id')->nullable()->after('tier_rank');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Remove the provider_product_id column
            $table->dropColumn('provider_product_id');
            
            // Drop the non-unique index
            $table->dropIndex(['code']);
            
            // Restore unique constraint (this will fail if there are duplicate codes)
            $table->unique('code');
        });
    }
};
