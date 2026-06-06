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
        Schema::create('theme_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('theme_sections')->onDelete('cascade');
            $table->string('name');
            $table->string('type'); // logo, navigation, search, cart, text, image, button, product_list, category_list, etc.
            $table->string('handle')->nullable(); // Unique identifier for programmatic access
            $table->text('description')->nullable();
            $table->json('settings')->nullable(); // Block-specific settings (content, styles, etc.)
            $table->json('content')->nullable(); // Block content data (text, links, image URLs, etc.)
            $table->integer('position')->default(0); // Display order within section
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_removable')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['section_id', 'position']);
            $table->index(['section_id', 'type']);
            $table->unique(['section_id', 'handle']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_blocks');
    }
};
