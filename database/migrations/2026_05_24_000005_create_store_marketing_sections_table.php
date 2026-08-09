<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store Marketing Page Sections
     * 
     * Reusable content blocks for store marketing pages
     */
    public function up(): void
    {
        Schema::create('store_marketing_sections', function (Blueprint $table) {
            $table->id();
            
            // Tenant isolation (MANDATORY)
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('store_marketing_page_id')
                ->constrained('store_marketing_pages')
                ->cascadeOnDelete();
            
            // Section metadata
            $table->string('section_type'); // hero, features, products, testimonials, cta, custom
            $table->string('identifier')->nullable(); // unique key for programmatic access
            $table->integer('sort_order')->default(0);
            
            // Content (JSON-localized)
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->json('content')->nullable();
            $table->json('settings')->nullable(); // layout, styling, behavior config
            
            // Visibility
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['store_id', 'store_marketing_page_id', 'sort_order'], 'store_marketing_sections_composite');
            $table->index('section_type');
            $table->unique(['store_marketing_page_id', 'identifier'], 'store_marketing_sections_unique_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_marketing_sections');
    }
};
