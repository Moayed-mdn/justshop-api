<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Page Template Overrides
     * 
     * Allows pages to override specific section settings from their template.
     * Enables per-page customization without duplicating entire templates.
     */
    public function up(): void
    {
        Schema::create('page_template_overrides', function (Blueprint $table) {
            $table->id();
            
            // Page relationship
            $table->foreignId('page_id')->constrained('store_marketing_pages')->cascadeOnDelete();
            
            // Section identification
            $table->string('section_id'); // "header", "footer", "hero", etc.
            
            // Override settings (JSON)
            $table->json('settings'); // Section-specific setting overrides
            
            $table->timestamps();
            
            // Composite unique constraint
            $table->unique(['page_id', 'section_id']);
            
            // Indexes
            $table->index('page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_template_overrides');
    }
};
