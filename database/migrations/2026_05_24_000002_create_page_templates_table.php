<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Page Templates
     * 
     * Defines reusable page layouts with section assignments.
     * Enables Shopify-style template customization for merchants.
     */
    public function up(): void
    {
        Schema::create('page_templates', function (Blueprint $table) {
            $table->id();
            
            // Tenant isolation (MANDATORY)
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            
            // Template identification
            $table->string('name'); // "Default Page", "Auth Page", "Legal Page"
            $table->string('handle'); // page.default, page.auth, page.legal
            $table->string('type'); // page, product, collection, article, blog, cart
            $table->text('description')->nullable();
            
            // Section configuration (JSON)
            $table->json('sections'); // Section assignments and settings
            $table->json('section_order'); // Array of section IDs in display order
            $table->json('section_settings')->nullable(); // Global section overrides
            
            // Template status
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['store_id', 'type']);
            $table->index(['store_id', 'is_default']);
            $table->unique(['store_id', 'handle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_templates');
    }
};
