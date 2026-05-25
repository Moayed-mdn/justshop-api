<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Platform Marketing Page Sections
     * 
     * Reusable content blocks for platform marketing pages
     */
    public function up(): void
    {
        Schema::create('platform_marketing_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_marketing_page_id')
                ->constrained('platform_marketing_pages')
                ->cascadeOnDelete();
            
            // Section metadata
            $table->string('section_type'); // hero, features, pricing, testimonials, cta, custom
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
            $table->index(['platform_marketing_page_id', 'sort_order'], 'platform_marketing_sections_composite');
            $table->index('section_type');
            $table->unique(['platform_marketing_page_id', 'identifier'], 'platform_marketing_sections_unique_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_marketing_sections');
    }
};
