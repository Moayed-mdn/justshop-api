<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Platform Marketing Pages
     * 
     * Platform-owned marketing content (home, pricing, features, etc.)
     * NO store_id — globally unique slugs
     */
    public function up(): void
    {
        Schema::create('platform_marketing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->nullable();
            
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
            $table->string('template')->nullable(); // home, pricing, features, generic
            $table->integer('sort_order')->default(0);
            
            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('status');
            $table->index(['status', 'published_at']);
            $table->index('sort_order');
            $table->unique('type', 'platform_marketing_pages_type_unique');
            $table->index('type', 'platform_marketing_pages_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_marketing_pages');
    }
};
