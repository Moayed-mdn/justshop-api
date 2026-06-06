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
        Schema::create('theme_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained('themes')->onDelete('cascade');
            $table->string('name');
            $table->string('type'); // header, footer, hero, products, categories, custom
            $table->string('handle')->nullable(); // Unique identifier for programmatic access
            $table->text('description')->nullable();
            $table->json('settings')->nullable(); // Section-specific settings (spacing, background, etc.)
            $table->integer('position')->default(0); // Display order
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_removable')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['theme_id', 'position']);
            $table->index(['theme_id', 'type']);
            $table->unique(['theme_id', 'handle']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_sections');
    }
};
