<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add Template Assignment to Pages
     * 
     * Links pages to templates for Shopify-style customization.
     * Nullable to maintain backward compatibility.
     */
    public function up(): void
    {
        Schema::table('store_marketing_pages', function (Blueprint $table) {
            $table->foreignId('page_template_id')
                ->nullable()
                ->after('template')
                ->constrained('page_templates')
                ->nullOnDelete();
            
            $table->index('page_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('store_marketing_pages', function (Blueprint $table) {
            $table->dropForeign(['page_template_id']);
            $table->dropIndex(['page_template_id']);
            $table->dropColumn('page_template_id');
        });
    }
};
