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
        Schema::create('theme_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theme_id')->constrained('themes')->onDelete('cascade');
            $table->string('name');
            $table->string('type'); // home, product, category, collection, page, cart, checkout
            $table->string('handle')->nullable(); // Unique identifier for programmatic access
            $table->text('description')->nullable();
            $table->json('settings')->nullable(); // Template-specific settings (layout, grid, etc.)
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['theme_id', 'type']);
            $table->unique(['theme_id', 'handle']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_templates');
    }
};
