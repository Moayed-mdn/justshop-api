<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Section Schemas
     * 
     * Defines available section types and their configurable settings.
     * Similar to Shopify's section schema system.
     */
    public function up(): void
    {
        Schema::create('section_schemas', function (Blueprint $table) {
            $table->id();
            
            // Section type identification
            $table->string('type')->unique(); // header, footer, hero, product-grid, etc.
            $table->string('name'); // "Header", "Footer", "Hero Banner"
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // layout, content, commerce, marketing
            
            // Schema definitions (JSON)
            $table->json('settings'); // Array of setting definitions
            $table->json('blocks')->nullable(); // Block types allowed in section
            $table->json('presets')->nullable(); // Default configurations
            
            // Metadata
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_schemas');
    }
};
