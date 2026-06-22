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
        Schema::table('store_marketing_pages', function (Blueprint $table) {
            $table->boolean('is_homepage')->default(false)->after('sort_order');
            
            // Add index for homepage lookups
            $table->index(['store_id', 'is_homepage', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('store_marketing_pages', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'is_homepage', 'status']);
            $table->dropColumn('is_homepage');
        });
    }
};
