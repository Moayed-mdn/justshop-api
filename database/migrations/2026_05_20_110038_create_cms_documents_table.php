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
        Schema::create('cms_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->nullable()->constrained('cms_document_sections')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('cms_documents')->nullOnDelete();
            $table->string('version')->nullable();

            $table->json('title');
            $table->json('slug');
            $table->json('excerpt')->nullable();
            $table->json('content');

            $table->integer('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            // SEO fields
            $table->json('seo')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'published_at']);
            $table->index('section_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_documents');
    }
};
