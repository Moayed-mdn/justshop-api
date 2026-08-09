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
        Schema::create('theme_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('theme_templates')->onDelete('cascade');
            $table->foreignId('section_id')->constrained('theme_sections')->onDelete('cascade');
            $table->integer('position')->default(0); // Display order of section within template
            $table->json('overrides')->nullable();
            $table->boolean('is_enabled')->default(true); // Template-specific overrides for section settings
            $table->timestamps();

            // Indexes
            $table->index(['template_id', 'position']);
            $table->unique(['template_id', 'section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_template_sections');
    }
};
