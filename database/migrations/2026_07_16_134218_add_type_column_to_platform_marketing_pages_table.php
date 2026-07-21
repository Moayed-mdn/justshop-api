<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add type column to platform_marketing_pages table.
     * 
     * Type defines the page category (home, about, pricing, etc.)
     * while template defines the layout/rendering template.
     */
    public function up(): void
    {
        Schema::table('platform_marketing_pages', function (Blueprint $table) {
            // Add type column for MarketingPageTypeEnum
            // This allows filtering by page category (home, about, contact, etc.)
            // Unlike slug which can change, type is stable identifier
            $table->string('type', 50)->nullable()->after('id');
            
            // Add unique index on type - each type should only exist once
            $table->unique('type', 'platform_marketing_pages_type_unique');
            
            // Add index for filtering
            $table->index('type', 'platform_marketing_pages_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('platform_marketing_pages', function (Blueprint $table) {
            $table->dropUnique('platform_marketing_pages_type_unique');
            $table->dropIndex('platform_marketing_pages_type_index');
            $table->dropColumn('type');
        });
    }
};
