<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store Marketing Pages
     * 
     * Tenant-owned marketing content (store landing pages, campaigns, etc.)
     * MUST include store_id — slug uniqueness scoped per store
     */
    public function up(): void
    {
        Schema::create('store_marketing_pages', function (Blueprint $table) {
            $table->id();
            
            // Tenant isolation (MANDATORY)
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            
            // Content fields (JSON-localized)
            $table->json('title');
            $table->json('slug');
            $table->json('excerpt')->nullable();
            $table->json('content');
            
            // Publishing
            $table->string('status'); // draft, published, scheduled
            $table->timestamp('published_at')->nullable();
            
            // SEO (unified contract)
            $table->json('seo')->nullable();
            
            // Metadata
            $table->string('template')->nullable(); // landing, campaign, promotion, generic
            $table->integer('sort_order')->default(0);
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'status', 'published_at']);
            $table->index(['store_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_marketing_pages');
    }
};
